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
use MultipleAuthors\Classes\Legacy\Util;
use MultipleAuthors\Factory;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MA_Ultimate_Post_Integration')) {
    /**
     * class MA_Ultimate_Post_Integration
     */
    class MA_Ultimate_Post_Integration extends Module
    {
        public $module_name = 'ultimate_post_integration';

        /**
         * Instance for the module
         *
         * @var stdClass
         */
        public $module;
        public $module_url;

        /**
         * Construct the MA_Ultimate_Post_Integration class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'             => __('Ultimate_Post Integration', 'publishpress-authors'),
                'short_description' => __('Add compatibility with the Ultimate Post plugin', 'publishpress-authors'),
                'module_url'        => $this->module_url,
                'icon_class'        => 'dashicons dashicons-feedback',
                'slug'              => 'ultimate-post-integration',
                'default_options'   => [
                    'enabled' => 'on',
                ],
                'options_page'      => false,
                'autoload'          => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_ultimate_post_integration_default_options',
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
            add_filter(
                'postx_loop_author_by',
                [$this, 'filter_postx_loop_author_by'],
                11,
                4
            );
        }

        /**
         * @param $authorBy
         * @param $user_id
         * @param $post_id
         * @param $class_name
         *
         * @return string
         */
        public function filter_postx_loop_author_by($authorBy, $user_id, $post_id, $class_name)
        {

            $authors = get_post_authors($post_id);

            $metaAuthorPrefix = apply_filters('pp_author_ultimate_post_author_prefix', __('By', 'publishpress-authors'));

            $output = '<span class="ultp-block-author">'. $metaAuthorPrefix .'';
            foreach ($authors as $index => $author) {
                if ($index !== 0) {
                    $output .= ', ';
                }
                $output .= '<a href="' . $author->link . '">' . $author->display_name .'</a>';
            }
            $output .= '</span>';

            return $output;
        }
    }
}
