<?php
/**
 * @package     MultipleAuthors\
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.7
 */

use MultipleAuthors\Classes\Authors_Iterator;

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('get_coauthors')) {
    function get_coauthors($post_id = 0)
    {
        return get_post_authors($post_id);
    }
}

if (!function_exists('is_coauthors_for_post')) {
    function is_coauthors_for_post($user, $post_id = 0)
    {
        return publishpress_authors_is_author_for_post($user, $post_id);
    }
}

if (!class_exists('Couthors_iterator')) {
    class Couthors_iterator extends Authors_Iterator
    {

    }
}

if (!function_exists('coauthors__echo')) {
    function coauthors__echo($tag, $type = 'tag', $separators = [], $tag_args = null, $echo = true)
    {
        return publishpress_authors_echo($tag, $type, $separators, $tag_args, $echo);
    }
}

if (!function_exists('coauthors')) {
    function coauthors($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_the_author($between, $betweenLast, $before, $after, $echo);
    }
}

if (!function_exists('coauthors_posts_links')) {
    function coauthors_posts_links(
        $between = null,
        $betweenLast = null,
        $before = null,
        $after = null,
        $echo = true
    ) {
        return publishpress_authors_posts_links($between, $betweenLast, $before, $after, $echo);
    }
}

if (!function_exists('coauthors_posts_links_single')) {
    function coauthors_posts_links_single($author)
    {
        return publishpress_authors_posts_links_single($author);
    }
}

if (!function_exists('coauthors_firstnames')) {
    function coauthors_firstnames($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_firstnames($between, $betweenLast, $before, $after, $echo);
    }
}

if (!function_exists('coauthors_lastnames')) {
    function coauthors_lastnames($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_lastnames($between, $betweenLast, $before, $after, $echo);
    }
}

if (!function_exists('coauthors_nicknames')) {
    function coauthors_nicknames($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_nicknames($between, $betweenLast, $before, $after, $echo);
    }
}

if (!function_exists('coauthors_links')) {
    function coauthors_links($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_links($between, $betweenLast, $before, $after, $echo);
    }
}

if (!function_exists('coauthors_emails')) {
    function coauthors_emails($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_emails($between, $betweenLast, $before, $after, $echo);
    }
}

if (!function_exists('coauthors_links_single')) {
    function coauthors_links_single($author)
    {
        return publishpress_authors_links_single($author);
    }
}

if (!function_exists('coauthors_ids')) {
    function coauthors_ids($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_ids($between, $betweenLast, $before, $after, $echo);
    }
}

if (!function_exists('get_the_coauthor_meta')) {
    function get_the_coauthor_meta($field)
    {
        return get_the_publishpress_author_meta($field);
    }
}

if (!function_exists('the_coauthor_meta')) {
    function the_coauthor_meta($field, $user_id = 0)
    {
        the_publishpress_author_meta($field, $user_id);
    }
}

if (!function_exists('coauthors_wp_list_authors')) {
    function coauthors_wp_list_authors($args)
    {
        return publishpress_authors_wp_list_authors($args);
    }
}

if (!function_exists('coauthors_get_avatar')) {
    function coauthors_get_avatar($coauthor, $size = 32, $default = '', $alt = false)
    {
        return publishpress_authors_get_avatar($coauthor, $size, $default, $alt);
    }
}

if (!function_exists('cap_filter_comment_moderation_email_recipients')) {
    /**
     * Filter array of moderation notification email addresses
     *
     * @param array $recipients
     * @param int $comment_id
     *
     * @return array
     */
    function cap_filter_comment_moderation_email_recipients($recipients, $comment_id)
    {
        $comment = get_comment($comment_id);
        $post_id = $comment->comment_post_ID;

        if (isset($post_id)) {
            $coauthors        = get_post_authors($post_id);
            $extra_recipients = [];
            foreach ($coauthors as $user) {
                if (!empty($user->user_email)) {
                    $extra_recipients[] = $user->user_email;
                }
            }

            return array_unique(array_merge($recipients, $extra_recipients));
        }

        return $recipients;
    }
}

if (!function_exists('cap_get_coauthor_terms_for_post')) {
    /**
     * Retrieve a list of coauthor terms for a single post.
     *
     * Grabs a correctly ordered list of authors for a single post, appropriately
     * cached because it requires `wp_get_object_terms()` to succeed.
     *
     * @param int $post_id ID of the post for which to retrieve authors.
     *
     * @return array Array of coauthor WP_Term objects
     */
    function cap_get_coauthor_terms_for_post($post_id)
    {
        global $multiple_authors_addon;

        return $multiple_authors_addon->get_coauthor_terms_for_post($post_id);
    }
}
