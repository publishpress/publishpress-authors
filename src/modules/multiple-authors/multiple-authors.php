<?php
/**
 * @package PublishPress Authors
 * @author  PublishPress
 *
 * Copyright (C) 2018 PublishPress
 *
 * This file is part of PublishPress Authors
 *
 * PublishPress Authors is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

use MultipleAuthors\Capability;
use MultipleAuthors\Classes\Admin_Ajax;
use MultipleAuthors\Classes\Author_Utils;
use MultipleAuthors\Classes\Installer;
use MultipleAuthors\Classes\Legacy\Module;
use MultipleAuthors\Classes\Legacy\Util;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;


if (!class_exists('MA_Multiple_Authors')) {
    /**
     * class MA_Multiple_Authors
     */
    class MA_Multiple_Authors extends Module
    {
        const SETTINGS_SLUG = 'ppma-settings';

        public $module_name = 'multiple_authors';

        /**
         * The menu slug.
         */
        const MENU_SLUG = 'ppma-authors';

        /**
         * List of post types which supports authors
         *
         * @var array
         */
        protected $post_types = [];

        /**
         * Instance for the module
         *
         * @var stdClass
         */
        public $module;
        public $module_url;

        /**
         * Construct the MA_Multiple_Authors class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'                => __('Multiple Authors', 'publishpress-authors'),
                'short_description'    => __(
                    'PublishPress Authors allows you to add multiple authors and guest authors to WordPress posts',
                    'publishpress-authors'
                ),
                'extended_description' => __(
                    'PublishPress Authors allows you to add multiple authors and guest authors to WordPress posts',
                    'publishpress-authors'
                ),
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-feedback',
                'slug'                 => 'multiple-authors',
                'default_options'      => [
                    'enabled'                      => 'on',
                    'post_types'                   => [
                        'post' => 'on'
                    ],
                    'append_to_content'            => 'yes',
                    'preppend_to_content'          => 'no',
                    'author_for_new_users'         => [],
                    'layout'                       => Utils::getDefaultLayout(),
                    'force_empty_author'           => 'no',
                    'username_in_search_field'      => 'no',
                    'enable_plugin_author_pages'   => 'no',
                    'author_pages_layout'          => 'list',
                    'show_author_pages_bio'        => 'yes',
                    'author_pages_bio_layout'      => Utils::getDefaultLayout(),
                    'author_legacy_layout_boxed'   => '',
                    'author_legacy_layout_centered' => '',
                    'author_legacy_layout_inline'   => '',
                    'author_legacy_layout_inline_avatar' => '',
                    'author_legacy_layout_simple_list' => '',
                    'author_pages_grid_layout_column' => '4',
                    'show_author_post_featured_image' => 'yes',
                    'show_author_post_excerpt'     => 'yes',
                    'remove_single_user_map_restriction'  => 'no',
                    'show_author_post_authors'     => 'yes',
                    'show_author_post_date'        => 'yes',
                    'show_author_post_comments'    => 'yes',
                    'show_author_post_category'    => 'yes',
                    'show_author_post_tags'        => 'yes',
                    'show_author_post_readmore'    => 'yes',
                    'show_author_page_title'       => 'yes',
                    'author_pages_title_header'    => 'h1',
                    'author_post_title_header'     => 'h2',
                    'author_post_custom_width'     => '',
                    'author_post_custom_height'    => '',
                    'author_post_excerpt_ellipsis' => '...',
                    'author_pages_posts_limit'     => '10',
                    'default_author_for_new_posts' => null,
                    'fallback_user_for_guest_post' => function_exists('get_current_user_id') ? get_current_user_id() : 0,
                    'author_page_post_types'       => [],
                    'disable_quick_edit_author_box' => 'no',
                    'enable_font_awesome'            => 'yes',
                    'enable_guest_author_user'     => 'no',
                    'author_boxes_opt_out'         => 'no',
                    'enable_guest_author_acount'   => 'yes',
                    'default_avatar'               => '',
                    'display_name_format'          => 'custom',
                ],
                'options_page'         => false,
                'autoload'             => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters('pp_multiple_authors_default_options', $args['default_options']);

            $legacyPlugin = Factory::getLegacyPlugin();

            $this->module = $legacyPlugin->register_module($this->module_name, $args);

            parent::__construct();
        }

        /**
         * Returns a list of post types the multiple authors module.
         *
         * @return array
         */
        public function get_post_types()
        {
            if (empty($this->post_types)) {
                $post_types = [
                    'post' => esc_html__('Posts', 'publishpress-authors'),
                    'page' => esc_html__('Pages', 'publishpress-authors'),
                ];

                // Apply filters to the list of requirements
                $this->post_types = apply_filters('pp_multiple_authors_post_types', $post_types);

                // Try a more readable name
                foreach ($this->post_types as $type => $label) {
                    $this->post_types[$type] = esc_html__(ucfirst($label));
                }
            }

            return $this->post_types;
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {
            if (is_admin()) {
                add_action('admin_init', [$this, 'register_settings']);
                add_action('admin_init', [$this, 'handle_maintenance_task']);
                add_action('admin_init', [$this, 'migrate_legacy_settings']);
                add_action('admin_init', [$this, 'dismissCoAuthorsMigrationNotice']);
                add_action('admin_init', [$this, 'dismissPermissionsSyncNotice']);
                add_action('admin_init', [$this, 'pp_blocks_is_active']);
                add_action('admin_notices', [$this, 'coauthorsMigrationNotice']);
                add_action('admin_notices', [$this, 'permissionsSyncNotice']);
                add_action('admin_notices', [$this, 'handle_maintenance_task_notice']);

                add_filter('gettext', [$this, 'filter_get_text'], 101, 3);

                // Menu
                add_action('multiple_authors_admin_menu_page', [$this, 'action_admin_menu_page']);
                add_action('multiple_authors_admin_submenu', [$this, 'action_admin_submenu'], 50);
                add_filter('custom_menu_order', [$this, 'filter_custom_menu_order']);
                //add plugin row meta
                add_filter('plugin_row_meta', [$this, 'add_plugin_meta'], 10, 2);

                add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
            }

            add_action('multiple_authors_delete_mapped_authors', [$this, 'action_delete_mapped_authors']);
            add_action('multiple_authors_delete_guest_authors', [$this, 'action_delete_guest_authors']);
            add_action('multiple_authors_create_post_authors', [$this, 'action_create_post_authors']);
            add_action('multiple_authors_create_role_authors', [$this, 'action_create_role_authors']);
            add_action('multiple_authors_copy_coauthor_plus_data', [$this, 'action_copy_coauthor_plus_data']);
            add_action('multiple_authors_create_author_categories', [$this, 'action_create_author_categories']);

            add_action('deleted_user', [$this, 'handle_deleted_user']);

            // Filters the list of authors in the Improved Notifications add-on.
            add_filter(
                'publishpress_notifications_receiver_post_authors',
                [$this, 'filter_workflow_receiver_post_authors'],
                10,
                3
            );

            add_filter('multiple_authors_validate_module_settings', [$this, 'validate_module_settings'], 10, 2);
            add_filter('publishpress_multiple_authors_settings_tabs', [$this, 'settings_tab']);

            if (!is_admin()) {
                add_filter('body_class', [$this, 'filter_body_class']);
                add_filter('comment_class', [$this, 'filterCommentClass'], 10, 5);
            } else {
                //author profile edit body class
                add_filter('admin_body_class', [$this, 'filter_admin_body_class']);
            }

            // Fix ACF Caps and upload permissions for multiple authors.
            add_filter('map_meta_cap', [$this, 'filter_map_meta_cap'], 11, 4);

            add_filter('publishpress_is_author_of_post', [$this, 'filter_is_author_of_post'], 10, 3);
            add_filter('publishpress_post_authors_names', [$this, 'filter_post_authors_names'], 10, 2);

            add_action('wp_ajax_migrate_coauthors', [$this, 'migrateCoAuthorsData']);
            add_action('wp_ajax_get_coauthors_migration_data', [$this, 'getCoauthorsMigrationData']);
            add_action('wp_ajax_finish_coauthors_migration', [$this, 'finishCoAuthorsMigration']);
            add_action('wp_ajax_get_sync_post_author_data', [$this, 'getSyncPostAuthorData']);
            add_action('wp_ajax_sync_post_author', [$this, 'syncPostAuthor']);
            add_action('wp_ajax_finish_sync_post_author', [$this, 'finishSyncPostAuthor']);
            add_action('wp_ajax_get_sync_author_slug_data', [$this, 'getSyncAuthorSlugData']);
            add_action('wp_ajax_sync_author_slug', [$this, 'syncAuthorSlug']);
            add_action('wp_ajax_finish_sync_author_slug', [$this, 'finishSyncAuthorSlug']);
            add_action('wp_ajax_deactivate_coauthors_plus', [$this, 'deactivateCoAuthorsPlus']);

            // PublishPress compatibility hooks.
            add_filter('publishpress_search_authors_results_pre_search', [$this, 'publishpressSearchAuthors'], 10, 2);
            add_filter('publishpress_author_can_edit_posts', [$this, 'publishpressAuthorCanEditPosts'], 10, 2);
            add_filter('publishpress_calendar_allow_multiple_authors', '__return_true');
            add_filter(
                'publishpress_calendar_after_create_post',
                [$this, 'publishpressCalendarAfterCreatePost'],
                10,
                2
            );
            add_filter('publishpress_calendar_default_author', [$this, 'publishpressCalendarDefaultAuthor'], 10, 2);
            add_filter('publishpress_author_filter_selected_option', [$this, 'publishpressAuthorFilterSelectedOption'], 10, 2);
            add_filter('PP_Content_Overview_posts_query_args', [$this, 'publishpressPostQueryArgs']);
            add_filter('publishpress_content_overview_author_column', [$this, 'publishpressContentOverviewAuthorColumn'], 10, 2);
            add_filter('pp_calendar_posts_query_args', [$this, 'publishpressPostQueryArgs']);

            add_filter('the_author_posts_link', [$this, 'theAuthorPostsLink']);

            // Fix authors metadata.
            add_filter('get_the_author_display_name', [$this, 'filter_author_metadata_display_name'], 10, 3);
            add_filter('get_the_author_first_name', [$this, 'filter_author_metadata_first_name'], 10, 3);
            add_filter('get_the_author_user_firstname', [$this, 'filter_author_metadata_first_name'], 10, 3);
            add_filter('get_the_author_last_name', [$this, 'filter_author_metadata_last_name'], 10, 3);
            add_filter('get_the_author_user_lastname', [$this, 'filter_author_metadata_last_name'], 10, 3);
            add_filter('get_the_author_ID', [$this, 'filter_author_metadata_ID'], 10, 3);
            add_filter('get_the_author_headline', [$this, 'filter_author_metadata_headline'], 10, 3);
            add_filter('get_the_author_aim', [$this, 'filter_author_metadata_aim'], 10, 3);
            add_filter('get_the_author_description', [$this, 'filter_author_metadata_description'], 10, 3);
            add_filter('get_the_author_user_description', [$this, 'filter_author_metadata_description'], 10, 3);
            add_filter('get_the_author_jabber', [$this, 'filter_author_metadata_jabber'], 10, 3);
            add_filter('get_the_author_nickname', [$this, 'filter_author_metadata_nickname'], 10, 3);
            add_filter('get_the_author_user_email', [$this, 'filter_author_metadata_user_email'], 10, 3);
            add_filter('get_the_author_user_nicename', [$this, 'filter_author_metadata_user_nicename'], 10, 3);
            add_filter('get_the_author_user_url', [$this, 'filter_author_metadata_user_url'], 10, 3);
            add_filter('get_the_author_yim', [$this, 'filter_author_metadata_yim'], 10, 3);
            add_filter('get_the_author_facebook', [$this, 'filter_author_metadata_facebook'], 10, 3);
            add_filter('get_the_author_twitter', [$this, 'filter_author_metadata_twitter'], 10, 3);
            add_filter('get_the_author_instagram', [$this, 'filter_author_metadata_instagram'], 10, 3);

            // Fix authors avatar.
            add_filter('pre_get_avatar_data', [$this, 'filter_pre_get_avatar_data'], 15, 2);

            add_action('publishpress_authors_set_post_authors', [$this, 'actionSetPostAuthors'], 10, 2);

            add_action('profile_update', [$this, 'userProfileUpdate'], 10, 2);

            add_filter('pre_comment_approved', [$this, 'preCommentApproved'], 10, 2);

            // Allow author to edit own author profile.
            add_filter('map_meta_cap', [$this, 'filter_term_map_meta_cap'], 10, 4);

            //add authors template
            add_filter('template_include', [$this, 'authors_taxonomy_template']);

            //author redirect
            add_action('template_redirect', [$this, 'authors_template_redirect']);

            //add authors page post limit
            add_filter('pre_get_posts', [$this, 'authors_taxonomy_post_limit']);

            //prevent guest author login
            add_filter('wp_authenticate_user', [$this, 'prevent_guest_author_login'], 1);

            //prevent outgoing email for guest author
            add_filter('send_password_change_email', [$this, 'prevent_guest_author_emails'], 10, 2);
            add_filter('wp_send_new_user_notification_to_admin', [$this, 'prevent_guest_author_emails'], 10, 2);
            add_filter('wp_send_new_user_notification_to_user', [$this, 'prevent_guest_author_emails'], 10, 2);
            add_filter('send_email_change_email', [$this, 'prevent_guest_author_emails'], 10, 2);

            // Redirect on plugin activation
            add_action('admin_init', [$this, 'redirect_on_activate'], 2000);
        }

        /**
         * Creates the admin menu if there is no menu set.
         */
        public function action_admin_menu_page()
        {
            add_menu_page(
                esc_html__('Authors', 'publishpress-authors'),
                esc_html__('Authors', 'publishpress-authors'),
                apply_filters('pp_multiple_authors_manage_authors_cap', 'ppma_manage_authors'),
                self::MENU_SLUG,
                '',
                'dashicons-groups',
                '26.7'
            );

            $current_author = Author::get_by_user_id(get_current_user_id());
            if ($current_author
                && is_object($current_author)
                && isset($current_author->term_id)
                ) {
                add_menu_page(
                    esc_html__('Author Profile', 'publishpress-authors'),
                    esc_html__('Author Profile', 'publishpress-authors'),
                    apply_filters('pp_multiple_authors_edit_own_profile_cap', 'ppma_edit_own_profile'),
                    'term.php?taxonomy=author&tag_ID='.$current_author->term_id,
                    __return_empty_string(),
                    'dashicons-groups',
                    '26.8'
                );
            }

        }

        /**
         * Add necessary things to the admin menu
         */
        public function action_admin_submenu()
        {
            global $submenu;
            $legacyPlugin = Factory::getLegacyPlugin();

            // Remove the author taxonomy from the all post types
            foreach ($submenu as $menu => $items) {
                if (is_array($items) && !empty($items)) {
                    foreach ($items as $key => $value) {
                        if (isset($value[2]) && strpos($value[2], 'edit-tags.php?taxonomy=author') !== false) {
                            unset($submenu[$menu][$key]);
                        }
                    }
                }
            }

            // Add the submenu to the PublishPress menu.
            add_submenu_page(
                self::MENU_SLUG,
                esc_html__('Authors', 'publishpress-authors'),
                esc_html__('Authors', 'publishpress-authors'),
                apply_filters('pp_multiple_authors_manage_authors_cap', 'ppma_manage_authors'),
                'edit-tags.php?taxonomy=author',
                __return_empty_string(),
                10
            );
        }

        /**
         * Add Authors and Settings to plugin row meta
         *
         * @param array $links
         * @param string $file
         *
         * @return array
         */
        public function add_plugin_meta($links, $file)
        {
            if ($file == plugin_basename(PP_AUTHORS_FILE)) {
                $links[] = '<a href="'. esc_url(admin_url('edit-tags.php?taxonomy=author')) .'">' . esc_html__('Authors', 'publishpress-authors') . '</a>';
                $links[] = '<a href="'. esc_url(admin_url('admin.php?page=ppma-modules-settings')) .'">' . esc_html__('Settings', 'publishpress-authors') . '</a>';
            }

            return $links;
        }

        public function redirect_to_edit_terms_page()
        {
            echo 'Redirecting...';
        }

        public function filter_custom_menu_order($menu_ord)
        {
            global $submenu;

            if (isset($submenu[self::MENU_SLUG])) {
                $currentSubmenu   = $submenu[self::MENU_SLUG];
                $newSubmenu       = [];
                $upgradeMenuSlugs = [];

                // Get the index for the menus, removing the first submenu which was automatically created by WP.
                $itemsToSort = [
                    'ppma-author-categories'            => null,
                    'edit.php?post_type=ppma_boxes'     => null,
                    'edit-tags.php?taxonomy=author'     => null,
                    'edit.php?post_type=ppmacf_field'   => null,
                    'ppma-author-list'                  => null,
                    'ppma-author-pages'                 => null,
                    'ppma-modules-settings'             => null,
                ];

                if (!defined('PUBLISHPRESS_AUTHORS_SKIP_VERSION_NOTICES')) {
                    $suffix           = \PPVersionNotices\Module\MenuLink\Module::MENU_SLUG_SUFFIX;
                    $upgradeMenuSlugs = [
                        'ppma-authors' . $suffix => null,
                    ];

                    $itemsToSort = array_merge($itemsToSort, $upgradeMenuSlugs);
                }

                foreach ($currentSubmenu as $index => $item) {
                    if (array_key_exists($item[2], $itemsToSort)) {
                        $itemsToSort[$item[2]] = $index;
                    }
                }

                // Authors
                if (isset($itemsToSort['edit-tags.php?taxonomy=author'])) {
                    $newSubmenu[] = $currentSubmenu[$itemsToSort['edit-tags.php?taxonomy=author']];

                    unset($currentSubmenu[$itemsToSort['edit-tags.php?taxonomy=author']]);
                }

                // Author Boxes
                if (isset($itemsToSort['edit.php?post_type=ppma_boxes'])) {
                    $newSubmenu[] = $currentSubmenu[$itemsToSort['edit.php?post_type=ppma_boxes']];

                    unset($currentSubmenu[$itemsToSort['edit.php?post_type=ppma_boxes']]);
                }

                // Author Categories
                if (isset($itemsToSort['ppma-author-categories'])) {
                    $newSubmenu[] = $currentSubmenu[$itemsToSort['ppma-author-categories']];

                    unset($currentSubmenu[$itemsToSort['ppma-author-categories']]);
                }

                // Fields
                if (isset($itemsToSort['edit.php?post_type=ppmacf_field'])) {
                    $newSubmenu[] = $currentSubmenu[$itemsToSort['edit.php?post_type=ppmacf_field']];

                    unset($currentSubmenu[$itemsToSort['edit.php?post_type=ppmacf_field']]);
                }

                // Author List
                if (isset($itemsToSort['ppma-author-list'])) {
                    $newSubmenu[] = $currentSubmenu[$itemsToSort['ppma-author-list']];

                    unset($currentSubmenu[$itemsToSort['ppma-author-list']]);
                }

                // Author Pages
                if (isset($itemsToSort['ppma-author-pages'])) {
                    $newSubmenu[] = $currentSubmenu[$itemsToSort['ppma-author-pages']];

                    unset($currentSubmenu[$itemsToSort['ppma-author-pages']]);
                }

                // Check if we have other menu items, except settings. They will be added to the end.
                if (count($currentSubmenu) >= 1) {
                    $itemsToIgnore = [
                        'ppma-authors',
                        'ppma-modules-settings',
                    ];

                    // Add the additional items
                    foreach ($currentSubmenu as $index => $item) {
                        if (in_array($item[2], $itemsToIgnore)) {
                            continue;
                        }

                        if (!array_key_exists($item[2], $itemsToSort)) {
                            $newSubmenu[] = $item;
                            unset($currentSubmenu[$index]);
                        }
                    }
                }

                // Settings
                if (isset($itemsToSort['ppma-modules-settings'])) {
                    $newSubmenu[] = $currentSubmenu[$itemsToSort['ppma-modules-settings']];

                    unset($currentSubmenu[$itemsToSort['ppma-modules-settings']]);
                }

                // Upgrade to Pro
                if (!defined('PUBLISHPRESS_AUTHORS_SKIP_VERSION_NOTICES')) {
                    $suffix = \PPVersionNotices\Module\MenuLink\Module::MENU_SLUG_SUFFIX;

                    foreach ($upgradeMenuSlugs as $index => $item) {
                        if (!is_null($itemsToSort[$index])) {
                            $newSubmenu[] = $currentSubmenu[$itemsToSort[$index]];
                        }
                    }
                }

                $submenu[self::MENU_SLUG] = $newSubmenu; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
            }

            return $menu_ord;
        }

        /**
         * Print the content of the configure tab.
         */
        public function print_configure_view()
        {
            $container = Factory::get_container();
            $view      = $container['view'];

            echo $view->render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                'settings-tab',
                [
                    'form_action'        => esc_url(menu_page_url($this->module->settings_slug, false)),
                    'options_group_name' => esc_html($this->module->options_group_name),
                    'module_name'        => esc_html($this->module->slug),
                ]
            );
        }

        /**
         * Check if Blocks Free or Pro are active
         */
        public function pp_blocks_is_active()
        {
            if (defined('PP_AUTHORS_BLOCKS_INSTALLED')) {
                return;
            }

            if (
                !MultipleAuthors\Classes\Utils::isPluginActive('advanced-gutenberg.php')
                && !MultipleAuthors\Classes\Utils::isPluginActive('advanced-gutenberg-pro.php')) {
                define('PP_AUTHORS_BLOCKS_INSTALLED', false);
            } else {
                define('PP_AUTHORS_BLOCKS_INSTALLED', true);
            }
        }

        /**
         * Register settings for notifications so we can partially use the Settings API
         * (We use the Settings API for form generation, but not saving)
         */
        public function register_settings()
        {
            /**
             * General
             */

            add_settings_section(
                $this->module->options_group_name . '_general',
                __return_false(),
                [$this, 'settings_section_general'],
                $this->module->options_group_name
            );

            do_action(
                'publishpress_authors_register_settings_before',
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            add_settings_field(
                'post_types',
                __('Enable PublishPress Authors for these post types:', 'publishpress-authors'),
                [$this, 'settings_post_types_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            add_settings_field(
                'author_page_post_types',
                __('Post types to display on the author\'s profile page:', 'publishpress-authors'),
                [$this, 'settings_author_page_post_types_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            add_settings_field(
                'author_for_new_users',
                __(
                    'Automatically create author profiles:',
                    'publishpress-authors'
                ),
                [$this, 'settings_author_for_new_users_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            add_settings_field(
                'username_in_search_field',
                __(
                    'Show username in the search field:',
                    'publishpress-authors'
                ),
                [$this, 'settings_username_in_search_field'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_advanced'
            );

            add_settings_field(
                'default_author_for_new_posts',
                __(
                    'Default author for new posts:',
                    'publishpress-authors'
                ),
                [$this, 'settings_default_author_for_new_posts'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_advanced'
            );

            add_settings_field(
                'fallback_user_for_guest_post',
                __(
                    'Fallback user for Guest Authors:',
                    'publishpress-authors'
                ),
                [$this, 'settings_fallback_user_for_guest_post'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_advanced'
            );

            add_settings_field(
                'remove_single_user_map_restriction',
                __(
                    'Remove single author map restriction:',
                    'publishpress-authors'
                ),
                [$this, 'settings_remove_single_user_map_restriction'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_advanced'
            );

            do_action('publishpress_authors_register_settings_after');

            /**
             * Display
             */

            add_settings_section(
                $this->module->options_group_name . '_display',
                __return_false(),
                [$this, 'settings_section_display'],
                $this->module->options_group_name
            );

            add_settings_field(
                'preppend_to_content',
                __('Show above the content:', 'publishpress-authors'),
                [$this, 'settings_preppend_to_content_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_display'
            );

            add_settings_field(
                'append_to_content',
                __('Show below the content:', 'publishpress-authors'),
                [$this, 'settings_append_to_content_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_display'
            );

            add_settings_field(
                'disable_quick_edit_author_box',
                __('Disable the "Authors" box when using "Quick Edit":', 'publishpress-authors'),
                [$this, 'settings_disable_quick_edit_author_box_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_advanced'
            );

            add_settings_field(
                'layout',
                __('Default Layout:', 'publishpress-authors'),
                [$this, 'settings_layout_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_display'
            );

            add_settings_field(
                'title_appended_to_content',
                __('Title for the author box:', 'publishpress-authors'),
                [$this, 'settings_title_appended_to_content_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_display'
            );

            add_settings_field(
                'color_scheme',
                __('Color scheme:', 'publishpress-authors'),
                [$this, 'settings_color_scheme_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_display',
                ['class' => 'ppauthors-color-scheme-field']
            );

            add_settings_field(
                'show_email_link',
                __('Show email link:', 'publishpress-authors'),
                [$this, 'settings_show_email_link_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_display'
            );

            add_settings_field(
                'show_site_link',
                __('Show site link:', 'publishpress-authors'),
                [$this, 'settings_show_site_link_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_display'
            );

            add_settings_field(
                'enable_font_awesome',
                __('Font Awesome icons:', 'publishpress-authors'),
                [$this, 'settings_enable_font_awesome_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_advanced'
            );

            /**
             * Author Pages
             */

            add_settings_section(
                $this->module->options_group_name . '_author_pages',
                __return_false(),
                [$this, 'settings_section_author_pages'],
                $this->module->options_group_name
            );

            add_settings_field(
                'enable_plugin_author_pages',
                __(
                    'Enable author pages:',
                    'publishpress-authors'
                ),
                [$this, 'settings_enable_plugin_author_pages'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'author_pages_posts_limit',
                __(
                    'Author pages posts limit:',
                    'publishpress-authors'
                ),
                [$this, 'settings_author_pages_posts_limit'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'author_pages_layout',
                __(
                    'Author pages layout:',
                    'publishpress-authors'
                ),
                [$this, 'settings_author_pages_layout'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'author_pages_grid_layout_column',
                __(
                    'Grid layout column:',
                    'publishpress-authors'
                ),
                [$this, 'settings_author_pages_grid_layout_column'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'show_author_pages_bio',
                __(
                    'Show author bio:',
                    'publishpress-authors'
                ),
                [$this, 'settings_show_author_pages_bio'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'author_pages_bio_layout',
                __('Author bio layout:', 'publishpress-authors'),
                [$this, 'settings_author_pages_bio_layout'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'show_author_page_title',
                __(
                    'Show author page title:',
                    'publishpress-authors'
                ),
                [$this, 'settings_show_author_page_title'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'author_post_excerpt_ellipsis',
                __(
                    'Author pages excerpt ellipsis:',
                    'publishpress-authors'
                ),
                [$this, 'settings_author_post_excerpt_ellipsis'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'author_pages_title_header',
                __('Author pages title header:', 'publishpress-authors'),
                [$this, 'settings_author_pages_title_header'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'author_post_title_header',
                __('Author post title header:', 'publishpress-authors'),
                [$this, 'settings_author_post_title_header'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'show_author_post_featured_image',
                __(
                    'Show featured image:',
                    'publishpress-authors'
                ),
                [$this, 'settings_show_author_post_featured_image'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'author_post_custom_width',
                __(
                    'Featured image custom width:',
                    'publishpress-authors'
                ),
                [$this, 'settings_author_post_custom_width'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'author_post_custom_height',
                __(
                    'Featured image custom height:',
                    'publishpress-authors'
                ),
                [$this, 'settings_author_post_custom_height'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'show_author_post_excerpt',
                __(
                    'Show excerpt:',
                    'publishpress-authors'
                ),
                [$this, 'settings_show_author_post_excerpt'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'show_author_post_authors',
                __(
                    'Show authors:',
                    'publishpress-authors'
                ),
                [$this, 'settings_show_author_post_authors'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'show_author_post_date',
                __(
                    'Show post date:',
                    'publishpress-authors'
                ),
                [$this, 'settings_show_author_post_date'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'show_author_post_comments',
                __(
                    'Show comment counts:',
                    'publishpress-authors'
                ),
                [$this, 'settings_show_author_post_comments'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'show_author_post_category',
                __(
                    'Show category:',
                    'publishpress-authors'
                ),
                [$this, 'settings_show_author_post_category'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'show_author_post_tags',
                __(
                    'Show tags:',
                    'publishpress-authors'
                ),
                [$this, 'settings_show_author_post_tags'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );

            add_settings_field(
                'show_author_post_readmore',
                __(
                    'Show read more link:',
                    'publishpress-authors'
                ),
                [$this, 'settings_show_author_post_readmore'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_author_pages'
            );


            /**
             * Shortcodes
             */

            add_settings_section(
                $this->module->options_group_name . '_shortcodes',
                __return_false(),
                [$this, 'settings_section_shortcodes'],
                $this->module->options_group_name
            );

            foreach ($this->settings_ppma_shortcodes() as $shortcode => $args) {
                $args['shortcode'] = $shortcode;
                add_settings_field(
                    $shortcode . 'settings_shortcodes',
                    $args['label'],
                    [$this, 'settings_shortcodes_callback'],
                    $this->module->options_group_name,
                    $this->module->options_group_name . '_shortcodes',
                    $args
                );
            }

            /**
             * Maintenance
             */

            add_settings_section(
                $this->module->options_group_name . '_maintenance',
                __return_false(),
                [$this, 'settings_section_maintenance'],
                $this->module->options_group_name
            );

            add_settings_field(
                'maintenance',
                __return_empty_string(),
                [$this, 'settings_maintenance_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_maintenance'
            );

            /**
             * Advanced
             */

            add_settings_section(
                $this->module->options_group_name . '_advanced',
                __return_false(),
                [$this, 'settings_section_advanced'],
                $this->module->options_group_name
            );

            /**
             * Guest Authors
             */

            add_settings_section(
                $this->module->options_group_name . '_guest_authors',
                __return_false(),
                [$this, 'settings_section_guest_authors'],
                $this->module->options_group_name
            );

            add_settings_field(
                'enable_guest_author_user',
                __(
                    'Enable Guest Author With No User Account',
                    'publishpress-authors'
                ),
                [$this, 'settings_enable_guest_author_user'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_advanced'
            );

            add_settings_field(
                'enable_guest_author_acount',
                __(
                    'Enable Guest Author With User Account',
                    'publishpress-authors'
                ),
                [$this, 'settings_enable_guest_author_acount'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_guest_authors'
            );

            add_settings_field(
                'author_boxes_opt_out',
                __(
                    'Allow users to opt out of Author Boxes',
                    'publishpress-authors'
                ),
                [$this, 'settings_author_boxes_opt_out'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_guest_authors'
            );

            add_settings_field(
                'display_name_format',
                __(
                    'Display Name',
                    'publishpress-authors'
                ),
                [$this, 'settings_display_name_format'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_guest_authors'
            );

            add_settings_field(
                'default_avatar',
                __(
                    'Default Avatar',
                    'publishpress-authors'
                ),
                [$this, 'settings_default_avatar'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_guest_authors'
            );

            add_settings_field(
                'author_legacy_layout_boxed',
                __('Boxed legacy layout Author Box:', 'publishpress-authors'),
                [$this, 'settings_author_legacy_layout_boxed'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_advanced'
            );

            add_settings_field(
                'author_legacy_layout_centered',
                __('Centered legacy layout Author Box:', 'publishpress-authors'),
                [$this, 'settings_author_legacy_layout_centered'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_advanced'
            );

            add_settings_field(
                'author_legacy_layout_inline',
                __('Inline legacy layout Author Box:', 'publishpress-authors'),
                [$this, 'settings_author_legacy_layout_inline'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_advanced'
            );

            add_settings_field(
                'author_legacy_layout_inline_avatar',
                __('Inline avatar legacy layout Author Box:', 'publishpress-authors'),
                [$this, 'settings_author_legacy_layout_inline_avatar'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_advanced'
            );

            add_settings_field(
                'author_legacy_layout_simple_list',
                __('Simple list legacy layout Author Box:', 'publishpress-authors'),
                [$this, 'settings_author_legacy_layout_simple_list'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_advanced'
            );

            do_action('pp_authors_register_settings');
        }

        public function settings_section_general()
        {
            echo '<input type="hidden" id="ppma-tab-general" />';
        }

        public function settings_section_display()
        {
            echo '<input type="hidden" id="ppma-tab-display" />';
        }

        public function settings_section_author_pages()
        {
            echo '<input type="hidden" id="ppma-tab-author-pages" />';
        }

        public function settings_section_advanced()
        {
            echo '<input type="hidden" id="ppma-tab-advanced" />';
        }

        public function settings_section_shortcodes()
        {
            echo '<input type="hidden" id="ppma-tab-shortcodes" />';
        }

        public function settings_section_maintenance()
        {
            echo '<input type="hidden" id="ppma-tab-maintenance" />';
        }

        public function settings_section_guest_authors()
        {
            echo '<input type="hidden" id="ppma-tab-guest-author" />';
        }

        /**
         * Displays the field to allow select the post types for authors.
         */
        public function settings_post_types_option()
        {
            $legacyPlugin = Factory::getLegacyPlugin();

            $legacyPlugin->settings->helper_option_custom_post_type($this->module);
        }

        public function settings_author_page_post_types_option()
        {
            $post_types = [
                'post' => __('Posts'),
                'page' => __('Pages'),
            ];
            $custom_post_types = $this->get_supported_post_types_for_module();
            if (count($custom_post_types)) {
                foreach ($custom_post_types as $custom_post_type => $args) {
                    $post_types[$custom_post_type] = $args->label;
                }
            }

            $checkedOption = is_array($this->module->options->author_page_post_types) ?
                array_filter(
                    $this->module->options->author_page_post_types,
                    function ($value, $key) {
                        return $value === 'on';
                    },
                    ARRAY_FILTER_USE_BOTH
                )
                : false;

            $checkPostByDefault = empty($checkedOption);

            foreach ($post_types as $post_type => $title) {
                echo '<label for="author_page_post_type_' . esc_attr($post_type) . '-' . esc_attr($this->module->slug) . '">';
                echo '<input id="author_page_post_type_' . esc_attr($post_type) . '-' . esc_attr($this->module->slug) . '" name="'
                    . esc_attr($this->module->options_group_name) . '[author_page_post_types][' . esc_attr($post_type) . ']"';

                    if (isset($this->module->options->author_page_post_types[$post_type])) {
                        checked($this->module->options->author_page_post_types[$post_type], 'on');
                    } elseif ($checkPostByDefault && $post_type === 'post') {
                        checked('on', 'on');
                    }

                // Defining post_type_supports in the functions.php file or similar should disable the checkbox
                disabled(post_type_supports($post_type, $this->module->post_type_support), true);
                echo ' type="checkbox" value="on" />&nbsp;&nbsp;&nbsp;' . esc_html($title) . '</label>';
                // Leave a note to the admin as a reminder that add_post_type_support has been used somewhere in their code
                if (post_type_supports($post_type, $this->module->post_type_support)) {
                    echo '&nbsp&nbsp;&nbsp;<span class="description">' . sprintf(esc_html__('Disabled because add_post_type_support(\'%1$s\', \'%2$s\') is included in a loaded file.', 'publishpress-authors'), esc_html($post_type), esc_html($this->module->post_type_support)) . '</span>';
                }
                echo '<br />';
            }
        }

        /**
         * Displays the field to choose display or not the author box above the content
         *
         * @param array
         */
        public function settings_preppend_to_content_option($args = [])
        {
            $id    = $this->module->options_group_name . '_preppend_to_content';
            $value = isset($this->module->options->preppend_to_content) ? $this->module->options->preppend_to_content : 'yes';

            echo '<label for="' . esc_attr($id) . '">';
            echo '<input type="checkbox" value="yes" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[preppend_to_content]" '
                . checked($value, 'yes', false) . ' />';
            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">' . esc_html__(
                    'This will display the authors box at the top of the content.',
                    'publishpress-authors'
                ) . '</span>';
            echo '</label>';
        }

        /**
         * Displays the field to choose display or not the author box at the
         * end of the content
         *
         * @param array
         */
        public function settings_append_to_content_option($args = [])
        {
            $id    = $this->module->options_group_name . '_append_to_content';
            $value = isset($this->module->options->append_to_content) ? $this->module->options->append_to_content : 'yes';

            echo '<label for="' . esc_attr($id) . '">';
            echo '<input type="checkbox" value="yes" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[append_to_content]" '
                . checked($value, 'yes', false) . ' />';
            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">' . esc_html__(
                    'This will display the authors box at the end of the content.',
                    'publishpress-authors'
                ) . '</span>';
            echo '</label>';
        }

        /**
         * Displays the field to choose the title for the author box at the
         * end of the content
         *
         * @param array
         */
        public function settings_title_appended_to_content_option($args = [])
        {
            $idSingle    = $this->module->options_group_name . '_title_appended_to_content';
            $singleValue = isset($this->module->options->title_appended_to_content) ? $this->module->options->title_appended_to_content : esc_html__('Author');

            $idPlural    = $this->module->options_group_name . '_title_appended_to_content_plural';
            $pluralValue = isset($this->module->options->title_appended_to_content_plural) ? $this->module->options->title_appended_to_content_plural : esc_html__(
                'Authors',
                'publishpress-authors'
            );

            echo '<div class="ppma-settings-left-column">';
            echo '<label for="' . esc_attr($idSingle) . '">' . esc_html__('Single', 'publishpress-authors') . '</label>';
            echo '<input type="text" value="' . esc_attr(
                    $singleValue
                ) . '" id="' . esc_attr($idSingle) . '" name="' . esc_attr($this->module->options_group_name) . '[title_appended_to_content]" class="regular-text" />';
            echo '</div>';

            echo '<div class="ppma-settings-left-column">';
            echo '<label for="' . esc_attr($idPlural) . '">' . esc_html__('Plural', 'publishpress-authors') . '</label>';
            echo '<input type="text" value="' . esc_attr(
                    $pluralValue
                ) . '" id="' . esc_attr($idPlural) . '" name="' . esc_attr($this->module->options_group_name) . '[title_appended_to_content_plural]" class="regular-text" />';
            echo '</div>';
        }

        /**
         * PublishPress Authors Shortcodes
         *
         * @param array $shortcodes
         *
         * @return array
         */
        private function settings_ppma_shortcodes($shortcodes = []) {

            $default_layout = Utils::getDefaultLayout();

            //add author box shortcode
            $shortcodes['publishpress_authors_box'] = [
                'label'         => esc_html__('Authors Box', 'publishpress-authors'),
                'description'   => esc_html__('With this shortcode you can display the author box in any part of the content. ', 'publishpress-authors'),
                'options'       => [
                    'option_1' => [
                        'shortcode' => '[publishpress_authors_box]'
                    ],
                    'option_2' => [
                        'shortcode'   => '[publishpress_authors_box layout="'. $default_layout .'"]',
                        'description' => sprintf(
                            esc_html__(
                                'You can specify layout by using author boxes layout slug. You can see full details of each layout option %1$s in this guide %2$s.',
                                'publishpress-authors'
                            ),
                            '<a href="https://publishpress.com/knowledge-base/layout/">',
                            '</a>'
                        ),
                    ],
                    'option_3' => [
                        'shortcode'   => '[publishpress_authors_box layout="'. $default_layout .'" show_title="true"]',
                        'description' => sprintf(
                            esc_html__(
                                'You can also decide whether or not to show the main title, using %1$s or %2$s .',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">show_title="true"</code>',
                            '<code class="color-red">show_title="false"</code>'
                        ),
                    ],
                    'option_4' => [
                        'shortcode'   => '[publishpress_authors_box layout="'. $default_layout .'" post_id="32"]',
                        'description' => esc_html__(
                            'You can load the authors for a specific post, even if you are not in that post currently. For example, this shortcode will load the authors for the post with the ID of 32.',
                            'publishpress-authors'
                        ),
                    ],
                    'option_5' => [
                        'shortcode'   => '[publishpress_authors_box layout="'. $default_layout .'" user_id="32"]',
                        'description' => esc_html__(
                            'You can load the authors for a specific user by providing the user id. For example, this shortcode will load the author with user id of 32',
                            'publishpress-authors'
                        ),
                    ],
                    'option_6' => [
                        'shortcode'   => '[publishpress_authors_box layout="'. $default_layout .'" term_id="32"]',
                        'description' => esc_html__(
                            'You can load the authors for a specific author by providing the author term id for both guest and user author. For example, this shortcode will load the author with term id of 32',
                            'publishpress-authors'
                        ),
                    ],
                    'option_7' => [
                        'shortcode'   => '[publishpress_authors_box layout="'. $default_layout .'" archive="1"]',
                        'description' => sprintf(
                            esc_html__(
                                'If you are having problems showing PublishPress Authors on author profile pages, you can use this shortcode below. The argument %s forces the plugin to retrieve the Author from the profile page and not any other posts on the same screen.',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">archive="1"</code>'
                        ),
                    ],
                    'option_8' => [
                        'shortcode'   => '[publishpress_authors_box layout="'. $default_layout .'" archive="true"]',
                        'description' => sprintf(
                            esc_html__(
                                'There is one final option to mention. This is mostly useful if you\'re using a theme or page builder to customize the Author profile pages you find at URLs such as /author/username/. You can use the following shortcode on the authors page to display the profile of the current author. You just need to add the parameter %s.',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">archive="true"</code>'
                        ),
                    ],
                    'option_9' => [
                    'shortcode'   => '[publishpress_authors_box author_categories="reviewer,coauthor"]',
                    'description' => esc_html__(
                        'You can load the authors belonging to a specific author categories by providing the category slug or slugs separated by comma for more than one category.',
                        'publishpress-authors'
                    ),
                    ],
                ],
            ];

            //add authors data shortcode
            $shortcodes['publishpress_authors_data'] = [
                'label'         => esc_html__('Authors Data', 'publishpress-authors'),
                'description'   => esc_html__('With this shortcode you can display the author names or any profile field in any part of the content.', 'publishpress-authors'),
                'options'       => [
                    'option_1' => [
                        'shortcode' => '[publishpress_authors_data]'
                    ],
                    'option_2' => [
                        'shortcode'   => '[publishpress_authors_data field="display_name"]',
                        'description' => sprintf(
                            esc_html__(
                                'The authors data shortcode accepts field parameter such as: %1$s %2$s %3$s %4$s %5$s %6$s %7$s. You can see full details and parameters %8$s in this guide %9$s.',
                                'publishpress-authors'
                            ),
                            '<code>display_name</code>',
                            '<code>first_name</code>',
                            '<code>last_name</code>',
                            '<code>ID</code>',
                            '<code>user_nicename</code>',
                            '<code>user_email</code>',
                            '<code>avatar</code>',
                            '<a href="https://publishpress.com/knowledge-base/authors-data-backup/">',
                            '</a>'
                        ),
                    ],
                    'option_3' => [
                        'shortcode'   => '[publishpress_authors_data separator=","]',
                        'description' => esc_html__(
                            'You can also specify the separator to be used for mulitple authors data.',
                            'publishpress-authors'
                        ),
                    ],
                    'option_4' => [
                        'shortcode'   => '[publishpress_authors_data post_id="32"]',
                        'description' => esc_html__(
                            'You can load the authors for a specific post, even if you are not in that post currently. For example, this shortcode will load the authors for the post with the ID of 32.',
                            'publishpress-authors'
                        ),
                    ],
                    'option_5' => [
                        'shortcode'   => '[publishpress_authors_data term_id="102"]',
                        'description' => esc_html__(
                            'You can retrieve a specific author by providing the author\'s term ID. For example, this shortcode will load the author with the term ID of 102.',
                            'publishpress-authors'
                        ),
                    ],
                    'option_6' => [
                        'shortcode'   => '[publishpress_authors_data user_objects="true"]',
                        'description' => sprintf(
                            esc_html__(
                                'You can also decide to return an array list of authors by using user_objects="true" which will return all the authors object data as an array. You can check full details and sample usage %2$s in this guide%3$s.',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">user_objects="true"</code>',
                            '<a href="https://publishpress.com/knowledge-base/authors-data-backup/">',
                            '</a>'
                        ),
                    ],
                    'option_7' => [
                        'shortcode'   => '[publishpress_authors_data author_categories="reviewer,coauthor"]',
                        'description' => esc_html__(
                            'You can retrieve authors belonging to a specific Author Category by providing the category slug or slugs. Separate multiple slugs with a comma.',
                            'publishpress-authors'
                        ),
                    ],
                ],
            ];

            //add authors list shortcode
            $shortcodes['publishpress_authors_list'] = [
                'label'         => esc_html__('Authors List', 'publishpress-authors'),
                'description'   => esc_html__('With this shortcode, you can show all the authors together in a single display.', 'publishpress-authors'),
                'options'       => [
                    'option_1' => [
                        'shortcode' => '[publishpress_authors_list]'
                    ],
                    'option_2' => [
                        'shortcode'   => '[publishpress_authors_list layout="'. $default_layout .'"]',
                        'description' => sprintf(
                            esc_html__(
                                'You can specify layout by using author boxes layout slug. You can see full details of each layout option %1$s in this guide %2$s. %3$s %4$s This shortcode also provides two custom layouts: %5$s %6$s.',
                                'publishpress-authors'
                            ),
                            '<a href="https://publishpress.com/knowledge-base/layout/">',
                            '</a>',
                            '<br />',
                            '<br />',
                            '<code>authors_index</code>',
                            '<code>authors_recent</code>'
                        ),
                    ],
                    'option_3' => [
                        'shortcode'   => '[publishpress_authors_list layout="'. $default_layout .'" layout_columns="2"]',
                        'description' => sprintf(
                            esc_html__(
                                'You can define the number of layout columns by using %1$s to show authors in 2 column.',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">layout_columns="2"</code>'
                        ),
                    ],
                    'option_4' => [
                        'shortcode'   => '[publishpress_authors_list layout="authors_recent" limit_per_page="12"]',
                        'description' => sprintf(
                            esc_html__(
                                'You can choose the number of authors per page using %1$s . %2$s Pagination will be automatically added if required.',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">limit_per_page="12"</code>',
                            '<br />'
                        ),
                    ],
                    'option_5' => [
                        'shortcode'   => '[publishpress_authors_list layout="authors_recent" show_empty="1"]',
                        'description' => sprintf(
                            esc_html__(
                                'You can limit the result to only authors who are assigned to posts by using %1$s . %2$s Alternatively, use %3$s to show all authors, including those without any posts.',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">show_empty="0"</code>',
                            '<br />',
                            '<code class="color-red">show_empty="1"</code>'
                        ),
                    ],
                    'option_6' => [
                        'shortcode'   => '[publishpress_authors_list layout="authors_recent" authors="guests"]',
                        'description' => sprintf(
                            esc_html__(
                                'You can limit the result to only guest authors by using %1$s . %2$s Alternatively, %3$s will show only authors with a WordPress account.',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">authors="guests"</code>',
                            '<br />',
                            '<code class="color-red">authors="users"</code>'
                        ),
                    ],
                    'option_7' => [
                        'shortcode'   => '[publishpress_authors_list layout="authors_recent" authors_recent_col="4"]',
                        'description' => sprintf(
                            esc_html__(
                                'If you are using the authors_recent layout, you can define the number of columns by using %1$s .',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">authors_recent_col="4"</code>'
                        ),
                    ],
                    'option_8' => [
                        'shortcode'   => '[publishpress_authors_list layout="authors_index" group_by="last_name"]',
                        'description' => sprintf(
                            esc_html__(
                                'For authors_index layout, you can group user by profile field by using %1$s .',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">group_by="last_name"</code>'
                        ),
                    ],
                    'option_9' => [
                        'shortcode'   => '[publishpress_authors_list orderby="count"]',
                        'description' => sprintf(
                            esc_html__(
                                'To order the results based on post count, use %1$s . To order the results by name, use %2$s . Alternatively, you can order by profile fields like %3$s, %4$s etc',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">orderby="count"</code>',
                            '<code class="color-red">orderby="name"</code>',
                            '<code class="color-red">first_name</code>',
                            '<code class="color-red">last_name</code>'
                        ),
                    ],
                    'option_10' => [
                        'shortcode'   => '[publishpress_authors_list orderby="name" order="asc"]',
                        'description' => sprintf(
                            esc_html__(
                                'To further customize the order of results, use %1$s or %2$s .',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">order="asc"</code>',
                            '<code class="color-red">order="desc"</code>'
                        ),
                    ],
                    'option_11' => [
                        'shortcode'   => '[publishpress_authors_list layout="authors_recent" search_box="true"]',
                        'description' => sprintf(
                            esc_html__(
                                'To display a search box for authors, use %1$s .',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">search_box="true"</code>'
                        ),
                    ],
                    'option_12' => [
                        'shortcode'   => '[publishpress_authors_list search_box="true" search_field="first_name,last_name" layout="authors_recent"]',
                        'description' => sprintf(
                            esc_html__(
                                'You can also show a dropdown menu that allows users to search on specific author fields. You can add fields to the dropdown using %1$s . This requires the search box to be active.',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">search_field="first_name,last_name"</code>'
                        ),
                    ],
                    'option_13' => [
                        'shortcode'   => '[publishpress_authors_list last_article_date="1 year ago"]',
                        'description' => sprintf(
                            esc_html__(
                                'You can limit the author list to users with a published post within a specific period using %1$s . This accept english date value like 1 week ago, 1 month ago, 6 months ago, 1 year ago etc.',
                                'publishpress-authors'
                            ),
                            '<code class="color-red">last_article_date="1 year ago"</code>'
                        ),
                    ],
                ],
            ];

            /**
             * Filter shortcodes.
             *
             * @param array $shortcodes
             */
            $shortcodes = apply_filters('ppma_shortcodes', $shortcodes);

            return $shortcodes;
        }

        /**
         * Displays shortcode tab content
         *
         * @param array
         */
        public function settings_shortcodes_callback($args = [])
        {
            ?>
            <?php if (isset($args['description'])) : ?>
                <span class="ppma_settings_field_description"><?php echo $args['description']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </span>
            <?php endif; ?>

            <?php foreach ($args['options'] as $option) : ?>
                    <div class="ppma-settings-shortcodes-shortcode">
                    <?php if (isset($option['description'])) : ?>
                        <div class="ppma_settings_field_description">
                            <?php echo $option['description']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php endif; ?>
                    <input
                        class="shortcode-field"
                        type="text"
                        value="<?php echo esc_attr($option['shortcode']); ?>"
                        readonly
                        />
                    <span class="ppma-copy-clipboard dashicons dashicons-admin-page">
                        <span data-copied="<?php echo esc_attr__('Copied!', 'publishpress-authors'); ?>"
                            data-copy="<?php echo esc_attr__('Click To Copy!', 'publishpress-authors'); ?>">
                            <?php echo esc_html__('Click To Copy!', 'publishpress-authors'); ?>
                        </span>
                    </span>
                    </div>
            <?php endforeach; ?>

            <?php
        }

        /**
         * @param array $args
         */
        public function settings_layout_option($args = [])
        {
            $id    = $this->module->options_group_name . '_layout';
            $value = isset($this->module->options->layout) ? $this->module->options->layout : Utils::getDefaultLayout();

            echo '<label for="' . esc_attr($id) . '">';

            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[layout]">';

            $layouts = apply_filters('pp_multiple_authors_author_layouts', []);
            unset($layouts['authors_index']);
            unset($layouts['authors_recent']);

            foreach ($layouts as $layout => $text) {
                $selected = $value === $layout ? 'selected="selected"' : '';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<option value="' . esc_attr($layout) . '" ' . $selected . '>' . esc_html($text) . '</option>';
            }

            echo '</select>';
            echo '</label>';
        }

        /**
         * @param array $args
         */
        public function settings_color_scheme_option($args = [])
        {
            $id    = $this->module->options_group_name . '_color_scheme';
            $value = isset($this->module->options->color_scheme) ? $this->module->options->color_scheme : '#655997';

            echo '<label for="' . esc_attr($id) . '">';

                echo '<input type="text" class="color-picker" data-default-color="#655997" name="' . esc_attr($this->module->options_group_name) . '[color_scheme]" value="' . esc_attr(
                $value
                ) . '"/>';

            echo '</label>';
        }

        /**
         * @param array $args
         */
        public function settings_author_for_new_users_option($args = [])
        {
            $id     = $this->module->options_group_name . '_author_for_new_users';
            $values = isset($this->module->options->author_for_new_users) ? $this->module->options->author_for_new_users : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_for_new_users][]" multiple="multiple" class="chosen-select" data-placeholder="'.  esc_attr__('Select some options', 'publishpress-authors') .'">';

            $roles = get_editable_roles();

            foreach ($roles as $role => $data) {
                $selected = in_array($role, $values) ? 'selected="selected"' : '';
                echo '<option value="' . esc_attr($role) . '" ' . $selected . '>' . esc_html($data['name']) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

            echo '</select>';

            echo '<p class="ppma_settings_field_description">' . esc_html__(
                    'Author profiles can be mapped to WordPress user accounts. This option allows you to automatically create author profiles when users are created in these roles. You can also do this for existing users by clicking the "Create missed authors from role" button in the Maintenance tab.',
                    'publishpress-authors'
                ) . '</p>';

            echo '</label>';
        }

        /**
         * @param array $args
         */
        public function settings_username_in_search_field($args = [])
        {
            $id    = $this->module->options_group_name . '_username_in_search_field';
            $value = isset($this->module->options->username_in_search_field) ? $this->module->options->username_in_search_field : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[username_in_search_field]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'If the Author is mapped to a WordPress user, this will display the authors\' "Display name" and their "Username". The default is to show only the "Display name". Showing the "Username" is useful if you have several authors with similar names.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }

        /**
         * @param array $args
         */
        public function settings_enable_guest_author_user($args = [])
        {
            $id    = $this->module->options_group_name . '_enable_guest_author_user';
            $value = isset($this->module->options->enable_guest_author_user) ? $this->module->options->enable_guest_author_user : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[enable_guest_author_user]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'Allow authors to be created without a real user account.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }

        /**
         * @param array $args
         */
        public function settings_author_boxes_opt_out($args = [])
        {
            $id    = $this->module->options_group_name . '_author_boxes_opt_out';
            $value = isset($this->module->options->author_boxes_opt_out) ? $this->module->options->author_boxes_opt_out : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_boxes_opt_out]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'Show a profile checkbox allowing Authors to be excluded from Author Boxes.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }

        /**
         * @param array $args
         */
        public function settings_enable_guest_author_acount($args = [])
        {
            $id    = $this->module->options_group_name . '_enable_guest_author_acount';
            $value = isset($this->module->options->enable_guest_author_acount) ? $this->module->options->enable_guest_author_acount : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[enable_guest_author_acount]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'Allow authors to be created without a mapped user.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }


        /**
         * @param array $args
         */
        public function settings_enable_plugin_author_pages($args = [])
        {
            $id    = $this->module->options_group_name . '_enable_plugin_author_pages';
            $value = isset($this->module->options->enable_plugin_author_pages) ? $this->module->options->enable_plugin_author_pages : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[enable_plugin_author_pages]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'If enabled, PublishPress Authors will replace the default WordPress author pages.',
                    'publishpress-authors'
                )
                . ' '
                . esc_html__('Please note this feature will not work for all themes.', 'publishpress-authors') . ' <a target="_blank" href="https://publishpress.com/knowledge-base/author-pages-troubleshooting/">'.  esc_html__('Click here for more details.', 'publishpress-authors') .'</a>'
                . '</span>';


            echo '</label>';
            if ($value === 'yes') {
                $current_author = Author::get_by_user_id(get_current_user_id());
                if (
                    $current_author &&
                    is_object($current_author) &&
                    isset($current_author->link)
                ) {
                    $author_url = $current_author->link;
                    echo '<div style="margin-top: 15px;"><a class="button" target="_blank" href="'. $author_url .'">'. esc_html__('View sample Author Page', 'publishpress-authors') .'</a></div>';
                }
            }
        }


        /**
         * @param array $args
         */
        public function settings_author_pages_layout($args = [])
        {
            $id    = $this->module->options_group_name . '_author_pages_layout';
            $value = isset($this->module->options->author_pages_layout) ? $this->module->options->author_pages_layout : 'list';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_pages_layout]">';

            $author_pages_layouts = apply_filters(
                'pp_multiple_authors_pages_layout',
                [
                    'list' => esc_html__('List', 'publishpress-authors'),
                    'grid' => esc_html__('Grid', 'publishpress-authors')
                ]
            );

            foreach ($author_pages_layouts as $layout => $text) {
                $selected = $value === $layout ? 'selected="selected"' : '';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<option value="' . esc_attr($layout) . '" ' . $selected . '>' . esc_html($text) . ' &emsp; &emsp; &emsp; &emsp; </option>';
            }

            echo '</select>';
            echo '</label>';
        }


        /**
         * @param array $args
         */
        public function settings_author_pages_title_header($args = [])
        {
            $id    = $this->module->options_group_name . '_author_pages_title_header';
            $value = isset($this->module->options->author_pages_title_header) ? $this->module->options->author_pages_title_header : 'list';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_pages_title_header]">';

            $author_pages_title_headers = [
                'h1' => esc_html__('H1', 'publishpress-authors'),
                'h2' => esc_html__('H2', 'publishpress-authors'),
                'h3' => esc_html__('H3', 'publishpress-authors'),
                'h4' => esc_html__('H4', 'publishpress-authors'),
                'h5' => esc_html__('H5', 'publishpress-authors'),
                'h6' => esc_html__('H6', 'publishpress-authors')
            ];

            foreach ($author_pages_title_headers as $layout => $text) {
                $selected = $value === $layout ? 'selected="selected"' : '';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<option value="' . esc_attr($layout) . '" ' . $selected . '>' . esc_html($text) . ' &emsp; &emsp; &emsp; &emsp; </option>';
            }

            echo '</select>';
            echo '</label>';
        }


        /**
         * @param array $args
         */
        public function settings_author_post_title_header($args = [])
        {
            $id    = $this->module->options_group_name . '_author_post_title_header';
            $value = isset($this->module->options->author_post_title_header) ? $this->module->options->author_post_title_header : 'list';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_post_title_header]">';

            $author_post_title_headers = [
                'h1' => esc_html__('H1', 'publishpress-authors'),
                'h2' => esc_html__('H2', 'publishpress-authors'),
                'h3' => esc_html__('H3', 'publishpress-authors'),
                'h4' => esc_html__('H4', 'publishpress-authors'),
                'h5' => esc_html__('H5', 'publishpress-authors'),
                'h6' => esc_html__('H6', 'publishpress-authors')
            ];

            foreach ($author_post_title_headers as $layout => $text) {
                $selected = $value === $layout ? 'selected="selected"' : '';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<option value="' . esc_attr($layout) . '" ' . $selected . '>' . esc_html($text) . ' &emsp; &emsp; &emsp; &emsp; </option>';
            }

            echo '</select>';
            echo '</label>';
        }

        /**
         * @param array $args
         */
        public function settings_author_pages_grid_layout_column($args = [])
        {
            $id    = $this->module->options_group_name . '_author_pages_grid_layout_column';
            $value = isset($this->module->options->author_pages_grid_layout_column) ? $this->module->options->author_pages_grid_layout_column : '';


            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="number" min="1" step="1" class="small-text" value="' . esc_attr($value) . '" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_pages_grid_layout_column]">';
            echo '</label>';

        }

        /**
         * @param array $args
         */
        public function settings_author_pages_posts_limit($args = [])
        {
            $id    = $this->module->options_group_name . '_author_pages_posts_limit';
            $value = isset($this->module->options->author_pages_posts_limit) ? $this->module->options->author_pages_posts_limit : '';


            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="number" step="1" class="small-text" value="' . esc_attr($value) . '" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_pages_posts_limit]">';
            echo '</label>';

        }

        /**
         * @param array $args
         */
        public function settings_author_post_custom_width($args = [])
        {
            $id    = $this->module->options_group_name . '_author_post_custom_width';
            $value = isset($this->module->options->author_post_custom_width) ? $this->module->options->author_post_custom_width : '';


            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="number" min="1" step="1" class="small-text" value="' . esc_attr($value) . '" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_post_custom_width]">';
            echo '</label>';

        }

        /**
         * @param array $args
         */
        public function settings_author_post_custom_height($args = [])
        {
            $id    = $this->module->options_group_name . '_author_post_custom_height';
            $value = isset($this->module->options->author_post_custom_height) ? $this->module->options->author_post_custom_height : '';


            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="number" min="1" step="1" class="small-text" value="' . esc_attr($value) . '" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_post_custom_height]">';
            echo '</label>';

        }

        /**
         * @param array $args
         */
        public function settings_author_post_excerpt_ellipsis($args = [])
        {
            $id    = $this->module->options_group_name . '_author_post_excerpt_ellipsis';
            $value = isset($this->module->options->author_post_excerpt_ellipsis) ? $this->module->options->author_post_excerpt_ellipsis : '';


            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="text" class="small-text" value="' . esc_attr($value) . '" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_post_excerpt_ellipsis]">';
            echo '</label>';

        }


        /**
         * @param array $args
         */
        public function settings_display_name_format($args = [])
        {
            $id    = $this->module->options_group_name . '_display_name_format';
            $value = isset($this->module->options->display_name_format) ? $this->module->options->display_name_format : 'custom';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[display_name_format]">';

            $display_name_formats = [
                'custom' => esc_html__('Allow User to Choose', 'publishpress-authors'),
                'first_name_last_name' => esc_html__('First Name Last Name', 'publishpress-authors'),
                'last_name_first_name' => esc_html__('Last Name First Name', 'publishpress-authors'),
                'first_name' => esc_html__('First Name', 'publishpress-authors'),
                'last_name' => esc_html__('Last Name', 'publishpress-authors')
            ];

            foreach ($display_name_formats as $key => $label) {
                $selected = $value === $key ? 'selected="selected"' : '';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
            }

            echo '</select>';
            echo '</label>';
        }

        /**
         * @param array $args
         */
        public function settings_default_avatar($args = [])
        {
            $id    = $this->module->options_group_name . '_default_avatar';
            $value = isset($this->module->options->default_avatar) ? $this->module->options->default_avatar : '';
            $default_image = '';
            if ($value) {
                $default_image = wp_get_attachment_image_url($value, 'thumbnail');
            }
            ?>
            <div class="author-image-field-wrapper">
                <div class="author-image-field-container">
                    <?php if ($default_image) : ?>
                        <img src="<?php echo esc_url($default_image); ?>" alt=""/>
                    <?php endif; ?>
                </div>
                <p class="hide-if-no-js">
                    <a class="select-author-image-field <?php echo $default_image ? 'hidden' : ''; ?>" href="#">
                        <?php esc_html_e('Select image', 'publishpress-authors'); ?>
                    </a>
                    <a class="delete-author-image-field <?php echo !$default_image ? 'hidden' : ''; ?>"
                       href="#">
                        <?php esc_html_e('Remove this image', 'publishpress-authors'); ?>
                    </a>
                </p>
                <input name="<?php echo esc_attr($this->module->options_group_name); ?>[default_avatar]" class="author-image-field-id" type="hidden"
                       value="<?php echo esc_attr($value); ?>"/>
            </div>
            <?php

echo '<span class="ppma_settings_field_description">'
    . esc_html__(
        'This avatar will be used as default avatar instead of gravatar where no custom avatar is added to profile.',
        'publishpress-authors'
    )
    . '</span>';

        }


        /**
         * @param array $args
         */
        public function settings_show_author_pages_bio($args = [])
        {
            $id    = $this->module->options_group_name . '_show_author_pages_bio';
            $value = isset($this->module->options->show_author_pages_bio) ? $this->module->options->show_author_pages_bio : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[show_author_pages_bio]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'This will display the author bio.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }

        /**
         *  @param array $args
         */
        public function settings_author_pages_bio_layout($args = [])
        {
            $id    = $this->module->options_group_name . '_author_pages_bio_layout';
            $value = isset($this->module->options->author_pages_bio_layout)
                ? $this->module->options->author_pages_bio_layout
                : Utils::getDefaultLayout();

            echo '<label for="' . esc_attr($id) . '">';

            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_pages_bio_layout]">';

            $layouts = apply_filters('pp_multiple_authors_author_layouts', []);
            unset($layouts['authors_index']);
            unset($layouts['authors_recent']);

            foreach ($layouts as $layout => $text) {
                $selected = $value === $layout ? 'selected="selected"' : '';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<option value="' . esc_attr($layout) . '" ' . $selected . '>' . esc_html($text) . '</option>';
            }

            echo '</select>';
            echo '</label>';
        }


        /**
         *  @param array $args
         */
        public function settings_author_legacy_layout_boxed($args = [])
        {
            $id    = $this->module->options_group_name . '_author_legacy_layout_boxed';
            $value = isset($this->module->options->author_legacy_layout_boxed)
                ? $this->module->options->author_legacy_layout_boxed
                : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_legacy_layout_boxed]">';

            $layouts = apply_filters('pp_multiple_authors_author_layouts', []);
            unset($layouts['authors_index']);
            unset($layouts['authors_recent']);

            echo '<option value="">' . esc_html__('Select option', 'publishpress-authors') . '</option>';
            foreach ($layouts as $layout => $text) {
                $selected = $value === $layout ? 'selected="selected"' : '';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<option value="' . esc_attr($layout) . '" ' . $selected . '>' . esc_html($text) . '</option>';
            }

            echo '</select>';

            echo '</label>';
            echo '<div class="ppma_settings_field_description">'
                . sprintf(
                    esc_html__('This is useful if you use legacy shortcode %s', 'publishpress-authors'),
                    '<strong>[publishpress_authors_box layout="boxed"]</strong>'
                )
                . '</div>';
        }


        /**
         *  @param array $args
         */
        public function settings_author_legacy_layout_centered($args = [])
        {
            $id    = $this->module->options_group_name . '_author_legacy_layout_centered';
            $value = isset($this->module->options->author_legacy_layout_centered)
                ? $this->module->options->author_legacy_layout_centered
                : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_legacy_layout_centered]">';

            $layouts = apply_filters('pp_multiple_authors_author_layouts', []);
            unset($layouts['authors_index']);
            unset($layouts['authors_recent']);

            echo '<option value="">' . esc_html__('Select option', 'publishpress-authors') . '</option>';
            foreach ($layouts as $layout => $text) {
                $selected = $value === $layout ? 'selected="selected"' : '';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<option value="' . esc_attr($layout) . '" ' . $selected . '>' . esc_html($text) . '</option>';
            }

            echo '</select>';

            echo '</label>';
            echo '<div class="ppma_settings_field_description">'
                . sprintf(
                    esc_html__('This is useful if you use legacy shortcode %s', 'publishpress-authors'),
                    '<strong>[publishpress_authors_box layout="centered"]</strong>'
                )
                . '</div>';
        }


        /**
         *  @param array $args
         */
        public function settings_author_legacy_layout_inline($args = [])
        {
            $id    = $this->module->options_group_name . '_author_legacy_layout_inline';
            $value = isset($this->module->options->author_legacy_layout_inline)
                ? $this->module->options->author_legacy_layout_inline
                : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_legacy_layout_inline]">';

            $layouts = apply_filters('pp_multiple_authors_author_layouts', []);
            unset($layouts['authors_index']);
            unset($layouts['authors_recent']);

            echo '<option value="">' . esc_html__('Select option', 'publishpress-authors') . '</option>';
            foreach ($layouts as $layout => $text) {
                $selected = $value === $layout ? 'selected="selected"' : '';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<option value="' . esc_attr($layout) . '" ' . $selected . '>' . esc_html($text) . '</option>';
            }

            echo '</select>';

            echo '</label>';
            echo '<div class="ppma_settings_field_description">'
                . sprintf(
                    esc_html__('This is useful if you use legacy shortcode %s', 'publishpress-authors'),
                    '<strong>[publishpress_authors_box layout="inline"]</strong>'
                )
                . '</div>';
        }


        /**
         *  @param array $args
         */
        public function settings_author_legacy_layout_inline_avatar($args = [])
        {
            $id    = $this->module->options_group_name . '_author_legacy_layout_inline_avatar';
            $value = isset($this->module->options->author_legacy_layout_inline_avatar)
                ? $this->module->options->author_legacy_layout_inline_avatar
                : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_legacy_layout_inline_avatar]">';

            $layouts = apply_filters('pp_multiple_authors_author_layouts', []);
            unset($layouts['authors_index']);
            unset($layouts['authors_recent']);

            echo '<option value="">' . esc_html__('Select option', 'publishpress-authors') . '</option>';
            foreach ($layouts as $layout => $text) {
                $selected = $value === $layout ? 'selected="selected"' : '';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<option value="' . esc_attr($layout) . '" ' . $selected . '>' . esc_html($text) . '</option>';
            }

            echo '</select>';

            echo '</label>';
            echo '<div class="ppma_settings_field_description">'
                . sprintf(
                    esc_html__('This is useful if you use legacy shortcode %s', 'publishpress-authors'),
                    '<strong>[publishpress_authors_box layout="inline_avatar"]</strong>'
                )
                . '</div>';
        }


        /**
         *  @param array $args
         */
        public function settings_author_legacy_layout_simple_list($args = [])
        {
            $id    = $this->module->options_group_name . '_author_legacy_layout_simple_list';
            $value = isset($this->module->options->author_legacy_layout_simple_list)
                ? $this->module->options->author_legacy_layout_simple_list
                : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[author_legacy_layout_simple_list]">';

            $layouts = apply_filters('pp_multiple_authors_author_layouts', []);
            unset($layouts['authors_index']);
            unset($layouts['authors_recent']);

            echo '<option value="">' . esc_html__('Select option', 'publishpress-authors') . '</option>';
            foreach ($layouts as $layout => $text) {
                $selected = $value === $layout ? 'selected="selected"' : '';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<option value="' . esc_attr($layout) . '" ' . $selected . '>' . esc_html($text) . '</option>';
            }

            echo '</select>';

            echo '</label>';
            echo '<div class="ppma_settings_field_description">'
                . sprintf(
                    esc_html__('This is useful if you use legacy shortcode %s', 'publishpress-authors'),
                    '<strong>[publishpress_authors_box layout="simple_list"]</strong>'
                )
                . '</div>';
        }


        /**
         * @param array $args
         */
        public function settings_show_author_post_featured_image($args = [])
        {
            $id    = $this->module->options_group_name . '_show_author_post_featured_image';
            $value = isset($this->module->options->show_author_post_featured_image) ? $this->module->options->show_author_post_featured_image : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[show_author_post_featured_image]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'This will display the featured image.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }


        /**
         * @param array $args
         */
        public function settings_show_author_page_title($args = [])
        {
            $id    = $this->module->options_group_name . '_show_author_page_title';
            $value = isset($this->module->options->show_author_page_title) ? $this->module->options->show_author_page_title : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[show_author_page_title]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'This will display author page title.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }


        /**
         * @param array $args
         */
        public function settings_show_author_post_excerpt($args = [])
        {
            $id    = $this->module->options_group_name . '_show_author_post_excerpt';
            $value = isset($this->module->options->show_author_post_excerpt) ? $this->module->options->show_author_post_excerpt : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[show_author_post_excerpt]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'This will display the excerpt.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }


        /**
         * @param array $args
         */
        public function settings_remove_single_user_map_restriction($args = [])
        {
            $id    = $this->module->options_group_name . '_remove_single_user_map_restriction';
            $value = isset($this->module->options->remove_single_user_map_restriction) ? $this->module->options->remove_single_user_map_restriction : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[remove_single_user_map_restriction]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'This will allows you map a WordPress user to more than one author. Don\'t use this feature unless requested to do so by the PublishPress team. This plugin works better when authors and users are paired one-to-one.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }


        /**
         * @param array $args
         */
        public function settings_show_author_post_authors($args = [])
        {
            $id    = $this->module->options_group_name . '_show_author_post_authors';
            $value = isset($this->module->options->show_author_post_authors) ? $this->module->options->show_author_post_authors : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[show_author_post_authors]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'This will display the authors.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }


        /**
         * @param array $args
         */
        public function settings_show_author_post_date($args = [])
        {
            $id    = $this->module->options_group_name . '_show_author_post_date';
            $value = isset($this->module->options->show_author_post_date) ? $this->module->options->show_author_post_date : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[show_author_post_date]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'This will display the published date.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }


        /**
         * @param array $args
         */
        public function settings_show_author_post_comments($args = [])
        {
            $id    = $this->module->options_group_name . '_show_author_post_comments';
            $value = isset($this->module->options->show_author_post_comments) ? $this->module->options->show_author_post_comments : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[show_author_post_comments]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'This will display the comment count.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }


        /**
         * @param array $args
         */
        public function settings_show_author_post_category($args = [])
        {
            $id    = $this->module->options_group_name . '_show_author_post_category';
            $value = isset($this->module->options->show_author_post_category) ? $this->module->options->show_author_post_category : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[show_author_post_category]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'This will display the categories.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }


        /**
         * @param array $args
         */
        public function settings_show_author_post_tags($args = [])
        {
            $id    = $this->module->options_group_name . '_show_author_post_tags';
            $value = isset($this->module->options->show_author_post_tags) ? $this->module->options->show_author_post_tags : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[show_author_post_tags]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'This will display the tags.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }


        /**
         * @param array $args
         */
        public function settings_show_author_post_readmore($args = [])
        {
            $id    = $this->module->options_group_name . '_show_author_post_readmore';
            $value = isset($this->module->options->show_author_post_readmore) ? $this->module->options->show_author_post_readmore : '';

            echo '<label for="' . esc_attr($id) . '">';

            echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[show_author_post_readmore]" value="yes" ' . ($value === 'yes' ? 'checked="checked"' : '') . '/>';

            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">'
                . esc_html__(
                    'This will display the read more link.',
                    'publishpress-authors'
                )
                . '</span>';


            echo '</label>';
        }

        /**
         * Default author for new posts
         *
         * @param array $args
         */
        public function settings_default_author_for_new_posts()
        {
            $id    = $this->module->options_group_name . '_default_author_for_new_posts';
            $value = isset($this->module->options->default_author_for_new_posts) ? $this->module->options->default_author_for_new_posts : '';
            ?>
            <label for="<?php echo esc_attr($id); ?>">
                <select data-value="<?php echo esc_attr($value); ?>"
                        name="<?php echo esc_attr($this->module->options_group_name) . '[default_author_for_new_posts]'; ?>"
                        data-nonce="<?php echo esc_attr(wp_create_nonce('authors-search')); ?>"
                        class="default-authors-select2"
                        data-placeholder="<?php esc_attr_e('Search for an author', 'publishpress-authors'); ?>" style="width: 350px">
                    <option value=""></option>
                    <?php
                    if (!empty($value)) {
                        $author = Author::get_by_term_id($value);
                        ?>
                        <option value="<?php echo esc_attr($value); ?>"
                                selected="selected"><?php echo esc_html($author->display_name); ?></option>
                    <?php } ?>
                </select>

            </label>
            <p class="ppma_settings_field_description">
                <?php echo esc_html__('This setting may be disabled for users who can not edit others posts.', 'publishpress-authors'); ?>
                <a href="https://publishpress.com/knowledge-base/troubleshooting/#default-author-is-not-applied-to-new-posts" target="_blank">
                    <?php echo esc_html__('Click here for more details.', 'publishpress-authors'); ?>
                </a>
            </p>
            <?php
        }

        /**
         * Fallback user for Guest Authors
         *
         * @param array $args
         */
        public function settings_fallback_user_for_guest_post()
        {
            $id    = $this->module->options_group_name . '_fallback_user_for_guest_post';
            $value = isset($this->module->options->fallback_user_for_guest_post) ? $this->module->options->fallback_user_for_guest_post : '';
            ?>
            <label for="<?php echo esc_attr($id); ?>">
                <select data-value="<?php echo esc_attr($value); ?>"
                        name="<?php echo esc_attr($this->module->options_group_name) . '[fallback_user_for_guest_post]'; ?>"
                        data-nonce="<?php echo esc_attr(wp_create_nonce('authors-user-search')); ?>"
                        class="authors-select2 authors-user-search fallback-user-search-select2"
                        data-placeholder="<?php esc_attr_e('Search for a fallback author', 'publishpress-authors'); ?>" style="width: 350px">
                    <option value=""></option>
                    <?php
                    if (!empty($value)) {
                        $author = Author::get_by_user_id($value);
                        ?>
                        <option value="<?php echo esc_attr($value); ?>"
                                selected="selected"><?php echo esc_html($author->display_name); ?></option>
                    <?php } ?>
                </select>

            </label>
            <p class="ppma_settings_field_description">
                <?php echo esc_html__('If you only have Guest Authors selected for a post, this user may be used as a fallback. WordPress sometimes requires a WordPress user to be assigned to each post. This user will not be visible on the front of your site.', 'publishpress-authors'); ?>
            </p>
            <?php
        }

        /**
         * Displays the field to choose display or not the email link/icon.
         *
         * @param array
         */
        public function settings_show_email_link_option($args = [])
        {
            $id    = $this->module->options_group_name . '_show_email_link';
            $value = isset($this->module->options->show_email_link) ? $this->module->options->show_email_link : 'yes';

            echo '<label for="' . esc_attr($id) . '">';
            echo '<input type="checkbox" value="yes" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[show_email_link]" '
                . checked($value, 'yes', false) . ' />';
            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">' . esc_html__(
                    'This will display the authors email in the author box.',
                    'publishpress-authors'
                ) . '</span>';
            echo '</label>';
        }

        /**
         * Displays the field to choose display or not the email link/icon.
         *
         * @param array
         */
        public function settings_show_site_link_option($args = [])
        {
            $id    = $this->module->options_group_name . '_show_site_link';
            $value = isset($this->module->options->show_site_link) ? $this->module->options->show_site_link : 'yes';

            echo '<label for="' . esc_attr($id) . '">';
            echo '<input type="checkbox" value="yes" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[show_site_link]" '
                . checked($value, 'yes', false) . ' />';
            echo '&nbsp;&nbsp;&nbsp; <span class="ppma_settings_field_description">' . esc_html__(
                    'This will display the authors site in the author box.',
                    'publishpress-authors'
                ) . '</span>';
            echo '</label>';
        }

        /**
         * Display layout icons option field.
         *
         * @param array
         */
        public function settings_enable_font_awesome_option($args = [])
        {
            $id    = $this->module->options_group_name . '_enable_font_awesome';
            $value = isset($this->module->options->enable_font_awesome) ? $this->module->options->enable_font_awesome : 'no';

            echo '<label for="' . esc_attr($id) . '">';
            echo '<input type="checkbox" value="yes" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[enable_font_awesome]" '
                . checked($value, 'yes', false) . ' />';
            echo '&nbsp;&nbsp;&nbsp; <span class="ppma_settings_field_description">' . esc_html__(
                    'This will load Font Awesome icons for use in Author Boxes.',
                    'publishpress-authors'
                ) . '</span>';
            echo '</label>';
        }

        /**
         * @param array
         */
        public function settings_disable_quick_edit_author_box_option($args = [])
        {
            $id    = $this->module->options_group_name . '_disable_quick_edit_author_box';
            $value = isset($this->module->options->disable_quick_edit_author_box) ? $this->module->options->disable_quick_edit_author_box : 'no';

            echo '<label for="' . esc_attr($id) . '">';
            echo '<input type="checkbox" value="yes" id="' . esc_attr($id) . '" name="' . esc_attr($this->module->options_group_name) . '[disable_quick_edit_author_box]" '
                . checked($value, 'yes', false) . ' />';
            echo '&nbsp;&nbsp;&nbsp; <span class="ppma_settings_field_description">' . esc_html__(
                    'This will remove the Authors Box in "Quick Edit".',
                    'publishpress-authors'
                ) . '</span>';
            echo '</label>';
        }

        /**
         * Displays the button to reset the author terms.
         *
         * @param array
         */
        public function settings_maintenance_option($args = [])
        {
            $nonce     = wp_create_nonce('multiple_authors_maintenance');
            $base_link = esc_url(admin_url('/admin.php?page=ppma-modules-settings&ppma_action=%s&nonce=' . $nonce));

            $actions = [
                'create_post_authors' => [
                    'title'        => esc_html__('Create PublishPress Authors Profiles for all post authors', 'publishpress-authors'),
                    'description'  => esc_html__('If a WordPress user is the author of a post, but does not have a PublishPress Authors profile, this will automatically create a profile for them.', 'publishpress-authors') . ' <a href="https://publishpress.com/knowledge-base/authors-maintenance" target="_blank">' . esc_html__('Click here for documentation', 'publishpress-authors') . '</a>.',
                    'button_label' => esc_html__('Create missing PublishPress Authors profiles', 'publishpress-authors'),
                ],

                'create_role_authors' => [
                    'title'        => esc_html__('Create PublishPress Authors Profiles for all users in a role', 'publishpress-authors'),
                    'description'  => esc_html__('This will find all the users in a role and create author profiles for them. You can choose the roles using the "Automatically create author profiles" setting.', 'publishpress-authors') . ' <a href="https://publishpress.com/knowledge-base/authors-maintenance" target="_blank">' . esc_html__('Click here for documentation', 'publishpress-authors') . '</a>.',
                    'button_label' => esc_html__('Create PublishPress Authors profiles', 'publishpress-authors'),
                ],

                'sync_post_author' => [
                    'title'       => esc_html__('Synchronize PublishPress Authors Fields and user profile fields', 'publishpress-authors'),
                    'description' => esc_html__('This will update all the author profiles in PublishPress Authors to match the default WordPress fields for each user.', 'publishpress-authors') . ' <a href="https://publishpress.com/knowledge-base/authors-maintenance" target="_blank">' . esc_html__('Click here for documentation', 'publishpress-authors') . '</a>.',
                    'button_link' => '',
                    'after'       => '<div id="publishpress-authors-sync-post-authors"></div>',
                ],

                'sync_author_slug' => [
                    'title'       => esc_html__('Synchronize the author and user URLs', 'publishpress-authors'),
                    'description' => esc_html__('This will update all the Author URLs in PublishPress Authors to match the default WordPress URLs for each user.', 'publishpress-authors') . ' <br /> <a href="https://publishpress.com/knowledge-base/authors-maintenance" target="_blank">' . esc_html__('Click here for documentation', 'publishpress-authors') . '</a>.',
                    'button_link' => '',
                    'after'       => '<div id="publishpress-authors-sync-author-slug"></div>',
                ],

                'create_author_categories' => [
                    'title'       => esc_html__('Create Author Categories', 'publishpress-authors'),
                    'description' => esc_html__('This will create author categories table if missing and add default categories.', 'publishpress-authors'),
                    'button_label' => esc_html__('Create Author Categories', 'publishpress-authors'),
                ],
            ];

            /**
             * @param array $actions
             */
            $actions = apply_filters('pp_authors_maintenance_actions', $actions);

            if (isset($GLOBALS['coauthors_plus']) && !empty($GLOBALS['coauthors_plus'])) {
                $actions['copy_coauthor_plus_data'] = [
                    'title'       => esc_html__('Copy Co-Authors Plus Data', 'publishpress-authors'),
                    'description' => esc_html__('This action will copy the authors from the plugin Co-Authors Plus allowing you to migrate to PublishPress Authors without losing any data. This action can be run multiple times.', 'publishpress-authors'),
                    'button_link' => '',
                    'after'       => '<div id="publishpress-authors-coauthors-migration"></div>',
                ];
            }

            $actions['delete_mapped_authors'] = [
                'title'        => esc_html__('Delete Mapped Authors', 'publishpress-authors'),
                'description'  => esc_html__('This action can reset the PublishPress Authors data before using other maintenance options. It will delete all author profiles that are mapped to a WordPress user account. This will not delete the WordPress user accounts, but any links between the posts and multiple authors will be lost.', 'publishpress-authors'),
                'button_label' => esc_html__('Delete all authors mapped to users', 'publishpress-authors'),
                'button_icon'  => 'dashicons-warning',
            ];

            $actions['delete_guest_authors'] = [
                'title'        => esc_html__('Delete Guest Authors', 'publishpress-authors'),
                'description'  => esc_html__('This action can reset the PublishPress Authors data before using other maintenance options. Guest authors are author profiles that are not mapped to a WordPress user account. This action will delete all guest authors.', 'publishpress-authors'),
                'button_label' => esc_html__('Delete all guest authors', 'publishpress-authors'),
                'button_icon'  => 'dashicons-warning',
            ];

            echo '<div id="ppma_maintenance_settings">';

            echo '<p class="ppma_warning">' . esc_html__(
                    'Please be careful clicking these buttons. Before clicking, we recommend taking a site backup in case anything goes wrong.',
                    'publishpress-authors'
                ) . '</p>';

            foreach ($actions as $actionName => $actionInfo) {
                if (isset($actionInfo['button_link'])) {
                    $link = $actionInfo['button_link'];
                } else {
                    $link = sprintf($base_link, $actionName);
                }

                echo '<div class="ppma_maintenance_action_wrapper">';
                echo '<h4>' . esc_html($actionInfo['title']) . '</h4>';
                echo '<p class="ppma_settings_field_description">' . wp_kses_post($actionInfo['description']) . ' </p>';

                if (!empty($link)) {
                    echo '<a href="' . esc_url($link) . '" class="button - secondary button - danger ppma_maintenance_button" id="' . esc_attr($actionName) . '">';

                    if (isset($actionInfo['button_icon'])) {
                        echo '<span class="dashicons ' . esc_attr($actionInfo['button_icon']) . '"></span>';
                    }

                    echo esc_html($actionInfo['button_label']) . '</a>';
                }

                if (isset($actionInfo['after'])) {
                    echo $actionInfo['after']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }

                echo '</div>';
            }

            echo '</div>';
        }

        /**
         * Validate data entered by the user
         *
         * @param array $new_options New values that have been entered by the user
         *
         * @return array $new_options Form values after they've been sanitized
         */
        public function validate_module_settings($new_options, $module_name)
        {
            if ($module_name !== 'multiple_authors') {
                return $new_options;
            }

            // Whitelist validation for the post type options
            if (!isset($new_options['post_types'])) {
                $new_options['post_types'] = [];
            }

            $new_options['post_types'] = $this->clean_post_type_options(
                $new_options['post_types'],
                $this->module->post_type_support
            );

            if (!isset($new_options['preppend_to_content'])) {
                $new_options['preppend_to_content'] = 'no';
            }

            if (!isset($new_options['append_to_content'])) {
                $new_options['append_to_content'] = 'no';
            }

            if (!isset($new_options['author_for_new_users']) || !is_array($new_options['author_for_new_users'])) {
                $new_options['author_for_new_users'] = [];
            }

            if (!isset($new_options['show_email_link'])) {
                $new_options['show_email_link'] = 'no';
            }

            if (!isset($new_options['show_site_link'])) {
                $new_options['show_site_link'] = 'no';
            }

            if (!isset($new_options['enable_font_awesome'])) {
                $new_options['enable_font_awesome'] = 'no';
            }

            if (!isset($new_options['disable_quick_edit_author_box'])) {
                $new_options['disable_quick_edit_author_box'] = 'no';
            }

            if (!isset($new_options['username_in_search_field'])) {
                $new_options['username_in_search_field'] = 'no';
            }

            if (!isset($new_options['enable_plugin_author_pages'])) {
                $new_options['enable_plugin_author_pages'] = 'no';
            }

            if (!isset($new_options['show_author_pages_bio'])) {
                $new_options['show_author_pages_bio'] = 'no';
            }

            if (!isset($new_options['show_author_post_featured_image'])) {
                $new_options['show_author_post_featured_image'] = 'no';
            }

            if (!isset($new_options['show_author_post_excerpt'])) {
                $new_options['show_author_post_excerpt'] = 'no';
            }

            if (!isset($new_options['remove_single_user_map_restriction'])) {
                $new_options['remove_single_user_map_restriction'] = 'no';
            }

            if (!isset($new_options['enable_guest_author_user'])) {
                $new_options['enable_guest_author_user'] = 'no';
            }

            if (!isset($new_options['author_boxes_opt_out'])) {
                $new_options['author_boxes_opt_out'] = 'no';
            }

            if (!isset($new_options['enable_guest_author_acount'])) {
                $new_options['enable_guest_author_acount'] = 'no';
            }

            if (!isset($new_options['show_author_post_authors'])) {
                $new_options['show_author_post_authors'] = 'no';
            }

            if (!isset($new_options['show_author_post_date'])) {
                $new_options['show_author_post_date'] = 'no';
            }

            if (!isset($new_options['show_author_post_comments'])) {
                $new_options['show_author_post_comments'] = 'no';
            }

            if (!isset($new_options['show_author_post_category'])) {
                $new_options['show_author_post_category'] = 'no';
            }

            if (!isset($new_options['show_author_post_tags'])) {
                $new_options['show_author_post_tags'] = 'no';
            }

            if (!isset($new_options['show_author_post_readmore'])) {
                $new_options['show_author_post_readmore'] = 'no';
            }

            if (!isset($new_options['show_author_page_title'])) {
                $new_options['show_author_page_title'] = 'no';
            }

            if (isset($new_options['layout'])) {
                /**
                 * Filter the list of available layouts.
                 */
                $layouts = apply_filters('pp_multiple_authors_author_layouts', []);

                if (!array_key_exists($new_options['layout'], $layouts)) {
                    $new_options['layout'] = Utils::getDefaultLayout();
                }

                if (!array_key_exists($new_options['author_pages_bio_layout'], $layouts)) {
                    $new_options['author_pages_bio_layout'] = Utils::getDefaultLayout();
                }
            }

            if (!isset($new_options['author_page_post_types']) || empty($new_options['author_page_post_types'])) {
                $new_options['author_page_post_types'] = ['post' => 'on'];
            }

            /**
             * @param array $newOptions
             * @param string $moduleName
             *
             * @return array
             */
            return apply_filters('pp_authors_validate_module_settings', $new_options, $module_name);
        }

        /**
         * @param array $tabs
         *
         * @return array
         */
        public function settings_tab($tabs)
        {
            $tabs = array_merge(
                $tabs,
                [
                    '#ppma-tab-general'     => esc_html__('General', 'publishpress-authors'),
                    '#ppma-tab-display'     => esc_html__('Author Boxes', 'publishpress-authors'),
                    '#ppma-tab-guest-author' => esc_html__('Author Profiles', 'publishpress-authors'),
                    '#ppma-tab-author-pages' => esc_html__('Author Pages', 'publishpress-authors'),
                    '#ppma-tab-shortcodes'  => esc_html__('Shortcodes', 'publishpress-authors'),
                    '#ppma-tab-maintenance' => esc_html__('Maintenance', 'publishpress-authors'),
                    '#ppma-tab-advanced' => esc_html__('Advanced', 'publishpress-authors'),
                ]
            );

            return $tabs;
        }

        public function theAuthorPostsLink($link)
        {
            $newLink   = '';
            $post      = get_post();
            $postID    = $post ? $post->ID : '';
            $authors   = get_post_authors($postID);

            if ($post && ! Utils::is_post_type_enabled($post->post_type)) {
                return $link;
            }

            foreach ($authors as $author) {
                if (!empty($newLink)) {
                    $newLink .= ', ';
                }

                $newLink .= '<a href="' . esc_url($author->link) . '" title="' . esc_attr(sprintf(esc_html__( 'Posts by %s' ), $author->display_name))
                    . '" rel="author" itemprop="author" itemscope="itemscope" itemtype="https://schema.org/Person">'
                    . esc_html($author->display_name) . '</a>';
            }

            return $newLink;
        }

        /**
         * Filters the list of receivers in the notification workflows provided
         * by the improved notifications add-on.
         *
         * @param array $receivers
         * @param int $workflowPostID
         * @param array $args
         *
         * @return array
         */
        public function filter_workflow_receiver_post_authors($receivers, $workflowPostID, $args)
        {
            if (!function_exists('get_post_authors')) {
                include_once PP_AUTHORS_SRC_PATH . 'functions/template-tags.php';
            }

            $authors = get_post_authors($args['params']['post_id']);

            if (!empty($authors)) {
                if (!is_array($receivers)) {
                    $receivers = [$receivers];
                }

                foreach ($authors as $author) {
                    if (!$author->is_guest() && !in_array($author->user_id, $receivers)) {
                        $receivers[] = $author->user_id;
                    }

                    if ($author->is_guest() && !empty($author->user_email)) {
                        $receivers[] = $author->user_email;
                    }
                }
            }

            return $receivers;
        }

        /**
         * Over hide some strings for Authors.
         *
         * @param string $translation Translated text.
         * @param string $text Text to translate.
         * @param string $domain Text domain. Unique identifier for retrieving translated strings.
         *
         * @return string
         */
        public function filter_get_text($translation, $text, $domain)
        {
            global $pagenow;

            if (!in_array($pagenow, ['edit-tags.php', 'term.php'])) {
                return $translation;
            }

            $taxonomy = isset($_GET['taxonomy']) ? sanitize_key($_GET['taxonomy']) : null;
            if ('author' !== $taxonomy) {
                return $translation;
            }

            // The name of field Slug, convert to Author URL
            if ('default' === $domain) {
                if ('Slug' === $translation) {
                    $translation = esc_html__('Author URL', 'publishpress-authors');
                }
            }

            return $translation;
        }

        /**
         * @param $classes
         *
         * @return mixed
         */
        public function filter_body_class($classes)
        {
            if (Util::isAuthor()) {
                if ($brokenItem = array_search('author-', $classes)) {
                    unset($classes[$brokenItem]);
                }

                $author = get_archive_author();

                if (!empty($author) && is_object($author)) {
                    $classes[] = $author->is_guest() ? 'guest-author' : 'not-guest-author';
                }
            }

            return $classes;
        }

        /**
         * @param string[] $classes
         * @param string $class
         * @param int $commentID
         * @param WP_Comment $comment
         * @param int|WP_Post $postID
         *
         * @return mixed
         */
        public function filterCommentClass($classes, $class, $commentID, $comment, $postID) {
            if (!function_exists('get_post_authors')) {
                return $classes;
            }

            $postAuthors = get_post_authors($postID);

            if (empty($postAuthors)) {
                return $classes;
            }

            if (in_array('bypostauthor', $classes, true)) {
                return $classes;
            }

            foreach ($postAuthors as $author) {
                if ($comment->user_id === $author->user_id) {
                    $classes[] = 'bypostauthor';
                    break;
                }
            }

            return $classes;
        }

        private function get_currrent_post_author()
        {
            global $post;

            // TODO: check if this works on the author archive. Do we need to check is_author to pass the is_archive param?

            if (is_archive() && Util::isAuthor()) {
                $authors = [get_archive_author()];
            } else {
                $authors = get_post_authors($post);
            }

            if (count($authors) > 0 && !is_wp_error($authors[0]) && is_object($authors[0])) {
                return $authors[0];
            }

            return false;
        }

        /**
         * @param int $id
         * @return false|Author|WP_User
         */
        private function get_author_by_id($id)
        {
            $author = false;

            if (empty($id)) {
                return false;
            }

            if (!is_numeric($id)) {
                $author = Author::get_by_term_slug($id);
            } elseif ($id > 0) {
                $author = Author::get_by_user_id($id);
            } else {
                $author = Author::get_by_term_id($id);
            }

            if (empty($author)) {
                $author = get_user_by('ID', $id);
            }

            return $author;
        }

        private function get_author_from_id_or_post($original_user_id)
        {
            $author = false;

            if (false === $original_user_id) {
                $author = $this->get_currrent_post_author($original_user_id);
            } else {
                $author = $this->get_author_by_id($original_user_id);
            }

            return $author;
        }

        private function get_author_meta($meta_key, $author_id)
        {
            $author = $this->get_author_from_id_or_post($author_id);

            if (is_object($author)) {
                $value = $author->get_meta($meta_key);
            }

            return $value;
        }

        private function is_author_instance($instance)
        {
            return is_object($instance) && get_class($instance) === Author::class;
        }


        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_ID($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                return $author->ID;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_display_name($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                return $author->display_name;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_first_name($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                return $author->first_name;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_last_name($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                return $author->last_name;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_headline($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                return $author->display_name;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_description($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                return $author->description;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_nickname($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                return $author->nickname;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_aim($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                $value = $author->aim;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_jabber($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                $value = $author->jabber;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_user_email($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                $value = $author->user_email;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_user_nicename($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                $value = $author->user_nicename;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_user_url($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                $value = $author->user_url;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_yim($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                $value = $author->yim;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_facebook($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                $value = $author->facebook;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_twitter($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                $value = $author->twitter;
            }

            return $value;
        }

        /**
         * @param string $value The value of the metadata.
         * @param int $user_id The user ID for the value.
         * @param int $original_user_id The original user ID
         *
         * @return mixed
         */
        public function filter_author_metadata_instagram($value, $user_id, $original_user_id)
        {
            $author = $this->get_author_from_id_or_post($original_user_id);

            if ($this->is_author_instance($author)) {
                $value = $author->instagram;
            }

            return $value;
        }

        /**
         * @param array $args Arguments passed to get_avatar_data(), after processing.
         * @param mixed $id_or_email The Gravatar to retrieve. Accepts a user ID, Gravatar MD5 hash,
         *                           user email, WP_User object, WP_Post object, or WP_Comment object.
         */
        public function filter_pre_get_avatar_data($args, $id_or_email)
        {
            // Stop if they are looking for the default avatar, otherwise we can start an infinity loop when get_avatar_data is called again.
            if (empty($id_or_email)) {
                return $args;
            }

            $termId = 0;

            if (is_numeric($id_or_email)) {
                $id_or_email = (int)$id_or_email;

                if ($id_or_email < 0) {
                    $termId = $id_or_email * -1;
                } else {
                    $author = Author::get_by_user_id($id_or_email);

                    if (is_object($author)) {
                        $termId = $author->term_id;
                    }
                }
            } else {
                if ($id_or_email instanceof WP_Comment) {
                    $id_or_email = $id_or_email->comment_author_email;
                }

                $termId = Author_Utils::get_author_term_id_by_email($id_or_email);
            }

            if (!empty($termId)) {
                $url = Author_Utils::get_avatar_url($termId);

                if (!empty($url)) {
                    $args['url'] = $url;
                }
            }

            return $args;
        }

        /**
         * Handle the action to reset author therms.
         * Remove all authors and regenerate based on posts' authors and the setting to automatically create authors
         * for specific roles.
         */
        public function handle_maintenance_task()
        {
            $actions = [
                'delete_mapped_authors',
                'delete_guest_authors',
                'create_post_authors',
                'create_role_authors',
                'copy_coauthor_plus_data',
                'sync_post_author',
                'sync_author_slug',
                'create_author_categories'
            ];

            if (! isset($_GET['ppma_action']) || isset($_GET['author_term_reset_notice'])
                || ! in_array($_GET['ppma_action'], $actions, true)
            ) {
                return;
            }

            $nonce = isset($_GET['nonce']) ? sanitize_key($_GET['nonce']) : '';
            if (! wp_verify_nonce($nonce, 'multiple_authors_maintenance')) {
                wp_die(esc_html__('Invalid nonce', 'publishpress-authors'));
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_die(esc_html__('Access denied', 'publishpress-authors'));
            }

            try {
                $action = sanitize_key($_GET['ppma_action']);

                do_action('multiple_authors_' . $action);

                wp_redirect(
                    admin_url('/admin.php?page=ppma-modules-settings&author_term_reset_notice=success'),
                    301
                );
                exit;
            } catch (Exception $e) {
                wp_redirect(
                    admin_url('/admin.php?page=ppma-modules-settings&author_term_reset_notice=fail'),
                    301
                );
                exit;
            }
        }

        public function action_delete_mapped_authors()
        {
            global $wpdb;

            $query = "
                SELECT tt.term_id
                FROM {$wpdb->term_taxonomy} AS tt
                WHERE
                tt.taxonomy = 'author'
                AND (SELECT COUNT(*)
                    FROM {$wpdb->termmeta} AS tm
                    WHERE tm.term_id = tt.term_id AND tm.meta_key = 'user_id'
                    ) > 0";

            $terms = $wpdb->get_results($query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

            if (!empty($terms)) {
                foreach ($terms as $term) {
                    wp_delete_term($term->term_id, 'author');
                }
            }
        }

        public function action_delete_guest_authors()
        {
            global $wpdb;

            $query = "
                SELECT tt.term_id
                FROM {$wpdb->term_taxonomy} AS tt
                WHERE
                    tt.taxonomy = 'author'
                    AND (
                        (SELECT tm.meta_value
                        FROM {$wpdb->termmeta} AS tm
                        WHERE tt.term_id = tm.term_id AND tm.meta_key = 'user_id'
                        ) = 0
                        OR (SELECT tm.meta_value
                            FROM {$wpdb->termmeta} AS tm
                            WHERE tt.term_id = tm.term_id AND tm.meta_key = 'user_id') IS null
                    )";

            $terms = $wpdb->get_results($query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

            if (!empty($terms)) {
                foreach ($terms as $term) {
                    wp_delete_term($term->term_id, 'author');
                }
            }
        }

        public function action_create_author_categories()
        {
            if (class_exists('MA_Author_Categories')) {
                $author_category_class = new MA_Author_Categories();
                $author_category_class->runInstallTasks('4.4.1');
            }
        }

        public function action_create_post_authors()
        {
            // Do not execute the post_author migration to post terms if Co-Authors Plus is activated.
            // The user need to manually run the Co-Authors migration task before running this again.
            if (!$this->isCoAuthorsPlusActivated()) {
                Installer::createAuthorTermsForLegacyCoreAuthors();
                Installer::createAuthorTermsForPostsWithLegacyCoreAuthors();
            }
        }

        private function isCoAuthorsPlusActivated()
        {
            return (isset($GLOBALS['coauthors_plus']) && !empty($GLOBALS['coauthors_plus']));
        }


        public function action_create_role_authors()
        {
            // Create authors for users in the taxonomies selected for automatic creation of authors.
            $legacyPlugin = Factory::getLegacyPlugin();

            $roles = (array)$legacyPlugin->modules->multiple_authors->options->author_for_new_users;

            // Check if we have any role selected to create an author for the new user.
            if (!empty($roles)) {
                // Get users from roles
                $args  = [
                    'role__in' => $roles,
                ];
                $users = get_users($args);

                if (!empty($users)) {
                    foreach ($users as $user) {
                        // Create author for this user
                        Author::create_from_user($user->ID);
                    }
                }
            }
        }

        public function action_copy_coauthor_plus_data()
        {
            if (!class_exists('CoAuthorsIterator')) {
                return [];
            }

            $i      = new CoAuthorsIterator();
            $output .= $separators['before'];
            $i->iterate();

            do {
                $author_text = '';

                if ('tag' === $type) {
                    $author_text = $tag($tag_args);
                } elseif ('field' === $type && isset($i->current_author->$tag)) {
                    $author_text = $i->current_author->$tag;
                } elseif ('callback' === $type && is_callable($tag)) {
                    $author_text = call_user_func($tag, $i->current_author);
                }

                // Fallback to user_nicename if we get something empty
                if (empty($author_text)) {
                    $author_text = $i->current_author->user_nicename;
                }

                // Append separators
                if ($i->count() - $i->position == 1) { // last author or only author
                    $output .= $author_text;
                } elseif ($i->count() - $i->position == 2) { // second to last
                    $output .= $author_text . $separators['betweenLast'];
                } else {
                    $output .= $author_text . $separators['between'];
                }
            } while ($i->iterate());

            // Co-Authors sometimes don't have a taxonomy term for the author, but uses the post_author value instead.
            Installer::createAuthorTermsForLegacyCoreAuthors();
            Installer::createAuthorTermsForPostsWithLegacyCoreAuthors();
        }

        /**
         *
         */
        public function handle_maintenance_task_notice()
        {
            if (!isset($_GET['author_term_reset_notice'])) {
                return;
            }

            if ($_GET['author_term_reset_notice'] === 'fail') {
                echo '<div class="notice notice - error is - dismissible">';
                echo '<p>' . esc_html__(
                        'Error. Author terms could not be reseted.',
                        'publishpress-authors'
                    ) . '</p>';
                echo '</div>';

                return;
            }

            if ($_GET['author_term_reset_notice'] === 'success') {
                echo '<div class="notice notice - success is - dismissible">';
                echo '<p>' . esc_html__('Maintenance completed successfully.', 'publishpress-authors') . '</p>';
                echo '</div>';

                return;
            }
        }

        /**
         * Fix ACF caps(#2024) and the upload of media for posts when
         * the user is a secondary author and can't edit others' posts.
         *
         * @param $caps
         * @param $cap
         * @param $user_id
         * @param $args
         *
         * @return mixed
         */
        public function filter_map_meta_cap($caps, $cap, $user_id, $args)
        {
            if (in_array($cap, ['edit_post', 'edit_others_posts']) && in_array('edit_others_posts', $caps, true)) {
                if (isset($args[0])) {
                    $post_id = (int)$args[0];

                    // Check if the user is an author for the current post
                    if ($post_id > 0) {
                        remove_filter('map_meta_cap', [$this, 'filter_map_meta_cap'], 10);
                        if (publishpress_authors_is_author_for_post($user_id, $post_id)) {
                            foreach ($caps as &$item) {
                                // If he is an author for this post we should only check edit_posts.
                                if ($item === 'edit_others_posts') {
                                    $item = 'edit_posts';
                                }
                            }
                        }
                    }
                    $caps = apply_filters('pp_authors_filter_map_meta_cap', $caps, $cap, $user_id, $post_id);
                }
            }

            return $caps;
        }

        /**
         * @param bool $isAuthor
         * @param int $userId
         * @param int $postId
         *
         * @return bool
         */
        public function filter_is_author_of_post($isAuthor, $userId, $postId)
        {
            $postAuthors = get_post_authors($postId);
            $userId      = (int)$userId;

            if (empty($userId)) {
                return false;
            }

            foreach ($postAuthors as $author) {
                if ((int)$author->user_id === $userId) {
                    return true;
                }
            }

            return false;
        }

        public function filter_post_authors_names($names, $postID)
        {
            $names = [];

            $authors = get_post_authors($postID);

            foreach ($authors as $author) {
                $names[] = $author->display_name;
            }

            return $names;
        }

        public function admin_enqueue_scripts()
        {
            if (isset($_GET['page']) && in_array($_GET['page'], ['ppma-modules-settings', 'ppma-author-pages'])) {
                wp_enqueue_script(
                    'multiple-authors-settings',
                    PP_AUTHORS_ASSETS_URL . 'js/settings.js',
                    ['jquery'],
                    PP_AUTHORS_VERSION
                );

                wp_localize_script('multiple-authors-settings', 'ppmaSettings', [
                    'tab' => !empty($_REQUEST['ppma_tab']) ? 'ppma-tab-' . sanitize_key($_REQUEST['ppma_tab']) : '',
                    'runScript' => !empty($_REQUEST['ppma_maint']) ? sanitize_key($_REQUEST['ppma_maint']) : '',
                ]);

                wp_enqueue_script(
                    'publishpress-authors-sync-post-author',
                    PP_AUTHORS_URL . 'src/assets/js/sync-post-author.min.js',
                    [
                        'react',
                        'react-dom',
                        'jquery',
                        'multiple-authors-settings',
                        'wp-element',
                        'wp-hooks',
                        'wp-i18n',
                    ],
                    PP_AUTHORS_VERSION
                );

                wp_localize_script(
                    'publishpress-authors-sync-post-author',
                    'ppmaSyncPostAuthor',
                    [
                        'nonce'     => wp_create_nonce('sync_post_author'),
                        'chunkSize' => PUBLISHPRESS_AUTHORS_SYNC_POST_AUTHOR_CHUNK_SIZE,
                        'buttonLabel'      => esc_html__('Sync author and user fields', 'publishpress-authors'),
                        'messageCollectingData'      => esc_html__('Collecting data...', 'publishpress-authors'),
                        'messageEndingProcess'      => esc_html__('Finishing the process...', 'publishpress-authors'),
                        'messageDone'      => esc_html__('Done! %d posts were updated.', 'publishpress-authors'),
                        'messageWait'      => esc_html__('Please, wait...', 'publishpress-authors'),
                        'messageStarting'      => esc_html__('Updating author field on posts...', 'publishpress-authors'),
                        'messageProgress'      => esc_html__('Updated %d of %d posts...', 'publishpress-authors'),
                    ]
                );

                wp_enqueue_script(
                    'publishpress-authors-sync-author-slug',
                    PP_AUTHORS_URL . '/src/assets/js/sync-author-slug.min.js',
                    [
                        'react',
                        'react-dom',
                        'jquery',
                        'multiple-authors-settings',
                        'wp-element',
                        'wp-hooks',
                        'wp-i18n',
                    ],
                    PP_AUTHORS_VERSION
                );

                wp_localize_script(
                    'publishpress-authors-sync-author-slug',
                    'ppmaSyncAuthorSlug',
                    [
                        'nonce'     => wp_create_nonce('sync_author_slug'),
                        'chunkSize' => PUBLISHPRESS_AUTHORS_SYNC_AUTHOR_SLUG_CHUNK_SIZE,
                        'buttonLabel'      => esc_html__('Sync author and user URLs', 'publishpress-authors'),
                        'messageCollectingData'      => esc_html__('Collecting data...', 'publishpress-authors'),
                        'messageEndingProcess'      => esc_html__('Finishing the process...', 'publishpress-authors'),
                        'messageDone'      => esc_html__('Done! %d authors were updated.', 'publishpress-authors'),
                        'messageWait'      => esc_html__('Please, wait...', 'publishpress-authors'),
                        'messageStarting'      => esc_html__('Updating authors slug...', 'publishpress-authors'),
                        'messageProgress'      => esc_html__('Updated %d of %d authors...', 'publishpress-authors'),
                    ]
                );

                wp_enqueue_style(
                    'publishpress-authors-data-migration-box',
                    PP_AUTHORS_URL . 'src/modules/multiple-authors/assets/css/data-migration-box.css',
                    false,
                    PP_AUTHORS_VERSION
                );

                if ($this->isCoAuthorsPlusActivated()) {
                    wp_enqueue_script(
                        'publishpress-authors-coauthors-migration',
                        PP_AUTHORS_URL . 'src/assets/js/coauthors-migration.min.js',
                        [
                            'react',
                            'react-dom',
                            'jquery',
                            'multiple-authors-settings',
                            'wp-element',
                            'wp-hooks',
                            'wp-i18n',
                        ],
                        PP_AUTHORS_VERSION
                    );

                    wp_localize_script(
                        'publishpress-authors-coauthors-migration',
                        'ppmaCoAuthorsMigration',
                        [
                            'nonce' => wp_create_nonce('migrate_coauthors'),
                            'start_message'      => esc_html__('Collecting data for the migration...', 'publishpress-authors'),
                            'error_message'      => esc_html__('Error: ', 'publishpress-authors'),
                            'progress_message'   => esc_html__('Creating missed post authors....', 'publishpress-authors'),
                            'wait_message'       => esc_html__('Please, wait...', 'publishpress-authors'),
                            'copying_message'    => esc_html__('Copying authors\' data...', 'publishpress-authors'),
                            'copy_message'       => esc_html__('Copy Co-Authors Plus data', 'publishpress-authors'),
                            'deactivating_message' => esc_html__('Deactivating Co-uthors Plus...', 'publishpress-authors'),
                            'deactivated_message'  => esc_html__('Done! Co-Authors Plus is deactivated.', 'publishpress-authors'),
                            'deactivate_message' => esc_html__('Deactivate Co-Authors Plus', 'publishpress-authors'),
                            'completed_message'  => esc_html__('Done! Co-Authors Plus data was copied.', 'publishpress-authors'),
                        ]
                    );
                }

                wp_enqueue_style('wp-color-picker');
                wp_enqueue_script(
                    'ppauthors-color-picker',
                    PP_AUTHORS_ASSETS_URL . 'js/color-picker.js',
                    ['wp-color-picker'],
                    false,
                    true
                );
            }
        }

        /**
         * @return bool
         */
        public function migrate_legacy_settings()
        {
            if (!get_option('publishpress_multiple_authors_settings_migrated_3_0_0')) {
                $legacyOptions = get_option('publishpress_multiple_authors_options');
                if (!empty($legacyOptions)) {
                    update_option('multiple_authors_multiple_authors_options', $legacyOptions);
                }

                update_option('publishpress_multiple_authors_settings_migrated_3_0_0', 1);
            }
        }

        private function getTotalOfNotMigratedCoAuthors()
        {
            $terms = get_terms(
                [
                    'taxonomy'   => 'author',
                    'hide_empty' => false,
                    'number'     => 0,
                    'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                        [
                            'key'     => 'ppma-migrated',
                            'compare' => 'NOT EXISTS',
                        ],
                    ],
                ]
            );

            return count($terms);
        }

        public function getCoauthorsMigrationData()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'migrate_coauthors')) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            // nonce: migrate_coauthors
            wp_send_json(
                [
                    'total' => $this->getTotalOfNotMigratedCoAuthors(),
                ]
            );
        }

        private function getCoAuthorGuestAuthorBySlug($slug)
        {
            $posts = get_posts(
                [
                    'name'        => $slug,
                    'post_type'   => 'guest-author',
                    'post_status' => 'publish',
                ]
            );

            if (!empty($posts)) {
                return $posts[0];
            }

            return false;
        }

        public function getSyncPostAuthorData()
        {
            global $wpdb;

            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'sync_post_author')) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            $postTypes = array_values(Util::get_post_types_for_module($this->module));
            $postTypes = '"' . implode('","', $postTypes) . '"';

            $result = $wpdb->get_results(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ({$postTypes}) AND post_status NOT IN ('trash')",
                ARRAY_N
            );

            $postIds = array_map(
                function ($value) {
                    return (int)$value[0];
                },
                $result
            );

            set_transient('publishpress_authors_sync_post_author_ids', $postIds, 24 * 60 * 60);

            // nonce: migrate_coauthors
            wp_send_json(
                [
                    'total' => count($postIds),
                ]
            );
        }

        public function getSyncAuthorSlugData()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'sync_author_slug')) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            $authorsToUpdate = Utils::detect_author_slug_mismatch();

            set_transient('publishpress_authors_sync_author_slug_ids', $authorsToUpdate, 24 * 60 * 60);

            // nonce: migrate_coauthors
            wp_send_json(
                [
                    'total' => count($authorsToUpdate),
                ]
            );
        }

        private function getCoAuthorUserAuthorBySlug($slug)
        {
            return get_user_by('slug', $slug);
        }

        public function migrateCoAuthorsData()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'migrate_coauthors')) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            $keyForNotMigrated = 'ppma-migrated';

            $termsToMigrate = get_terms(
                [
                    'taxonomy'   => 'author',
                    'hide_empty' => false,
                    'number'     => 5,
                    'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                        [
                            'key'     => $keyForNotMigrated,
                            'compare' => 'NOT EXISTS',
                        ],
                    ],
                ]
            );

            if (!empty($termsToMigrate)) {
                foreach ($termsToMigrate as $term) {
                    $author = $this->getCoAuthorGuestAuthorBySlug($term->slug);

                    if (!empty($author)) {
                        // Guest author
                        if ($author instanceof WP_Post) {
                            $name        = get_post_meta($author->ID, 'cap-display_name', true);
                            $firstName   = get_post_meta($author->ID, 'cap-first_name', true);
                            $lastName    = get_post_meta($author->ID, 'cap-last_name', true);
                            $email       = get_post_meta($author->ID, 'cap-user_email', true);
                            $url         = get_post_meta($author->ID, 'cap-website', true);
                            $description = get_post_meta($author->ID, 'cap-description', true);

                            update_term_meta($term->term_id, 'first_name', $firstName);
                            update_term_meta($term->term_id, 'last_name', $lastName);
                            update_term_meta($term->term_id, 'user_email', $email);
                            update_term_meta($term->term_id, 'user_url', $url);
                            update_term_meta($term->term_id, 'description', $description);
                            wp_update_term(
                                $term->term_id,
                                'author',
                                [
                                    'name' => $name,
                                    'slug' => str_replace('cap-', '', $term->slug),
                                ]
                            );

                            $avatar = get_post_meta($author->ID, '_thumbnail_id', true);
                            if (!empty($avatar)) {
                                update_term_meta($term->term_id, 'avatar', $avatar);
                            }
                        }
                    } else {
                        $author = $this->getCoAuthorUserAuthorBySlug(str_replace('cap-', '', $term->slug));

                        // User author
                        if ($author instanceof WP_User) {
                            $description = get_user_meta($author->ID, 'description', true);

                            update_term_meta($term->term_id, 'first_name', $author->first_name);
                            update_term_meta($term->term_id, 'last_name', $author->last_name);
                            update_term_meta($term->term_id, 'user_email', $author->user_email);
                            update_term_meta($term->term_id, 'user_url', $author->user_url);
                            update_term_meta($term->term_id, 'description', $description);
                            update_term_meta($term->term_id, 'user_id', $author->ID);

                            wp_update_term(
                                $term->term_id,
                                'author',
                                [
                                    'name' => $author->display_name,
                                    'slug' => str_replace('cap-', '', $author->user_nicename),
                                ]
                            );
                        }
                    }

                    update_term_meta($term->term_id, $keyForNotMigrated, 1);
                }
            }

            // nonce: migrate_coauthors
            wp_send_json(
                [
                    'success' => true,
                    'total'   => $this->getTotalOfNotMigratedCoAuthors(),
                ]
            );
        }

        public function syncPostAuthor()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'sync_post_author')) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            $postIdsToSync = get_transient('publishpress_authors_sync_post_author_ids');

            $totalMigrated = 0;

            if (!empty($postIdsToSync)) {
                $chunkSize = isset($_GET['chunkSize']) ? (int)$_GET['chunkSize'] : 10;

                reset($postIdsToSync);
                for ($i = 0; $i < $chunkSize; $i++) {
                    $postId = (int)current($postIdsToSync);

                    if (!empty($postId)) {
                        $authors = get_post_authors($postId);
                        Utils::sync_post_author_column($postId, $authors);
                        $totalMigrated++;
                    }
                    unset($postIdsToSync[key($postIdsToSync)]);
                }
            }

            set_transient('publishpress_authors_sync_post_author_ids', $postIdsToSync, 24 * 60 * 60);

            wp_send_json(
                [
                    'success'       => true,
                    'totalMigrated' => $totalMigrated,
                ]
            );
        }

        public function syncAuthorSlug()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'sync_author_slug')) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            $termToSync = get_transient('publishpress_authors_sync_author_slug_ids');

            $totalMigrated = 0;

            if (!empty($termToSync)) {
                $chunkSize = isset($_GET['chunkSize']) ? (int)$_GET['chunkSize'] : 10;

                reset($termToSync);
                for ($i = 0; $i < $chunkSize; $i++) {
                    $term = current($termToSync);

                    if (!empty($term)) {
                        Utils::sync_author_slug_to_user_nicename([$term]);
                        $totalMigrated++;
                    }
                    unset($termToSync[key($termToSync)]);
                }
            }

            set_transient('publishpress_authors_sync_author_slug_ids', $termToSync, 24 * 60 * 60);

            wp_send_json(
                [
                    'success'       => true,
                    'totalMigrated' => $totalMigrated,
                ]
            );
        }

        public function deactivateCoAuthorsPlus()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'migrate_coauthors')) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            deactivate_plugins('co-authors-plus/co-authors-plus.php');

            // nonce: migrate_coauthors
            wp_send_json(
                [
                    'deactivated' => true,
                ]
            );
        }

        public function finishCoAuthorsMigration()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'migrate_coauthors')) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            // Co-Authors sometimes don't have a taxonomy term for the author, but uses the post_author value instead.
            Installer::createAuthorTermsForLegacyCoreAuthors();
            Installer::createAuthorTermsForPostsWithLegacyCoreAuthors();

            do_action('publishpress_authors_flush_cache');

            // nonce: migrate_coauthors
            wp_send_json(
                [
                    'success' => true,
                ]
            );
        }

        public function finishSyncPostAuthor()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'sync_post_author')) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            delete_transient('publishpress_authors_sync_post_author_ids');

            do_action('publishpress_authors_flush_cache');

            wp_send_json(
                [
                    'success' => true,
                ]
            );
        }

        public function finishSyncAuthorSlug()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'sync_author_slug')) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            delete_transient('publishpress_authors_sync_author_slug_ids');

            update_option('publishpress_multiple_authors_usernicename_sync', 1);

            do_action('publishpress_authors_flush_cache');

            wp_send_json(
                [
                    'success' => true,
                ]
            );
        }

        public function handle_deleted_user($id)
        {
            // Check if we have an author for the user
            $author = Author::get_by_user_id($id);

            if (false !== $author) {
                Author::convert_into_guest_author($author->term_id);
            }
        }

        public function coauthorsMigrationNotice()
        {
            global $pagenow;

            if (!isset($GLOBALS['coauthors_plus']) || empty($GLOBALS['coauthors_plus'])) {
                return;
            }

            $requirements = [
                (isset($_GET['page']) && $_GET['page'] === 'ppma-modules-settings') ? 1 : 0,
                ($pagenow === 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'author') ? 1 : 0
            ];

            if (array_sum($requirements) === 0) {
                return;
            }

            if (! Capability::currentUserCanManageSettings()) {
                return;
            }

            if (get_option('publishpress_authors_dismiss_coauthors_migration_notice') == 1) {
                return;
            }

            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php esc_html_e('It looks like you have Co-Authors Plus installed.', 'publishpress-authors'); ?>
                    <a href="https://publishpress.com/knowledge-base/co-authors-plus/"><?php esc_html_e(
                            'Please click here and read this guide!',
                            'publishpress-authors'
                        ); ?></a>
                    |
                    <a href="<?php echo esc_url(add_query_arg(['action' => 'dismiss_coauthors_migration_notice'])); ?>"><?php esc_html_e(
                            'Dismiss',
                            'publishpress-authors'
                        ); ?></a>
                </p>
            </div>
            <?php
        }

        public function dismissCoAuthorsMigrationNotice()
        {
            if (!isset($_GET['action']) || $_GET['action'] !== 'dismiss_coauthors_migration_notice') {
                return;
            }

            update_option('publishpress_authors_dismiss_coauthors_migration_notice', 1);
        }

        public function permissionsSyncNotice($args = [])
        {
            global $pagenow;

            // Only request the script if also running a PublishPress Permissions version which supports posts query integration
            if (!defined('PRESSPERMIT_VERSION') || version_compare(constant('PRESSPERMIT_VERSION'), '3.4-alpha', '<') || defined('PRESSPERMIT_DISABLE_AUTHORS_JOIN')) {
                return;
            }

            // Display the notice on Authors and Permissions plugin screens
            $is_pp_plugin_page = (isset($_GET['page']) && in_array($_GET['page'], ['ppma-modules-settings', 'presspermit-settings', 'presspermit-groups']))
            || ('edit-tags.php' == $pagenow && !empty($_REQUEST['taxonomy']) && ('author' == $_REQUEST['taxonomy']));

            $requirements = [
                in_array($pagenow, ['plugins.php', 'edit.php', 'edit-tags.php']),
                $is_pp_plugin_page,
            ];

            // Only display the notice on specified admin pages
            if (array_sum($requirements) === 0) {
                return;
            }

            // This request is launching Sync script directly
            if (!empty($_REQUEST['ppma_maint']) && ('ppma_maint=sync-user-login' == $_REQUEST['ppma_maint'])) {
                return;
            }

            // Sync script already run
            if (get_option('publishpress_multiple_authors_usernicename_sync')) {
                return;
            }

            // Notice is non-dismissible on Authors and Permissions plugin screens
            $ignore_dismissal = $is_pp_plugin_page || !empty($args['ignore_dismissal']);

            // This notice is not forced, and has been dismissed
            if (!$ignore_dismissal && get_option('publishpress_authors_dismiss_permissions_sync_notice') == 1) {
                return;
            }

            // User cannot run this script
            if (!current_user_can('ppma_manage_authors')) {
                return;
            }

            ?>
            <div class="updated">
                <p>
                    <?php esc_html_e('PublishPress Authors needs a database update for Permissions integration.', 'publishpress-authors'); ?>
                    &nbsp;<a href="<?php echo esc_url(admin_url('admin.php?page=ppma-modules-settings&ppma_tab=maintenance&ppma_maint=sync-user-login#publishpress-authors-sync-author-slug'));?>"><?php esc_html_e(
                            'Click to run the update now',
                            'publishpress-authors'
                        ); ?></a>
                    <?php if (!$ignore_dismissal):?>
                    &nbsp;|&nbsp;
                    <a href="<?php echo esc_url(add_query_arg(['action' => 'dismiss_permissions_sync_notice'])); ?>"><?php esc_html_e(
                            'Dismiss',
                            'publishpress-authors'
                        ); ?></a>
                    <?php endif;?>
                </p>
            </div>
            <?php
        }

        public function dismissPermissionsSyncNotice()
        {
            if (!isset($_GET['action']) || $_GET['action'] !== 'dismiss_permissions_sync_notice') {
                return;
            }

            update_option('publishpress_authors_dismiss_permissions_sync_notice', 1);
        }

        /**
         * @param $results
         * @param $searchText
         */
        public function publishpressSearchAuthors($results, $searchText)
        {
            $authors = Admin_Ajax::get_possible_authors_for_search($searchText);

            if (!empty($authors)) {
                $results = [];

                foreach ($authors as $author) {
                    $results[] = [
                        'id'   => $author['term'] * -1,
                        'text' => $author['text'],
                    ];
                }
            }

            return $results;
        }

        public function publishpressAuthorCanEditPosts($canEdit, $authorId)
        {
            try {
                if ($authorId > 0) {
                    $author = Author::get_by_user_id($authorId);
                } else {
                    $author = Author::get_by_term_id($authorId * -1);

                    if (is_object($author) && $author->is_guest()) {
                        return true;
                    }
                }

                if (!is_object($author)) {
                    return $canEdit;
                }

                $user = $author->get_user_object();

                if (is_object($user)) {
                    return $user->has_cap('edit_posts');
                }
            } catch (Exception $e) {
            }

            return $canEdit;
        }

        public function publishpressCalendarAfterCreatePost($postId, $postAuthorIds)
        {
            $validPostAuthors = [];

            foreach  ($postAuthorIds as $authorId) {
                if ($authorId > 0) {
                    $author = Author::get_by_user_id($authorId);
                } else {
                    $author = Author::get_by_term_id(abs($authorId));
                }

                if (!empty($author)) {
                    $validPostAuthors[] = $author;
                }
            }

            if (!empty($validPostAuthors)) {
                Utils::set_post_authors($postId, $validPostAuthors);

                do_action('publishpress_authors_flush_cache_for_post', $postId);
            }

            return $postId;
        }

        public function publishpressCalendarDefaultAuthor($defaultAuthor)
        {
            $default_author_setting = isset($this->module->options->default_author_for_new_posts) ?
                $this->module->options->default_author_for_new_posts : '';

            if (!empty($default_author_setting)) {
                $defaultAuthor = -$default_author_setting;
            }

            return $defaultAuthor;
        }

        public function publishpressAuthorFilterSelectedOption($option, $authorId)
        {
            if ($authorId < 0) {
                $author = Author::get_by_term_id($authorId);
                $option = '<option value="' . esc_attr($authorId) . '" selected="selected">' . esc_html($author->display_name) . '</option>';
            }

            return $option;
        }

        public function publishpressPostQueryArgs($args)
        {
            // Add support for guest authors in the post query
            $selectedPostTypes = array_values(Util::get_post_types_for_module($this->module));

            if (isset($args['author']) && $args['author'] !== '' && $args['author'] < 0) {
                $authorId = abs((int)$args['author']);

                if ($authorId > 0) {
                    if (isset($args['tax_query'])) {
                        $args['tax_query']['relation'] = 'AND';
                    }

                    unset($args['author']);

                    $args['tax_query'][] = [
                        'taxonomy' => 'author',
                        'field' => 'id',
                        'terms' => [$authorId],
                    ];
                }
            }

            return $args;
        }

        public function publishpressContentOverviewAuthorColumn($authorName, $post)
        {
            $selectedPostTypes = array_values(Util::get_post_types_for_module($this->module));

            if (in_array($post->post_type, $selectedPostTypes)) {
                $authors = get_post_authors($post->ID);

                $authorNamesArray = [];
                foreach ($authors as $author)
                {
                    $authorNamesArray[] = $author->display_name;
                }

                $authorName = implode(', ', $authorNamesArray);
            }

            return $authorName;
        }

        public function actionSetPostAuthors($postId, $authors)
        {
            Utils::set_post_authors($postId, $authors);

            do_action('publishpress_authors_flush_cache_for_post', $postId);
        }

        public function userProfileUpdate($userId, $oldUserData)
        {
            $author = Author::get_by_user_id($userId);

            if (is_object($author) && !is_wp_error($author)) {
                $user = get_user_by('id', $userId);

                global $wpdb, $wp_rewrite;

                $wpdb->update($wpdb->terms, ['slug' => $user->user_nicename], ['term_id' => $author->term_id]);

                if (is_object($wp_rewrite)) {
                    $wp_rewrite->flush_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rules_flush_rules
                }
            }
        }

        public function preCommentApproved($approved, $commentData)
        {
            $currentUser = wp_get_current_user();

            if (empty($currentUser)) {
                return $approved;
            }

            $currentUserAuthor = Author::get_by_user_id($currentUser->ID);

            if (empty($currentUserAuthor) || !is_object($currentUserAuthor) || is_wp_error($currentUserAuthor)) {
                return $approved;
            }

            if (Utils::isAuthorOfPost($commentData['comment_post_ID'], $currentUserAuthor)) {
                return true;
            }

            return $approved;
        }

        /**
         * Allow author to edit own author profile.
         *
         * @param $caps
         * @param $cap
         * @param $user_id
         * @param $args
         *
         * @return mixed
         */
        public function filter_term_map_meta_cap($caps, $cap, $user_id, $args)
        {
            if (in_array($cap, ['edit_term', 'ppma_manage_authors']) && in_array('ppma_manage_authors', $caps, true)) {

                $term_id = 0;
                if (isset($args[0])) {
                    $term_id = (int)$args[0];
                }else if (isset($_POST['action']) && $_POST['action'] === 'editedtag' && isset($_POST['tag_ID']) && (int)$_POST['tag_ID'] > 0) {
                    //this is needed for when saving the profile as it run through edit-tags.php which user doesn't have permission
                    $term_id = (int)$_POST['tag_ID'];
                }
                $current_author = Author::get_by_user_id(get_current_user_id());

                //allow user to edit own profile.
                if (
                    $term_id > 0 &&
                    $current_author &&
                    is_object($current_author) &&
                    isset($current_author->term_id) &&
                    (int)$current_author->term_id === $term_id
                ) {
                    foreach ($caps as &$item) {
                        if ($item === 'ppma_manage_authors') {
                            $item = 'ppma_edit_own_profile';
                        }
                    }
                    $caps = apply_filters('pp_authors_filter_term_map_meta_cap', $caps, $cap, $user_id, $term_id);
                }
            }

            return $caps;
        }

        /**
         * Author profile edit body class
         *
         * @param string $class
         *
         * @return string
         */
        public function filter_admin_body_class($classes) {

            if (!function_exists('get_current_screen')) {
                return $classes;
            }

            $screen = get_current_screen();

            if ($screen && is_object($screen) && isset($screen->id) && $screen->id === 'edit-author') {

                $current_tag_id =  (isset($_REQUEST['tag_ID']) && (int) $_REQUEST['tag_ID'] > 0) ? (int) $_REQUEST['tag_ID'] : 0;
                $current_author = Author::get_by_user_id(get_current_user_id());

            if (
                $current_author &&
                is_object($current_author) &&
                isset($current_author->term_id) &&
                (int) $current_author->term_id === $current_tag_id
                ) {
                    $classes .= ' own-profile-edit ';
                } elseif (current_user_can(apply_filters('pp_multiple_authors_manage_authors_cap', 'ppma_manage_authors'))) {
                    $classes .= ' authorised-profile-edi ';
                } else {
                    $classes .= ' own-profile-edit ';
                }
            }

            return $classes;
        }

        /**
         * Add authors page post limit
         *
         * @param object $wp_query
         * @return void
         */
        public function authors_taxonomy_post_limit( $wp_query ) {
            if(!is_admin() && $wp_query->is_tax('author') && $wp_query->is_main_query() ) {
                $legacyPlugin             = Factory::getLegacyPlugin();
                $author_pages_posts_limit = (int) $legacyPlugin->modules->multiple_authors->options->author_pages_posts_limit;
                $author_pages_posts_limit = ($author_pages_posts_limit === 0) ? 10 : $author_pages_posts_limit;
                $wp_query->set('posts_per_page', $author_pages_posts_limit);
            }
        }

        /**
         * Add authors template
         *
         * @param string $taxonomy_template
         * @return string
         */
        public function authors_taxonomy_template($taxonomy_template) {

            if (is_tax('author')) {
                $legacyPlugin          = Factory::getLegacyPlugin();
                //look for authors template from theme
                $taxonomy_template = locate_template(['taxonomy-author.php']);
                if (!$taxonomy_template ) {
                    $taxonomy_template = PP_AUTHORS_BASE_PATH . 'src/templates/taxonomy-author.php';
                }
                $author_pages_bio_layout  = (int) $legacyPlugin->modules->multiple_authors->options->author_pages_grid_layout_column;
                $author_pages_bio_layout  = $author_pages_bio_layout === 0 ? 4 : $author_pages_bio_layout;

                //get inline style for grid column
                $inline_style = '';
                for ( $i=1; $i<=$author_pages_bio_layout; $i++ ) {
                    $inline_style .= '.ppma-page-content.grid .ppma-article:nth-child('.$i.')';
                    $inline_style .= ($i === $author_pages_bio_layout) ? '' : ',';
                }
                $inline_style .= '{
                    margin-top: 0;
                }';
                $inline_style .= '.ppma-page-content.grid .ppma-article:nth-child('.$author_pages_bio_layout.'n +1) {clear: both;}';

                $inline_style .= '.ppma-page-content.grid .ppma-article {width: '.((100-8)/$author_pages_bio_layout) .'%;}';

                wp_enqueue_style(
                    'multiple-authors-page-css',
                    PP_AUTHORS_ASSETS_URL . 'css/multiple-authors-page.css',
                    false,
                    PP_AUTHORS_VERSION,
                    'all'
                );
                wp_add_inline_style('multiple-authors-page-css', $inline_style);
            }

            return $taxonomy_template;
        }

        /**
         * Redirect to author's page
         *
         * @param string $taxonomy_template
         * @return string
         */
        public function authors_template_redirect($taxonomy_template) {
            global $wp_query;
            if ( $wp_query->is_author ) {
                $legacyPlugin          = Factory::getLegacyPlugin();
                if (!empty($legacyPlugin) && isset($legacyPlugin->multiple_authors)
                    && isset($legacyPlugin->modules->multiple_authors->options->enable_plugin_author_pages)
                    && $legacyPlugin->modules->multiple_authors->options->enable_plugin_author_pages === 'yes'
                ) {
                    $author_id = (int) get_query_var('author');
                    if ($author_id > 0) {
                        $author_data    = Author::get_by_user_id($author_id);
                    } elseif (isset($wp_query->queried_object->term_id)) {
                        $author_term_id = abs($wp_query->queried_object->term_id);
                        $author_data    = Author::get_by_term_id($author_term_id);
                    }

                    if (is_object($author_data)) {
                        wp_safe_redirect(get_term_link($author_data->term_id), 301);
                        die();
                    }
                }
            }
        }

        /**
         * Prevent guest author login
         *
         * @param $user (null|WP_User|WP_Error) WP_User if the user is authenticated. WP_Error or null otherwise.
         *
         * @return WP_User object if credentials authenticate the user. WP_Error or null otherwise
        */
        public function prevent_guest_author_login($user) {

            if (is_wp_error($user)) {
                return $user;
            }

            if (isset($user->roles) && is_array($user->roles) && in_array('ppma_guest_author', $user->roles)) {
                return new WP_Error('ppma_guest_author_login_denied', __('Guest Author cannot login on the site.', 'publishpress-authors'));
            }

            return $user;
        }

        /**
         * Prevent outgoing email for guest author
         *
         * @param bool $notify
         * @param object $user
         *
         * @return array $args
         */
        public function prevent_guest_author_emails($notify, $user) {
            if ($user && is_object($user) && isset($user->roles) && in_array('ppma_guest_author', (array) $user->roles)) {
                return false;
            }
            return $notify;
        }

        /**
         * Redirect user on plugin activation
         *
         * @return void
         */
        public function redirect_on_activate()
        {
            if (get_option('ppma_activated')) {
                delete_option('ppma_activated');
                wp_redirect(admin_url("edit-tags.php?taxonomy=author&welcome"));
                exit;
              }
        }
    }
}
