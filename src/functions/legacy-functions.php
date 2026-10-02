<?php

/**
 * @package   MultipleAuthors
 * @author    PublishPress <help@publishpress.com>
 * @copyright Copyright (C) 2018 PublishPress. All rights reserved.
 * @license   GPLv2 or later
 * @since     1.0.0
 * 
 * These are deprecated functions that are no longer in use.
 * More note of them can be seen in https://github.com/publishpress/PublishPress-Authors/issues/520
 * 
 */


if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('get_multiple_authors')) {
    /**
     * Get all authors for a post.
     * 
     * @deprecated use publishpress_authors_get_post_authors instead.
     *
     * @param WP_Post|int|null $post Post to fetch authors for. Defaults to global post.
     * @param bool $filter_the_author_deprecated Deprecated. Removed for fixing infinity loop issues.
     * @param bool $archive_deprecated If true, will ignore the $post param and return the current author. Deprecated, use function get_archive_author instead.
     *                                            specified by the "author_name" URL param - for author pages.
     * @param bool $ignoreCache This cache cause sometimes errors in data received especially
     *                                            in quick edit after saving.
     *                                            That's why in Post_Editor we called this function with overriding
     *                                            ignoreCache value to be equal true.
     * @return array Array of Author objects, a single WP_User object, or empty.
     */
    function get_multiple_authors($post = 0, $filter_the_author_deprecated = false, $archive_deprecated = false, $ignoreCache = false)
    {
        return publishpress_authors_get_post_authors($post, $filter_the_author_deprecated, $archive_deprecated, $ignoreCache);
    }
}

if (!function_exists('multiple_authors_get_all_authors')) {
    /**
     * 
     * @deprecated use publishpress_authors_get_all_authors instead.
     * 
     * @param array $args
     * @param array $instance The widget  call object instance.
     *
     * @return array|int|WP_Error
     */
    function multiple_authors_get_all_authors($args = [], $instance = [])
    {

        return publishpress_authors_get_all_authors($args, $instance);
    }
}

if (!function_exists('is_multiple_author_for_post')) {
    /**
     * Checks to see if the the specified user is author of the current global post or post (if specified)
     * 
     * @deprecated use publishpress_authors_is_author_for_post instead.
     *
     * @param object|int $user
     * @param int $post_id
     */
    function is_multiple_author_for_post($user, $post_id = 0)
    {

        return publishpress_authors_is_author_for_post($user, $post_id);
    }
}

if (!function_exists('multiple_authors__echo')) {
    /**
     * Helper function for the following new template tags
     * 
     * @deprecated use publishpress_authors_echo instead.
     *
     * @param [type] $tag
     * @param string $type
     * @param array $separators
     * @param [type] $tag_args
     * @param boolean $echo
     * @return void
     */
    function multiple_authors__echo($tag, $type = 'tag', $separators = [], $tag_args = null, $echo = true)
    {
        return publishpress_authors_echo($tag, $type, $separators, $tag_args, $echo);
    }
}

if (!function_exists('multiple_authors')) {
    /**
     * Outputs the co-authors display names, without links to their posts.
     * PublishPress Authors equivalent of the_author() template tag.
     * 
     * @deprecated use publishpress_authors_the_author instead.
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function multiple_authors($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_the_author($between, $betweenLast, $before, $after, $echo);
    }
}

if (!function_exists('multiple_authors_posts_links')) {
    /**
     * Outputs the co-authors display names, with links to their posts.
     * PublishPress Authors equivalent of the_author_posts_link() template tag.
     * 
     * @deprecated use publishpress_authors_posts_links instead.
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function multiple_authors_posts_links(
        $between = null,
        $betweenLast = null,
        $before = null,
        $after = null,
        $echo = true
    ) {
        return publishpress_authors_posts_links(
            $between,
            $betweenLast,
            $before,
            $after,
            $echo
        );
    }
}

if (!function_exists('multiple_authors_posts_links_single')) {
    /**
     * Outputs a single co-author linked to their post archive.
     * 
     * @deprecated use publishpress_authors_posts_links_single instead.
     *
     * @param object $author
     *
     * @return string
     */
    function multiple_authors_posts_links_single($author)
    {
        return publishpress_authors_posts_links_single($author);
    }
}

if (!function_exists('multiple_authors_firstnames')) {
    /**
     * Outputs the co-authors first names, without links to their posts.
     * 
     * @deprecated use publishpress_authors_firstnames instead.
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function multiple_authors_firstnames(
        $between = null,
        $betweenLast = null,
        $before = null,
        $after = null,
        $echo = true
    ) {
        return publishpress_authors_firstnames(
            $between,
            $betweenLast,
            $before,
            $after,
            $echo
        );
    }
}

if (!function_exists('multiple_authors_lastnames')) {
    /**
     * Outputs the co-authors last names, without links to their posts.
     * 
     * @deprecated use publishpress_authors_lastnames instead.
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function multiple_authors_lastnames(
        $between = null,
        $betweenLast = null,
        $before = null,
        $after = null,
        $echo = true
    ) {
        return publishpress_authors_lastnames(
            $between,
            $betweenLast,
            $before,
            $after,
            $echo
        );
    }
}

if (!function_exists('multiple_authors_nicknames')) {
    /**
     * Outputs the co-authors nicknames, without links to their posts.
     * 
     * @deprecated use publishpress_authors_nicknames instead.
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function multiple_authors_nicknames(
        $between = null,
        $betweenLast = null,
        $before = null,
        $after = null,
        $echo = true
    ) {

        return publishpress_authors_nicknames(
            $between,
            $betweenLast,
            $before,
            $after,
            $echo
        );
    }
}

if (!function_exists('multiple_authors_links')) {
    /**
     * Outputs the co-authors display names, with links to their websites if they've provided them.
     * 
     * @deprecated use publishpress_authors_links instead.
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function multiple_authors_links($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_links($between, $betweenLast, $before, $after, $echo);
    }
}

if (!function_exists('multiple_authors_emails')) {
    /**
     * Outputs the co-authors email addresses
     * 
     * @deprecated use publishpress_authors_emails instead.
     *
     * @param string $between Delimiter that should appear between the email addresses
     * @param string $betweenLast Delimiter that should appear between the last two email addresses
     * @param string $before What should appear before the presentation of email addresses
     * @param string $after What should appear after the presentation of email addresses
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function multiple_authors_emails($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_emails($between, $betweenLast, $before, $after, $echo);
    }
}

if (!function_exists('multiple_authors_links_single')) {
    /**
     * Outputs a single co-author, linked to their website if they've provided one.
     * 
     * @deprecated use publishpress_authors_links_single instead.
     *
     * @param object $author
     *
     * @return string
     */
    function multiple_authors_links_single($author)
    {
        return publishpress_authors_links_single($author);
    }
}

if (!function_exists('multiple_authors_ids')) {
    /**
     * Outputs the co-authors IDs
     * 
     * @deprecated use publishpress_authors_ids instead.
     *
     * @param string $between Delimiter that should appear between the co-authors
     * @param string $betweenLast Delimiter that should appear between the last two co-authors
     * @param string $before What should appear before the presentation of co-authors
     * @param string $after What should appear after the presentation of co-authors
     * @param bool $echo Whether the co-authors should be echoed or returned. Defaults to true.
     */
    function multiple_authors_ids($between = null, $betweenLast = null, $before = null, $after = null, $echo = true)
    {
        return publishpress_authors_ids($between, $betweenLast, $before, $after, $echo);
    }
}

if (!function_exists('get_the_multiple_author_meta')) {
    /**
     * Undocumented function
     * 
     * @deprecated use get_the_publishpress_author_meta instead.
     *
     * @param [type] $field
     * @return void
     */
    function get_the_multiple_author_meta($field)
    {
        return get_the_publishpress_author_meta($field);
    }
}

if (!function_exists('the_multiple_author_meta')) {
    function the_multiple_author_meta($field, $user_id = 0)
    {
        the_publishpress_author_meta($field, $user_id);
    }
}

if (!function_exists('multiple_authors_wp_list_authors')) {
    /**
     * List all the *co-authors* of the blog, with several options available.
     * optioncount (boolean) (false): Show the count in parenthesis next to the author's name.
     * show_fullname (boolean) (false): Show their full names.
     * hide_empty (boolean) (true): Don't show authors without any posts.
     * feed (string) (''): If isn't empty, show links to author's feeds.
     * feed_image (string) (''): If isn't empty, use this image to link to feeds.
     * echo (boolean) (true): Set to false to return the output, instead of echoing.
     * 
     * @deprecated use publishpress_authors_wp_list_authors instead.
     *
     * @param array $args The argument array.
     *
     * @return null|string The output, if echo is set to false.
     */
    function multiple_authors_wp_list_authors($args = [])
    {    
        return publishpress_authors_wp_list_authors($args);
    }
}

if (!function_exists('multiple_authors_get_avatar')) {
    /**
     * Retrieve a Co-Author's Avatar.
     *
     * Since Guest Authors doesn't enforce unique email addresses, simply loading the avatar by email won't work when
     * multiple Guest Authors share the same address.
     *
     * This is a replacement for using get_avatar(), which only operates on email addresses and cannot differentiate
     * between Guest Authors (who may share an email) and regular user accounts
     * 
     * @deprecated use publishpress_authors_get_avatar instead.
     *
     * @param object $coauthor The Co Author
     * @param int $size The desired size
     *
     * @return string             The image tag for the avatar, or an empty string if none could be determined
     */
    function multiple_authors_get_avatar($coauthor, $size = 32, $default = '', $alt = false)
    {
        return publishpress_authors_get_avatar($coauthor, $size, $default, $alt);
    }
}

if (!function_exists('the_authors')) {
    /**
     * Renders the authors display names, without links to their posts.
     *
     * Equivalent to the_author() template tag.
     * 
     * @deprecated use publishpress_authors_the_authors instead.
     */
    function the_authors()
    {
        publishpress_authors_the_authors();
    }
}

if (!function_exists('get_the_authors')) {
    /**
     * Gets the authors display names, without links to their posts.
     *
     * Equivalent to get_the_author() template tag.
     * 
     * @deprecated use publishpress_authors_get_the_authors instead.
     */
    function get_the_authors()
    {
        return publishpress_authors_get_the_authors();
    }
}

if (!function_exists('the_authors_posts_links')) {
    /**
     * Renders the authors display names, with links to their posts.
     *
     * Equivalent to the_author_posts_link() template tag.
     * 
     * @deprecated use publishpress_authors_get_the_authors instead.
     */
    function the_authors_posts_links()
    {
        publishpress_authors_the_authors_posts_links();
    }
}

if (!function_exists('get_the_authors_posts_links')) {
    /**
     * Renders the authors display names, with links to their posts.
     * 
     * @deprecated use publishpress_authors_get_the_authors_posts_links instead.
     */
    function get_the_authors_posts_links()
    {
        return publishpress_authors_get_the_authors_posts_links();
    }
}

if (!function_exists('the_authors_links')) {
    /**
     * Renders the authors display names, with their website link if it exists.
     *
     * Equivalent to the_author_link() template tag.
     * 
     * @deprecated use publishpress_authors_the_authors_links instead.
     */
    function the_authors_links()
    {
        publishpress_authors_the_authors_links();
    }
}

if (!function_exists('get_the_authors_links')) {
    /**
     * Renders the authors display names, with their website link if it exists.
     * 
     * @deprecated use publishpress_authors_get_the_authors_links instead.
     */
    function get_the_authors_links()
    {
        return publishpress_authors_get_the_authors_links();
    }
}

if (!function_exists('authors_render')) {
    /**
     * Display one or more authors, according to arguments provided.
     * 
     * @deprecated use publishpress_authors_render instead.
     *
     * @param array $authors Set of authors to display.
     * @param callable $render_callback Callback to return rendered author.
     * @param array $args Arguments to affect display.
     */
    function authors_render($authors, $render_callback, $args = [])
    {
        return publishpress_authors_render($authors, $render_callback, $args);
    }
}