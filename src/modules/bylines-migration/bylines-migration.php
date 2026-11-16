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

if (!class_exists('MA_Bylines_Migration')) {
    /**
     * class MA_Bylines_Migration
     */
    class MA_Bylines_Migration extends Module
    {
        const SETTINGS_SLUG = 'ppma-settings';

        const NONCE_ACTION = 'bylines_migration';

        const META_MIGRATED = 'ppma_migrated';
        const META_ERROR_ON_MIGRATING = 'ppma_error_migrating';

        public $module_name = 'bylines_migration';

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
         * Construct the MA_Bylines_Migration class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'                => __('Migrate Bylines Data', 'publishpress-authors'),
                'short_description'    => __('Add migration option for Bylines', 'publishpress-authors'),
                'extended_description' => __('Add migration option for Bylines', 'publishpress-authors'),
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-feedback',
                'slug'                 => 'bylines-migration',
                'default_options'      => [
                    'enabled' => 'on',
                ],
                'options_page'         => false,
                'autoload'             => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters('pp_bylines_migration_default_options', $args['default_options']);

            $legacyPlugin = Factory::getLegacyPlugin();

            $this->module = $legacyPlugin->register_module($this->module_name, $args);

            parent::__construct();
        }

        /**
         * @return array
         */
        private function getNotMigratedPostsId()
        {
            $migratedPostIds = get_option('publishpress_multiple_authors_bylines_migrated_posts', []);

            return [];
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {
            if (is_admin()) {
                add_filter('pp_authors_maintenance_actions', [$this, 'registerMaintenanceAction']);
                add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
            }

            add_action('wp_ajax_migrate_bylines', [$this, 'migrateBylinesData']);
            add_action('wp_ajax_get_bylines_migration_data', [$this, 'getBylinesMigrationData']);
            add_action('wp_ajax_deactivate_bylines', [$this, 'deactivateBylines']);
        }

        public function adminEnqueueScripts()
        {
            wp_enqueue_script(
                'publishpress-authors-bylines-migration',
                PP_AUTHORS_URL . 'src/assets/js/bylines-migration.min.js',
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
                'publishpress-authors-bylines-migration',
                'ppmaBylinesMigration',
                [
                    'notMigratedPostsId' => $this->getNotMigratedPostsId(),
                    'nonce'              => wp_create_nonce(self::NONCE_ACTION),
                    'start_message'      => esc_html__('Collecting data for the migration...', 'publishpress-authors'),
                    'error_message'      => esc_html__('Error: ', 'publishpress-authors'),
                    'completed_message'  => esc_html__('Done! Bylines data was copied and you can deactivate the plugin.', 'publishpress-authors'),
                    'wait_message'       => esc_html__('Please, wait...', 'publishpress-authors'),
                    'progress_message'   => esc_html__('Copying authors\' data...', 'publishpress-authors'),
                    'deactivating_message' => esc_html__('Deactivating Bylines...', 'publishpress-authors'),
                    'deactivated_message'  => esc_html__('Done! Bylines is deactivated.', 'publishpress-authors'),
                    'deactivate_message' => esc_html__('Deactivate Bylines', 'publishpress-authors'),
                    'copy_message'       => esc_html__('Copy Bylines Data', 'publishpress-authors'),
                ]
            );

            wp_enqueue_style(
                'publishpress-authors-bylines-migration-css',
                PP_AUTHORS_URL . 'src/modules/bylines-migration/assets/css/bylines-migration.css',
                false,
                PP_AUTHORS_VERSION
            );
        }

        public function registerMaintenanceAction($actions)
        {
            $actions['copy_bylines_data'] = [
                'title'       => __('Copy Bylines Data', 'publishpress-authors'),
                'description' => 'This action will copy the authors from the plugin Bylines allowing you to migrate to PublishPress Authors without losing any data. This action can be run multiple times.',
                'button_link' => '',
                'after'       => '<div id="publishpress-authors-bylines-migration"></div>',
            ];

            return $actions;
        }

        /**
         * @param int $number
         *
         * @return array|WP_Error
         */
        private function getNotMigratedBylines($number = 5)
        {
            $terms = get_terms(
                [
                    'taxonomy'   => 'byline',
                    'hide_empty' => false,
                    'number'     => $number,
                    'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                        [
                            'key'     => self::META_MIGRATED,
                            'compare' => 'NOT EXISTS',
                        ],
                    ],
                ]
            );

            return $terms;
        }

        /**
         * @return int|string
         */
        private function getTotalOfNotMigratedBylines()
        {
            $terms = $this->getNotMigratedBylines(0);

            if (is_wp_error($terms)) {
                return $terms->get_error_message();
            }

            return count($terms);
        }

        public function getBylinesMigrationData()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), self::NONCE_ACTION)) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            $total = $this->getTotalOfNotMigratedBylines();

            if (!is_numeric($total)) {
                wp_send_json(
                    [
                        'success' => false,
                        'error'   => $total,
                    ]
                );
            } else {
                wp_send_json(
                    [
                        'success' => true,
                        'total'   => $total,
                    ]
                );
            }
        }

        public function migrateBylinesData()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), self::NONCE_ACTION)) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            $termsToMigrate = $this->getNotMigratedBylines(5);

            if (is_wp_error($termsToMigrate)) {
                wp_send_json(
                    [
                        'success' => false,
                        'error'   => $termsToMigrate->get_error_message()
                    ]
                );

                return;
            }

            if (!empty($termsToMigrate)) {
                global $wpdb;

                foreach ($termsToMigrate as $bylinesTerm) {
                    $description = get_term_meta($bylinesTerm->term_id, 'description', true);
                    $firstName   = get_term_meta($bylinesTerm->term_id, 'first_name', true);
                    $lastName    = get_term_meta($bylinesTerm->term_id, 'last_name', true);
                    $userEmail   = get_term_meta($bylinesTerm->term_id, 'user_email', true);
                    $userUrl     = get_term_meta($bylinesTerm->term_id, 'user_url', true);

                    $author = Author::create(
                        [
                            'display_name' => $bylinesTerm->name,
                            'slug'         => str_replace('cap-', '', $bylinesTerm->slug),
                        ]
                    );

                    if (is_wp_error($author)) {
                        update_term_meta(
                            $bylinesTerm->term_id,
                            self::META_ERROR_ON_MIGRATING,
                            $author->get_error_message()
                        );
                        update_term_meta($bylinesTerm->term_id, self::META_MIGRATED, 1);

                        continue;
                    }

                    update_term_meta($author->term_id, 'first_name', $firstName);
                    update_term_meta($author->term_id, 'last_name', $lastName);
                    update_term_meta($author->term_id, 'user_email', $userEmail);
                    update_term_meta($author->term_id, 'user_url', $userUrl);
                    update_term_meta($author->term_id, 'description', $description);

                    $userId = get_term_meta($bylinesTerm->term_id, 'user_id', true);
                    if (!empty($userId)) {
                        update_term_meta($author->term_id, 'user_id', $userId);
                    }

                    // Migrate the posts' terms relationship.
                    $wpdb->get_results(
                        $wpdb->prepare(
                            "
                            INSERT INTO {$wpdb->term_relationships}
                                SELECT object_id, %s, term_order
                                FROM {$wpdb->term_relationships}
                                WHERE term_taxonomy_id = %s
                            
                            ",
                            $author->term_id,
                            $bylinesTerm->term_id
                        )
                    );

                    update_term_meta($bylinesTerm->term_id, self::META_MIGRATED, 1);
                }
            }

            wp_send_json(
                [
                    'success' => true,
                    'total'   => $this->getTotalOfNotMigratedBylines(),
                ]
            );
        }

        public function deactivateBylines()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), self::NONCE_ACTION)) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            deactivate_plugins('bylines/bylines.php');

            wp_send_json(
                [
                    'deactivated' => true,
                ]
            );
        }
    }
}
