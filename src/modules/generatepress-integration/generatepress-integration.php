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

if (!class_exists('MA_Generatepress_Integration')) {
    /**
     * class MA_Generatepress_Integration
     */
    class MA_Generatepress_Integration extends Module
    {
        public $module_name = 'generatepress_integration';

        /**
         * Instance for the module
         *
         * @var stdClass
         */
        public $module;
        public $module_url;

        /**
         * Construct the MA_Generatepress_Integration class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'             => __('Generatepress Integration', 'publishpress-authors'),
                'short_description' => __('Add compatibility with the Generatepress theme', 'publishpress-authors'),
                'module_url'        => $this->module_url,
                'icon_class'        => 'dashicons dashicons-feedback',
                'slug'              => 'generatepress-integration',
                'default_options'   => [
                    'enabled' => 'on',
                ],
                'options_page'      => false,
                'autoload'          => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_generatepress_integration_default_options',
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
            // Add compatibility with GeneratePress theme.
            add_filter('generate_post_author_output', [$this, 'generatepress_author_output']);
        }

        /**
         * Customize/fix the author byline output for the GeneratePress theme.
         *
         * @param $output
         *
         * @return false|string
         */
        public function generatepress_author_output($output)
        {
            global $post;

            $layout = apply_filters('pp_multiple_authors_generatepress_box_layout', 'inline');

            ob_start();
            do_action('pp_multiple_authors_show_author_box', false, $layout, false, true, $post->ID);

            $output = ob_get_clean();

            return $output;
        }
    }
}
