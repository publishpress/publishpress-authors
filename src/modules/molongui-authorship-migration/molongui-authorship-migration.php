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

use MultipleAuthors\Classes\Legacy\Module;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Capability;
use MultipleAuthors\Factory;

if (!class_exists('MA_Molongui_Authorship_Migration')) {
    /**
     * class MA_Molongui_Authorship_Migration
     */
    class MA_Molongui_Authorship_Migration extends Module
    {
        const SETTINGS_SLUG = 'ppma-settings';
        const NONCE_ACTION = 'molongui_authorship_migration';
        const META_MIGRATED = 'ppma_molongui_migrated';
        const META_ERROR_ON_MIGRATING = 'ppma_molongui_error_migrating';

        public $module_name = 'molongui_authorship_migration';

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
         * Construct the MA_Molongui_Authorship_Migration class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title' => __('Migrate Molongui Data', 'publishpress-authors'),
                'short_description' => __('Add migration option for Molongui Authorship', 'publishpress-authors'),
                'extended_description' => __('Add migration option for Molongui Authorship', 'publishpress-authors'),
                'module_url' => $this->module_url,
                'icon_class' => 'dashicons dashicons-feedback',
                'slug' => 'molongui-migration',
                'default_options' => [
                    'enabled' => 'on',
                ],
                'options_page' => false,
                'autoload' => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters('pp_molongui_authorship_migration_default_options', $args['default_options']);

            $legacyPlugin = Factory::getLegacyPlugin();
            $this->module = $legacyPlugin->register_module($this->module_name, $args);

            parent::__construct();
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {
            if (is_admin()) {
                add_action('admin_init', [$this, 'dismissMolonguiAuthorshipMigrationNotice']);
                add_action('admin_notices', [$this, 'molonguiAuthorshipMigrationNotice']);
                add_filter('pp_authors_maintenance_actions', [$this, 'registerMaintenanceAction']);
                add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
            }

            add_action('wp_ajax_migrate_molongui_authorship', [$this, 'migrateMolonguiAuthorshipData']);
            add_action('wp_ajax_get_molongui_authorship_migration_data', [$this, 'getMolonguiAuthorshipMigrationData']);
            add_action('wp_ajax_deactivate_molongui_authorship', [$this, 'deactivateMolonguiAuthorship']);
        }

        public function adminEnqueueScripts()
        {
            if (isset($_GET['page']) && in_array($_GET['page'], ['ppma-modules-settings', 'ppma-author-pages'])) {
                wp_enqueue_script(
                    'publishpress-authors-molongui-authorship-migration',
                    PP_AUTHORS_URL . 'src/assets/js/molongui-authorship-migration.min.js',
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
                    'publishpress-authors-molongui-authorship-migration',
                    'ppmaMolonguiAuthorshipMigration',
                    [
                        'nonce'              => wp_create_nonce(self::NONCE_ACTION),
                        'start_message'      => esc_html__('Collecting data for the migration...', 'publishpress-authors'),
                        'error_message'      => esc_html__('Error: ', 'publishpress-authors'),
                        'completed_message'  => esc_html__('Done! Molongui Authorship data was copied and you can deactivate the plugin.', 'publishpress-authors'),
                        'wait_message'       => esc_html__('Please, wait...', 'publishpress-authors'),
                        'progress_message'   => esc_html__('Migrating data...', 'publishpress-authors'),
                        'deactivating_message' => esc_html__('Deactivating Molongui Authorship...', 'publishpress-authors'),
                        'deactivated_message'  => esc_html__('Done! Molongui Authorship is deactivated.', 'publishpress-authors'),
                        'deactivate_message' => esc_html__('Deactivate Molongui Authorship', 'publishpress-authors'),
                        'copy_message'       => esc_html__('Copy Molongui Authorship Data', 'publishpress-authors'),
                    ]
                );
            }
        }

        public function registerMaintenanceAction($actions)
        {
            $actions['copy_molongui_authorship_data'] = [
                'title' => esc_html__('Copy Molongui Authorship Data', 'publishpress-authors'),
                'description' => esc_html__('This action will copy the authors from the plugin Molongui Authorship allowing you to migrate to PublishPress Authors without losing any data including post author relation. This action can be run multiple times.', 'publishpress-authors'),
                'button_link' => '',
                'after' => '<div id="publishpress-authors-molongui-authorship-migration"></div>',
            ];

            return $actions;
        }

        public function molonguiAuthorshipMigrationNotice()
        {
            global $pagenow;

            if (!Utils::isMolonguiAuthorshipActivated()) {
                return;
            }

            if ($pagenow !== 'edit-tags.php' || !isset($_GET['taxonomy']) || $_GET['taxonomy'] !== 'author') {
                return;
            }

            if (!Capability::currentUserCanManageSettings()) {
                return;
            }

            if (get_option('publishpress_authors_dismiss_molongui_authorship_migration_notice') == 1) {
                return;
            }

            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php esc_html_e('It looks like you have Molongui Authorship installed.', 'publishpress-authors'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ppma-modules-settings#ppma-tab-maintenance')); ?>"><?php esc_html_e(
                           'Please click here to migrate to PublishPress Authors',
                           'publishpress-authors'
                       ); ?></a>
                    |
                    <a href="<?php echo esc_url(add_query_arg(['action' => 'dismiss_molongui_authorship_migration_notice'])); ?>"><?php esc_html_e(
                             'Dismiss',
                             'publishpress-authors'
                         ); ?></a>
                </p>
            </div>
            <?php
        }

        public function dismissMolonguiAuthorshipMigrationNotice()
        {
            if (!isset($_GET['action']) || $_GET['action'] !== 'dismiss_molongui_authorship_migration_notice') {
                return;
            }

            update_option('publishpress_authors_dismiss_molongui_authorship_migration_notice', 1);
        }

        /**
         * Get Molongui guest authors that haven't been migrated yet
         *
         * @param int $number Number of authors to retrieve
         * @return array|WP_Error
         */
        private function getNotMigratedMolonguiAuthors($number = 5)
        {
            global $wpdb;

            $posts = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT p.ID, p.post_title, p.post_content, p.post_name
                     FROM {$wpdb->posts} p
                     LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
                     WHERE p.post_type = %s
                     AND p.post_status = 'publish'
                     AND pm.meta_value IS NULL
                     LIMIT %d",
                    self::META_MIGRATED,
                    'guest_author',
                    $number
                )
            );

            if (empty($posts)) {
                return [];
            }

            $authors = [];
            foreach ($posts as $post) {
                // Get all meta for this guest author
                $meta = get_post_meta($post->ID);

                $authors[] = (object) [
                    'ID' => $post->ID,
                    'display_name' => $post->post_title,
                    'slug' => $post->post_name,
                    'description' => $post->post_content,
                    'first_name' => isset($meta['_molongui_guest_author_first_name'][0]) ? $meta['_molongui_guest_author_first_name'][0] : '',
                    'last_name' => isset($meta['_molongui_guest_author_last_name'][0]) ? $meta['_molongui_guest_author_last_name'][0] : '',
                    'user_email' => isset($meta['_molongui_guest_author_mail'][0]) ? $meta['_molongui_guest_author_mail'][0] : '',
                    'user_url' => isset($meta['_molongui_guest_author_web'][0]) ? $meta['_molongui_guest_author_web'][0] : '',
                    'job_title' => isset($meta['_molongui_guest_author_job'][0]) ? $meta['_molongui_guest_author_job'][0] : '',
                    'avatar' => get_post_thumbnail_id($post->ID),
                ];
            }

            return $authors;
        }

        /**
         * Get total count of unmigrated Molongui data (both users and guests)
         */
        private function getTotalOfNotMigratedMolongui()
        {
            global $wpdb;

            // Count guest authors
            $guest_count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(p.ID)
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
                    WHERE p.post_type = %s
                    AND p.post_status = 'publish'
                    AND pm.meta_value IS NULL",
                    self::META_MIGRATED,
                    'guest_author'
                )
            );

            // Count posts with Molongui authorship that haven't been migrated
            $post_count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT p.ID)
                    FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
                    WHERE pm.meta_key IN ('_molongui_author', '_molongui_main_author')
                    AND pm2.meta_value IS NULL",
                    'ppma_post_migrated'
                )
            );

            return (int) ($guest_count + $post_count);
        }

        public function getMolonguiAuthorshipMigrationData()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), self::NONCE_ACTION)) {
                wp_send_json_error(null, 403);
            }

            if (!Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            wp_send_json(
                [
                    'total' => $this->getTotalOfNotMigratedMolongui(),
                ]
            );
        }

        public function migrateMolonguiAuthorshipData()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), self::NONCE_ACTION)) {
                wp_send_json_error(null, 403);
            }

            if (!Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            // First migrate guest authors
            $authorsToMigrate = $this->getNotMigratedMolonguiAuthors(3);

            if (!empty($authorsToMigrate)) {
                foreach ($authorsToMigrate as $molonguiAuthor) {
                    $this->migrateGuestAuthor($molonguiAuthor);
                }
            }

            // Then migrate post relationships
            $postsToMigrate = $this->getNotMigratedMolonguiPosts(2);

            if (!empty($postsToMigrate)) {
                foreach ($postsToMigrate as $post) {
                    $this->migratePostAuthorship($post->ID);
                }
            }

            wp_send_json([
                'success' => true,
                'total' => $this->getTotalOfNotMigratedMolongui(),
            ]);
        }

        /**
         * Get posts with Molongui authorship that haven't been migrated
         */
        private function getNotMigratedMolonguiPosts($number = 5)
        {
            global $wpdb;

            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DISTINCT p.ID, p.post_title
                    FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
                    WHERE pm.meta_key IN ('_molongui_author', '_molongui_main_author')
                    AND pm2.meta_value IS NULL
                    LIMIT %d",
                    'ppma_post_migrated',
                    $number
                )
            );
        }

        /**
         * Migrate post authorship from Molongui
         */
        private function migratePostAuthorship($post_id)
        {
            global $wpdb;

            // Get all Molongui author references for this post
            $author_refs = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT meta_value FROM {$wpdb->postmeta}
                    WHERE post_id = %d AND meta_key IN ('_molongui_author', '_molongui_main_author')",
                    $post_id
                )
            );

            $ppma_authors = [];

            foreach (array_unique($author_refs) as $ref) {
                if (empty($ref)) {
                    continue;
                }

                // Parse reference format: "user-123" or "guest-456"
                $parts = explode('-', $ref);

                if (count($parts) !== 2) {
                    continue;
                }

                $type = $parts[0];
                $id = $parts[1];

                if ($type === 'user') {
                    $ppma_author_id = $this->getOrCreateUserAuthor($id);
                } else if ($type === 'guest') {
                    $ppma_author_id = $this->findMigratedGuestAuthor($id);
                }

                if ($ppma_author_id) {
                    // Get the Author object instead of just the term ID
                    $author = Author::get_by_term_id($ppma_author_id);
                    if ($author) {
                        $ppma_authors[] = $author;
                    }
                }
            }

            if (!empty($ppma_authors)) {
                Utils::set_post_authors($post_id, $ppma_authors);
            }

            update_post_meta($post_id, 'ppma_post_migrated', 1);
        }

        /**
         * Get or create PublishPress author for WordPress user
         */
        private function getOrCreateUserAuthor($user_id)
        {
            $user = get_user_by('id', $user_id);
            if (!$user) {
                return false;
            }

            // Check if author already exists
            $existing = get_terms([
                'taxonomy' => 'author',
                'meta_query' => [
                    [
                        'key' => 'user_id',
                        'value' => $user_id,
                        'compare' => '='
                    ]
                ],
                'hide_empty' => false
            ]);

            if (!empty($existing)) {
                return $existing[0]->term_id;
            }

            // Create new author for user
            $author = Author::create([
                'display_name' => $user->display_name,
                'slug' => $user->user_nicename,
            ]);

            if (!is_wp_error($author)) {
                update_term_meta($author->term_id, 'user_id', $user_id);
                update_term_meta($author->term_id, 'first_name', $user->first_name);
                update_term_meta($author->term_id, 'last_name', $user->last_name);
                update_term_meta($author->term_id, 'user_email', $user->user_email);
                update_term_meta($author->term_id, 'user_url', $user->user_url);
                update_term_meta($author->term_id, 'description', $user->description);

                return $author->term_id;
            }

            return false;
        }

        /**
         * Migrate a single guest author from Molongui
         *
         * @param object $molonguiAuthor
         */
        private function migrateGuestAuthor($molonguiAuthor)
        {
            $existing_author = get_term_by('slug', $molonguiAuthor->slug, 'author');

            if ($existing_author) {
                // Use existing author
                $author = (object) ['term_id' => $existing_author->term_id];
            } else {
                $author = Author::create(
                    [
                        'display_name' => $molonguiAuthor->display_name,
                        'slug' => $molonguiAuthor->slug,
                    ]
                );

                if (is_wp_error($author)) {
                    update_post_meta(
                        $molonguiAuthor->ID,
                        self::META_ERROR_ON_MIGRATING,
                        $author->get_error_message()
                    );
                    update_post_meta($molonguiAuthor->ID, self::META_MIGRATED, 1);
                    return;
                }
            }

            // Set author metadata
            update_term_meta($author->term_id, 'first_name', $molonguiAuthor->first_name);
            update_term_meta($author->term_id, 'last_name', $molonguiAuthor->last_name);
            update_term_meta($author->term_id, 'user_email', $molonguiAuthor->user_email);
            update_term_meta($author->term_id, 'user_url', $molonguiAuthor->user_url);
            update_term_meta($author->term_id, 'description', $molonguiAuthor->description);

            if (!empty($molonguiAuthor->job_title)) {
                update_term_meta($author->term_id, 'job_title', $molonguiAuthor->job_title);
            }

            // Handle avatar
            if (!empty($molonguiAuthor->avatar)) {
                update_term_meta($author->term_id, 'avatar', $molonguiAuthor->avatar);
            }

            // Store reference to original Molongui author
            update_term_meta($author->term_id, 'molongui_guest_author_id', $molonguiAuthor->ID);

            // Mark as migrated
            update_post_meta($molonguiAuthor->ID, self::META_MIGRATED, 1);
        }

        /**
         * Find migrated guest author by original Molongui ID
         */
        private function findMigratedGuestAuthor($molongui_id)
        {
            $terms = get_terms([
                'taxonomy' => 'author',
                'meta_query' => [
                    [
                        'key' => 'molongui_guest_author_id',
                        'value' => $molongui_id,
                        'compare' => '='
                    ]
                ],
                'hide_empty' => false
            ]);

            return !empty($terms) ? $terms[0]->term_id : false;
        }

        /**
         * Deactivate Molongui plugin
         */
        public function deactivateMolonguiAuthorship()
        {
            if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_key($_GET['nonce']), self::NONCE_ACTION)) {
                wp_send_json_error(null, 403);
            }

            if (! Capability::currentUserCanManageSettings()) {
                wp_send_json_error(null, 403);
            }

            // Check if plugin is already deactivated
            if (!is_plugin_active('molongui-authorship/molongui-authorship.php')) {
                wp_send_json(['success' => true, 'message' => 'Plugin already deactivated']);
                return;
            }

            // Add a transient to prevent duplicate deactivation attempts
            $deactivation_key = 'molongui_deactivation_' . get_current_user_id();
            if (get_transient($deactivation_key)) {
                wp_send_json_error('Deactivation already in progress', 409);
                return;
            }

            set_transient($deactivation_key, true, 30);

            try {
                deactivate_plugins('molongui-authorship/molongui-authorship.php');
                delete_transient($deactivation_key);
                wp_send_json(['success' => true]);
            } catch (Exception $e) {
                delete_transient($deactivation_key);
                wp_send_json_error('Deactivation failed: ' . $e->getMessage());
            }
        }
    }
}