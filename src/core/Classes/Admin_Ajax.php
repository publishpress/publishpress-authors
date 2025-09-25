<?php
/**
 * @package     MultipleAuthors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.1.0
 */

namespace MultipleAuthors\Classes;

use MultipleAuthors\Capability;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Factory;

/**
 * Admin ajax endpoints
 *
 * Based on Bylines' class Byline_Editor.
 *
 * @package MultipleAuthors\Classes
 */
class Admin_Ajax
{

    /**
     * Handle a request to search available authors
     */
    public static function handle_authors_search()
    {
        header('Content-Type: application/javascript');

        if (empty($_GET['nonce'])
            || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'authors-search')) {
            wp_send_json_error(null, 403);
        }

        if (! Capability::currentUserCanEditPostAuthors()) {
            wp_send_json_error(null, 403);
        }

        $search   = !empty($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
        $ignored  = !empty($_GET['ignored']) ? array_map('sanitize_text_field', $_GET['ignored']) : [];
        $authors  = self::get_possible_authors_for_search($search, $ignored);
        $response = [
            'results' => $authors,
        ];
        echo wp_json_encode($response);
        exit;
    }

    /**
     * Handle an ajax request to search filter available authors
     */
     public static function handle_filter_authors_search()
     {
         header('Content-Type: application/javascript');

        if (empty($_GET['nonce'])
            || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'authors-user-search')
        ) {
            wp_send_json_error(null, 403);
        }

        if (! Capability::currentUserCanEditPostAuthors()) {
            wp_send_json_error(null, 403);
        }

        $search   = !empty($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
        $ignored  = !empty($_GET['ignored']) ? array_map('sanitize_text_field', $_GET['ignored']) : [];
        $authors  = self::get_possible_authors_for_search($search, $ignored);

        $results = [];
        foreach ($authors as $author) {
            $results[] = [
                'id'   => (isset($_GET['field']) && sanitize_key($_GET['field']) === 'slug') ? $author['slug'] : $author['id'],
                'text' => $author['display_name'],
            ];
        }

        $response = [
            'results' => $results,
        ];
        echo wp_json_encode($response);
        exit;
    }

    /**
     * Handle an ajax request to search filter available posts
     */
     public static function handle_filter_posts_search()
     {
         header('Content-Type: application/javascript');

        if (empty($_GET['nonce'])
            || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'authors-post-search')
        ) {
            wp_send_json_error(null, 403);
        }

        if (! Capability::currentUserCanEditPostAuthors()) {
            wp_send_json_error(null, 403);
        }

        $search     = !empty($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
        $post_type  = !empty($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'post';

        $post_args = [
            'post_type'         => $post_type,
            'post_status'       => 'publish',
            'posts_per_page'    => apply_filters('ppma_authors_editor_post_result_limit', 20),
        ];

        if (!empty($search)) {
            $post_args['s'] = $search;
        }

        $posts = get_posts($post_args);
        $results = [];

        foreach ($posts as $post) {
            $results[] = [
                'id' => $post->ID,
                'text' => $post->post_title,
            ];
        }

        $response = [
            'results' => $results,
        ];
        echo wp_json_encode($response);
        exit;
    }

    /**
     * Get the possible authors for a given search query.
     *
     * @param string $search Search query.
     * @param array $ignored Any authors that should be ignored.
     * @param array $onlyUsers True if should return only users
     *
     * @return array
     */
    public static function get_possible_authors_for_search($search, $ignored = [], $onlyUsers = false)
    {
        $legacyPlugin = Factory::getLegacyPlugin();

        $authors   = [];
        $term_args = [
            'taxonomy'   => 'author',
            'hide_empty' => false,
            'number'     => apply_filters('ppma_authors_editor_user_result_limit', 20),
            'order_by'   => 'name',
        ];

        if (!empty($search)) {
            $search = str_replace(['\"', "\'"], '', $search);
            $term_args['search'] = $search;
        }

        if (!empty($ignored)) {
            $term_args['exclude'] = [];
            foreach ($ignored as $val) {
                if (is_numeric($val)) {
                    $term_args['exclude'][] = (int)$val;
                }
            }
        }

        $terms = get_terms($term_args);
        if ($terms && !is_wp_error($terms)) {
            $show_user_name = $legacyPlugin->modules->multiple_authors->options->username_in_search_field === 'yes';

            foreach ($terms as $term) {
                $author = Author::get_by_term_id($term->term_id);
                $text = $term->name;

                if ($show_user_name) {
                    if (!$author->is_guest()) {
                        $user = $author->get_user_object();

                        if (!is_wp_error($user) && is_object($user)) {
                            $text .= sprintf(' (%s)', $user->user_nicename);
                        }
                    }
                }

                if ($onlyUsers && $author->is_guest()) {
                    continue;
                }

                $authors[] = [
                    'id'           => (int)$term->term_id,
                    'text'         => $text,
                    'term'         => (int)$term->term_id,
                    'display_name' => $text,
                    'slug'         => $term->slug,
                    'user_id'      => $author->user_id,
                    'category_id'  => $author->author_category,
                    'is_guest'     => $author->is_guest() ? 1 : 0,
                ];
            }
        }

        return $authors;
    }

    /**
     * Handle an ajax request to search available users
     */
    public static function handle_users_search()
    {
        header('Content-Type: application/javascript');

        if (empty($_GET['nonce'])
            || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'authors-user-search')) {
            wp_send_json_error(null, 403);
        }

        if (! Capability::currentUserCanEditPostAuthors()) {
            wp_send_json_error(null, 403);
        }

        $legacyPlugin = Factory::getLegacyPlugin();
        $allowed_roles = isset($legacyPlugin->modules->multiple_authors->options->mapped_author_roles)
            ? $legacyPlugin->modules->multiple_authors->options->mapped_author_roles
            : [];

        $user_args = [
            'number' => apply_filters('ppma_authors_editor_user_result_limit', 20),
        ];
        if (!empty($allowed_roles)) {
            $user_args['role__in'] = $allowed_roles;
        } else {
            $user_args['capability'] = 'edit_posts';
        }

        if (!empty($_GET['q'])) {
            $user_args['search'] = sanitize_text_field('*' . $_GET['q'] . '*');
        }

        $users   = get_users($user_args);
        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id'   => (isset($_GET['field']) && sanitize_key($_GET['field']) === 'slug') ? $user->user_nicename : $user->ID,
                'text' => $user->display_name,
            ];
        }
        $response = [
            'results' => $results,
        ];
        echo wp_json_encode($response);
        exit;
    }

    /**
     * Handle a GET request to create a new author from a user
     */
    public static function handle_author_create_from_user()
    {
        if (empty($_GET['nonce'])
            || empty($_GET['user_id'])
            || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'author_create_from_user' . (int)$_GET['user_id'])) {
            wp_send_json_error(null, 403);
        }

        if (! Capability::currentUserCanManageSettings()) {
            wp_send_json_error(null, 403);
        }

        $user_id = (int)$_GET['user_id'];
        $author  = Author::create_from_user($user_id);
        if (is_wp_error($author)) {
            wp_die(esc_html($author->get_error_message()));
        }
        $link = get_edit_term_link($author->term_id, 'author');
        wp_safe_redirect($link);
        exit;
    }

    /**
     * Handle a GET request to create a new author from a user
     */
    public static function handle_author_get_user_data()
    {
        if (empty($_GET['nonce'])
            || empty($_GET['user_id'])
            || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'author_get_user_data_nonce')) {
            wp_send_json_error(null, 403);
        }

        if (! Capability::currentUserCanManageSettings()) {
            wp_send_json_error(null, 403);
        }

        $user_id = (int)$_GET['user_id'];

        $user = get_user_by('ID', $user_id);

        $response = [
            'first_name'  => $user->first_name,
            'last_name'   => $user->last_name,
            'user_email'  => $user->user_email,
            'user_url'    => $user->user_url,
            'description' => $user->description,
            'slug'        => $user->user_nicename,
        ];

        echo wp_json_encode($response);
        exit;
    }

    /**
     * Handle a request to validate mapped author.
     */
    public static function handle_mapped_author_validation()
    {

        $response['status']  = 'success';
        $response['content'] = esc_html__('Request status.', 'publishpress-authors');

        //do not process request if nonce validation failed
        if (empty($_POST['nonce'])
            || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'mapped_author_nonce')
        ) {
            $response['status']  = 'error';
            $response['content'] = esc_html__(
                'Security error. Kindly reload this page and try again',
                'publishpress-authors'
            );
        } else {
            $author_slug = !empty($_POST['author_slug']) ? sanitize_title($_POST['author_slug']) : '';
            $author_id   = !empty($_POST['author_id']) ? (int) $_POST['author_id'] : 0;
            $term_id     = !empty($_POST['term_id']) ? (int) $_POST['term_id'] : 0;
            $legacyPlugin = Factory::getLegacyPlugin();
            $remove_single_user_map_restriction = publishpress_authors_remove_single_user_map_restriction();
            $enable_guest_author_user = $legacyPlugin->modules->multiple_authors->options->enable_guest_author_user === 'yes';


            if (!$remove_single_user_map_restriction && $author_id > 0) {
                $author = Author::get_by_user_id($author_id);
                if ($author && is_object($author) && isset($author->term_id)) {
                    if ((int)$author->term_id !== (int)$term_id) {
                        $response['status']  = 'error';
                        $response['content'] = esc_html__(
                            'Sorry, this WordPress user is already mapped to another Author. By default, each user can only be connected to one Author profile.',
                            'publishpress-authors'
                        );
                        wp_send_json($response);
                        exit;
                    }
                }
            }

            if (!$enable_guest_author_user && $author_id === 0) {
                $response['status']  = 'error';
                $response['content'] = esc_html__(
                    'Mapped user is required.',
                    'publishpress-authors'
                );
                wp_send_json($response);
                exit;
            }

            if (empty($author_slug)) {
                $response['status']  = 'error';
                $response['content'] = esc_html__('Author URL cannot be empty.', 'publishpress-authors');
            } else {
                $author_slug_user = get_user_by('slug', $author_slug);
                if ($author_slug_user && is_object($author_slug_user) && isset($author_slug_user->ID)) {
                    if (($author_id === 0)
                        || ($author_id > 0
                        && (int)$author_slug_user->ID != (int)$author_id)
                    ) {
                        /**
                         * Return error if author is not linked or
                         * linked author ID is not equal return ID
                         */
                        $response['status']  = 'error';
                        $response['content'] = esc_html__(
                            'Another user with Author URL already exists.',
                            'publishpress-authors'
                        );
                        wp_send_json($response);
                        exit;
                    }
                }
            }
        }

        wp_send_json($response);
        exit;
    }

    /**
     * Handle a request to generate author slug.
     */
    public static function handle_author_slug_generation()
    {

        $response['status']  = 'success';
        $response['content'] = esc_html__('Request status.', 'publishpress-authors');

        //do not process request if nonce validation failed
        if (empty($_POST['nonce'])
            || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'generate_author_slug_nonce')
        ) {
            $response['status']  = 'error';
            $response['content'] = esc_html__(
                'Security error. Kindly reload this page and try again',
                'publishpress-authors'
            );
        } elseif (empty($_POST['author_name'])) {
            $response['status']  = 'error';
            $response['content'] = esc_html__('Author name is required', 'publishpress-authors');
        } else {
            $author_slug        = !empty($_POST['author_name']) ? sanitize_title($_POST['author_name']) : '';
            $generated_slug     =  $author_slug;
            $generated_slug_n   = '';

            $new_slug           = $generated_slug;
            while (get_term_by('slug', $new_slug, 'author')) {
                if ($generated_slug_n == '') {
                    $generated_slug_n = 1;
                } else {
                    $generated_slug_n++;
                }
                $new_slug = $generated_slug .'-' . $generated_slug_n;
            }
            $response['author_slug'] = $new_slug;
        }

        wp_send_json($response);
        exit;
    }
}
