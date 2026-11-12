<?php
/** Included by the full-bootstrap isolated smoke suite. */
$deny = static function ($caps, $cap) {
    return in_array($cap, ['manage_options', 'ppma_manage_authors', 'ppma_manage_layouts', 'ppma_manage_author_categories', 'ppma_edit_post_authors'], true) ? ['do_not_allow'] : $caps;
};
add_filter('map_meta_cap', $deny, 10, 2);
try {
    foreach (['get-plugin-settings', 'get-author-profile', 'get-author-box-fields', 'list-author-categories', 'get-post-byline'] as $action) {
        $check(wp_get_ability('publishpress-authors/' . $action)->check_permissions(['author_id' => $authorId, 'post_id' => $postId]) !== true, 'Denied Administrator cannot run ' . $action);
    }
} finally {
    remove_filter('map_meta_cap', $deny, 10);
}
$denyPost = static function ($caps, $cap, $user, $args) use ($postId, $boxId) {
    return $cap === 'edit_post' && in_array($args[0] ?? 0, [$postId, $boxId], true) ? ['do_not_allow'] : $caps;
};
add_filter('map_meta_cap', $denyPost, 10, 4);
try {
    $check(is_wp_error($call('get-post-byline', ['post_id' => $postId])), 'Object denial blocks byline data');
    $check(is_wp_error($call('get-author-box', ['id' => $boxId])), 'Object denial blocks native box data');
    $boxes = $success($call('list-author-boxes', ['per_page' => 100]), 'List boxes while one target is denied');
    $check(!in_array($boxId, array_column($boxes['items'], 'id'), true), 'Denied boxes are filtered from listings');
} finally {
    remove_filter('map_meta_cap', $denyPost, 10);
}
$legacy->update_module_option('author_list', 'enabled', 'off');
$check(is_wp_error($call('get-author-list-fields')), 'Disabled module blocks field discovery');
$legacy->update_module_option('author_list', 'enabled', 'on');
wp_set_current_user($users['subscriber']);
foreach ($abilities as $ability) {
    $check($ability->check_permissions(['post_id' => $postId, 'author_id' => $authorId, 'id' => $boxId]) !== true, 'Subscriber denied ' . $ability->get_name());
}
wp_set_current_user($users['editor']);
$check(wp_get_ability('publishpress-authors/get-post-byline')->check_permissions(['post_id' => $postId]) === true, 'Editor byline access uses native edit_post permissions');
wp_set_current_user($users['administrator']);
$check(is_wp_error($call('get-author-profile', ['author_id' => 99999999])), 'Unknown profile cannot expose arbitrary terms');
$translation = static function ($translated, $original, $domain) { return $domain === 'publishpress-authors' ? 'translated:' . $translated : $translated; };
add_filter('gettext', $translation, 10, 3);
try {
    $error = $call('save-author', ['display_name' => 'Fixture', 'slug' => $profile['author']->slug]);
    $check(is_wp_error($error) && strpos($error->get_error_message(), 'translated:') === 0, 'Domain errors use the plugin text domain');
} finally {
    remove_filter('gettext', $translation, 10);
}

$denyFields = static function ($caps, $cap) {
    return $cap === 'ppma_manage_custom_fields' ? ['do_not_allow'] : $caps;
};
add_filter('map_meta_cap', $denyFields, 10, 2);
try {
    foreach (['list-custom-fields', 'get-custom-field-fields'] as $action) {
        $check(is_wp_error($call($action)), 'Denied Author Fields capability blocks ' . $action);
    }
    if (\MultipleAuthors\Classes\Utils::isAuthorsProActive()) {
        $check(is_wp_error($call('save-custom-field', ['title' => 'Must not save'])), 'Pro activation does not bypass denied Author Fields capability');
    }
    $check(!is_wp_error($call('get-plugin-settings')), 'Independent settings permission remains available');
} finally {
    remove_filter('map_meta_cap', $denyFields, 10);
}
$denyTerm = static function ($caps, $cap, $user, $args) use ($authorId) {
    return $cap === 'edit_term' && ($args[0] ?? 0) === $authorId ? ['do_not_allow'] : $caps;
};
add_filter('map_meta_cap', $denyTerm, 10, 4);
try {
    foreach (['get-author', 'get-author-profile', 'get-profile-fields'] as $action) {
        $check(is_wp_error($call($action, ['author_id' => $authorId])), 'Per-author denial blocks ' . $action);
    }
    $listed = $success($call('list-authors', ['per_page' => 100]), 'List profiles while one author is denied');
    $check(!in_array($authorId, array_column($listed['authors'], 'term_id'), true), 'Profile list omits denied author terms');
} finally {
    remove_filter('map_meta_cap', $denyTerm, 10);
}
$denyBox = static function ($allcaps, $required, $args) use ($boxId) {
    if ($args[0] === 'edit_ppma_box' && ($args[2] ?? 0) === $boxId) {
        foreach ($required as $cap) {
            $allcaps[$cap] = false;
        }
    }
    return $allcaps;
};
add_filter('user_has_cap', $denyBox, 10, 3);
try {
    $check(is_wp_error($call('get-author-box', ['id' => $boxId])), 'Native box meta-cap denial blocks object reads');
    $boxes = $call('list-author-boxes', ['per_page' => 100]);
    $check(!in_array($boxId, array_column($boxes['items'], 'id'), true), 'Native box meta-cap denial filters discovery too');
} finally {
    remove_filter('user_has_cap', $denyBox, 10);
}
$denyAccount = static function ($caps, $cap, $user, $args) use ($users) {
    return $cap === 'edit_user' && ($args[0] ?? 0) === $users['administrator'] ? ['do_not_allow'] : $caps;
};
add_filter('map_meta_cap', $denyAccount, 10, 4);
try {
    $freshLinked = $call('get-author-profile', ['author_id' => $linked['author']->term_id]);
    $check(is_wp_error($call('save-author', ['author_id' => $linked['author']->term_id, 'expected_hash' => $freshLinked['hash'], 'fields' => ['first_name' => 'Denied'], 'dry_run' => true])), 'Linked account edit denial is respected during profile previews');
} finally {
    remove_filter('map_meta_cap', $denyAccount, 10);
}

$editableRoles = static function ($roles) { unset($roles['subscriber']); return $roles; };
add_filter('editable_roles', $editableRoles);
try {
    $choices = $call('get-plugin-settings')['schema']['properties']['mapped_author_roles']['items']['enum'];
    $check(!in_array('subscriber', $choices, true), 'Role choices honor the native editable_roles filter');
    $check(is_wp_error($call('update-plugin-settings', ['settings' => ['mapped_author_roles' => ['subscriber']], 'dry_run' => true])), 'Undiscoverable role settings are rejected during previews');
} finally {
    remove_filter('editable_roles', $editableRoles);
}

$denyTermCreation = static function ($caps, $cap) {
    return $cap === get_taxonomy('author')->cap->edit_terms ? ['do_not_allow'] : $caps;
};
add_filter('map_meta_cap', $denyTermCreation, 10, 2);
try {
    $check(is_wp_error($call('save-author', ['display_name' => 'Denied creation', 'dry_run' => true])), 'Author creation cannot bypass native taxonomy management denial');
} finally {
    remove_filter('map_meta_cap', $denyTermCreation, 10);
}
$restrictBoxPublish = static function ($allcaps, $required, $args) {
    if ($args[0] === get_post_type_object('ppma_boxes')->cap->publish_posts) {
        foreach ($required as $cap) { $allcaps[$cap] = false; }
    }
    return $allcaps;
};
add_filter('user_has_cap', $restrictBoxPublish, 10, 3);
try {
    $check(is_wp_error($call('save-author-box', ['title' => 'Denied publish', 'status' => 'publish', 'dry_run' => true])), 'Box publishing cannot bypass its native publish capability');
} finally {
    remove_filter('user_has_cap', $restrictBoxPublish, 10);
}

$denySettingsOnly = static function ($caps, $cap) { return $cap === 'manage_options' ? ['do_not_allow'] : $caps; };
add_filter('map_meta_cap', $denySettingsOnly, 10, 2);
try {
    $check(!is_wp_error($call('get-custom-field-fields')), 'Dedicated Author Fields access does not require general settings capability');
} finally {
    remove_filter('map_meta_cap', $denySettingsOnly, 10);
}
