<?php
/** Run against an isolated full Free/Pro plugin bootstrap, never a live site. */
if (PHP_SAPI !== 'cli' || !isset($argv[1]) || !in_array(getenv('AUTHORS_MCP_MODE'), ['free', 'pro'], true)) {
    exit("Usage: AUTHORS_MCP_MODE=free|pro php smoke.php /path/to/isolated/wp-load.php [adapter-directory]\n");
}
require $argv[1];
if ($GLOBALS['wpdb']->prefix !== 'mcp_smoke_') {
    exit("Use an isolated mcp_smoke_ database.\n");
}
require_once ABSPATH . 'wp-admin/includes/user.php';
\MultipleAuthorCategories\AuthorCategoriesSchema::createTableIfNotExists();
\MultipleAuthorCategories\AuthorCategoriesSchema::createMetaTableIfNotExists();
\MultipleAuthorCategories\AuthorCategoriesSchema::createRelationTableIfNotExists();
if (!get_option('authors_mcp_fixture_installed')) {
    \MultipleAuthors\Classes\Installer::runInstallTasks(PP_AUTHORS_VERSION);
    update_option('authors_mcp_fixture_installed', 1);
}
if (\MultipleAuthors\Classes\Utils::isAuthorsProActive()) {
    (new \PPAuthorsPro\Installer())->checkAndTriggerInstaller();
}
$checks = 0;
$check = static function ($condition, $message) use (&$checks) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
    echo "PASS: $message\n";
};
$call = static function ($name, $input = []) { return wp_get_ability('publishpress-authors/' . $name)->execute($input); };
$success = static function ($value, $message) use ($check) {
    $check(!is_wp_error($value), $message . (is_wp_error($value) ? ': ' . $value->get_error_message() : ''));
    return $value;
};
$legacy = \MultipleAuthors\Factory::getLegacyPlugin();
$snapshots = [];
foreach ($legacy->modules as $name => $module) {
    $snapshots[$name] = unserialize(serialize($module->options));
}
$posts = $terms = $users = $categories = [];
try {
    foreach (['administrator', 'editor', 'author', 'subscriber'] as $role) {
        $id = wp_insert_user(['user_login' => 'authors_mcp_' . $role . wp_generate_password(8, false), 'user_pass' => wp_generate_password(), 'user_email' => wp_generate_password(12, false) . '@example.org', 'role' => $role]);
        if (is_wp_error($id)) {
            throw new RuntimeException('Could not create a synthetic user.');
        }
        $users[$role] = $id;
    }
    wp_set_current_user($users['administrator']);
    $abilities = array_filter(wp_get_abilities(), static function ($a) { return strpos($a->get_name(), 'publishpress-authors/') === 0; });
    $check(count($abilities) === (getenv('AUTHORS_MCP_MODE') === 'pro' ? 38 : 35), 'Full distribution registers the expected actions');
    foreach ($abilities as $ability) {
        $meta = $ability->get_meta();
        $check($ability->get_category() === 'publishpress' && !$meta['public'] && !$meta['show_in_rest'] && !$meta['mcp']['public'], $ability->get_name() . ' is private');
    }
    $check(is_wp_error($call('get-plugin-settings', ['unexpected' => 'value'])), 'Unknown empty-input fields return a validation error');
    $settings = $success($call('get-plugin-settings'), 'Discover native settings');
    $check(isset($settings['schema']['properties']['enable_guest_author_user']), 'Guest-author switch has a typed schema');
    $before = serialize($legacy->modules->multiple_authors->options);
    $preview = $success($call('update-plugin-settings', ['settings' => ['enable_guest_author_user' => true], 'dry_run' => true]), 'Settings preview');
    $check($before === serialize($legacy->modules->multiple_authors->options) && $preview['settings']->enable_guest_author_user, 'Settings preview saves nothing');
    $success($call('update-plugin-settings', ['settings' => ['enable_guest_author_user' => true, 'force_empty_author' => true]]), 'Enable synthetic guest profiles');
    $check(is_wp_error($call('update-plugin-settings', ['settings' => ['license_key' => 'never-accepted']])), 'License fields are rejected');
    $check(is_wp_error($call('update-plugin-settings', ['settings' => ['append_to_content' => 'yes']])), 'String booleans are rejected');
    $fields = $success($call('get-profile-fields'), 'Discover enabled profile fields');
    $check(isset($fields['schema']['properties']['description']) && !isset($fields['schema']['properties']['user_email']), 'Discovery separates public fields and identity');
    $profile = $success($call('save-author', ['display_name' => 'MCP Synthetic Guest', 'slug' => 'mcp-guest-' . strtolower(wp_generate_password(8, false)), 'fields' => ['description' => '<p>Synthetic biography</p>']]), 'Create guest through native author service');
    $terms[] = $authorId = $profile['author']->term_id;
    $check($profile['author']->is_guest && strpos($profile['fields']->description, 'Synthetic biography') !== false, 'Native profile data round-trips');
    $updated = $success($call('save-author', ['author_id' => $authorId, 'expected_hash' => $profile['hash'], 'fields' => ['first_name' => 'Fixture']]), 'Patch a profile');
    $check(strpos($updated['fields']->description, 'Synthetic biography') !== false, 'Profile patch preserves biography');
    $check(is_wp_error($call('save-author', ['author_id' => $authorId, 'expected_hash' => $profile['hash'], 'display_name' => 'Stale'])), 'Stale profile hash rejected');
    $check(is_wp_error($call('save-author', ['author_id' => $authorId, 'expected_hash' => $updated['hash'], 'fields' => ['role' => 'administrator']])), 'Privileged profile metadata rejected');
    $category = $success($call('save-author-category', ['category_name' => 'Fixture Contributor ' . wp_generate_password(6, false), 'plural_name' => 'Fixture Contributors', 'enabled' => true]), 'Create native category');
    $categories[] = $categoryId = (int) $category['category']->id;
    $postId = wp_insert_post(['post_title' => 'Synthetic byline target', 'post_status' => 'draft', 'post_author' => $users['author']]);
    $posts[] = $postId;
    $byline = $success($call('get-post-byline', ['post_id' => $postId]), 'Read native byline without synchronization');
    $input = ['post_id' => $postId, 'expected_hash' => $byline['hash'], 'author_ids' => [$authorId], 'categories' => [(string) $authorId => [$categoryId]]];
    $preview = $success($call('update-post-byline', $input + ['dry_run' => true]), 'Preview byline');
    $check($call('get-post-byline', ['post_id' => $postId])['hash'] === $byline['hash'] && $preview['author_ids'] === [$authorId], 'Byline preview preserves saved state');
    $byline = $success($call('update-post-byline', $input), 'Save ordered author byline');
    $check($byline['author_ids'] === [$authorId], 'Native byline contains exact author terms');
    $check(is_wp_error($call('update-post-byline', $input)), 'Stale byline rejected');
    $check(is_wp_error($call('delete-author', ['author_id' => $authorId, 'expected_hash' => $updated['hash']])), 'Assigned author cannot be silently deleted');
    $before = $byline['hash'];
    $check(is_wp_error($call('bulk-update-post-bylines', ['changes' => [
        ['post_id' => $postId, 'expected_hash' => $before, 'author_ids' => [$authorId]],
        ['post_id' => 99999999, 'expected_hash' => str_repeat('0', 64), 'author_ids' => [$authorId]],
    ]])), 'Bulk validates a failing final target');
    $check($call('get-post-byline', ['post_id' => $postId])['hash'] === $before, 'Invalid batch leaves earlier bylines unchanged');
    $boxFields = $success($call('get-author-box-fields'), 'Discover native Author Box fields');
    $check(isset($boxFields['schema']['properties']['show_title']), 'Author Box field labels and types are available');
    $box = $success($call('save-author-box', ['title' => 'Synthetic MCP Box', 'fields' => ['show_title' => true, 'title_text' => 'Synthetic authors']]), 'Create Author Box');
    $posts[] = $boxId = $box['id'];
    $check((string) get_post_meta($boxId, 'ppma_boxes_layout_meta_value', true)['show_title'] === '1', 'Native box metadata uses expected checkbox values');
    $success($call('save-author-box', ['id' => $boxId, 'expected_hash' => $box['hash'], 'fields' => ['title_text' => 'Updated fixture']]), 'Patch native box settings');
    $check(is_wp_error($call('save-author-box', ['id' => $boxId, 'expected_hash' => $box['hash'], 'fields' => ['unknown_key' => 'x']])), 'Unknown box fields rejected');
    $listFields = $success($call('get-author-list-fields'), 'Discover native Author List fields');
    $layout = array_values(array_filter($listFields['schema']['properties']['layout']['enum']))[0];
    $list = $success($call('save-author-list', ['fields' => ['title' => 'Synthetic MCP List', 'layout' => $layout]]), 'Create native list preset');
    $check(strpos($list['shortcode'], '[publishpress_authors_list') === 0, 'List generates a native shortcode');
    $expectedDynamic = '[publishpress_authors_list list_id="' . $list['id'] . '"]';
    $savedList = $legacy->modules->author_list->options->author_list_data[$list['id']];
    $check($savedList['dynamic_shortcode'] === $expectedDynamic, 'MCP creation stores the native dynamic shortcode');
    $secondList = $success($call('save-author-list', ['fields' => ['title' => 'Second synthetic MCP List', 'layout' => $layout]]), 'Create another native list preset');
    $secondSaved = $legacy->modules->author_list->options->author_list_data[$secondList['id']];
    $check($secondList['id'] !== $list['id'] && $secondSaved['dynamic_shortcode'] === '[publishpress_authors_list list_id="' . $secondList['id'] . '"]', 'Each MCP list gets its own dynamic shortcode');
    require_once dirname(__DIR__, 2) . '/src/modules/author-list/classes/AuthorListTable.php';
    $tableClass = new ReflectionClass(\MultipleAuthorList\AuthorListTable::class);
    $table = $tableClass->newInstanceWithoutConstructor();
    $column = $tableClass->getMethod('column_dynamic_shortcode');
    $column->setAccessible(true);
    $legacyList = $savedList;
    unset($legacyList['dynamic_shortcode']);
    $beforeRender = serialize($legacy->modules->author_list->options);
    set_error_handler(static function ($level, $message) {
        throw new RuntimeException($message);
    }, E_WARNING | E_NOTICE);
    try {
        $legacyHtml = $column->invoke($table, $legacyList);
        $legacyList['dynamic_shortcode'] = '';
        $emptyHtml = $column->invoke($table, $legacyList);
        $savedHtml = $column->invoke($table, $secondSaved);
    } finally {
        restore_error_handler();
    }
    $check(strpos($legacyHtml, esc_attr($expectedDynamic)) !== false && $emptyHtml === $legacyHtml, 'Existing lists with missing or empty shortcode render without warnings');
    $check(strpos($savedHtml, esc_attr($secondSaved['dynamic_shortcode'])) !== false, 'Saved MCP shortcode renders in the native table');
    $check(serialize($legacy->modules->author_list->options) === $beforeRender, 'Rendering an older list does not change stored options');
    $previewOptions = serialize($legacy->modules->author_list->options);
    $previewList = $success($call('save-author-list', ['fields' => ['title' => 'Preview list', 'layout' => $layout], 'dry_run' => true]), 'Preview list shortcode');
    $check($previewList['dry_run'] && serialize($legacy->modules->author_list->options) === $previewOptions, 'List shortcode preview saves nothing');

    $success($call('save-author-list', ['id' => $list['id'], 'expected_hash' => $list['hash'], 'fields' => ['title' => 'Renamed synthetic list']]), 'Patch list without replacing layout');
    $check(is_wp_error($call('save-author-list', ['id' => $list['id'], 'expected_hash' => $list['hash'], 'fields' => ['title' => 'Stale']])), 'List revision check protects concurrent edits');
    $check($legacy->modules->author_list->options->author_list_data[$list['id']]['dynamic_shortcode'] === $expectedDynamic, 'List update keeps the same dynamic shortcode');
    $inventory = $success($call('list-features'), 'Discover native modules and workflows');
    $check(count($inventory['features']) >= 10, 'Inventory covers native modules');
    require __DIR__ . '/workflow-cases.php';
    require __DIR__ . '/permission-cases.php';
    require __DIR__ . '/edition-cases.php';
    if (isset($argv[2])) {
        require_once $argv[2] . '/mcp-adapter.php';
        $discovery = \WP\MCP\Abilities\DiscoverAbilitiesAbility::execute([]);
        $check(strpos(wp_json_encode($discovery), 'publishpress-authors/') === false, 'Default adapter discovery excludes Authors');
        foreach ($abilities as $ability) {
            $tool = \WP\MCP\Domain\Tools\McpTool::fromAbility($ability);
            $check(!is_wp_error($tool), 'Released adapter projects ' . $ability->get_name());
            $check(is_wp_error(\WP\MCP\Abilities\ExecuteAbilityAbility::check_permission(['ability_name' => $ability->get_name(), 'parameters' => []])), 'Default adapter denies ' . $ability->get_name());
        }
    }
    if (isset($argv[2])) {
        $tool = \WP\MCP\Domain\Tools\McpTool::fromAbility(wp_get_ability('publishpress-authors/get-plugin-settings'));
        $check(!is_wp_error($tool->execute([])), 'Custom-server tool executes with native identity');
    }
    echo "Completed $checks Authors checks in " . getenv('AUTHORS_MCP_MODE') . " mode.\n";
} finally {
    wp_set_current_user($users['administrator'] ?? 0);
    foreach ($posts as $id) { wp_delete_post($id, true); }
    foreach ($terms as $id) { wp_delete_term($id, 'author'); }
    foreach ($categories as $id) { $legacy->author_categories->deleteAuthorCategory($id); }
    foreach ($users as $id) {
        $linkedTerms = get_terms(['taxonomy' => 'author', 'hide_empty' => false, 'fields' => 'ids', 'meta_key' => 'user_id', 'meta_value' => $id]);
        foreach (is_array($linkedTerms) ? $linkedTerms : [] as $termId) { wp_delete_term($termId, 'author'); }
        \MultipleAuthors\Classes\Objects\Author::clear_cache();
        wp_delete_user($id);
    }
    foreach ($snapshots as $name => $options) { $legacy->update_all_module_options($name, $options); }
}
