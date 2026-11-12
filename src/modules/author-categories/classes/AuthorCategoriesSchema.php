<?php

/**
 * @package     MultipleAuthorCategories
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       4.3.0
 */

namespace MultipleAuthorCategories;

/**
 * AuthorCategoriesSchema
 *
 * @package MultipleAuthorCategories\Classes
 *
 */
class AuthorCategoriesSchema
{
    /**
     * Author categories table name
     *
     * @return string
     */
    public static function tableName()
    {
        global $wpdb;

        return $wpdb->prefix . 'ppma_author_categories';
    }
    /**
     * Author categories meta table name
     *
     * @return string
     */
    public static function metaTableName()
    {
        global $wpdb;

        return $wpdb->prefix . 'ppma_author_categories_meta';
    }

    /**
     * Author categories relationship table name
     *
     * @return string
     */
    public static function relationTableName()
    {
        global $wpdb;

        return $wpdb->prefix . 'ppma_author_relationships';
    }

    /**
     * Check if a table exists
     *
     * @param string $table_name
     * @return bool
     */
    public static function tableExists($table_name)
    {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name))
        ) === $table_name;
    }

    /**
     * Create author categories table if not exist
     *
     * @return void
     */
    public static function createTableIfNotExists()
    {
        global $wpdb;

        $table_name = self::tableName();

        if (!self::tableExists($table_name)) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE {$table_name} (
                id bigint(20) unsigned NOT NULL auto_increment,
                category_name varchar(191) NOT NULL default '',
                plural_name varchar(191) NOT NULL default '',
                slug varchar(191) NOT NULL default '',
                category_order int(11) NOT NULL default 0,
                category_status int(11) NOT NULL default 1,
                created_at datetime NOT NULL,
                meta_data longtext NOT NULL default '',
                PRIMARY KEY  (id),
                UNIQUE KEY slug (slug),
                KEY category_name (category_name),
                KEY plural_name (plural_name)
            ) $charset_collate;";

            self::createTable($sql);
        }
    }

    /**
     * Create author categories table if not exist
     *
     * @return void
     */
    public static function createMetaTableIfNotExists()
    {
        global $wpdb;

        $table_name = self::metaTableName();

        if (!self::tableExists($table_name)) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE {$table_name} (
                meta_id bigint(20) unsigned NOT NULL auto_increment,
                category_id bigint(20) unsigned NOT NULL default '0',
                meta_key varchar(191) NOT NULL default '',
                meta_value longtext NOT NULL default '',
                PRIMARY KEY  (meta_id),
                KEY category_id (category_id),
                KEY meta_key (meta_key)
            ) $charset_collate;";

            self::createTable($sql);
        }
    }

    /**
     * Create author categories relationship table if not exist
     *
     * @return void
     */
    public static function createRelationTableIfNotExists()
    {
        global $wpdb;

        $table_name = self::relationTableName();

        if (!self::tableExists($table_name)) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE {$table_name} (
                id bigint(20) unsigned NOT NULL auto_increment,
                category_id bigint(20) unsigned NOT NULL,
                category_slug varchar(191) NOT NULL default '',
                post_id bigint(20) unsigned NOT NULL,
                author_term_id bigint(20) unsigned NOT NULL default '0',
                author_user_id bigint(20) unsigned NOT NULL default '0',
                PRIMARY KEY  (id),
                KEY category_id (category_id),
                KEY category_slug (category_slug),
                KEY post_id (post_id),
                KEY author_term_id (author_term_id),
                KEY author_user_id (author_user_id)
            ) $charset_collate;";

            self::createTable($sql);
        }
    }

    /**
     * Create new table
     *
     * @param string $sql
     */
    private static function createTable($sql)
    {
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta($sql);
    }
}
