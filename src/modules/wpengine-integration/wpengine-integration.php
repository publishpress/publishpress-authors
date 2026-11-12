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
use MultipleAuthors\Factory;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MA_Wpengine_Integration')) {
    /**
     * class MA_Wpengine_Integration
     */
    class MA_Wpengine_Integration extends Module
    {
        public $module_name = 'wpengine_integration';

        /**
         * Instance for the module
         *
         * @var stdClass
         */
        public $module;
        public $module_url;

        /**
         * Construct the MA_Wpengine_Integration class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'             => __('WPEngine Integration', 'publishpress-authors'),
                'short_description' => __(
                    'Add compatibility with the WPEngine object cache.',
                    'publishpress-authors'
                ),
                'module_url'        => $this->module_url,
                'icon_class'        => 'dashicons dashicons-feedback',
                'slug'              => 'wpengine-integration',
                'default_options'   => [
                    'enabled' => 'on',
                ],
                'options_page'      => false,
                'autoload'          => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_wpengine_integration_default_options',
                $args['default_options']
            );

            $this->module = Factory::getLegacyPlugin()->register_module($this->module_name, $args);

            parent::__construct();
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {
            add_action('publishpress_authors_flush_cache', [$this, 'flushWPECache']);
            add_action('publishpress_authors_flush_cache_for_post', [$this, 'flushWPECacheForPost']);
        }

        public function flushWPECacheForPost($post_ids) {
            $this->flushWPECache();
        }

        /**
         * Full WP Engine cache flush.
         *
         * Based on WP Engine Cache Flush by Aaron Holbrook
         * https://github.org/a7/wpe-cache-flush/
         * http://github.org/a7/
         */
        public function flushWPECache()
        {
            if (!class_exists('WpeCommon')) {
                return false;
            }

            if (function_exists('WpeCommon::purge_memcached')) {
                \WpeCommon::purge_memcached();
            }

            if (function_exists('WpeCommon::clear_maxcdn_cache')) {
                \WpeCommon::clear_maxcdn_cache();
            }

            if (function_exists('WpeCommon::purge_varnish_cache')) {
                \WpeCommon::purge_varnish_cache();
            }

            global $wp_object_cache;
            // Check for valid cache. Sometimes this is broken -- we don't know why! -- and it crashes when we flush.
            // If there's no cache, we don't need to flush anyway.

            if (!empty($wp_object_cache) && is_object($wp_object_cache)) {
                if (function_exists('wp_cache_flush')) {
                    wp_cache_flush();
                }
            }
        }
    }
}
