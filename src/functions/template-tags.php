<?php

/**
 * @package   MultipleAuthors
 * @author    PublishPress <help@publishpress.com>
 * @copyright Copyright (C) 2018 PublishPress. All rights reserved.
 * @license   GPLv2 or later
 * @since     1.0.0
 */

use MultipleAuthors\Classes\Authors_Iterator;
use MultipleAuthors\Classes\Post_Editor;
use MultipleAuthors\Classes\Legacy\Util;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;

if (!function_exists('get_archive_author')) {
    /**
     * Get the author on the archive page.
     *
     * @return Author|false
     */
    function get_archive_author()
    {
        if (!is_author() && !is_tax('author')) {
            return false;
        }

        $authorName = is_tax('author') ? get_query_var('ppma_author') : get_query_var('author_name');

        if (empty($authorName)) {
            $authorId = get_query_var('author');
            $user = get_user_by('ID', $authorId);
            if (!is_object($user) || !isset($user->user_nicename)) {
                return false;
            }
            $authorName = $user->user_nicename;
        }

        $term = get_term_by('slug', $authorName, 'author');

        if (empty($term) || !is_object($term)) {
            return false;
        }

        return Author::get_by_term_id($term->term_id);
    }
}

if (!function_exists('get_post_authors')) {
    /**
     * Get all authors of a post.
     *
     * @param WP_Post|int|null $post Post to fetch authors for. Defaults to global post.
     * @param bool $ignoreCache This cache cause sometimes errors in data received especially
     *                                            in quick edit after saving.
     *                                            That's why in Post_Editor we called this function with overriding
     *                                            ignoreCache value to be equal true.
     * @param bool $updateAuthors Updating authors early causes issue with some plugins that get post earlier
     *
     * @return array Array of Author objects, a single WP_User object, or empty.
     */
    function get_post_authors($post = 0, $ignoreCache = false, $updateAuthors = true)
    {
        if (is_object($post)) {
            $post = $post->ID;
        } elseif (empty($post)) {
            $post = get_post();

            if (is_object($post) && !is_wp_error($post)) {
                $post = $post->ID;
            }
        }

        $postId = (int)$post;

        if (empty($postId)) {
            return [];
        }

        $post = get_post($postId);

        if (
            $post &&
            ! is_wp_error($post) &&
            is_object($post) &&
            ! empty($post->post_author) &&
            ! Utils::is_post_type_enabled($post->post_type)
        ) {
            // this post type is not enabled for Authors

            $author = Author::get_by_user_id($post->post_author);

            if (empty($author) || is_wp_error($author)) {
                $postTypes = Util::get_selected_post_types();

                if (in_array($post->post_type, $postTypes)) {
                    $author = Author::create_from_user($post->post_author);
                    $authorsInstances = [$author];
                } else {
                    return [get_userdata($post->post_author)];
                }
            } else {
                $authorsInstances = [$author];
            }

            if (!empty($authorsInstances) && $updateAuthors) {
                Utils::set_post_authors($postId, $authorsInstances);
            }
            return $authorsInstances;
        }

        $authorsInstances = false;
        if (!$ignoreCache) {
            $authorsInstances = wp_cache_get($postId, 'get_post_authors:authors');
        }

        if (false !== $authorsInstances) {
            return $authorsInstances;
        }

        $authorsInstances = [];

        global $wpdb;

        $authorTerms = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT tt.term_id
                        FROM {$wpdb->term_relationships} AS tr
                        INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.`term_taxonomy_id` = tt.`term_taxonomy_id`)
                        WHERE tr.object_id = %d AND tt.taxonomy = 'author'
                        ORDER BY tr.term_order",
                $postId
            )
        );

        if (is_wp_error($authorTerms)) {
            return [];
        }

        if (!empty($authorTerms)) {
            // We found authors
            foreach ($authorTerms as $term) {
                if (is_wp_error($term) || empty($term)) {
                    continue;
                }

                if (is_object($term)) {
                    $term = $term->term_id;
                }

                $termId = (int)$term;

                $authorsInstances[] = Author::get_by_term_id($termId);
            }
        } else {
            // Fallback to the post author, fixing the post and author relationship
            // TODO: Should we really just fail silently? Check WP_DEBUG and add a log error message.
            if (empty($post) || is_wp_error($post) || !is_object($post) || empty($post->post_author)) {
                return [];
            }

            $author = Author::get_by_user_id($post->post_author);

            if (empty($author) || is_wp_error($author)) {
                $postTypes = Util::get_selected_post_types();

                if (in_array($post->post_type, $postTypes)) {
                    $author = Author::create_from_user($post->post_author);
                    $authorsInstances = [$author];
                } else {
                    return [];
                }
            } else {
                $authorsInstances = [$author];
            }

            if (!empty($authorsInstances) && $updateAuthors) {
                Utils::set_post_authors($postId, $authorsInstances);
            }
        }

        wp_cache_set($postId, $authorsInstances, 'get_post_authors:authors');

        return (array)$authorsInstances;
    }
}

if (!function_exists('ppma_post_authors_categorized')) {
    /**
     * Get all authors of a post.
     *
     * @param WP_Post|int|null $post Post to fetch authors for. Defaults to global post.
     * @param array $category_slugs limit the result to category slugs
     *
     * @return array
     */
    function ppma_post_authors_categorized($post = 0, $category_slugs = [])
    {
        if (is_object($post)) {
            $post = $post->ID;
        } elseif (empty($post)) {
            $post = get_post();
            if (is_object($post) && !is_wp_error($post)) {
                $post = $post->ID;
            }
        }

        $postId = (int)$post;

        if (empty($postId)) {
            return [];
        }

        // TODO: Cache the result
        $authors = get_post_authors($postId, true);
        $categorized_authors = [];
        $author_relations = get_ppma_author_relations(['post_id' => $postId, 'slug' => $category_slugs]);
        if (empty($author_relations)) {
            $categorized_authors['author'] = $authors;
        } else {
            $categorized_authors = [];
            foreach ($author_relations as $data) {
                $category_slug = $data['category_slug'];
                foreach ($authors as $author) {
                    if ($author->term_id == $data['author_term_id']) {
                        $categorized_authors[$category_slug][] = $author;
                        break; // Break the inner loop once a match is found
                    }
                }
            }
        }

        if (!empty($category_slugs)) {
            $category_slugs = array_values($category_slugs);
            $filtered_authors     = array_intersect_key($categorized_authors, array_flip($category_slugs));
            $categorized_authors = array_intersect_key($categorized_authors, $filtered_authors);

        }

        return $categorized_authors;
    }

}

if (!function_exists('publishpress_authors_get_post_authors')) {
    /**
     * Get all authors for a post.
     *
     * @param WP_Post|int|null $post Post to fetch authors for. Defaults to global post.
     * @param bool $filter_the_author_deprecated Deprecated. Removed for fixing infinity loop issues.
     * @param bool $archive_deprecated If true, will ignore the $post param and return the current author. Deprecated, use function get_archive_author instead.
     *                                            specified by the "author_name" URL param - for author pages.
     * @param bool $ignoreCache This cache cause sometimes errors in data received especially
     *                                            in quick edit after saving.
     *                                            That's why in Post_Editor we called this function with overriding
     *                                            ignoreCache value to be equal true.
     * @deprecated Use get_post_authors instead.
     * @return array Array of Author objects, a single WP_User object, or empty.
     */
    function publishpress_authors_get_post_authors($post = 0, $filter_the_author_deprecated = false, $archive_deprecated = false, $ignoreCache = false)
    {
        if ($archive_deprecated) {
            $archiveAuthor = get_archive_author();

            return empty($archiveAuthor) ? [] : [$archiveAuthor];
        }

        return get_post_authors($post, $ignoreCache);
    }
}


if (!function_exists('multiple_authors_get_author_recent_posts')) {
    /**
     * Get multiple authors recent posts
     * @param object $author
     * @param boolen $id_only
     * @param integer $limit
     * @param string $orderby
     * @param string $order
     *
     * @return array
     */
    function multiple_authors_get_author_recent_posts(
        $author = false,
        $id_only = true,
        $limit = 5,
        $orderby = 'post_date',
        $order = 'DESC',
        $post_types = []
    ) {
        if (!$author) {
            $author = Author::get_by_user_id(get_current_user_id());
        }

        if ((int)$limit === 0) {
            $limit = 5;
        }

        $author_recent_args = [
            'orderby'        => $orderby,
            'order'          => $order,
            'posts_per_page' => $limit,
            'tax_query' => [
                [
                    'taxonomy' => 'author',
                    'terms'    => $author->term_id,
                    'field'     => 'term_id'
                ]
            ]
        ];

        if (! empty($post_types)) {
            $author_recent_args['post_type'] = $post_types;
        }

        if ($id_only) {
            $author_recent_args['fields'] = 'ids';
        }

        $author_recent_posts = get_posts($author_recent_args);

        return $author_recent_posts;
    }
}


if (!function_exists('publishpress_authors_get_all_authors')) {
    /**
     * @param array $args
     * @param array $instance The widget  call object instance.
     *
     * @return array|int|WP_Error
     */
    function publishpress_authors_get_all_authors($args = [], $instance = [])
    {
        global $wpdb;

        //determine result type
        if (isset($instance['layout']) && $instance['layout'] === 'authors_index') {
            $result_type = 'grouped';
        } elseif (isset($instance['layout']) && $instance['layout'] === 'authors_recent') {
            $result_type = 'recent';
        } else {
            $result_type = 'default';
        }

        //instantiate default values
        $paged       = false;
        $offset      = false;
        $guests_only = false;
        $users_only  = false;
        $exclude_guest_user = false;
        $exclude_real_user = false;
        $per_page    = 0;
        $term_counts = 0;
        $can_exclude = true;

        // limit to term ids
        if (isset($instance['term_id']) && !empty($instance['term_id'])) {
            $can_exclude = false;
            $term_ids  = explode(',', $instance['term_id']);
        } else {
            $term_ids = [];
        }

        // author category ids
        if (isset($instance['category_id']) && !empty($instance['category_id'])) {
            $category_ids = array_map('intval', explode(',', $instance['category_id']));
        } else {
            $category_ids = [];
        }

        // exclude ids
        if ($can_exclude && isset($instance['exclude_term_id']) && !empty($instance['exclude_term_id'])) {
            $exclude_term_ids  = explode(',', $instance['exclude_term_id']);
        } else {
            $exclude_term_ids = [];
        }

        // limit to roles
        if (isset($instance['roles']) && !empty($instance['roles'])) {
            $user_roles  = explode(',', $instance['roles']);
        } else {
            $user_roles = [];
        }

        //check if result is set to be guest only or user only
        /**
         * $guests_only: Term without user_id meta
         * $users_only : All users except guest author role (!=ppma_guest_author)
         * $exclude_real_user: User in ppma_guest_author AND authors without user_id meta
         * $exclude_guest_user: Users in all roles except guest author role (!=ppma_guest_author) AND authors without user_id meta
         */
        if (isset($instance['authors']) && !empty($instance['authors'])) {
            $author_types = explode(',', $instance['authors']);
            // we want to make sure all the 3 options are not selected otherwise the filter is not neccessary
            if (count($author_types) < 3) {
                // check all possible combination between  users,guests_users,guests
                if (in_array('users', $author_types) && in_array('guests_users', $author_types)) {
                    $user_roles = array_keys(get_ppma_get_all_user_roles());
                } else if (in_array('users', $author_types) && in_array('guests', $author_types)) {
                    // Real users and Guest authors?
                    $exclude_guest_user = true;
                } else if (in_array('guests_users', $author_types) && in_array('guests', $author_types)) {
                    // Guest Users and Guests?
                    $exclude_real_user = true;
                } elseif (in_array('users', $author_types)) {
                    // maybe it's only users
                    $users_only  = true;
                } elseif (in_array('guests_users', $author_types)) {
                    // maybe it's only guests_users
                    $user_roles[] = 'ppma_guest_author';
                } elseif (in_array('guests', $author_types)) {
                    // maybe it's only guests
                    $guests_only = true;
                }

            }
        }

        // roles without guest author roles
        $other_roles  = array_keys(get_ppma_get_all_user_roles());
        $other_roles  = array_diff($other_roles, ["ppma_guest_author"]);

        if ($users_only) {
            $user_roles = $other_roles;
        }

        //add sort option
        if (!isset($args['order']) && isset($instance['order'])) {
            $args['order'] = $instance['order'];
        }
        if (!isset($args['orderby']) && isset($instance['orderby'])) {
            $args['orderby'] = $instance['orderby'];
        }

        //check if result limit is set
        if (isset($instance['limit_per_page']) && (int)$instance['limit_per_page'] > 0) {
            $paged          = (int)$instance['page'];
            $per_page       = (int)$instance['limit_per_page'];
            $offset         = ($paged-1) * $per_page;
            $args['number'] = $per_page;
            $args['offset'] = $offset;
        }

        $search_instance = isset($instance['search_box']) && ($instance['search_box'] === true || $instance['search_box'] === 'true');

        $search_text = false;
        $search_field = false;
        if ($search_instance && !empty($_GET['seach_query'])) {
            $search_text =  sanitize_text_field($_GET['seach_query']);
            $search_field = !empty($_GET['search_field']) ? sanitize_text_field($_GET['search_field']) : false;
        }

        //other query limit condition
        $last_article_date  = (!empty($instance['last_article_date']))
            ? sanitize_text_field($instance['last_article_date']) : false;

        $defaults = [
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ];

        $args = wp_parse_args($args, $defaults);

        if (!empty($term_ids)) {
            $args['include'] = array_values($term_ids);
        } elseif (!empty($exclude_term_ids)) {
            $args['exclude'] = array_values($exclude_term_ids);
        }

        // Add author_category meta query to args
        if (!empty($category_ids)) {
            $args['meta_query'] = [
                [
                    'key' => 'author_category',
                    'value' => $category_ids,
                    'compare' => 'IN',
                ]
            ];
        }

        /**
         * Filter author query args
         *
         * @param array $args Original passed args.
         * @param array $instance Original passed instance.
         */
        $args = apply_filters('pp_multiple_authors_get_all_authors_args', $args, $instance);

        $args['orderby'] = sanitize_sql_orderby($args['orderby'] ?? 'name');
        if (empty($args['orderby'])) {
            $args['orderby'] = 'name';
        }

        $args['order'] = strtoupper($args['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        if (!empty($args['orderby']) && !in_array($args['orderby'], ['name', 'count'])) {
            $meta_order = true;
        } else {
            $meta_order = false;
        }

        if (true === $args['hide_empty'] || $search_text || $meta_order || $last_article_date || !empty($user_roles) || $guests_only || $exclude_real_user || $exclude_guest_user) {
            $postTypes = Utils::get_enabled_post_types();
            $postTypes = array_map(function($item) {
                return '"' . $item . '"';
            }, $postTypes);
            $postTypes = implode(', ', $postTypes);

            $term_query = "SELECT t.term_id as `term_id` ";
            $term_query .= "FROM {$wpdb->terms} AS t ";
            $term_query .= "INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tt.term_id = t.term_id) ";
            if (true === $args['hide_empty'] || $last_article_date) {
                $term_query .= "INNER JOIN {$wpdb->term_relationships} AS tr ON (tt.term_taxonomy_id = tr.term_taxonomy_id) ";
                $term_query .= "INNER JOIN {$wpdb->posts} AS p ON (tr.object_id = p.ID) ";
            }
            if (($search_text && $search_field) || $meta_order || !empty($user_roles) || $exclude_real_user || $exclude_guest_user) {
                $term_query .= "LEFT JOIN {$wpdb->termmeta} AS tm ON (tm.term_id = t.term_id) ";
            }
            if ($guests_only || $exclude_real_user || $exclude_guest_user) {
                $term_query .= "LEFT JOIN {$wpdb->termmeta} tm2 ON t.term_id = tm2.term_id AND tm2.meta_key = 'user_id'";
            }

            if (!empty($user_roles) || $exclude_real_user || $exclude_guest_user) {
                $term_query .= "LEFT JOIN {$wpdb->users} u ON u.ID = tm.meta_value ";
                $term_query .= "LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id ";
            }

            // Add author_category filtering to custom query
            if (!empty($category_ids)) {
                $term_query .= "LEFT JOIN {$wpdb->termmeta} AS tm_cat ON (t.term_id = tm_cat.term_id AND tm_cat.meta_key = 'author_category') ";
            }

            $term_query .= "WHERE tt.taxonomy = 'author' ";

            if (!empty($category_ids)) {
                $term_query .= "AND tm_cat.meta_value IN (" . implode(',', $category_ids) . ") ";
            }

            if (!empty($term_ids)) {
                $term_ids_string = implode(',', array_map('intval', $term_ids));
                $term_query .= "AND t.term_id IN ({$term_ids_string}) ";
            } elseif (!empty($exclude_term_ids)) {
                $exclude_term_ids_string = implode(',', array_map('intval', $exclude_term_ids));
                $term_query .= "AND t.term_id NOT IN ({$exclude_term_ids_string}) ";
            }

            if ($guests_only) {
                $term_query .= "AND (tm2.meta_value IS NULL OR tm2.meta_value = '' OR tm2.meta_value = 0) ";
            }

            if ($exclude_real_user) {
                $role_condition = '%"ppma_guest_author"%';
                $term_query .= "AND (
                    tm2.meta_key IS NULL
                    OR tm2.meta_value IS NULL
                    OR tm2.meta_value = ''
                    OR tm2.meta_value = '0'
                    OR (
                        tm.meta_key = 'user_id'
                        AND um.meta_value LIKE '{$role_condition}'
                    )
                ) ";
            }

            if ($exclude_guest_user) {
                $role_conditions = [];
                foreach ($other_roles as $role) {
                    $role_conditions[] = $wpdb->prepare("um.meta_value LIKE %s", '%"' . $wpdb->esc_like($role) . '"%');
                }
                $role_conditions_string = implode(' OR ', $role_conditions);
                $term_query .= "AND (
                    tm2.meta_key IS NULL
                    OR tm2.meta_value IS NULL
                    OR tm2.meta_value = ''
                    OR tm2.meta_value = '0'
                    OR (
                        tm.meta_key = 'user_id'
                        AND ($role_conditions_string)
                    )
                ) ";
            }

            if (!empty($user_roles)) {
                $role_conditions = [];
                foreach ($user_roles as $role) {
                    $role_conditions[] = $wpdb->prepare("um.meta_value LIKE %s", '%"' . $wpdb->esc_like($role) . '"%');
                }
                $role_conditions_string = implode(' OR ', $role_conditions);
                $term_query .= "AND tm.meta_key = 'user_id' ";
                $term_query .= "AND um.meta_key = '{$wpdb->prefix}capabilities' ";
                $term_query .= "AND ($role_conditions_string) ";
            }

            if (true === $args['hide_empty'] || $last_article_date) {
                $term_query .= "AND p.post_status IN ('publish') ";
                $term_query .= "AND p.post_type IN ({$postTypes}) ";

                if ($last_article_date) {
                    $last_article_date = str_replace(' ago', '', $last_article_date);
                    $term_query .= 'AND p.post_date > "' . date('Y-m-d H:i:s', strtotime("-{$last_article_date}")) . '" ';
                }
            }
            if ($search_text && !$search_field) {
                $term_query .= $wpdb->prepare(
                    "AND (t.name LIKE '%%%s%%' OR t.slug LIKE '%%%s%%')",
                    $search_text,
                    $search_text
                );
            } elseif ($search_text && $search_field) {
                $term_query .= $wpdb->prepare(
                    "AND (tm.meta_key = '%s' AND tm.meta_value LIKE '%%%s%%') ",
                    $search_field,
                    $search_text
                );
            }

            if ($meta_order) {
                $term_query .= "AND (tm.meta_key = '{$args['orderby']}') ";
            }

            //get term count before before limit and group by in case it's paginated query
            if ($paged) {
                $term_count_query = str_replace("SELECT t.term_id as `term_id`", "SELECT COUNT(DISTINCT t.term_id)", $term_query);

                /**
                 * Filter author terms query
                 *
                 * @param array $term_count_query.
                 * @param array $args Original passed args.
                 * @param array $instance Original passed instance.
                 */
                $term_count_query = apply_filters('pp_multiple_authors_get_all_authors_term_count_query', $term_count_query, $args, $instance);

                $term_counts = $wpdb->get_var($term_count_query);
            }

            $term_query .= "GROUP BY t.term_id ";

            if ($args['orderby'] === 'count') {
                $sql_order_by = 'tt.' . $args['orderby'];
            } elseif ($args['orderby'] === 'name') {
                $sql_order_by = 't.' . $args['orderby'];
            } else {
                $sql_order_by = "tm.meta_value";
            }
            $sql_order_by = $sql_order_by . ' ' . $args['order'];

            $term_query .= "ORDER BY {$sql_order_by}";
            //add limit if it's a paginated request
            if ($paged) {
                $term_query .= " LIMIT {$offset}, {$per_page}";
            }

            /**
             * Filter author terms query
             *
             * @param array $term_query.
             * @param array $args Original passed args.
             * @param array $instance Original passed instance.
             */
            $term_query = apply_filters('pp_multiple_authors_get_all_authors_term_query', $term_query, $args, $instance);

            $terms = $wpdb->get_results($term_query);// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        } else {
            $terms   = get_terms('author', $args);
            if ($paged) {
                $count_args = $args;
                if (isset($count_args['number'])) {
                    unset($count_args['number']);
                }
                if (isset($count_args['offset'])) {
                    unset($count_args['offset']);
                }
                $term_counts = wp_count_terms('author', $count_args);
            }
        }

        $authors      = [];
        foreach ($terms as $term) {
            $author    = Author::get_by_term_id($term->term_id);

            if ($result_type === 'grouped') {
                //group authors by first letter of their name
                $group_by     = isset($instance['group_by']) ? $instance['group_by'] : 'display_name';
                $grouped_name = (!empty($author->$group_by)) ? $author->$group_by : $author->display_name;

                $first_char = mb_substr($grouped_name, 0, 1, 'UTF-8');
                $normalized_char = publishpress_authors_normalize_character($first_char);

                $authors[strtolower($normalized_char)][] = $author;

            } elseif ($result_type === 'recent') {
                //query recent post by authors
                $author_recent_posts = multiple_authors_get_author_recent_posts($author);
                $featured_image_size = isset($instance['featured_image_size']) ? $instance['featured_image_size'] : '';

                //add recent posts
                $author_recent    = '';
                $author_view_text = '';
                if (!empty($author_recent_posts)) {
                    $author_recent = [];
                    $post_index    = 0;
                    foreach ($author_recent_posts as $author_recent_post) {
                        $post_index++;
                        if ($post_index === 1) {
                            $featured_image = PP_AUTHORS_ASSETS_URL . 'img/no-image.jpeg';
                            if (has_post_thumbnail($author_recent_post)) {
                                if (!empty($featured_image_size)) {
                                    $featured_image_data = wp_get_attachment_image_src(get_post_thumbnail_id($author_recent_post), $featured_image_size);
                                } else {
                                    $featured_image_data = wp_get_attachment_image_src(get_post_thumbnail_id($author_recent_post));
                                }
                                if ($featured_image_data && is_array($featured_image_data)) {
                                    $featured_image = $featured_image_data[0];
                                }
                            }

                        } else {
                            $featured_image = false;
                        }
                        $author_recent[$author_recent_post] = [
                            'ID'              => $author_recent_post,
                            'post_title'      => html_entity_decode(get_the_title($author_recent_post)),
                            'permalink'       => get_the_permalink($author_recent_post),
                            'featuired_image' => $featured_image

                        ];
                    }
                    //only add show more link if author has more than limit posts
                    if ($post_index > 4) {
                        $author_view_text = sprintf(
                            esc_html__('%1sView all posts%2s by %3s', 'publishpress-authors'),
                            '<span>',
                            '</span>',
                            $author->display_name
                        );
                    }
                }

                $authors[] = ['author'=> $author, 'recent_posts'=> $author_recent, 'view_link' => $author_view_text];
            } else {
                //simply add author data directly
                $authors[] = $author;
            }
        }

        if ($result_type === 'grouped') {
            ksort($authors);
        }

        /**
         * Arguments for changing author result
         *
         * @param array $author Result author list.
         * @param array $args Original passed args.
         * @param array $instance Original passed instance.
         */
        $authors = apply_filters('pp_multiple_authors_get_all_authors_result', $authors, $args, $instance);

        //Return more data for paginated result to enable pagination handler
        if ($paged) {
            return [
                'authors'  => $authors,
                'page'     => $paged,
                'offset'   => $offset,
                'per_page' => $per_page,
                'total'    => $term_counts
            ];
        }

        return $authors;
    }
}


if (!function_exists('publishpress_authors_get_character_mapping')) {
    /**
     * Get comprehensive character mapping for alphabetical indexing
     * Handles Icelandic, European, and other extended characters
     */
    function publishpress_authors_get_character_mapping() {
        $character_map = [
            // Icelandic characters
            'Á' => 'A', 'á' => 'A',
            'Í' => 'I', 'í' => 'I',
            'Ó' => 'O', 'ó' => 'O',
            'Ú' => 'U', 'ú' => 'U',
            'Ý' => 'Y', 'ý' => 'Y',
            'Æ' => 'A', 'æ' => 'A',
            'Þ' => 'T', 'þ' => 'T',
            'Ð' => 'D', 'ð' => 'D',

            // French characters
            'À' => 'A', 'à' => 'A', 'Â' => 'A', 'â' => 'A', 'Ä' => 'A', 'ä' => 'A',
            'È' => 'E', 'è' => 'E', 'É' => 'E', 'é' => 'E', 'Ê' => 'E', 'ê' => 'E', 'Ë' => 'E', 'ë' => 'E',
            'Ì' => 'I', 'ì' => 'I', 'Î' => 'I', 'î' => 'I', 'Ï' => 'I', 'ï' => 'I',
            'Ò' => 'O', 'ò' => 'O', 'Ô' => 'O', 'ô' => 'O', 'Ö' => 'O', 'ö' => 'O',
            'Ù' => 'U', 'ù' => 'U', 'Û' => 'U', 'û' => 'U', 'Ü' => 'U', 'ü' => 'U',
            'Ç' => 'C', 'ç' => 'C',

            // German characters
            'ß' => 'S',

            // Spanish characters
            'Ñ' => 'N', 'ñ' => 'N',

            // Other European characters
            'Å' => 'A', 'å' => 'A',
            'Ø' => 'O', 'ø' => 'O',
            'Œ' => 'O', 'œ' => 'O',
        ];

        return apply_filters('publishpress_authors_index_character_mapping', $character_map);
    }
}

if (!function_exists('publishpress_authors_normalize_character')) {
    /**
     * Normalize character for alphabetical indexing
     */
    function publishpress_authors_normalize_character($char) {
        $character_map = publishpress_authors_get_character_mapping();
        return isset($character_map[$char]) ? $character_map[$char] : strtoupper($char);
    }
}

if (!function_exists('publishpress_authors_get_index_titles')) {
    /**
     * Get index title mapping for different languages
     */
    function publishpress_authors_get_index_titles() {
        $index_titles = [
            'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E',
            'F' => 'F', 'G' => 'G', 'H' => 'H', 'I' => 'I', 'J' => 'J',
            'K' => 'K', 'L' => 'L', 'M' => 'M', 'N' => 'N', 'O' => 'O',
            'P' => 'P', 'Q' => 'Q', 'R' => 'R', 'S' => 'S', 'T' => 'T',
            'U' => 'U', 'V' => 'V', 'W' => 'W', 'X' => 'X', 'Y' => 'Y',
            'Z' => 'Z'
        ];

        /**
         * Filter the index title mapping for different languages
         *
         * Example for Icelandic:
         * $icelandic_titles = [
         *     'A' => 'A/Á', 'B' => 'B', 'C' => 'C', 'D' => 'D/Ð',
         *     'E' => 'E/É', 'F' => 'F', 'G' => 'G', 'H' => 'H',
         *     'I' => 'I/Í', 'J' => 'J', 'K' => 'K', 'L' => 'L',
         *     'M' => 'M', 'N' => 'N', 'O' => 'O/Ó', 'P' => 'P',
         *     'Q' => 'Q', 'R' => 'R', 'S' => 'S', 'T' => 'T/Þ',
         *     'U' => 'U/Ú', 'V' => 'V', 'W' => 'W', 'X' => 'X',
         *     'Y' => 'Y/Ý', 'Z' => 'Z', 'AE' => 'Æ'
         * ];
         *
         * @param array $index_titles The default index title mapping
         */
        return apply_filters('publishpress_authors_index_titles', $index_titles);
    }
}

if (!function_exists('publishpress_authors_get_index_display_title')) {
    /**
     * Get display title for index character
     */
    function publishpress_authors_get_index_display_title($normalized_char) {
        $index_titles = publishpress_authors_get_index_titles();

        return isset($index_titles[strtoupper($normalized_char)]) ? $index_titles[strtoupper($normalized_char)] : $normalized_char;
    }
}

if (!function_exists('publishpress_authors_is_author_for_post')) {
    /**
     * Checks to see if the the specified user is author of the current global post or post (if specified)
     *
     * @param object|int $user
     * @param int $post_id
     */
    function publishpress_authors_is_author_for_post($user, $post_id = 0)
    {
        global $post;
        global $postAuthorsCache;
        global $authordata;

        if (empty($postAuthorsCache)) {
            $postAuthorsCache = [];
        }

        if (!$post_id && $post) {
            $post_id = $post->ID;
        }

        if (!$post_id) {
            return false;
        }

        $post_id = (int) $post_id;

        if (empty($post_id)) {
            return false;
        }

        if (!$user) {
            return false;
        }

        if (get_post_status($post_id) === 'auto-draft') {
            return false;
        }

        $currentPostType = get_post_type($post_id);
        $enabledPostTypes  = Utils::get_enabled_post_types();

        if (!in_array($currentPostType, $enabledPostTypes)) {
            $authorDataUserid = isset($authordata->ID) ? $authordata->ID : 0;
            $postAuthorId = get_the_author_meta('ID', $authorDataUserid);

            if (is_numeric($user)) {
                $userId = $user;
            } else {
                $userId = $user->ID;
            }

            return (int)$postAuthorId === (int)$userId;
        }

        if (!isset($postAuthorsCache[$post_id])) {

            $coauthors = get_post_authors($post_id, false, false);

            $postAuthorsCache[$post_id] = $coauthors;
        }
        $coauthors = $postAuthorsCache[$post_id];

        if (is_numeric($user)) {
            $user = (int)$user;

            if ($user > 0) {
                $user_term = Author::get_by_user_id($user);
            } elseif ($user < 0) {
                $user_term = Author::get_by_term_id($user);
            }
        } else {
            $user_term = Author::get_by_user_id($user->ID);
        }

        if (empty($user_term) || is_wp_error($user_term)) {
            $post_author = get_post_field('post_author', $post_id);

            if (is_numeric($user)) {
                $userId = $user;
            } else {
                $userId = $user->ID;
            }

            return (int)$post_author === (int)$userId;
        }

        foreach ($coauthors as $coauthor) {
            if (is_object($user_term) &&
                is_object($coauthor) &&
                isset($user_term->term_id) &&
                isset($coauthor->term_id) &&
                $user_term->term_id == $coauthor->term_id
                ) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('publishpress_authors_echo')) {
    //Helper function for the following new template tags
    function publishpress_authors_echo($tag, $type = 'tag', $separators = [], $tag_args = null, $echo = true)
    {
        // Define the standard output separator. Constant support is for backwards compat.
        $default_before       = (defined('COAUTHORS_DEFAULT_BEFORE')) ? COAUTHORS_DEFAULT_BEFORE : '';
        $default_between      = (defined('COAUTHORS_DEFAULT_BETWEEN')) ? COAUTHORS_DEFAULT_BETWEEN : ', ';
        $default_between_last = (defined('COAUTHORS_DEFAULT_BETWEEN_LAST')) ? COAUTHORS_DEFAULT_BETWEEN_LAST : __(
            ' and ',
            'publishpress-authors'
        );
        $default_after        = (defined('COAUTHORS_DEFAULT_AFTER')) ? COAUTHORS_DEFAULT_AFTER : '';

        if (!isset($separators['before']) || null === $separators['before']) {
            $separators['before'] = apply_filters('coauthors_default_before', $default_before);
        }
        if (!isset($separators['between']) || null === $separators['between']) {
            $separators['between'] = apply_filters('coauthors_default_between', $default_between);
        }
        if (!isset($separators['betweenLast']) || null === $separators['betweenLast']) {
            $separators['betweenLast'] = apply_filters('coauthors_default_between_last', $default_between_last);
        }
        if (!isset($separators['after']) || null === $separators['after']) {
            $separators['after'] = apply_filters('coauthors_default_after', $default_after);
        }

        $output = '';

        $authors_iterator = new Authors_Iterator();
        $output           .= $separators['before'];
        while ($authors_iterator->iterate()) {
            $author_text = '';

            if ('tag' === $type) {
                $author_text = $tag($tag_args);
            } else {
                if ('field' === $type && isset($authors_iterator->current_author->$tag)) {
                    $author_text = $authors_iterator->current_author->$tag;
                } else {
                    if ('callback' === $type && is_callable($tag)) {
                        $author_text = call_user_func($tag, $authors_iterator->current_author);
                    }
                }
            }

            // Fallback to user_nicename if we get something empty
            if (empty($author_text)) {
                $author_text = $authors_iterator->current_author->user_nicename;
            }

            // Append separators
            if ($authors_iterator->count() - $authors_iterator->position == 1) { // last author or only author
                $output .= $author_text;
            } else {
                if ($authors_iterator->count() - $authors_iterator->position == 2) { // second to last
                    $output .= $author_text . $separators['betweenLast'];
                } else {
                    $output .= $author_text . $separators['between'];
                }
            }
        }

        $output .= $separators['after'];

        if ($echo) {
            echo $output;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        return $output;
    }
}

if (!function_exists('publishpress_authors_the_author')) {
    /**
     * Outputs the co-authors display names, without links to their posts.
     * PublishPress Authors equivalent of the_author() template tag.
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function publishpress_authors_the_author($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_echo(
            'display_name',
            'field',
            [
                'between'     => $between,
                'betweenLast' => $betweenLast,
                'before'      => $before,
                'after'       => $after,
            ],
            null,
            $echo
        );
    }
}

if (!function_exists('publishpress_authors_posts_links')) {
    /**
     * Outputs the co-authors display names, with links to their posts.
     * PublishPress Authors equivalent of the_author_posts_link() template tag.
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function publishpress_authors_posts_links(
        $between = null,
        $betweenLast = null,
        $before = null,
        $after = null,
        $echo = true
    ) {
        return publishpress_authors_echo(
            'publishpress_authors_posts_links_single',
            'callback',
            [
                'between'     => $between,
                'betweenLast' => $betweenLast,
                'before'      => $before,
                'after'       => $after,
            ],
            null,
            $echo
        );
    }
}

if (!function_exists('publishpress_authors_posts_links_single')) {
    /**
     * Outputs a single co-author linked to their post archive.
     *
     * @param object $author
     *
     * @return string
     */
    function publishpress_authors_posts_links_single($author)
    {
        // Return if the fields we are trying to use are not sent
        if (!isset($author->display_name)) {
            _doing_it_wrong(
                'publishpress_authors_posts_links_single',
                'Invalid author object used',
                '3.2'
            );

            return;
        }
        $args        = [
            'before_html' => '',
            'href'        => get_author_posts_url($author->ID, $author->user_nicename),
            'rel'         => 'author',
            'title'       => sprintf(
                __('Posts by %s', 'publishpress-authors'), $author->display_name
            ),
            'class'       => 'author url fn',
            'text'        => $author->display_name,
            'after_html'  => '',
        ];
        $args        = apply_filters('coauthors_posts_link', $args, $author);
        $single_link = sprintf(
            '<a href="%1$s" title="%2$s" class="%3$s" rel="%4$s">%5$s</a>',
            esc_url($args['href']),
            esc_attr($args['title']),
            esc_attr($args['class']),
            esc_attr($args['rel']),
            esc_html($args['text'])
        );

        return $args['before_html'] . $single_link . $args['after_html'];
    }
}

if (!function_exists('publishpress_authors_firstnames')) {
    /**
     * Outputs the co-authors first names, without links to their posts.
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function publishpress_authors_firstnames(
        $between = null,
        $betweenLast = null,
        $before = null,
        $after = null,
        $echo = true
    ) {
        return publishpress_authors_echo(
            'get_the_author_meta',
            'tag',
            [
                'between'     => $between,
                'betweenLast' => $betweenLast,
                'before'      => $before,
                'after'       => $after,
            ],
            'first_name',
            $echo
        );
    }
}

if (!function_exists('publishpress_authors_lastnames')) {
    /**
     * Outputs the co-authors last names, without links to their posts.
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function publishpress_authors_lastnames(
        $between = null,
        $betweenLast = null,
        $before = null,
        $after = null,
        $echo = true
    ) {
        return publishpress_authors_echo(
            'get_the_author_meta',
            'tag',
            [
                'between'     => $between,
                'betweenLast' => $betweenLast,
                'before'      => $before,
                'after'       => $after,
            ],
            'last_name',
            $echo
        );
    }
}

if (!function_exists('publishpress_authors_nicknames')) {
    /**
     * Outputs the co-authors nicknames, without links to their posts.
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function publishpress_authors_nicknames(
        $between = null,
        $betweenLast = null,
        $before = null,
        $after = null,
        $echo = true
    ) {
        return publishpress_authors_echo(
            'get_the_author_meta',
            'tag',
            [
                'between'     => $between,
                'betweenLast' => $betweenLast,
                'before'      => $before,
                'after'       => $after,
            ],
            'nickname',
            $echo
        );
    }
}

if (!function_exists('publishpress_authors_links')) {
    /**
     * Outputs the co-authors display names, with links to their websites if they've provided them.
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function publishpress_authors_links($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_echo(
            'publishpress_authors_links_single',
            'callback',
            [
                'between'     => $between,
                'betweenLast' => $betweenLast,
                'before'      => $before,
                'after'       => $after,
            ],
            null,
            $echo
        );
    }
}

if (!function_exists('publishpress_authors_emails')) {
    /**
     * Outputs the co-authors email addresses
     *
     * @param string $between Delimiter that should appear between the email addresses
     * @param string $betweenLast Delimiter that should appear between the last two email addresses
     * @param string $before What should appear before the presentation of email addresses
     * @param string $after What should appear after the presentation of email addresses
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function publishpress_authors_emails($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_echo(
            'get_the_author_meta',
            'tag',
            [
                'between'     => $between,
                'betweenLast' => $betweenLast,
                'before'      => $before,
                'after'       => $after,
            ],
            'user_email',
            $echo
        );
    }
}

if (!function_exists('publishpress_authors_links_single')) {
    /**
     * Outputs a single co-author, linked to their website if they've provided one.
     *
     * @param object $author
     *
     * @return string
     */
    function publishpress_authors_links_single($author)
    {
        if (get_the_author_meta('url')) {
            return sprintf(
                '<a href="%s" title="%s" rel="external">%s</a>',
                get_the_author_meta('url'),
                esc_attr(sprintf(__('Visit %s&#8217;s website'), get_the_author())),
                get_the_author()
            );
        } else {
            return get_the_author();
        }
    }
}

if (!function_exists('publishpress_authors_ids')) {
    /**
     * Outputs the co-authors IDs
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function publishpress_authors_ids($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_echo(
            'ID',
            'field',
            [
                'between'     => $between,
                'betweenLast' => $betweenLast,
                'before'      => $before,
                'after'       => $after,
            ],
            null,
            $echo
        );
    }
}

if (!function_exists('get_the_publishpress_author_meta')) {
    function get_the_publishpress_author_meta($field)
    {
        $authors = get_post_authors();
        $meta    = [];

        foreach ($authors as $author) {
            $meta[] = $author->$field;
        }

        return $meta;
    }
}

if (!function_exists('the_publishpress_author_meta')) {
    function the_publishpress_author_meta($field, $user_id = 0)
    {
        // TODO: need before after options
        echo get_the_publishpress_author_meta($field, $user_id);  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

if (!function_exists('publishpress_authors_wp_list_authors')) {
    /**
     * List all the *co-authors* of the blog, with several options available.
     * optioncount (boolean) (false): Show the count in parenthesis next to the author's name.
     * show_fullname (boolean) (false): Show their full names.
     * hide_empty (boolean) (true): Don't show authors without any posts.
     * feed (string) (''): If isn't empty, show links to author's feeds.
     * feed_image (string) (''): If isn't empty, use this image to link to feeds.
     * echo (boolean) (true): Set to false to return the output, instead of echoing.
     *
     * @param array $args The argument array.
     *
     * @return null|string The output, if echo is set to false.
     */
    function publishpress_authors_wp_list_authors($args = [])
    {
        $defaults = [
            'optioncount'   => false,
            'show_fullname' => false,
            'hide_empty'    => true,
            'feed'          => '',
            'feed_image'    => '',
            'feed_type'     => '',
            'echo'          => true,
            'style'         => 'list',
            'html'          => true,
            'number'        => 20, // A sane limit to start to avoid breaking all the things
        ];

        $args   = wp_parse_args($args, $defaults);
        $return = publishpress_authors_get_the_authors();

        if (!$args['echo']) {
            return $return;
        }

        echo $return;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

if (!function_exists('publishpress_authors_get_avatar')) {
    /**
     * Retrieve a Co-Author's Avatar.
     *
     * Since Guest Authors doesn't enforce unique email addresses, simply loading the avatar by email won't work when
     * multiple Guest Authors share the same address.
     *
     * This is a replacement for using get_avatar(), which only operates on email addresses and cannot differentiate
     * between Guest Authors (who may share an email) and regular user accounts
     *
     * @param object $coauthor The Co Author
     * @param int $size The desired size
     *
     * @return string             The image tag for the avatar, or an empty string if none could be determined
     */
    function publishpress_authors_get_avatar($coauthor, $size = 32, $default = '', $alt = false)
    {
        global $multiple_authors_addon;

        if (is_object($coauthor)) {
            if (method_exists($coauthor, 'get_avatar')) {
                return $coauthor->get_avatar($size);
            }

            $email = $coauthor->user_email;
        } else {
            $email = $coauthor;
        }

        // Make sure we're dealing with an object for which we can retrieve an email
        if (!empty($email)) {
            return get_avatar($email, $size, $default, $alt);
        }

        // Nothing matched, an invalid object was passed.
        return '';
    }
}

// ========================================
// Bylines methods

/**
 * Utility functions for use by themes.
 */

if (!function_exists('publishpress_authors_the_authors')) {
    /**
     * Renders the authors display names, without links to their posts.
     *
     * Equivalent to the_author() template tag.
     */
    function publishpress_authors_the_authors()
    {
        echo publishpress_authors_get_the_authors();  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

if (!function_exists('publishpress_authors_get_the_authors')) {
    /**
     * Gets the authors display names, without links to their posts.
     *
     * Equivalent to get_the_author() template tag.
     */
    function publishpress_authors_get_the_authors()
    {
        return publishpress_authors_render(
            get_post_authors(),
            function ($author) {
                return $author->display_name;
            }
        );
    }
}

if (!function_exists('publishpress_authors_the_authors_posts_links')) {
    /**
     * Renders the authors display names, with links to their posts.
     *
     * Equivalent to the_author_posts_link() template tag.
     */
    function publishpress_authors_the_authors_posts_links()
    {
        echo publishpress_authors_get_the_authors_posts_links();  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

if (!function_exists('publishpress_authors_get_the_authors_posts_links')) {
    /**
     * Renders the authors display names, with links to their posts.
     */
    function publishpress_authors_get_the_authors_posts_links()
    {
        return publishpress_authors_render(
            get_post_authors(),
            function ($author) {
                $link = is_a($author, 'WP_User') ? get_author_posts_url($author->ID) : $author->link;
                $args = [
                    'before_html' => '',
                    'href'        => $link,
                    'rel'         => 'author',
                    // translators: Posts by a given author.
                    'title'       => sprintf(
                        __('Posts by %1$s', 'publishpress-authors'),
                        $author->display_name
                    ),
                    'class'       => 'author url fn',
                    'text'        => $author->display_name,
                    'after_html'  => '',
                ];
                /**
                 * Arguments for determining the display of authors with posts links
                 *
                 * @param array $args Arguments determining the rendering of the author.
                 * @param Author $author The author to be rendered.
                 */
                $args        = apply_filters('authors_posts_links', $args, $author);
                $single_link = sprintf(
                    '<a href="%1$s" title="%2$s" class="%3$s" rel="%4$s">%5$s</a>',
                    esc_url($args['href']),
                    esc_attr($args['title']),
                    esc_attr($args['class']),
                    esc_attr($args['rel']),
                    esc_html($args['text'])
                );

                return $args['before_html'] . $single_link . $args['after_html'];
            }
        );
    }
}

if (!function_exists('publishpress_authors_the_authors_links')) {
    /**
     * Renders the authors display names, with their website link if it exists.
     *
     * Equivalent to the_author_link() template tag.
     */
    function publishpress_authors_the_authors_links()
    {
        echo publishpress_authors_get_the_authors_links();  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

if (!function_exists('publishpress_authors_get_the_authors_links')) {
    /**
     * Renders the authors display names, with their website link if it exists.
     */
    function publishpress_authors_get_the_authors_links()
    {
        return publishpress_authors_render(
            get_post_authors(),
            function ($author) {
                if ($author->user_url) {
                    return sprintf(
                        '<a href="%s" title="%s" rel="external">%s</a>',
                        esc_url($author->user_url),
                        // Translators: refers to the author's website.
                        esc_attr(sprintf(__('Visit %s&#8217;s website'), $author->display_name)),
                        $author->display_name
                    );
                } else {
                    return $author->display_name;
                }
            }
        );
    }
}

if (!function_exists('publishpress_authors_render')) {
    /**
     * Display one or more authors, according to arguments provided.
     *
     * @param array $authors Set of authors to display.
     * @param callable $render_callback Callback to return rendered author.
     * @param array $args Arguments to affect display.
     */
    function publishpress_authors_render($authors, $render_callback, $args = [])
    {
        if (
            empty($authors)
            || empty($render_callback)
            || !is_callable($render_callback)
        ) {
            return '';
        }
        $defaults = [
            'between'           => ', ',
            'between_last_two'  => __(' and ', 'publishpress-authors'),
            'between_last_many' => __(', and ', 'publishpress-authors'),
        ];
        $args     = array_merge($defaults, $args);
        $total    = count($authors);
        $current  = 0;
        $output   = '';
        foreach ($authors as $author) {
            $current++;
            if ($current > 1) {
                if ($current === $total) {
                    if (2 === $total) {
                        $output .= $args['between_last_two'];
                    } else {
                        $output .= $args['between_last_many'];
                    }
                } elseif ($total >= 2) {
                    $output .= $args['between'];
                }
            }
            $output .= $render_callback($author);
        }

        return $output;
    }
}


if (!function_exists('get_ppma_author_categories')) {
    /**
     * Get author categories
     *
     * @param array $args
     *
     * @return array|integer
     */
    function get_ppma_author_categories($args = []) {
        global $wpdb;

        $default_args = [
            'paged'             => 1,
            'limit'             => 20,
            'id'                => 0,
            'slug'              => '',
            'category_name'     => '',
            'plural_name'       => '',
            'search'            => '',
            'category_status'   => '',
            'orderby'           => 'category_order',
            'order'             => 'ASC',
            'count_only'        => false,
            'no_cache'          => false,
            'post_type'         => [],
            'post_type_and_empty' => '',
            'meta_query'        => []
        ];

        $args = wp_parse_args($args, $default_args);

        $table_name      = $wpdb->prefix . 'ppma_author_categories';
        $meta_table_name = $wpdb->prefix . 'ppma_author_categories_meta';

        if (! Utils::isAuthorsProActive()) {
            // make sure post type query doesn't work even if added to custom query unless pro is active
            $args['post_type'] = [];
            $args['post_type_and_empty'] = '';
        }

        $paged           = intval($args['paged']);
        $limit           = intval($args['limit']);
        $id              = intval($args['id']);
        $slug            = sanitize_text_field($args['slug']);
        $category_name   = sanitize_text_field($args['category_name']);
        $plural_name     = sanitize_text_field($args['plural_name']);
        $search          = sanitize_text_field($args['search']);
        $orderby         = sanitize_sql_orderby($args['orderby'] . ' ' . strtoupper($args['order']));
        $category_status = sanitize_text_field($args['category_status']);
        $count_only      = boolval($args['count_only']);
        $no_cache        = boolval($args['no_cache']);
        $post_types      = !empty($args['post_types']) && is_array($args['post_types']) ? array_map('sanitize_key', $args['post_types'], ) : [];
        $post_type_and_empty = !empty($args['post_type_and_empty']) ? sanitize_text_field($args['post_type_and_empty']) : '';

        if (empty($orderby)) {
            $orderby = 'category_order ASC';
        }

        $field_search = $field_value = false;
        if (!empty($id)) {
            $field_search = 'id';
            $field_value  = $id;
        } elseif (!empty($slug)) {
            $field_search = 'slug';
            $field_value  = $slug;
        } elseif (!empty($category_name)) {
            $field_search = 'category_name';
            $field_value  = $category_name;
        } elseif (!empty($plural_name)) {
            $field_search = 'plural_name';
            $field_value  = $plural_name;
        }

        $cache_key = 'author_categories_results_' . md5(serialize($args));

        $category_results = wp_cache_get($cache_key, 'author_categories_results_cache');
        $single_result = false;
        if ($no_cache || $category_results === false) {
            $category_results = [];
            if ($field_search) {
                // Single result
                $query = $wpdb->prepare(
                    "SELECT * FROM {$table_name}
                    WHERE {$table_name}.{$field_search} = %s
                    ORDER BY {$orderby}
                    LIMIT 1",
                    $field_value
                );
                $category_row = $wpdb->get_row($query, ARRAY_A);

                if ($category_row) {
                    // Fetch all meta for this ID
                    $meta_query = $wpdb->prepare(
                        "SELECT meta_key, meta_value
                        FROM {$meta_table_name}
                        WHERE category_id = %d",
                        $category_row['id']
                    );
                    $metas = $wpdb->get_results($meta_query, ARRAY_A);

                    // Merge meta into main row
                    foreach ($metas as $meta) {
                        $category_row[$meta['meta_key']] = $meta['meta_value'];
                    }
                }

                $category_results = $category_row;

                $single_result = true;
            } else {
                // Multiple results

                $offset = ($paged - 1) * $limit;

                if ($count_only) {
                    $query = "SELECT COUNT(*) FROM {$table_name} WHERE 1=1";
                } else {
                    $query = "SELECT * FROM {$table_name} WHERE 1=1";
                }

                if (!empty($post_types)) {
                    $like_conditions = [];
                    $prepare_values = [];

                    foreach ($post_types as $post_type) {
                        $like_conditions[] = "meta_value LIKE %s";
                        $prepare_values[] = '%"' . $wpdb->esc_like($post_type) . '"%';
                    }

                    $subquery = $wpdb->prepare(
                        "SELECT DISTINCT category_id
                        FROM {$meta_table_name}
                        WHERE meta_key = 'post_types'
                        AND (" . implode(' OR ', $like_conditions) . ")",
                        ...$prepare_values
                    );

                    $query .= " AND {$table_name}.id IN ({$subquery})";
                } elseif (!empty($post_type_and_empty)) {
                    $post_type = $post_type_and_empty;

                    $subquery = $wpdb->prepare(
                        "SELECT DISTINCT category_id
                        FROM {$meta_table_name}
                        WHERE meta_key = 'post_types'
                        AND (meta_value LIKE %s OR meta_value IS NULL OR meta_value = '')",
                        '%"' . $wpdb->esc_like($post_type) . '"%'
                    );

                    $query .= " AND ({$table_name}.id IN ({$subquery}) OR {$table_name}.id NOT IN (
                        SELECT DISTINCT category_id
                        FROM {$meta_table_name}
                        WHERE meta_key = 'post_types'
                        AND meta_value IS NOT NULL
                        AND meta_value != ''
                    ))";
                }

                if (!empty($search)) {
                    $query .= $wpdb->prepare(
                        " AND (slug LIKE '%%%s%%' OR category_name LIKE '%%%s%%' OR plural_name LIKE '%%%s%%')",
                        $search,
                        $search,
                        $search
                    );
                }

                if ($category_status !== '') {
                    $query .= $wpdb->prepare(
                        " AND category_status = %d",
                        $category_status
                    );
                }

                if ($count_only) {
                    return $wpdb->get_var($query);
                }

                $query .= " ORDER BY {$orderby} LIMIT %d OFFSET %d";
                $query = $wpdb->prepare($query, $limit, $offset);

                $categories = $wpdb->get_results($query, ARRAY_A);
                if (!$categories) {
                    return [];
                }

                // Query metas
                $ids = wp_list_pluck($categories, 'id');
                $placeholders = implode(',', array_fill(0, count($ids), '%d'));
                $meta_query = $wpdb->prepare(
                    "SELECT category_id, meta_key, meta_value
                    FROM {$meta_table_name}
                    WHERE category_id IN ($placeholders)",
                    ...$ids
                );
                $metas = $wpdb->get_results($meta_query, ARRAY_A);

                // Merge metas into main categories
                foreach ($metas as $meta) {
                    foreach ($categories as &$cat) {
                        if ($cat['id'] == $meta['category_id']) {
                            $cat[$meta['meta_key']] = $meta['meta_value'];
                        }
                    }
                }
                unset($cat);

                $category_results = $categories;

                wp_cache_set($cache_key, $category_results, 'author_categories_results_cache', 3600);
            }
        }

        if (is_array($category_results)) {
            $merged_results = $single_result ? [$category_results] : $category_results;
            $category_results = array_reduce($merged_results, function ($accumulator, $item) {
                $id = $item['id'];

                if (!isset($accumulator[$id])) {
                    $accumulator[$id] = $item;
                }

                if (isset($item['meta_key']) && isset($item['meta_value'])) {
                    $accumulator[$id][$item['meta_key']] = $item['meta_value'];
                }

                unset($accumulator[$id]['meta_key'], $accumulator[$id]['meta_value']);

                return $accumulator;
            }, []);
            $category_results = $single_result ? array_values($category_results)[0] : array_values($category_results);
        }

        return $category_results;
    }

}


if (!function_exists('ppma_get_grouped_post_authors')) {
    function ppma_get_grouped_post_authors($post = 0, $authors = false, $author_categories = false) {

        if (is_object($post)) {
            $post = $post->ID;
        } elseif (empty($post)) {
            $post = get_post();
            if (is_object($post) && !is_wp_error($post)) {
                $post = $post->ID;
            }
        }

        $post_id = (int)$post;

        if (empty($post_id) && !$authors ) {
            return [];
        }

        if (!$authors) {
            $authors = get_post_authors($post_id);
        }

        $authors = \MA_Author_Boxes::removeExcludedAuthors($authors);

        if (empty($authors)) {
            return [];
        }
        // make sure there's a default grouping
        $author_categories_data = [];
        $author_categories_data[] = [
            'title'       => '',
            'singular_title' => '',
            'description' => '',
            'slug'        => '',
            'id'          => '',
            'authors'     => $authors
        ];

        if (!$author_categories) {
            $author_categories = get_ppma_author_categories(['category_status' => 1]);
        }
        // group authors to categories
        if (!empty($author_categories)) {
            $author_relations       = get_ppma_author_relations(['post_id' => $post_id]);
            $author_categories_data = Post_Editor::group_category_authors($author_categories, $author_relations, $authors);
        }

        // TODO: Cache this data
        return $author_categories_data;
    }
}


if (!function_exists('get_ppma_author_relations')) {
    function get_ppma_author_relations($args = []) {
        global $wpdb;

        $default_args = [
            'post_id'  => '',
            'author_term_id' => ''
        ];

        $args = wp_parse_args($args, $default_args);

        $post_id        = intval($args['post_id']);
        $author_term_id = intval($args['author_term_id']);

        $cache_key = 'author_categories_relation_' . md5(serialize($args));

        $results = wp_cache_get($cache_key, 'author_categories_relation_cache');

        if ($results === false) {
            $relationships_table    = $wpdb->prefix . 'ppma_author_relationships';
            $categories_table       = $wpdb->prefix . 'ppma_author_categories';

            $sql = "SELECT * FROM $relationships_table WHERE 1=1";

            $sql = "SELECT r.*, c.category_name, c.plural_name FROM $relationships_table r LEFT JOIN $categories_table c ON r.category_id = c.id  WHERE 1=1";

            if ($post_id !== '') {
                $sql .= $wpdb->prepare(" AND r.post_id = %d", $post_id);
            }

            if (!empty($author_term_id)) {
                $sql .= $wpdb->prepare(" AND r.author_term_id = %d", $author_term_id);
            }

            $results = $wpdb->get_results($sql, ARRAY_A);

            wp_cache_set($cache_key, $results, 'author_categories_relation_cache', 3600);
        }

        return $results;
    }
}

if (!function_exists('get_ppma_author_category')) {
    /**
     * Get author category
     *
     * @param object $author
     * @param array $author_categories_data
     *
     * @return array
     */
    function get_ppma_author_category($author, $author_categories_data) {

        $author_category = [];

        foreach ($author_categories_data as $author_category_data) {
            if (!empty($author_category_data['singular_title']) && !empty($author_category_data['authors'])) {
                $author_term_id = array_column($author_category_data['authors'], 'term_id');
                if (in_array($author->term_id, $author_term_id)) {
                    $author_category = $author_category_data;
                }
            }

        }

        return $author_category;
    }

}

if (!function_exists('get_ppma_section_content')) {
    /**
     * Return section content
     *
     * @param string $page
     *
     * @return string
     */
    function get_ppma_section_content($page) {
        ob_start();

        do_settings_sections($page);

        return ob_get_clean();
    }

}


if (!function_exists('get_ppma_get_all_user_roles')) {
    /**
     * Return WordPress role object
     *
     * @return object
     */
    function get_ppma_get_all_user_roles()
    {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }

        return $wp_roles->roles;
    }
}

if (!function_exists('publishpress_authors_remove_single_user_map_restriction')) {
    /**
     * Determine if single user map restriction should be removed
     */
    function publishpress_authors_remove_single_user_map_restriction() {
         $legacyPlugin = Factory::getLegacyPlugin();

         $remove = $legacyPlugin->modules->multiple_authors->options->enable_guest_author_user === 'yes';

         if (function_exists('pll_current_language') && function_exists('pll_get_term')) {
            // This restriction should be removed for Polylang due to multi-lang feature
            $remove = true;
         }

        return apply_filters('publishpress_authors_remove_single_user_map_restriction', $remove);
    }
}

// Keep backward compatibility with Bylines, legacy versions of PublishPress Authors and CoAuthors
if (PUBLISHPRESS_AUTHORS_LOAD_COAUTHORS_FUNCTIONS) {
    require_once 'coauthors-functions.php';
}

if (PUBLISHPRESS_AUTHORS_LOAD_BYLINES_FUNCTIONS) {
    require_once 'bylines-functions.php';
}

if (PUBLISHPRESS_AUTHORS_LOAD_DEPRECATED_LEGACY_CODE) {
    require_once 'legacy-functions.php';
}
