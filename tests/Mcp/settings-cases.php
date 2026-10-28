<?php
/** Exercise every exposed setting through the registered ability and native storage. */
$initialSettingsOptions = unserialize(serialize($legacy->modules->multiple_authors->options));
try {
    register_post_type('mcp_public_no_author', ['public' => true, 'label' => 'Fixture public type', 'supports' => ['title']]);
    register_post_type('mcp_private_author', ['public' => false, 'label' => 'Fixture private type', 'supports' => ['title', 'author']]);
    $settingsState = $success($call('get-plugin-settings'), 'Read the complete settings contract');
    $check(!is_wp_error(rest_validate_value_from_schema((array) $settingsState['settings'], $settingsState['schema'])), 'Every discovered setting value matches its schema');
    $schema = $settingsState['schema']['properties'];
    $check(isset($schema['post_types']['properties']['mcp_public_no_author']) && !isset($schema['post_types']['properties']['mcp_private_author']), 'Post-type choices match the native public custom-type controls');
    foreach (['layout', 'author_pages_bio_layout', 'author_legacy_layout_boxed', 'author_legacy_layout_centered', 'author_legacy_layout_inline', 'author_legacy_layout_inline_avatar', 'author_legacy_layout_simple_list', 'author_pages_layout', 'author_pages_title_header', 'author_post_title_header', 'display_name_format'] as $key) {
        $method = 'settings_' . $key . (in_array($key, ['layout'], true) ? '_option' : '');
        ob_start();
        $legacy->multiple_authors->$method();
        $markup = ob_get_clean();
        preg_match_all('/<option value="([^"]*)"/', $markup, $matches);
        $choices = array_map(static function ($v) { return html_entity_decode($v, ENT_QUOTES, 'UTF-8'); }, $matches[1]);
        $check($choices === $schema[$key]['enum'], $key . ' choices match the native select');
    }
    foreach ($schema as $key => $field) {
        $type = $field['type'];
        if (array_key_exists('default', $field)) {
            $check(!is_wp_error(rest_validate_value_from_schema($field['default'], $field)), $key . ' has a correctly typed native default');
        }
        if ($type === 'boolean') {
            $candidates = [false, true];
        } elseif ($type === 'integer') {
            $candidates = $key === 'fallback_user_for_guest_post' ? [0, $users['author']]
                : ($key === 'default_author_for_new_posts' ? [0, $authorId]
                    : ($key === 'author_pages_posts_limit' ? [-1, 0, 12] : [1, 3]));
        } elseif (is_array($type)) {
            $candidates = ['', 320];
        } elseif ($type === 'array') {
            $candidates = [[], ['subscriber']];
        } elseif ($type === 'object') {
            $candidates = [['page' => true, 'mcp_public_no_author' => true], ['page' => false, 'mcp_public_no_author' => false]];
        } elseif (isset($field['enum'])) {
            $candidates = $field['enum'];
        } else {
            $candidates = [$key === 'color_scheme' ? '#123456' : ($key === 'default_avatar' ? '' : 'MCP audit value')];
        }
        foreach ($candidates as $value) {
            $before = serialize($legacy->modules->multiple_authors->options);
            $preview = $success($call('update-plugin-settings', ['settings' => [$key => $value], 'dry_run' => true]), $key . ' accepts its discovered value');
            $check(serialize($legacy->modules->multiple_authors->options) === $before, $key . ' preview preserves storage');
            $saved = $success($call('update-plugin-settings', ['settings' => [$key => $value]]), $key . ' saves through native validation');
            $read = $success($call('get-plugin-settings'), $key . ' reads after saving');
            $check($saved['settings']->$key == $preview['settings']->$key && $read['settings']->$key == $preview['settings']->$key, $key . ' saved values match preview and readback');
            $check(!is_wp_error(rest_validate_value_from_schema((array) $read['settings'], $read['schema'])), $key . ' preserves a valid complete settings contract');
            if ($type === 'boolean') {
                $raw = $legacy->modules->multiple_authors->options->$key;
                $check($raw === ($key === 'display_branding' ? ($value ? 'on' : 'off') : ($value ? 'yes' : 'no')), $key . ' uses the native checkbox representation');
            }
        }
    }

    $sampleField = static function (array $field) use (&$sampleField) {
        if (isset($field['enum'])) {
            return $field['enum'][0];
        }
        $type = is_array($field['type']) ? $field['type'][0] : $field['type'];
        if ($type === 'boolean') { return true; }
        if ($type === 'integer') { return $field['minimum'] ?? 1; }
        if ($type === 'array') { return [$sampleField($field['items'])]; }
        if ($type === 'object') {
            $value = [];
            foreach ($field['properties'] ?? [] as $name => $child) { $value[$name] = $sampleField($child); }
            return $value;
        }
        if (($field['format'] ?? '') === 'email') { return 'fixture@example.org'; }
        if (isset($field['pattern'])) { return $field['pattern'] === '^$' ? '' : '#123456'; }
        return 'Fixture';
    };
    foreach (['author-box' => 'save-author-box', 'author-list' => 'save-author-list', 'custom-field' => 'save-custom-field'] as $kind => $actionName) {
        if ($kind === 'custom-field' && !\MultipleAuthors\Classes\Utils::isAuthorsProActive()) { continue; }
        $discovery = $success($call('get-' . $kind . '-fields'), 'Discover ' . $kind . ' field contract');
        $ability = wp_get_ability('publishpress-authors/' . $actionName);
        $transport = $ability->get_input_schema()['properties']['fields'];
        foreach ($discovery['schema']['properties'] as $name => $field) {
            $sample = $sampleField($field);
            $validNative = rest_validate_value_from_schema($sample, $field);
            $check(!is_wp_error($validNative), $kind . ' ' . $name . ' has a valid typed sample');
            $validTransport = rest_validate_value_from_schema([$name => $sample], $transport);
            $check(!is_wp_error($validTransport), $kind . ' ' . $name . ' transport accepts its native field shape');
        }
    }
    foreach (['author_post_custom_width', 'author_post_custom_height'] as $key) {
        $check(is_wp_error($call('update-plugin-settings', ['settings' => [$key => '320px']])), $key . ' rejects CSS strings for a numeric control');
        $check(is_wp_error($call('update-plugin-settings', ['settings' => [$key => 0]])), $key . ' rejects zero dimensions');
    }
    $check(is_wp_error($call('update-plugin-settings', ['settings' => ['layout' => 'authors_index']])), 'Author List layouts cannot be used as an Author Box layout');
    $check(is_wp_error($call('update-plugin-settings', ['settings' => ['layout' => '']])), 'Required box layout cannot be silently normalized from empty');

    $check(is_wp_error($call('save-author-box', ['id' => $boxId, 'expected_hash' => $call('get-author-box', ['id' => $boxId])['hash'],
        'fields' => ['shortcodes' => ['shortcode' => '[fixture]', 'position' => 'meta']]])), 'Malformed shortcode scalar lists return an error');
    $check(is_wp_error($call('save-author-list', ['id' => $list['id'], 'expected_hash' => $call('get-author-list', ['id' => $list['id']])['hash'],
        'fields' => ['display_fields' => 'description']])), 'List multi-select strings cannot be coerced to arrays');
    $recent = $success($call('save-author-list', ['fields' => ['title' => 'Audited recent authors', 'layout' => 'authors_recent', 'layout_columns' => 3]]), 'Create recent authors with native columns');
    $storedRecent = $legacy->modules->author_list->options->author_list_data[$recent['id']];
    $check(($storedRecent['shortcode_args']['authors_recent_col'] ?? '') === '3' && !isset($storedRecent['shortcode_args']['layout_columns']), 'Recent columns use the native authors_recent_col shortcode argument');
    // Existing presets may contain native filter arguments not shown in the basic MCP field schema.
    $allLists = $legacy->modules->author_list->options->author_list_data;
    $allLists[$recent['id']]['shortcode_args']['roles'] = 'editor,author';
    $allLists[$recent['id']]['shortcode_args']['native_extension_arg'] = 'preserve-me';
    $legacy->update_module_option('author_list', 'author_list_data', $allLists);
    $recent = $success($call('get-author-list', ['id' => $recent['id']]), 'Read preset with native filter arguments');
    $success($call('save-author-list', ['id' => $recent['id'], 'expected_hash' => $recent['hash'], 'fields' => ['title' => 'Preserved recent authors']]), 'Rename native preset without changing filters');
    $storedRecent = $legacy->modules->author_list->options->author_list_data[$recent['id']];
    $check($storedRecent['shortcode_args']['roles'] === 'editor,author' && $storedRecent['shortcode_args']['native_extension_arg'] === 'preserve-me'
        && $storedRecent['shortcode_args']['authors_recent_col'] === '3', 'List edits preserve unrelated native filters and shortcode arguments');
    $nativePagesLayout = static function ($layouts) { $layouts['mcp_custom'] = 'Fixture custom layout'; return $layouts; };
    add_filter('pp_multiple_authors_pages_layout', $nativePagesLayout);
    try {
        $check(in_array('mcp_custom', $call('get-plugin-settings')['schema']['properties']['author_pages_layout']['enum'], true), 'Page layout discovery honors the native filter');
        // A filter installed after registration is validated by the live domain schema as well.
        $check(!is_wp_error(\MultipleAuthors\Mcp\Settings::update(['settings' => ['author_pages_layout' => 'mcp_custom'], 'dry_run' => true])), 'Native filtered page layout is accepted by domain validation');
    } finally {
        remove_filter('pp_multiple_authors_pages_layout', $nativePagesLayout);
    }
} finally {
    $legacy->update_all_module_options('multiple_authors', $initialSettingsOptions);
    unregister_post_type('mcp_public_no_author');
    unregister_post_type('mcp_private_author');
}
