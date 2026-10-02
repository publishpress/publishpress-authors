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

use MultipleAuthors\Classes\Content_Model;
use MultipleAuthors\Classes\Legacy\Module;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MA_Debug')) {
    /**
     * class MA_Debug
     */
    class MA_Debug extends Module
    {
        public $module_name = 'debug';

        /**
         * Instance for the module
         *
         * @var stdClass
         */
        public $module;
        public $module_url;

        /**
         * Construct the MA_Debug class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'             => __('Debug', 'publishpress-authors'),
                'short_description' => __('Add debug information for the plugin', 'publishpress-authors'),
                'module_url'        => $this->module_url,
                'icon_class'        => 'dashicons dashicons-feedback',
                'slug'              => 'debug',
                'default_options'   => [
                    'enabled' => 'on',
                ],
                'options_page'      => false,
                'autoload'          => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_debug_default_options',
                $args['default_options']
            );

            $legacyPlugin = Factory::getLegacyPlugin();

            $this->module = $legacyPlugin->register_module($this->module_name, $args);
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {
            if (is_admin()) {
                add_action(
                    'add_meta_boxes',
                    [$this, 'addMetaBoxForDebugInformation'],
                    105
                );
            }
        }

        public function addMetaBoxForDebugInformation()
        {
            $supportedPostTypes = Content_Model::get_author_supported_post_types();

            foreach ($supportedPostTypes as $postType) {
                add_meta_box(
                    'authors_debug',
                    __('Authors Debug', 'publishpress-authors'),
                    [$this, 'renderDebugMetaBox'],
                    $postType,
                    'normal',
                    'default'
                );
            }
        }

        public function renderDebugMetaBox()
        {
            global $wpdb;

            $dataList = [];

            // The post ID.
            $post = get_post();
            $dataList['$post->ID'] = $post->ID;

            // Query the post_author.
            $postAuthor = $wpdb->get_var(
                $wpdb->prepare("SELECT post_author FROM {$wpdb->posts} WHERE ID = %d", $post->ID)
            );
            $dataList['$post->post_author'] = $postAuthor;

            // get_post_authors function.
            $resultGetMultipleAuthors = get_post_authors();
            $dataList['get_post_authors()'] = $resultGetMultipleAuthors;

            // Get the post terms for "author".
            $authorTerms = wp_get_post_terms($post->ID, 'author');
            $dataList['Post terms [author]'] = $authorTerms;

            echo '<pre><ul>';
            foreach ($dataList as $key => $data) {
                echo '<li style="border-bottom: 1px solid silver; padding: 5px;">' . esc_html($key) . ' = ' . esc_html(print_r($data, true)) . '</li>';
            }
            echo '</ul></pre>';
        }
    }
}
