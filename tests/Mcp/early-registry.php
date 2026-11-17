<?php

/**
 * Run with AUTHORS_MCP_MODE=free|pro against an isolated mcp_smoke_ WordPress database.
 * Usage: php early-registry.php /path/to/wp-load.php /path/to/released-adapter
 */

if (PHP_SAPI !== 'cli' || !isset($argv[2]) || !in_array(getenv('AUTHORS_MCP_MODE'), ['free', 'pro'], true)) {
    exit("Provide an isolated WordPress bootstrap, adapter directory and AUTHORS_MCP_MODE.\n");
}
$checks = 0;
$check = static function ($condition, $message) use (&$checks) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
    echo "PASS: $message\n";
};
// WordPress converts preinitialized hooks into WP_Hook instances during bootstrap.
$GLOBALS['wp_filter']['init'][10][] = ['function' => static function () use ($check) {
    $check(empty(\MultipleAuthors\Factory::getLegacyPlugin()->multiple_authors), 'Registry starts before native modules');
    $check(wp_get_ability('publishpress-authors/update-plugin-settings') !== null, 'Early registry initializes Authors');
}, 'accepted_args' => 0];
$GLOBALS['wp_filter']['wp_abilities_api_init'][99][] = ['function' => static function () {
    wp_register_ability('authors-test/sentinel', [
        'label' => 'Later callback', 'description' => 'Synthetic registration sentinel.',
        'category' => 'publishpress', 'input_schema' => ['type' => 'object'],
        'output_schema' => ['type' => 'object'], 'permission_callback' => '__return_true',
        'execute_callback' => static function () {
            return [];
        },
    ]);
}, 'accepted_args' => 0];
$GLOBALS['wp_filter']['init'][20][] = ['function' => static function () {
    add_role('mcp_late_role', 'Late role', ['read' => true]);
    add_filter('pp_multiple_authors_author_layouts', static function ($layouts) {
        $layouts['mcp_late_layout'] = 'Late layout';
        return $layouts;
    });
    register_post_type('mcp_late_book', ['public' => true, 'label' => 'Late books', 'supports' => ['title']]);
}, 'accepted_args' => 0];
require $argv[1];
if ($GLOBALS['wpdb']->prefix !== 'mcp_smoke_') {
    exit("Use an isolated mcp_smoke_ database.\n");
}
require_once $argv[2] . '/mcp-adapter.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
$check(wp_get_ability('authors-test/sentinel') !== null, 'Later ability callbacks still run');
$legacy = \MultipleAuthors\Factory::getLegacyPlugin();
$original = (array) $legacy->modules->multiple_authors->options;
$user = wp_insert_user(['user_login' => 'early_mcp_' . wp_generate_password(8, false),
    'user_pass' => wp_generate_password(), 'role' => 'administrator']);
$check(!is_wp_error($user), 'Create isolated administrator');
wp_set_current_user($user);
$tool = \WP\MCP\Domain\Tools\McpTool::fromAbility(wp_get_ability('publishpress-authors/update-plugin-settings'));
$check(!is_wp_error($tool), 'Released adapter projects early-registered settings');
try {
    $schema = \MultipleAuthors\Mcp\Settings::get()['schema'];
    $check(isset($schema['properties']['post_types']['properties']['mcp_late_book']), 'Discovery sees late native post type');
    foreach (['post_types', 'author_page_post_types'] as $field) {
        $input = ['settings' => [$field => ['mcp_late_book' => true]], 'dry_run' => true];
        $preview = $tool->execute($input);
        $check(!is_wp_error($preview), $field . ' previews through released adapter');
        $check((array) $legacy->modules->multiple_authors->options === $original, 'Preview preserves native options');
        $input['dry_run'] = false;
        $saved = $tool->execute($input);
        $check(!is_wp_error($saved), $field . ' saves through released adapter');
        $check($legacy->modules->multiple_authors->options->{$field}['mcp_late_book'] === 'on', 'Native storage uses on');
        $check(\MultipleAuthors\Mcp\Settings::get()['settings']->{$field}->mcp_late_book === true, 'Native readback returns boolean');
        \MultipleAuthors\Mcp\Registry::saveModuleOptions('multiple_authors', $original);
    }
    foreach (
        [
        ['mapped_author_roles' => ['mcp_late_role']],
        ['author_for_new_users' => ['mcp_late_role']],
        ['layout' => 'mcp_late_layout'],
        ] as $patch
    ) {
        $check(!is_wp_error($tool->execute(['settings' => $patch, 'dry_run' => true])), 'Late dynamic choice passes adapter validation');
        $check((array) $legacy->modules->multiple_authors->options === $original, 'Dynamic-choice preview preserves native options');
    }
    foreach (
        [
        ['mapped_author_roles' => ['missing-role']],
        ['post_types' => ['mcp_unknown' => true]],
        ['post_types' => ['mcp_late_book' => 'false']],
        ['append_to_content' => false, 'post_types' => ['mcp_unknown' => true]],
        ['unknown_setting' => true],
        ['layout' => 'missing-layout'],
        ] as $patch
    ) {
        $result = $tool->execute(['settings' => $patch]);
        $check(is_wp_error($result), 'Invalid or mixed patch is rejected');
        $check((array) $legacy->modules->multiple_authors->options === $original, 'Rejected patch preserves all options');
    }
    $deny = static function ($caps) {
        $caps[\MultipleAuthors\Capability::getManageOptionsCapability()] = false;
        return $caps;
    };
    add_filter('user_has_cap', $deny);
    try {
        $check(is_wp_error($tool->execute(['settings' => ['append_to_content' => false]])), 'Administrator cannot bypass denied settings capability');
    } finally {
        remove_filter('user_has_cap', $deny);
    }
    echo "Completed $checks early-registry checks in " . getenv('AUTHORS_MCP_MODE') . " mode.\n";
} finally {
    \MultipleAuthors\Mcp\Registry::saveModuleOptions('multiple_authors', $original);
    wp_delete_user($user);
    remove_role('mcp_late_role');
}
