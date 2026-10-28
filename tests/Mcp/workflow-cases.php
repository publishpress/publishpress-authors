<?php
/** Native preservation, previews and compensation; included by smoke.php. */
$schema = $call('get-author-box-fields');
$template = array_keys((array) $schema['templates'])[0];
$templateBox = $success($call('save-author-box', ['title' => 'Native template fixture', 'template' => $template]), 'Create box from a discovered native template');
$posts[] = $templateBox['id'];
$check(count((array) $templateBox['fields']) > 5, 'Native template supplies real editor defaults');
$boxState = $success($call('get-author-box', ['id' => $boxId]), 'Read fresh box revision');
$metaBefore = get_post_meta($boxId, 'ppma_boxes_layout_meta_value', true);
$preview = $success($call('save-author-box', ['id' => $boxId, 'expected_hash' => $boxState['hash'], 'fields' => ['title_text' => 'Preview only'], 'dry_run' => true]), 'Preview native box patch');
$check($preview['dry_run'] && get_post_meta($boxId, 'ppma_boxes_layout_meta_value', true) === $metaBefore, 'Box preview leaves storage unchanged');
$failBox = static function ($checkValue, $id, $key, $value) use ($boxId) {
    return $id === $boxId && $key === 'ppma_boxes_layout_meta_value' && is_array($value) && ($value['title_text'] ?? '') === 'Failure fixture' ? false : $checkValue;
};
add_filter('update_post_metadata', $failBox, 10, 4);
try {
    $failure = $call('save-author-box', ['id' => $boxId, 'expected_hash' => $boxState['hash'], 'title' => 'Should roll back', 'fields' => ['title_text' => 'Failure fixture']]);
    $check(is_wp_error($failure), 'Native metadata failure is reported');
    $check(get_post($boxId)->post_title === $boxState['title'] && get_post_meta($boxId, 'ppma_boxes_layout_meta_value', true) === $metaBefore, 'Failed box save restores title and metadata');
} finally {
    remove_filter('update_post_metadata', $failBox, 10);
}
$plain = wp_insert_post(['post_title' => 'Read-only fixture', 'post_status' => 'draft', 'post_author' => $users['subscriber']]);
$posts[] = $plain;
$termCount = (int) wp_count_terms(['taxonomy' => 'author', 'hide_empty' => false]);
$readOnly = $success($call('get-post-authors', ['post_id' => $plain]), 'Read stored byline for a user without an author profile');
$check((int) wp_count_terms(['taxonomy' => 'author', 'hide_empty' => false]) === $termCount, 'Byline reads do not create fallback author profiles');
$choices = $success($call('get-byline-choices', ['post_id' => $postId, 'per_page' => 100]), 'Discover choices for an editable post');
$check(in_array($authorId, array_column($choices['authors'], 'author_id'), true), 'Byline discovery resolves names to exact term IDs');
$second = $success($call('save-author', ['display_name' => 'Second synthetic author', 'slug' => 'mcp-second-' . strtolower(wp_generate_password(6, false))]), 'Create second ordering fixture');
$terms[] = $secondId = $second['author']->term_id;
$byline = $call('get-post-byline', ['post_id' => $postId]);
$ordered = $success($call('update-post-byline', ['post_id' => $postId, 'expected_hash' => $byline['hash'], 'author_ids' => [$secondId, $authorId]]), 'Save a deliberately reversed author order');
$check($ordered['author_ids'] === [$secondId, $authorId], 'Native relationship ordering is preserved');
$cleared = $success($call('update-post-byline', ['post_id' => $postId, 'expected_hash' => $ordered['hash'], 'categories' => (object) []]), 'Explicitly clear category assignments');
$check((array) $cleared['categories'] === [], 'Explicit empty categories do not retain old relationships');
$batch = $success($call('bulk-update-post-bylines', ['changes' => [
    ['post_id' => $postId, 'expected_hash' => $cleared['hash'], 'author_ids' => [$authorId]],
    ['post_id' => $plain, 'expected_hash' => $call('get-post-byline', ['post_id' => $plain])['hash'], 'author_ids' => [$secondId]],
], 'dry_run' => true]), 'Preview a valid multi-post batch');
$check(count($batch['results']) === 2 && $call('get-post-byline', ['post_id' => $postId])['hash'] === $cleared['hash'], 'Batch preview saves no targets');
$hidden = static function ($fields) {
    $fields['mcp_hidden_fixture'] = ['label' => 'Hidden fixture', 'type' => 'text', 'show_in_rest' => 'off'];
    $fields['_application_passwords'] = ['label' => 'Blocked auth fixture', 'type' => 'text'];
    return $fields;
};
add_filter('multiple_authors_author_fields', $hidden, 100);
try {
    update_term_meta($authorId, 'mcp_hidden_fixture', 'synthetic-private-marker');
    $profileRead = $call('get-author-profile', ['author_id' => $authorId]);
    $check(strpos(wp_json_encode($profileRead), 'synthetic-private-marker') === false, 'Native hidden field is excluded from profile reads');
    $fieldRead = $call('get-profile-fields', ['author_id' => $authorId]);
    $check(!isset($fieldRead['schema']['properties']['_application_passwords']), 'Authentication metadata never becomes a writable profile field');
    $check(is_wp_error($call('save-author', ['author_id' => $authorId, 'expected_hash' => $profileRead['hash'], 'fields' => ['_application_passwords' => 'blocked']])), 'Authentication metadata writes are rejected');
} finally {
    remove_filter('multiple_authors_author_fields', $hidden, 100);
}
$linked = $success($call('save-author', ['user_id' => $users['administrator']]), 'Create profile for an existing WordPress user without guest creation');
$terms[] = $linked['author']->term_id;
$check($linked['author']->user_id === $users['administrator'] && !$linked['author']->is_guest, 'Existing-user profile is linked without creating an account');
$check(is_wp_error($call('save-author-category', ['id' => $categoryId, 'expected_hash' => str_repeat('0', 64), 'plural_name' => 'Stale'])), 'Category revisions reject stale changes');
$check(is_wp_error($call('get-byline-choices', ['post_id' => $postId, 'per_page' => 101])), 'Choice discovery pagination is bounded');

$orderBox = $call('get-author-box', ['id' => $boxId]);
$order = array_reverse($orderBox['field_order']);
$orderedBox = $success($call('save-author-box', ['id' => $boxId, 'expected_hash' => $orderBox['hash'], 'field_order' => $order]), 'Save native box profile-field order');
$check($orderedBox['field_order'] === $order, 'Box profile-field order round-trips');
$numeric = $success($call('save-author-box', ['id' => $boxId, 'expected_hash' => $orderedBox['hash'], 'fields' => ['title_size' => '']]), 'Clear an optional numeric box control');
$check($numeric['fields']->title_size === '', 'Clearing a numeric control preserves native inheritance rather than zero');

$check(is_wp_error($call('save-author', ['author_id' => $linked['author']->term_id, 'expected_hash' => $linked['hash'], 'slug' => 'not-the-account-nicename'])), 'Linked profile rejects an ignored account slug change');
wp_update_user(['ID' => $users['administrator'], 'user_url' => 'https://example.org/mcp-stale']);
$check(is_wp_error($call('save-author', ['author_id' => $linked['author']->term_id, 'expected_hash' => $linked['hash'], 'fields' => ['first_name' => 'Stale']])), 'Linked account changes invalidate the profile revision');
$linkedBefore = $call('get-author-profile', ['author_id' => $linked['author']->term_id]);
$userBefore = get_user_meta($users['administrator'], 'first_name', true);
$failProfile = static function ($value, $id, $key, $data) use ($linked) {
    return $id === $linked['author']->term_id && $key === 'first_name' && $data === 'McpFailure' ? false : $value;
};
add_filter('update_term_metadata', $failProfile, 10, 4);
try {
    $failure = $call('save-author', ['author_id' => $linked['author']->term_id, 'expected_hash' => $linkedBefore['hash'], 'fields' => ['first_name' => 'McpFailure']]);
    $check(is_wp_error($failure), 'Profile metadata failure is reported');
    $debugAfter = $call('get-author-profile', ['author_id' => $linked['author']->term_id]);
    $check($debugAfter['hash'] === $linkedBefore['hash'] && get_user_meta($users['administrator'], 'first_name', true) === $userBefore, 'Failed profile save restores profile and linked user state');
} finally {
    remove_filter('update_term_metadata', $failProfile, 10);
}
$beforeBatch = $call('get-post-byline', ['post_id' => $postId]);
$beforePlain = $call('get-post-byline', ['post_id' => $plain]);
$failByline = static function ($value, $id, $key, $data) use ($plain) {
    return $id === $plain && $key === 'ppma_disable_author_box' && $data === '1' ? false : $value;
};
add_filter('update_post_metadata', $failByline, 10, 4);
try {
    $failure = $call('bulk-update-post-bylines', ['changes' => [
        ['post_id' => $postId, 'expected_hash' => $beforeBatch['hash'], 'author_ids' => [$authorId]],
        ['post_id' => $plain, 'expected_hash' => $beforePlain['hash'], 'author_ids' => [$secondId], 'disable_author_box' => true],
    ]]);
    $check(is_wp_error($failure), 'Batch reports a native failure after an earlier target saves');
    $check($call('get-post-byline', ['post_id' => $postId])['hash'] === $beforeBatch['hash'] && $call('get-post-byline', ['post_id' => $plain])['hash'] === $beforePlain['hash'], 'Failed batch restores every applied target');
} finally {
    remove_filter('update_post_metadata', $failByline, 10);
}
$batchSaved = $success($call('bulk-update-post-bylines', ['changes' => [
    ['post_id' => $postId, 'expected_hash' => $beforeBatch['hash'], 'author_ids' => [$authorId]],
    ['post_id' => $plain, 'expected_hash' => $beforePlain['hash'], 'author_ids' => [$secondId]],
]]), 'Save a valid native multi-post batch');
$check(count($batchSaved['results']) === 2 && $batchSaved['results'][1]['author_ids'] === [$secondId], 'Every batch result matches saved byline data');

$settingsBeforeFailure = $call('get-plugin-settings');
$blockOption = static function ($value, $old) { return $old; };
add_filter('pre_update_option_multiple_authors_multiple_authors_options', $blockOption, 10, 2);
try {
    $check(is_wp_error($call('update-plugin-settings', ['settings' => ['append_to_content' => !$settingsBeforeFailure['settings']->append_to_content]])), 'Blocked native settings persistence returns an error');
    $check($call('get-plugin-settings')['settings'] == $settingsBeforeFailure['settings'], 'Failed settings save restores the native in-memory mirror');
} finally {
    remove_filter('pre_update_option_multiple_authors_multiple_authors_options', $blockOption, 10);
}

$categoryBeforeFailure = $call('get-author-category', ['id' => $categoryId]);
$blockCategory = static function ($sql) {
    return stripos($sql, 'UPDATE') === 0 && strpos($sql, 'ppma_author_categories') !== false && strpos($sql, 'McpBlockedCategory') !== false ? 'SELECT 1' : $sql;
};
add_filter('query', $blockCategory);
try {
    $check(is_wp_error($call('save-author-category', ['id' => $categoryId, 'expected_hash' => $categoryBeforeFailure['hash'], 'plural_name' => 'McpBlockedCategory', 'schema_property' => 'reviewedBy'])), 'Native category persistence failure is reported');
    $check($call('get-author-category', ['id' => $categoryId])['hash'] === $categoryBeforeFailure['hash'], 'Failed category patch restores its metadata too');
} finally {
    remove_filter('query', $blockCategory);
}

$display = $success($call('update-plugin-settings', ['settings' => ['title_appended_to_content' => 'Writer', 'title_appended_to_content_plural' => 'Writers', 'color_scheme' => '#123456', 'show_email_link' => false, 'show_site_link' => false]]), 'Save all legacy Display controls');
$check($display['settings']->title_appended_to_content_plural === 'Writers' && $display['settings']->color_scheme === '#123456' && !$display['settings']->show_email_link, 'Legacy Display values round-trip through native storage');
$freshBox = $call('get-author-box', ['id' => $boxId]);
$shortcodes = ['shortcode' => ['<strong>Read time</strong> [reading_time]'], 'position' => ['bio']];
$withShortcodes = $success($call('save-author-box', ['id' => $boxId, 'expected_hash' => $freshBox['hash'], 'menu_order' => 7, 'fields' => ['shortcodes' => $shortcodes, 'box_tab_custom_css' => '.ppma-test { color: pink; }']]), 'Save native box shortcodes, CSS and list order');
$check($withShortcodes['menu_order'] === 7 && (array) $withShortcodes['fields']->shortcodes === $shortcodes, 'Shortcode positions and object ordering round-trip');
$check(get_post_meta($boxId, 'ppma_boxes_layout_meta_value', true)['box_tab_custom_css'] === '.ppma-test { color: pink; }', 'Custom CSS uses native editor storage');
$check(is_wp_error($call('save-author-box', ['id' => $boxId, 'expected_hash' => $withShortcodes['hash'], 'fields' => ['shortcodes' => ['shortcode' => ['[reading_time]'], 'position' => []]]])), 'Mismatched shortcode positions are rejected');
$check(is_wp_error($call('save-author-box', ['id' => $boxId, 'expected_hash' => $withShortcodes['hash'], 'fields' => ['shortcodes' => ['shortcode' => ['[reading_time]'], 'position' => ['invalid']]]])), 'Unknown shortcode positions are rejected');

$activeCategory = $call('get-author-category', ['id' => $categoryId]);
$inactiveCategory = $success($call('save-author-category', ['id' => $categoryId, 'expected_hash' => $activeCategory['hash'], 'enabled' => false]), 'Disable a category for assignment checks');
$freshByline = $call('get-post-byline', ['post_id' => $postId]);
$check(is_wp_error($call('update-post-byline', ['post_id' => $postId, 'expected_hash' => $freshByline['hash'], 'categories' => [(string) $authorId => [$categoryId]], 'dry_run' => true])), 'Post bylines reject disabled categories even when their IDs exist');
$success($call('save-author-category', ['id' => $categoryId, 'expected_hash' => $inactiveCategory['hash'], 'enabled' => true]), 'Restore the synthetic assignment category');
