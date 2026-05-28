<?php
/**
 * @package     MultipleAuthors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.1.0
 */

namespace MultipleAuthors\Classes;

use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Plugin;

/**
 * Class Term_Editor
 *
 * @package MultipleAuthors\Classes
 */
class Term_Editor
{
    /**
     * Register callbacks for managing custom columns
     */
    public static function action_admin_init()
    {
        add_filter("manage_author_custom_column", [__CLASS__, 'filter_manage_custom_column'], 10, 3);
        add_filter('manage_edit-author_columns', [__CLASS__, 'filter_columns']);
        add_filter('term_updated_messages', [__CLASS__, 'filter_author_updated_messages']);
    }

    /**
     * Modify author updated messages
     *
     * @param array $messages
     * @return array
     */
    public static function filter_author_updated_messages($messages) {

        $messages[Plugin::$coauthor_taxonomy] = array(
            0 => '',
            1 => __( 'New author added.', 'publishpress-authors' ),
            2 => __( 'Author profile deleted.', 'publishpress-authors' ),
            3 => __( 'Author profile updated.', 'publishpress-authors' ),
            4 => __( 'Error adding author account.', 'publishpress-authors' ),
            5 => __( 'Error updating author profile.', 'publishpress-authors' ),
            6 => __( 'Authors deleted.', 'publishpress-authors' ),
        );
    
        return $messages;
    }

    /**
     * @param string $column Name of the column.
     * @param int $post_id ID of the post being rendered.
     */
    public static function filter_manage_custom_column($content, $column, $term_id)
    {
        if ('author_url' === $column) {
            $author = Author::get_by_term_id($term_id);

            $content = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url($author->link),
                esc_html(urldecode($author->slug))
            );
        }

        return $content;
    }

    public static function filter_columns($columns)
    {
        if (isset($columns['slug'])) {
            unset($columns['slug']);
        }

        if (!isset($columns['author_url'])) {
            $columns['author_url'] = __('Author URL', 'publishpress-authors');
        }

        return $columns;
    }
}
