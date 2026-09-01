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
use MultipleAuthors\Factory;
use WP_Error;

/**
 * Utility methods for managing authors
 *
 * @package MultipleAuthors\Classes
 *
 */
abstract class Author_Utils
{
    private static $authorTermByEmail = [];

    public static function get_author_term_id_by_email($emailAddress, $ignoreCache = false)
    {
        global $wpdb;

        if (!is_string($emailAddress)) {
            return false;
        }

        $emailAddress = sanitize_email($emailAddress);

        if (empty($emailAddress)) {
            return false;
        }

        if (!isset(static::$authorTermByEmail[$emailAddress]) || $ignoreCache) {
            // Get all termmeta with that value, for author terms
            $terms = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT tm.term_id
                            FROM {$wpdb->termmeta} as tm 
                            INNER JOIN {$wpdb->term_taxonomy} as tt ON (tm.term_id = tt.term_id)
                            WHERE tm.meta_value = %s AND
                            tt.taxonomy = 'author'",
                    $emailAddress
                )
            );

            if (empty($terms) || is_wp_error($terms)) {
                static::$authorTermByEmail[$emailAddress] = false;
            } else {
                $firstTerm = $terms[0];
    
                static::$authorTermByEmail[$emailAddress] = $firstTerm->term_id;
            }
        }

        return static::$authorTermByEmail[$emailAddress];
    }

    public static function author_has_custom_avatar($termId)
    {
        $legacyPlugin = Factory::getLegacyPlugin();

        $avatarAttachmentId = (int)get_term_meta($termId, 'avatar', true);

        if ($avatarAttachmentId === 0) {
            $avatarAttachmentId = (int)isset($legacyPlugin->modules->multiple_authors->options->default_avatar) ? $legacyPlugin->modules->multiple_authors->options->default_avatar : 0;
        }

        return !empty($avatarAttachmentId);
    }

    public static function get_author_meta($termId, $metaKey, $single = true)
    {
        return get_term_meta($termId, $metaKey, $single);
    }

    public static function update_author_meta($termId, $metaKey, $value, $single = true)
    {
        return update_term_meta($termId, $metaKey, $value);
    }

    public static function validate_author_email_available($emailAddress, $allowedUserId = 0)
    {
        $emailAddress = sanitize_email($emailAddress);

        if (empty($emailAddress)) {
            return true;
        }

        $existingUserId = email_exists($emailAddress);

        if (!empty($existingUserId) && (int)$existingUserId !== (int)$allowedUserId) {
            return new WP_Error(
                'publishpress_authors_email_exists',
                __('This email address is already linked to another WordPress user.', 'publishpress-authors')
            );
        }

        return true;
    }

    public static function unlink_author_from_user($termId)
    {
        $metas = get_term_meta($termId);

        foreach ($metas as $key => $meta) {
            if (0 === strpos($key, 'user_id_')) {
                delete_term_meta($termId, $key);
            }
        }

        delete_term_meta($termId, 'user_id');
        clean_term_cache($termId, 'author');
        Author::clear_cache();
    }

    public static function author_is_guest($termId)
    {
        $userId = (int)self::get_author_meta($termId, 'user_id');

        return empty($userId);
    }

    public static function get_avatar_url($termId, $size = 96)
    {
        $legacyPlugin = Factory::getLegacyPlugin();

        $url = false;

        if (self::author_has_custom_avatar($termId)) {
            $avatar_attachment_id = (int)self::get_author_meta($termId, 'avatar');

        if ($avatar_attachment_id === 0) {
            $avatar_attachment_id = (int)isset($legacyPlugin->modules->multiple_authors->options->default_avatar) ? $legacyPlugin->modules->multiple_authors->options->default_avatar : 0;
        }

            if (!empty($avatar_attachment_id)) {
                $url = wp_get_attachment_image_url($avatar_attachment_id, $size);
            }
        }

        return $url;
    }
}
