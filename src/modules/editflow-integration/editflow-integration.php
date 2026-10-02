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

if (!class_exists('MA_Editflow_Integration')) {
    /**
     * class MA_Editflow_Integration
     */
    class MA_Editflow_Integration extends Module
    {
        public $module_name = 'editflow_integration';

        /**
         * Instance for the module
         *
         * @var stdClass
         */
        public $module;
        public $module_url;

        /**
         * Construct the MA_Editflow_Integration class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'             => __('Edit Flow Integration', 'publishpress-authors'),
                'short_description' => __(
                    'Add compatibility with the Edit Flow plugin',
                    'publishpress-authors'
                ),
                'module_url'        => $this->module_url,
                'icon_class'        => 'dashicons dashicons-feedback',
                'slug'              => 'editflow-integration',
                'default_options'   => [
                    'enabled' => 'on',
                ],
                'options_page'      => false,
                'autoload'          => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_editflow_integration_default_options',
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
            add_filter(
                'ef_calendar_item_information_fields',
                [$this, 'filterCalendarItemFields'],
                10,
                2
            );
            add_filter(
                'ef_story_budget_term_column_value',
                [$this, 'filterStoryBudgetTermColumnValue'],
                10,
                3
            );
        }

        /**
         * Filter Edit Flow's 'ef_calendar_item_information_fields' to add co-authors
         *
         * @param array $information_fields
         * @param int $post_id
         *
         * @return array
         */
        public function filterCalendarItemFields($information_fields, $post_id)
        {
            // Don't add the author row again if another plugin has removed
            if (!array_key_exists('author', $information_fields)) {
                return $information_fields;
            }

            $co_authors = get_post_authors($post_id);
            if (count($co_authors) > 1) {
                $information_fields['author']['label'] = __('Authors', 'publishpress-authors');
            }
            $co_authors_names = '';
            foreach ($co_authors as $co_author) {
                $co_authors_names .= $co_author->display_name . ', ';
            }
            $information_fields['author']['value'] = rtrim($co_authors_names, ', ');

            return $information_fields;
        }

        /**
         * Filter Edit Flow's 'ef_story_budget_term_column_value' to add co-authors to the story budget
         *
         * @param string $column_name
         * @param object $post
         * @param object $parent_term
         *
         * @return string
         */
        public function filterStoryBudgetTermColumnValue($column_name, $post, $parent_term)
        {
            // We only want to modify the 'author' column
            if ('author' != $column_name) {
                return $column_name;
            }

            $co_authors       = get_post_authors($post->ID);
            $co_authors_names = '';
            foreach ($co_authors as $co_author) {
                $co_authors_names .= $co_author->display_name . ', ';
            }

            return rtrim($co_authors_names, ', ');
        }
    }
}
