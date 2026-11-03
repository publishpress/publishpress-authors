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
use MultipleAuthors\Classes\Legacy\Module;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Factory;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MA_Byline_Migration')) {
    /**
     * class MA_Byline_Migration
     */
    class MA_Byline_Migration extends Module
    {
        const SETTINGS_SLUG = 'ppma-settings';

        const NONCE_ACTION = 'byline_migration';

        public $module_name = 'byline_migration';

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

        private $eddAPIUrl = 'https://publishpress.com';


        /**
         * Construct the MA_Byline_Migration class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'                => __('Migrate Byline Data', 'publishpress-authors'),
                'short_description'    => __('Add migration option for Byline', 'publishpress-authors'),
                'extended_description' => __('Add migration option for Byline', 'publishpress-authors'),
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-feedback',
                'slug'                 => 'byline-migration',
                'default_options'      => [
                    'enabled' => 'on',
                ],
                'options_page'         => false,
                'autoload'             => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters('pp_byline_migration_default_options', $args['default_options']);

            $legacyPlugin = Factory::getLegacyPlugin();


            $this->module = $legacyPlugin->register_module($this->module_name, $args);

            parent::__construct();
        }

        /**
         * @return array
         */
        private function getNotMigratedPostsId()
        {
            $migratedPostIds = get_option('publishpress_multiple_authors_byline_migrated_posts', []);

            return [];
        }

        /**
         * Initialize the module.
         */
        public function init()
        {
            if (is_admin()) {
                add_filter('pp_authors_maintenance_actions', [$this, 'registerMaintenanceAction']);
                add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
            }

            add_action('wp_ajax_migrate_byline', [$this, 'migrateBylineData']);
            add_action('wp_ajax_get_byline_migration_data', [$this, 'getBylineMigrationData']);
            add_action('wp_ajax_deactivate_byline', [$this, 'deactivateByline']);
        }

        public function adminEnqueueScripts()
        {
            wp_enqueue_script(
                'publishpress-authors-byline-migration',
                PP_AUTHORS_URL . 'src/assets/js/byline-migration.min.js',
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
                'publishpress-authors-byline-migration',
                'ppmaBylineMigration',
                [
                    'notMigratedPostsId' => $this->getNotMigratedPostsId(),
                    'nonce'              => wp_create_nonce(self::NONCE_ACTION),
                    'start_message'      => esc_html__('Collecting data for the migration...', 'publishpress-authors'),
                    'error_message'      => esc_html__('Error: ', 'publishpress-authors'),
                    'completed_message'  => esc_html__('Done! Byline data was copied and you can deactivate the plugin.', 'publishpress-authors'),
                    'wait_message'       => esc_html__('Please, wait...', 'publishpress-authors'),
                    'progress_message'   => esc_html__('Copying authors\' data...', 'publishpress-authors'),
                    'deactivating_message' => esc_html__('Deactivating Byline...', 'publishpress-authors'),
                    'deactivated_message'  => esc_html__('Done! Byline is deactivated.', 'publishpress-authors'),
                    'deactivate_message' => esc_html__('Deactivate Byline', 'publishpress-authors'),
                    'copy_message'       => esc_html__('Copy Byline Data', 'publishpress-authors'),
                ]
            );

            wp_enqueue_style(
                'publishpress-authors-byline-migration-css',
                PP_AUTHORS_URL . 'src/modules/byline-migration/assets/css/byline-migration.css',
                false,
                PP_AUTHORS_VERSION
            );
        }

        public function registerMaintenanceAction($actions)
        {
            $actions['copy_byline_data'] = [
                'title'       => __('Copy Byline Data', 'publishpress-authors'),
                'description' => 'This action will copy the authors from the plugin Byline allowing you to migrate to PublishPress Authors without losing any data. This action can be run multiple times.',
                'button_link' => '',
                'after'       => '<div id="publishpress-authors-byline-migration"></div>',
            ];

            return $actions;
        }

        private function getTotalOfNotMigratedByline()
        {
            $terms = get_terms(
                [
                    'taxonomy'   => 'byline',
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

        public function getBylineMigrationData()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), self::NONCE_ACTION)) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            // nonce: migrate_coauthors
            wp_send_json(
                [
                    'total' => $this->getTotalOfNotMigratedByline(),
                ]
            );
        }

        public function migrateBylineData()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), self::NONCE_ACTION)) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            $keyForNotMigrated = 'ppma-migrated';

            $termsToMigrate = get_terms(
                [
                    'taxonomy'   => 'byline',
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
                    $author = Author::create(
                        [
                            'display_name' => $term->name,
                            'slug'         => $term->slug,
                        ]
                    );

                    update_term_meta($author->term_id, 'description', $term->description);

                    // Migrate the posts for the author
                    $posts = get_posts(
                        [
                            'numberposts' => -1,
                            'tax_query'   => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                                [
                                    'taxonomy' => 'byline',
                                    'terms'    => $term->term_id,
                                ]
                            ]
                        ]
                    );

                    if (!empty($posts)) {
                        foreach ($posts as $post) {
                            wp_add_object_terms($post->ID, $author->term_id, 'author');
                        }
                    }

                    update_term_meta($term->term_id, $keyForNotMigrated, 1);
                }
            }

            wp_send_json(
                [
                    'success' => true,
                    'total'   => $this->getTotalOfNotMigratedByline(),
                ]
            );
        }

        public function deactivateByline()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), self::NONCE_ACTION)) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            deactivate_plugins('byline/byline.php');

            wp_send_json(
                [
                    'deactivated' => true,
                ]
            );
        }
    }
}
