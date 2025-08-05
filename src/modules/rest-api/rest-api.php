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
use MultipleAuthors\Classes\Legacy\Util;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Author_Editor;
use MultipleAuthors\Factory;

if (!class_exists('MA_REST_API')) {
    /**
     * class MA_REST_API
     */
    class MA_REST_API extends Module
    {
        const SETTINGS_SLUG = 'ppma-settings';

        public $module_name = 'rest_api';

        /**
         * Instance for the module
         *
         * @var stdClass
         */
        public $module;
        public $module_url;


        /**
         * Construct the MA_REST_API class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title' => __('Rest API', 'publishpress-authors'),
                'short_description' => __('Rest API support', 'publishpress-authors'),
                'extended_description' => __('Rest API support', 'publishpress-authors'),
                'module_url' => $this->module_url,
                'icon_class' => 'dashicons dashicons-feedback',
                'slug' => 'rest-api',
                'default_options' => [
                    'enabled' => 'on'
                ],
                'options_page' => false,
                'autoload' => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters('pp_rest_api_default_options', $args['default_options']);

            $legacyPlugin = Factory::getLegacyPlugin();

            $this->module = $legacyPlugin->register_module($this->module_name, $args);

            parent::__construct();
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {
            add_action('rest_api_init', [$this, 'initRestAPI']);
        }

        public function initRestAPI()
        {
            register_rest_field(
                'post',
                'authors',
                [
                    'get_callback' => [$this, 'getPostAuthorsCallback'],
                    'schema' => [
                        'description' => __('Authors.'),
                        'type' => 'array'
                    ],
                ]
            );

            register_rest_route('publishpress-authors/v1', '/authors', [
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'createAuthorCallback'],
                    'permission_callback' => [$this, 'checkCreatePermissions'],
                    'args' => $this->getCreateAuthorArgs()
                ]
            ]);

            register_rest_route('publishpress-authors/v1', '/authors/(?P<id>\d+)', [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'getAuthorCallback'],
                    'permission_callback' => [$this, 'checkReadPermissions'],
                    'args' => [
                        'id' => [
                            'required' => true,
                            'type' => 'integer',
                            'sanitize_callback' => 'absint'
                        ]
                    ]
                ],
                [
                    'methods' => 'PUT',
                    'callback' => [$this, 'updateAuthorCallback'],
                    'permission_callback' => [$this, 'checkUpdatePermissions'],
                    'args' => $this->getUpdateAuthorArgs()
                ],
                [
                    'methods' => 'PATCH',
                    'callback' => [$this, 'patchAuthorCallback'],
                    'permission_callback' => [$this, 'checkUpdatePermissions'],
                    'args' => $this->getPatchAuthorArgs()
                ]
            ]);
        }

        public function checkCreatePermissions($request)
        {
            return current_user_can('ppma_manage_authors');
        }

        public function checkUpdatePermissions($request)
        {
            return current_user_can('ppma_manage_authors');
        }

        public function checkReadPermissions($request)
        {
            return true;
        }

        public function getCreateAuthorArgs()
        {
            return [
                'display_name' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return !empty(trim($param));
                    }
                ],
                'user_email' => [
                    'required' => false,
                    'type' => 'string',
                    'format' => 'email',
                    'sanitize_callback' => 'sanitize_email'
                ],
                'user_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ],
                'slug' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_title'
                ],
                'author_fields' => [
                    'required' => false,
                    'type' => 'object',
                    'sanitize_callback' => function ($param) {
                        return is_array($param) ? $param : [];
                    }
                ]
            ];
        }

        public function getUpdateAuthorArgs()
        {
            $args = $this->getCreateAuthorArgs();
            $args['display_name']['required'] = false;
            return $args;
        }

        public function getPatchAuthorArgs()
        {
            return $this->getUpdateAuthorArgs();
        }

        public function getPostAuthorsCallBack($postData)
        {
            $legacyPlugin = Factory::getLegacyPlugin();

            $selectedPostTypes = array_values(Util::get_post_types_for_module($legacyPlugin->modules->multiple_authors));

            $post = get_post($postData['id']);

            if (!in_array($post->post_type, $selectedPostTypes)) {
                return [];
            }

            $authors = get_post_authors($postData['id']);

            $authorsData = [];

            $authors_fields = Author_Editor::get_fields(false);
            $authors_fields = apply_filters('multiple_authors_author_fields', $authors_fields, false);
            $authors_fields = array_keys($authors_fields);

            $excluded_fields = ['user_id', 'user_email', 'avatar'];
            $excluded_fields = apply_filters('ppma_rest_api_authors_meta_excluded_fields', $excluded_fields);

            foreach ($authors as $author) {
                $isGuest = 0;
                if (is_a($author, Author::class)) {
                    $isGuest = $author->is_guest() ? 1 : 0;
                }

                if (!is_object($author) || is_wp_error($author)) {
                    continue;
                }

                //add default fields
                $currentAuthorData = [
                    'term_id' => (int) $author->term_id,
                    'user_id' => (int) $author->user_id,
                    'is_guest' => $isGuest,
                    'slug' => $author->slug,
                    'display_name' => $author->display_name,
                    'avatar_url' => $author->get_avatar_url(),
                ];

                //add other fields
                foreach ($authors_fields as $authors_field) {
                    if (in_array($authors_field, $excluded_fields)) {
                        continue;
                    }
                    $currentAuthorData[$authors_field] = $author->$authors_field;
                }

                $authorsData[] = $currentAuthorData;
            }

            return $authorsData = apply_filters('ppma_rest_api_authors_data', $authorsData);
        }
        public function createAuthorCallback($request)
        {
            $legacyPlugin = Factory::getLegacyPlugin();
            $enable_guest_author_user = $legacyPlugin->modules->multiple_authors->options->enable_guest_author_user === 'yes';
            $enable_guest_author_account = $legacyPlugin->modules->multiple_authors->options->enable_guest_author_acount === 'yes';

            $params = $request->get_params();
            $display_name = $params['display_name'];
            $user_email = !empty($params['user_email']) ? $params['user_email'] : '';
            $user_id = !empty($params['user_id']) ? $params['user_id'] : 0;
            $author_fields = !empty($params['author_fields']) ? $params['author_fields'] : [];

            if ($user_id > 0) {
                $author_type = 'existing_user';
            } elseif (!empty($user_email)) {
                $author_type = 'new_user';
            } else {
                $author_type = 'guest_author';
            }

            $slug = !empty($params['slug']) ? $params['slug'] : sanitize_title($display_name);

            if ($author_type === 'guest_author' && !$enable_guest_author_user) {
                return new WP_Error(
                    'guest_authors_disabled',
                    __('Guest authors without user accounts is not enabled.', 'publishpress-authors'),
                    ['status' => 400]
                );
            }

            if ($author_type === 'new_user' && !$enable_guest_author_account) {
                return new WP_Error(
                    'guest_user_accounts_disabled',
                    __('Guest author with user accounts is not enabled.', 'publishpress-authors'),
                    ['status' => 400]
                );
            }

            if (get_term_by('slug', $slug, 'author')) {
                return new WP_Error(
                    'slug_exists',
                    __('An author with this slug already exists.', 'publishpress-authors'),
                    ['status' => 409]
                );
            }

            if ($author_type === 'existing_user') {
                $existing_author = Author::get_by_user_id($user_id);
                $remove_single_user_map_restriction = publishpress_authors_remove_single_user_map_restriction();

                if (!$remove_single_user_map_restriction && $existing_author) {
                    return new WP_Error(
                        'user_already_mapped',
                        __('This WordPress user is already mapped to another author.', 'publishpress-authors'),
                        ['status' => 409]
                    );
                }
            }

            try {
                $mapped_user_id = 0;

                if ($author_type === 'new_user') {
                    if (!get_role('ppma_guest_author')) {
                        add_role('ppma_guest_author', 'Guest Author', []);
                    }

                    $user_data = [
                        'user_login' => $slug,
                        'display_name' => $display_name,
                        'user_email' => $user_email,
                        'user_pass' => wp_generate_password(),
                        'role' => 'ppma_guest_author',
                    ];

                    $new_user_id = wp_insert_user($user_data);

                    if (is_wp_error($new_user_id)) {
                        return new WP_Error(
                            'user_creation_failed',
                            $new_user_id->get_error_message(),
                            ['status' => 500]
                        );
                    }

                    $mapped_user_id = $new_user_id;
                } elseif ($author_type === 'existing_user') {
                    $mapped_user_id = $user_id;
                }

                $term_data = wp_insert_term($display_name, 'author', [
                    'slug' => $slug
                ]);

                if (is_wp_error($term_data)) {
                    return new WP_Error(
                        'author_creation_failed',
                        $term_data->get_error_message(),
                        ['status' => 500]
                    );
                }

                $term_id = $term_data['term_id'];

                if ($mapped_user_id > 0) {
                    update_term_meta($term_id, 'user_id', $mapped_user_id);
                    update_term_meta($term_id, 'user_id_' . $mapped_user_id, $mapped_user_id);
                }

                if (!empty($user_email)) {
                    update_term_meta($term_id, 'user_email', $user_email);
                }

                $this->setAuthorFields($term_id, $author_fields);

                $author = Author::get_by_term_id($term_id);

                if (!$author) {
                    return new WP_Error(
                        'author_retrieval_failed',
                        __('Author was created but could not be retrieved.', 'publishpress-authors'),
                        ['status' => 500]
                    );
                }

                return $this->formatAuthorResponse($author, $author_type, 201);

            } catch (Exception $e) {
                return new WP_Error(
                    'unexpected_error',
                    $e->getMessage(),
                    ['status' => 500]
                );
            }
        }

        public function getAuthorCallback($request)
        {
            $author_id = $request->get_param('id');
            $author = Author::get_by_term_id($author_id);

            if (!$author) {
                return new WP_Error(
                    'author_not_found',
                    __('Author not found.', 'publishpress-authors'),
                    ['status' => 404]
                );
            }

            return $this->formatAuthorResponse($author, 'existing', 200);
        }

        public function updateAuthorCallback($request)
        {
            $author_id = $request->get_param('id');
            $author = Author::get_by_term_id($author_id);

            if (!$author) {
                return new WP_Error(
                    'author_not_found',
                    __('Author not found.', 'publishpress-authors'),
                    ['status' => 404]
                );
            }

            $params = $request->get_params();
            $author_fields = !empty($params['author_fields']) ? $params['author_fields'] : [];

            return $this->updateExistingAuthor($author, $params, $author_fields);
        }

        public function patchAuthorCallback($request)
        {
            $author_id = $request->get_param('id');
            $author = Author::get_by_term_id($author_id);

            if (!$author) {
                return new WP_Error(
                    'author_not_found',
                    __('Author not found.', 'publishpress-authors'),
                    ['status' => 404]
                );
            }

            $params = $request->get_params();
            $author_fields = !empty($params['author_fields']) ? $params['author_fields'] : [];

            return $this->updateExistingAuthor($author, $params, $author_fields, true);
        }

        private function updateExistingAuthor($author, $params, $author_fields, $partial_update = false)
        {
            $term_id = $author->term_id;

            $author_user_id = $author->user_id;

            if (!empty($author_user_id)) {
                $user = get_user_by('id', $author_user_id);

                if ($user && (int)$author_user_id !== get_current_user_id()) {
                    // Prevent editing administrators completely
                    if (in_array('administrator', $user->roles)) {
                        return new WP_Error(
                            'cannot_edit_administrator',
                            __('You cannot edit author mapped to administrator account.', 'publishpress-authors'),
                            ['status' => 403]
                        );
                    }

                    // Check if the user lacks the necessary capabilities
                    if (!current_user_can(get_taxonomy('author')->cap->manage_terms)
                        || !current_user_can('edit_user', $author_user_id)) {
                        return new WP_Error(
                            'insufficient_permissions',
                            __('You do not have permission to edit this author.', 'publishpress-authors'),
                            ['status' => 403]
                        );
                    }
                }
            }

            // update user args data
            $updated_args = [];
            if ($author_user_id) {
                $updated_args['ID'] = $author_user_id;
            }

            // update terms args data
            $update_data = [];
            if (!empty($params['display_name'])) {
                $update_data['name'] = $params['display_name'];
                if ($author_user_id) {
                    $updated_args['display_name'] = $params['display_name'];
                }
            }

            if (!empty($params['slug'])) {
                $update_data['slug'] = sanitize_title($params['slug']);
            }

            if (!empty($update_data)) {
                $result = wp_update_term($term_id, 'author', $update_data);
                if (is_wp_error($result)) {
                    return new WP_Error(
                        'author_update_failed',
                        $result->get_error_message(),
                        ['status' => 500]
                    );
                }
            }

            // Update email and sync to user if mapped
            if (!empty($params['user_email'])) {
                update_term_meta($term_id, 'user_email', sanitize_email($params['user_email']));
                if ($author_user_id) {
                    update_user_meta($author_user_id, 'user_email', sanitize_email($params['user_email']));
                    $updated_args['user_email'] = sanitize_email($params['user_email']);
                }
            }

            $this->setAuthorFields($term_id, $author_fields, $author_user_id, $updated_args);

            if ($author_user_id && count($updated_args) > 1) {
                wp_update_user($updated_args);
            }

            // Sync slug with user nicename if mapped
            if ($author_user_id) {
                $user = get_user_by('id', $author_user_id);
                if ($user && $author->slug !== $user->user_nicename) {
                    global $wpdb;
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE {$wpdb->terms} SET slug=%s WHERE term_id = %d",
                            $user->user_nicename,
                            $term_id
                        )
                    );
                }
            }

            $updated_author = Author::get_by_term_id($term_id);

            return $this->formatAuthorResponse($updated_author, 'existing', 200);
        }

        private function setAuthorFields($term_id, $author_fields, $user_id = null, &$updated_args = []) {
            $available_fields = Author_Editor::get_fields(false);
            $available_fields = apply_filters('multiple_authors_author_fields', $available_fields, false);

            foreach ($author_fields as $field_name => $field_value) {
                if (isset($available_fields[$field_name])) {
                    $field_config = $available_fields[$field_name];
                    $sanitized_value = $this->sanitizeFieldValue($field_value, $field_config);
                    update_term_meta($term_id, $field_name, $sanitized_value);

                    // Also update user meta if there's a mapped user
                    if ($user_id) {
                        update_user_meta($user_id, $field_name, $sanitized_value);
                        $updated_args[$field_name] = $sanitized_value;
                    }
                }
            }
        }

        private function formatAuthorResponse($author, $author_type, $status_code = 200)
        {
            $available_fields = Author_Editor::get_fields(false);
            $available_fields = apply_filters('multiple_authors_author_fields', $available_fields, false);

            $response_data = [
                'term_id' => (int) $author->term_id,
                'user_id' => (int) $author->user_id,
                'is_guest' => $author->is_guest() ? 1 : 0,
                'slug' => $author->slug,
                'display_name' => $author->display_name,
                'avatar_url' => $author->get_avatar_url(),
                'author_type' => $author_type,
                'edit_link' => get_edit_term_link($author->term_id, 'author')
            ];

            // Add all author fields to response
            foreach (array_keys($available_fields) as $field_name) {
                if (!in_array($field_name, ['user_id', 'avatar'])) {
                    $response_data[$field_name] = $author->$field_name;
                }
            }

            return new WP_REST_Response($response_data, $status_code);
        }

        private function sanitizeFieldValue($value, $field_config)
        {
            $field_type = isset($field_config['type']) ? $field_config['type'] : 'text';

            switch ($field_type) {
                case 'textarea':
                case 'wysiwyg':
                    return wp_kses_post($value);
                case 'text_email':
                    return sanitize_email($value);
                case 'text_url':
                    return esc_url_raw($value);
                case 'text':
                default:
                    return sanitize_text_field($value);
            }
        }
    }
}
