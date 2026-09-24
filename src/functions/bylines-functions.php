<?php
/**
 * @package     MultipleAuthors\
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.7
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('get_bylines')) {
    /**
     * Get all bylines for a post.
     *
     * @param WP_Post|null $post Post to fetch bylines for. Defaults to global post.
     *
     * @return array Array of Byline objects, a single WP_User object, or empty.
     */
    function get_bylines($post = null)
    {
        return get_post_authors($post);
    }
}

if (!function_exists('the_bylines')) {
    /**
     * Renders the bylines display names, without links to their posts.
     *
     * Equivalent to the_author() template tag.
     *
     * @return string
     */
    function the_bylines()
    {
        return publishpress_authors_the_authors();
    }
}

if (!function_exists('get_the_bylines')) {
    /**
     * Gets the bylines display names, without links to their posts.
     *
     * Equivalent to get_the_author() template tag.
     *
     * @return string
     */
    function get_the_bylines()
    {
        return publishpress_authors_get_the_authors();
    }
}

if (!function_exists('the_bylines_posts_links')) {
    /**
     * Renders the bylines display names, with links to their posts.
     *
     * Equivalent to the_author_posts_link() template tag.
     *
     * @return string
     */
    function the_bylines_posts_links()
    {
        return publishpress_authors_the_authors_posts_links();
    }
}

if (!function_exists('get_the_bylines_posts_links')) {
    /**
     * Renders the bylines display names, with links to their posts.
     *
     * @return string
     */
    function get_the_bylines_posts_links()
    {
        return publishpress_authors_get_the_authors_posts_links();
    }
}

if (!function_exists('the_bylines_links')) {
    /**
     * Renders the bylines display names, with their website link if it exists.
     *
     * Equivalent to the_author_link() template tag.
     *
     * @return string
     */
    function the_bylines_links()
    {
        return publishpress_authors_the_authors_links();
    }
}

if (!function_exists('get_the_bylines_links')) {
    /**
     * Renders the bylines display names, with their website link if it exists.
     *
     * @return string
     */
    function get_the_bylines_links()
    {
        return publishpress_authors_get_the_authors_links();
    }
}

if (!function_exists('bylines_render')) {
    /**
     * Display one or more bylines, according to arguments provided.
     *
     * @param array $bylines Set of bylines to display.
     * @param callable $render_callback Callback to return rendered byline.
     * @param array $args Arguments to affect display.
     *
     * @return string
     */
    function bylines_render($bylines, $render_callback, $args = [])
    {
        return publishpress_authors_render($bylines, $render_callback, $args);
    }
}
