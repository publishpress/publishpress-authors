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

if (!class_exists('MA_All_In_One_Seo_Pack_Integration')) {
    /**
     * class MA_All_In_One_Seo_Pack_Integration
     */
    class MA_All_In_One_Seo_Pack_Integration extends Module
    {
        public $module_name = 'all_in_one_seo_pack_integration';

        /**
         * Instance for the module
         *
         * @var stdClass
         */
        public $module;
        public $module_url;

        /**
         * Construct the MA_All_In_One_Seo_Pack_Integration class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'             => __('All In One Seo Pack Integration', 'publishpress-authors'),
                'short_description' => __('Add compatibility with the All In One Seo Pack plugin', 'publishpress-authors'),
                'module_url'        => $this->module_url,
                'icon_class'        => 'dashicons dashicons-feedback',
                'slug'              => 'all-in-one-seo-pack-integration',
                'default_options'   => [
                    'enabled' => 'on',
                ],
                'options_page'      => false,
                'autoload'          => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_all_in_one_seo_pack_integration_default_options',
                $args['default_options']
            );

            $legacyPlugin = Factory::getLegacyPlugin();

            $this->module = $legacyPlugin->register_module($this->module_name, $args);

            parent::__construct();
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {
            add_filter('aioseo_title', [$this, 'all_in_one_seo_pack_author_title']);
        }

        /**
         * Set author page title
         *
         * @param string title
         * @return string
         */
        public function all_in_one_seo_pack_author_title($title) {

            if (is_author() && !is_tax('author')) {
                $archiveAuthor = get_archive_author();
                if (is_object($archiveAuthor) && isset($archiveAuthor->display_name)) {
                    $title = $archiveAuthor->display_name . $title;
                }
            }
            
            return $title;
        }

    }
}
