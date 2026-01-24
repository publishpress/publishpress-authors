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
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Factory;

if (!class_exists('MA_UltimateMember')) {
    /**
     * class MA_UltimateMember
     */
    class MA_UltimateMember_Integration extends Module
    {
        public $module_name = 'ultimatemember_integration';

        /**
         * Instance for the module
         *
         * @var stdClass
         */
        public $module;
        public $module_url;

        /**
         * Construct the MA_UltimateMember class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'             => __('Ultimate Member Integration', 'publishpress-authors'),
                'short_description' => __('Add compatibility with the Ultimate Member plugin', 'publishpress-authors'),
                'module_url'        => $this->module_url,
                'icon_class'        => 'dashicons dashicons-feedback',
                'slug'              => 'ultimatemember-integration',
                'default_options'   => [
                    'enabled' => 'on',
                ],
                'options_page'      => false,
                'autoload'          => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_ultimatemember_integration_default_options',
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
            add_filter('um_profile_query_make_posts', [$this, 'filterProfileMakePosts']);
        }

        public function filterProfileMakePosts($args)
        {
            $legacyPlugin = Factory::getLegacyPlugin();
            $selectedPostTypes = array_values(Util::get_post_types_for_module($legacyPlugin->modules->multiple_authors));

            if (isset($args['author']) && in_array($args['post_type'], $selectedPostTypes)) {
                if (isset($args['tax_query'])) {
                    $args['tax_query']['relation'] = 'AND';
                }

                $author = Author::get_by_user_id($args['author']);
                if ($author && is_object($author)) {
                    $args['tax_query'][] = [
                        'taxonomy' => 'author',
                        'field' => 'id',
                        'terms' => [$author->term_id],
                    ];
                }

            }

            return $args;
        }
    }
}
