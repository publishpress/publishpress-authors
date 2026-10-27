<?php

declare(strict_types=1);

namespace MultipleAuthors\Mcp;

use MultipleAuthors\Capability;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;

\defined('ABSPATH') || exit;

/** Explicit non-secret native settings and optional feature switches. @since 4.18.0 */
class Settings
{
    public static function register()
    {
        $output = ['settings' => ['type' => 'object'], 'schema' => ['type' => 'object'], 'dry_run' => ['type' => 'boolean']];
        Registry::add(
            'get-plugin-settings',
            __('Read Authors settings', 'publishpress-authors'),
            __('Read typed General, Display, Author Pages, Guest Authors and Advanced settings, with defaults and native layout/role/post-type choices. Credentials and license keys are excluded. Whole-module switches use list-features and set-feature-enabled.', 'publishpress-authors'),
            Registry::object(),
            $output,
            [__CLASS__, 'get'],
            [Capability::class, 'currentUserCanManageSettings']
        );
        Registry::add(
            'update-plugin-settings',
            __('Update Authors settings', 'publishpress-authors'),
            __('Patch only supplied Authors settings; preserves unrelated settings. Use get-plugin-settings for exact fields and native choices. dry_run validates and returns proposed values without saving. Author Pages changes refresh rewrite rules. Pro branding requires Pro.', 'publishpress-authors'),
            Registry::object(['settings' => ['type' => 'object', 'minProperties' => 1, 'maxProperties' => 100, 'additionalProperties' => ['type' => ['string', 'integer', 'boolean', 'object', 'array'], 'items' => ['type' => 'string'], 'additionalProperties' => ['type' => 'boolean']]], 'dry_run' => Registry::preview()], ['settings']),
            $output,
            [__CLASS__, 'update'],
            [Capability::class, 'currentUserCanManageSettings'],
            true
        );
        Registry::add(
            'list-features',
            __('Discover Authors features and actions', 'publishpress-authors'),
            __('List native Authors screens/modules, enabled state, permissions, edition and read/write workflows. Separate author profiles, post bylines, Author Boxes, Author Lists, Author Categories and Pro Custom Fields. Use field discovery before editing.', 'publishpress-authors'),
            Registry::object(),
            ['features' => ['type' => 'array', 'items' => ['type' => 'object']], 'pro_active' => ['type' => 'boolean']],
            [__CLASS__, 'features'],
            [Capability::class, 'currentUserCanManageSettings']
        );
        Registry::add(
            'set-feature-enabled',
            __('Enable or disable an Authors module', 'publishpress-authors'),
            __('Change one discovered optional module switch. Autoloaded/core modules cannot be disabled. Does not change individual feature settings or author fields. dry_run previews without saving; refresh the MCP connection after enabling a module.', 'publishpress-authors'),
            Registry::object(['feature' => ['type' => 'string', 'minLength' => 1], 'enabled' => ['type' => 'boolean'], 'dry_run' => Registry::preview()], ['feature', 'enabled']),
            ['feature' => ['type' => 'string'], 'enabled' => ['type' => 'boolean'], 'dry_run' => ['type' => 'boolean']],
            [__CLASS__, 'setEnabled'],
            [Capability::class, 'currentUserCanManageSettings'],
            true
        );
    }

    public static function schema(): array
    {
        $properties = [
            'append_to_content' => ['type' => 'boolean', 'default' => true, 'title' => __('Show Author Box below content', 'publishpress-authors')],
            'preppend_to_content' => ['type' => 'boolean', 'default' => false, 'title' => __('Show Author Box above content', 'publishpress-authors')],
            'force_empty_author' => ['type' => 'boolean', 'default' => false, 'title' => __('Force empty author', 'publishpress-authors')],
            'username_in_search_field' => ['type' => 'boolean', 'default' => false, 'title' => __('Username in search field', 'publishpress-authors')],
            'enable_plugin_author_pages' => ['type' => 'boolean', 'default' => false, 'title' => __('Enable plugin author pages', 'publishpress-authors')],
            'show_author_pages_bio' => ['type' => 'boolean', 'default' => true, 'title' => __('Show author pages bio', 'publishpress-authors')],
            'show_author_post_featured_image' => ['type' => 'boolean', 'default' => true, 'title' => __('Show author post featured image', 'publishpress-authors')],
            'link_author_post_featured_image' => ['type' => 'boolean', 'default' => false, 'title' => __('Link author post featured image', 'publishpress-authors')],
            'show_author_post_excerpt' => ['type' => 'boolean', 'default' => true, 'title' => __('Show author post excerpt', 'publishpress-authors')],
            'remove_single_user_map_restriction' => ['type' => 'boolean', 'default' => false, 'title' => __('Remove single user map restriction', 'publishpress-authors')],
            'show_author_post_authors' => ['type' => 'boolean', 'default' => true, 'title' => __('Show author post authors', 'publishpress-authors')],
            'show_author_post_date' => ['type' => 'boolean', 'default' => true, 'title' => __('Show author post date', 'publishpress-authors')],
            'show_author_post_comments' => ['type' => 'boolean', 'default' => true, 'title' => __('Show author post comments', 'publishpress-authors')],
            'show_author_post_category' => ['type' => 'boolean', 'default' => true, 'title' => __('Show author post category', 'publishpress-authors')],
            'show_author_post_tags' => ['type' => 'boolean', 'default' => true, 'title' => __('Show author post tags', 'publishpress-authors')],
            'show_author_post_readmore' => ['type' => 'boolean', 'default' => true, 'title' => __('Show author post readmore', 'publishpress-authors')],
            'hide_author_pages_empty_posts_message' => ['type' => 'boolean', 'default' => false, 'title' => __('Hide author pages empty posts message', 'publishpress-authors')],
            'show_author_page_title' => ['type' => 'boolean', 'default' => true, 'title' => __('Show author page title', 'publishpress-authors')],
            'disable_quick_edit_author_box' => ['type' => 'boolean', 'default' => false, 'title' => __('Disable quick edit author box', 'publishpress-authors')],
            'enable_font_awesome' => ['type' => 'boolean', 'default' => true, 'title' => __('Enable font awesome', 'publishpress-authors')],
            'enable_guest_author_user' => ['type' => 'boolean', 'default' => false, 'title' => __('Allow guest authors without a user account', 'publishpress-authors')],
            'author_boxes_opt_out' => ['type' => 'boolean', 'default' => false, 'title' => __('Author boxes opt out', 'publishpress-authors')],
            'allow_author_multiple_categories' => ['type' => 'boolean', 'default' => false, 'title' => __('Allow author multiple categories', 'publishpress-authors')],
            'enable_guest_author_acount' => ['type' => 'boolean', 'default' => true, 'title' => __('Allow guest authors with a user account', 'publishpress-authors')],
            'show_editor_author_box_selection' => ['type' => 'boolean', 'default' => true, 'title' => __('Show editor author box selection', 'publishpress-authors')],
            'default_avatar_decorative' => ['type' => 'boolean', 'default' => false, 'title' => __('Default avatar decorative', 'publishpress-authors')],
            'translate_author_taxonomy' => ['type' => 'boolean', 'default' => true, 'title' => __('Translate author taxonomy', 'publishpress-authors')],
            'allow_admin_edit_admin_profiles' => ['type' => 'boolean', 'default' => false, 'title' => __('Allow administrators to edit other administrator profiles', 'publishpress-authors')],
        ];
        $properties['show_email_link'] = ['type' => 'boolean', 'default' => true, 'title' => __('Show email link in legacy Author Box', 'publishpress-authors')];
        $properties['show_site_link'] = ['type' => 'boolean', 'default' => true, 'title' => __('Show website link in legacy Author Box', 'publishpress-authors')];
        $properties['title_appended_to_content'] = ['type' => 'string', 'maxLength' => 200, 'default' => __('Author', 'publishpress-authors')];
        $properties['title_appended_to_content_plural'] = ['type' => 'string', 'maxLength' => 200, 'default' => __('Authors', 'publishpress-authors')];
        $properties['color_scheme'] = ['type' => 'string', 'pattern' => '^#[a-fA-F0-9]{6}$', 'default' => '#655997', 'title' => __('Legacy Author Box color scheme', 'publishpress-authors')];
        $layouts = array_map('strval', array_keys(apply_filters('pp_multiple_authors_author_layouts', [])));
        foreach (['layout', 'author_pages_bio_layout', 'author_legacy_layout_boxed', 'author_legacy_layout_centered', 'author_legacy_layout_inline', 'author_legacy_layout_inline_avatar', 'author_legacy_layout_simple_list'] as $key) {
            $properties[$key] = ['type' => 'string', 'enum' => array_values(array_unique(array_merge([''], $layouts))), 'description' => __('Choose a native Author Box layout ID or slug from discovery.', 'publishpress-authors')];
        }
        foreach (['post_types', 'author_page_post_types'] as $key) {
            $fields = [];
            foreach (Utils::get_post_types_that_support_authors() as $postType) {
                $fields[$postType] = ['type' => 'boolean'];
            }
            $properties[$key] = Registry::object($fields);
        }
        foreach (['mapped_author_roles', 'author_for_new_users'] as $key) {
            $properties[$key] = ['type' => 'array', 'maxItems' => 100, 'uniqueItems' => true,
                'items' => ['type' => 'string', 'enum' => array_keys(apply_filters('editable_roles', wp_roles()->roles))]];
        }
        $properties['author_pages_layout'] = ['type' => 'string', 'enum' => ['list', 'grid']];
        foreach (['author_pages_title_header', 'author_post_title_header'] as $key) {
            $properties[$key] = ['type' => 'string', 'enum' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']];
        }
        foreach (['author_post_custom_width', 'author_post_custom_height', 'default_avatar'] as $key) {
            $properties[$key] = ['type' => 'string', 'maxLength' => 1000];
        }
        foreach (['author_pages_posts_limit' => [1, 1000], 'author_pages_grid_layout_column' => [1, 12], 'fallback_user_for_guest_post' => [0, PHP_INT_MAX], 'default_author_for_new_posts' => [0, PHP_INT_MAX]] as $key => $range) {
            $properties[$key] = ['type' => 'integer', 'minimum' => $range[0], 'maximum' => $range[1]];
        }
        $properties['author_post_excerpt_ellipsis'] = ['type' => 'string', 'maxLength' => 100];
        $properties['default_avatar_alt'] = ['type' => 'string', 'maxLength' => 500];
        $properties['display_name_format'] = ['type' => 'string', 'enum' => ['custom', 'first_name_last_name', 'last_name_first_name', 'first_name', 'last_name']];
        if (Utils::isAuthorsProActive()) {
            $properties['display_branding'] = ['type' => 'boolean', 'description' => __('Pro: display PublishPress branding.', 'publishpress-authors')];
        }
        $properties['mapped_author_roles']['description'] = __('Roles available when mapping existing WordPress users to authors.', 'publishpress-authors');
        $properties['author_for_new_users']['description'] = __('Automatically create author profiles for new users in these roles.', 'publishpress-authors');
        $properties['default_author_for_new_posts']['description'] = __('Default author taxonomy term ID for new posts; zero uses native defaults.', 'publishpress-authors');
        $properties['fallback_user_for_guest_post']['description'] = __('Existing WordPress user ID used as the core post author for guest bylines.', 'publishpress-authors');
        $properties['enable_guest_author_acount']['description'] = __('Native guest account setting. MCP profile actions only link existing accounts and never create user accounts.', 'publishpress-authors');
        return Registry::object($properties);
    }

    private static function publicValues(array $raw): array
    {
        $result = [];
        foreach (self::schema()['properties'] as $name => $field) {
            $value = $raw[$name] ?? ($field['default'] ?? ($field['type'] === 'integer' ? 0 : ($field['type'] === 'array' ? [] : '')));
            if ($field['type'] === 'boolean') {
                $value = in_array($value, ['yes', 'on', true, 1, '1'], true);
            } elseif ($field['type'] === 'integer') {
                $value = (int) $value;
            } elseif ($field['type'] === 'object') {
                $values = [];
                foreach ((array) $value as $key => $enabled) {
                    if (isset($field['properties'][$key])) {
                        $values[$key] = in_array($enabled, ['on', true, 1, '1'], true);
                    }
                }
                $value = (object) $values;
            }
            $result[$name] = $value;
        }
        return $result;
    }

    public static function get(): array
    {
        return ['settings' => (object) self::publicValues((array) Factory::getLegacyPlugin()->modules->multiple_authors->options), 'schema' => self::schema(), 'dry_run' => false];
    }

    public static function update(array $input)
    {
        $schema = self::schema();
        $valid = rest_validate_value_from_schema($input['settings'], $schema, 'settings');
        if (is_wp_error($valid)) {
            return $valid;
        }
        $legacy = Factory::getLegacyPlugin();
        $current = (array) $legacy->modules->multiple_authors->options;
        $values = $current;
        foreach ($input['settings'] as $name => $value) {
            $type = $schema['properties'][$name]['type'];
            if (($type === 'boolean' && !is_bool($value)) || ($type === 'integer' && !is_int($value))) {
                return Registry::error(__('Use exact setting types from discovery.', 'publishpress-authors'));
            }
            if ($type === 'boolean') {
                $value = $name === 'display_branding' ? ($value ? 'on' : 'off') : ($value ? 'yes' : 'no');
            } elseif ($type === 'object') {
                $map = (array) ($current[$name] ?? []);
                foreach ($value as $key => $enabled) {
                    if (!is_bool($enabled)) {
                        return Registry::error(__('Post type switches require true or false.', 'publishpress-authors'));
                    }
                    if (post_type_supports($key, $legacy->modules->multiple_authors->post_type_support) && !$enabled) {
                        return Registry::error(__('This post type is enabled by site code and cannot be disabled here.', 'publishpress-authors'));
                    }
                    $map[$key] = $enabled ? 'on' : 'off';
                }
                $value = $map;
            } elseif (is_string($value)) {
                $value = sanitize_text_field($value);
            }
            if ($name === 'default_author_for_new_posts' && $value && !Registry::author($value)) {
                return Registry::error(__('Choose an existing default author profile.', 'publishpress-authors'));
            }
            if ($name === 'fallback_user_for_guest_post' && $value && !get_userdata($value)) {
                return Registry::error(__('Choose an existing fallback user.', 'publishpress-authors'));
            }
            if ($name === 'default_avatar' && $value !== '' && !wp_http_validate_url($value)) {
                return Registry::error(__('Choose a valid avatar URL.', 'publishpress-authors'));
            }
            $values[$name] = $value;
        }
        $validated = apply_filters('multiple_authors_validate_module_settings', array_diff_key($values, ['license_key' => true, 'license_status' => true]), 'multiple_authors');
        if (is_wp_error($validated) || !is_array($validated)) {
            return is_wp_error($validated) ? $validated : Registry::error(__('Native settings validation failed.', 'publishpress-authors'));
        }
        // Native validators normalize a complete form; only supplied settings are patched here.
        foreach ($input['settings'] as $name => $unused) {
            $current[$name] = $validated[$name] ?? $values[$name];
        }
        if (empty($input['dry_run'])) {
            $saved = Registry::saveModuleOptions('multiple_authors', $current);
            if (is_wp_error($saved)) {
                return $saved;
            }
            if (array_intersect(array_keys($input['settings']), ['enable_plugin_author_pages', 'post_types', 'author_page_post_types'])) {
                set_transient('ppma_flush_rewrite_rules', true, WEEK_IN_SECONDS);
            }
        }
        return ['settings' => (object) self::publicValues($current), 'schema' => $schema, 'dry_run' => !empty($input['dry_run'])];
    }

    public static function features(): array
    {
        $items = [];
        foreach (Factory::getLegacyPlugin()->modules as $name => $module) {
            $items[] = ['id' => $name, 'label' => wp_strip_all_tags($module->title), 'enabled' => ($module->options->enabled ?? 'off') === 'on',
                'switchable' => empty($module->autoload), 'description' => wp_strip_all_tags($module->short_description ?? '')];
        }
        foreach (
            [
            ['author-box-workflow', __('Author Boxes', 'publishpress-authors'), ['list-author-boxes', 'get-author-box', 'get-author-box-fields'], ['save-author-box', 'delete-author-box'], [apply_filters('pp_multiple_authors_manage_layouts_cap', 'ppma_manage_layouts'), 'edit_post (target box)'], false],
            ['author-list-workflow', __('Author Lists', 'publishpress-authors'), ['list-author-lists', 'get-author-list', 'get-author-list-fields'], ['save-author-list', 'delete-author-list'], [Capability::getManageOptionsCapability()], false],
            ['category-workflow', __('Author Categories', 'publishpress-authors'), ['list-author-categories', 'get-author-category', 'get-author-category-fields'], ['save-author-category', 'delete-author-category'], [apply_filters('pp_multiple_authors_manage_categories_cap', 'ppma_manage_author_categories')], false],
            ['custom-field-workflow', __('Author field definitions', 'publishpress-authors'), ['list-custom-fields', 'get-custom-field', 'get-custom-field-fields'], ['save-custom-field', 'delete-custom-field'], [apply_filters('pp_multiple_authors_manage_custom_fields_cap', 'ppma_manage_custom_fields'), 'edit_post (target definition)'], true],
            ['settings-workflow', __('Settings, Author Pages and integrations', 'publishpress-authors'), ['get-plugin-settings', 'list-features'], ['update-plugin-settings', 'set-feature-enabled'], [Capability::getManageOptionsCapability()], false],
            ] as $workflow
        ) {
            $items[] = ['id' => $workflow[0], 'label' => $workflow[1], 'read' => $workflow[2], 'write' => $workflow[3], 'required_capabilities' => $workflow[4], 'pro_required_for_writes' => $workflow[5], 'write_available' => !$workflow[5] || Utils::isAuthorsProActive()];
        }
        $items[] = ['id' => 'profile-workflow', 'label' => __('Author profiles', 'publishpress-authors'), 'read' => ['list-authors', 'get-author-profile', 'get-profile-fields'], 'write' => ['save-author', 'link-author-user', 'delete-author'], 'required_capabilities' => [Capability::getManageAuthorsCapability(), 'edit_term', 'edit_user (linked account)']];
        $items[] = ['id' => 'post-byline-workflow', 'label' => __('Ordered post bylines and per-post Author Box', 'publishpress-authors'), 'read' => ['get-post-byline', 'get-byline-choices'], 'write' => ['update-post-byline', 'bulk-update-post-bylines'], 'required_capabilities' => [Capability::getEditPostAuthorsCapability(), 'edit_post', 'assign_terms']];
        return ['features' => $items, 'pro_active' => Utils::isAuthorsProActive()];
    }

    public static function setEnabled(array $input)
    {
        $legacy = Factory::getLegacyPlugin();
        $module = $legacy->modules->{$input['feature']} ?? null;
        if (!$module || !empty($module->autoload)) {
            return Registry::error(__('Choose a discovered optional module.', 'publishpress-authors'));
        }
        if (empty($input['dry_run'])) {
            $options = (array) $module->options;
            $options['enabled'] = $input['enabled'] ? 'on' : 'off';
            $saved = Registry::saveModuleOptions($input['feature'], $options);
            if (is_wp_error($saved)) {
                return $saved;
            }
        }
        return ['feature' => $input['feature'], 'enabled' => $input['enabled'], 'dry_run' => !empty($input['dry_run'])];
    }
}
