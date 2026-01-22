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
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;
use MA_Author_Custom_Fields as PPAuthorFields;

if (!class_exists('MA_Rank_Math_Seo_Integration')) {
    /**
     * class MA_Rank_Math_Seo_Integration
     */
    class MA_Rank_Math_Seo_Integration extends Module
    {
        public $module_name = 'rank_math_seo_integration';

        /**
         * Instance for the module
         *
         * @var stdClass
         */
        public $module;
        public $module_url;

        /**
         * Construct the MA_Rank_Math_Seo_Integration class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'             => __('Rank Math Seo Integration', 'publishpress-authors'),
                'short_description' => __('Add compatibility with the Rank Math Seo plugin', 'publishpress-authors'),
                'module_url'        => $this->module_url,
                'icon_class'        => 'dashicons dashicons-feedback',
                'slug'              => 'rank-math-seo-integration',
                'default_options'   => [
                    'enabled' => 'on',
                ],
                'options_page'      => false,
                'autoload'          => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_rank_math_seo_integration_default_options',
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
            // Add support for structured data for authors in Rank Math Seo plugin.
            add_filter('rank_math/json_ld', [$this, 'rank_math_seo_json_ld'], 99, 2);
            add_filter('rank_math/json_ld', [$this, 'rank_math_author_term_seo_json_ld'], 99, 2);
        }

        /**
         * Generate author schema for an author
         *
         * @param object $author
         * @return array
         */
        private function generate_author_schema($author) {

            if (!is_object($author)) {
                return [];
            }

            $author_avatar = $author->get_avatar_url();
            if (is_array($author_avatar)) {
                $author_avatar = $author_avatar['url'];
            }
            $author_profile_schema = [
                '@type'         => 'Person',
                '@id'           => $author->link,
                'name'         => $author->display_name,
                'description'  => $author->description,
                'url'          => $author->link,
                'image'        => [
                    '@type'     => 'ImageObject',
                    '@id'         => $author_avatar,
                    'url'        => $author_avatar,
                    'caption'    => $author->display_name,
                    'inLanguage' => apply_filters('rank_math/schema/language', get_bloginfo('language'))
                ]
            ];

            $author_profile_schema = $this->add_author_schema_property($author_profile_schema, $author);

            return $author_profile_schema;
        }

        /**
         * Add support for structured data for author terms
         * in Rank Math Seo plugin.
         *
         * @param $output
         *
         * @return false|string
         */
        public function rank_math_author_term_seo_json_ld($data, $jsonld)
        {
            if (is_tax('author')) {

                if (!function_exists('get_archive_author')) {
                    require_once PP_AUTHORS_BASE_PATH . 'src/functions/template-tags.php';
                }

                $page_author         = get_archive_author();
                if (is_object($page_author) && isset($page_author->display_name)) {
                    $author_profile_data  = $this->generate_author_schema($page_author);

                    $data['WebPage']['@type']  = 'ProfilePage';
                    $data['ProfilePage']        = $author_profile_data;

                    $mainEntityOfPage = $page_author->link;
                    if (isset($data['WebPage']['@id'])) {
                        $mainEntityOfPage = $data['WebPage']['@id'];
                    }
                    $data['ProfilePage']['mainEntityOfPage'] = [
                        '@id' => $mainEntityOfPage
                    ];
                }

            }

            return $data;
        }

        /**
         * Add support for structured data for post multiple authors
         * in Rank Math Seo plugin.
         *
         * @param $output
         *
         * @return false|string
         */
        public function rank_math_seo_json_ld($data, $jsonld)
        {

            if (!empty($data) && is_singular(Utils::get_enabled_post_types())) {

                if (!function_exists('publishpress_authors_get_post_authors')) {
                    require_once PP_AUTHORS_BASE_PATH . 'src/functions/template-tags.php';
                }

                $post_authors        = publishpress_authors_get_post_authors();
                $post_author         = $post_authors[0];
                $authors_schema_data = [];

                if (is_object($post_author) && isset($post_author->display_name)) {
                    $author_profile_data  = $this->generate_author_schema($post_author);

                    if (count($post_authors) === 1) {
                        $profile_page_authors = ['@id' => $post_author->link];
                        $publisher_profile_page_authors = ['@id' => $post_author->link, 'name' => $post_author->display_name];
                        $authors_schema_data[$post_author->user_email] = $this->generate_author_schema($post_author);
                    } else {
                        $profile_page_authors = [];
                        $publisher_profile_page_authors = [];
                        foreach ($post_authors as $key => $post_author) {
                            if (is_object($post_author) && isset($post_author->display_name)) {
                                $profile_page_authors[] = $this->generate_author_schema($post_author);
                                $publisher_profile_page_authors[] = $this->generate_author_schema($post_author);
                                $authors_schema_data[$post_author->user_email] = $this->generate_author_schema($post_author);
                            }
                        }
                    }

                    if (isset($data['richSnippet'])) {
                        $data['richSnippet']['author'] = $profile_page_authors;
                    }
                    $data['ProfilePage'] = $author_profile_data;

                    if (isset($data['publisher']) && !empty($data['richSnippet']['@type'])) {

                        $publisherTypes = [];
                        $richSnippetType = [];
                        if (isset($data['publisher']['@type'])) {
                            $publisherTypes = (array) $data['publisher']['@type'];
                        }
                        if (isset($data['richSnippet']['@type'])) {
                            $richSnippetType = (array) $data['richSnippet']['@type'];
                        }

                        // #2196 - Never override Organization publisher with Person data
                        // #1901 - Do not override publisher for NewsArticle
                        $canUpdate = false;
                        if (
                            count($publisherTypes) === 1
                            && in_array('Person', $publisherTypes, true)
                            && !in_array('NewsArticle', $richSnippetType, true)
                        ) {
                            $canUpdate = true;
                        }

                        if ($canUpdate) {
                            $data_publisher = $data['publisher'];
                            if (isset($author_profile_data['@name'])) {
                                $data_publisher['name'] = $author_profile_data['@name'];
                            } elseif (isset($author_profile_data['name'])) {
                                $data_publisher['name'] = $author_profile_data['name'];
                            }
                            if (isset($author_profile_data['sameAs'])) {
                                $data_publisher['sameAs'] = $author_profile_data['sameAs'];
                            }

                            if (isset($author_profile_data['@image'])) {
                                if (isset($data_publisher['image'])) {
                                    $data_publisher['image'] = $author_profile_data['@image'];
                                } elseif (isset($data_publisher['logo'])) {
                                    $data_publisher['logo'] = $author_profile_data['@image'];
                                }
                            } elseif (isset($author_profile_data['image'])) {
                                if (isset($data_publisher['image'])) {
                                    $data_publisher['image'] = $author_profile_data['image'];
                                } elseif (isset($data_publisher['logo'])) {
                                    $data_publisher['logo'] = $author_profile_data['image'];
                                }
                            }
                            $data['publisher']       = $data_publisher;
                        }
                    }

                    //replace author at every possible location
                    foreach ($data as $index => $details) {
                        if (isset($details['author'])) {
                            $data[$index]['author'] = $publisher_profile_page_authors;
                        }

                        // add author category schema property
                        if ($index == 'WebPage') {
                            $author_categorized = false;
                            foreach (get_ppma_author_categories() as $author_category) {
                                if (!empty($author_category['schema_property'])) {
                                    if (!$author_categorized) {
                                        $author_categorized = ppma_post_authors_categorized();
                                    }
                                    if (isset($author_categorized[$author_category['slug']])) {
                                        $category_schema = [];
                                        foreach ($author_categorized[$author_category['slug']] as $author_categorized_author) {
                                            if (isset($authors_schema_data[$author_categorized_author->user_email])) {
                                                $category_schema[] = $authors_schema_data[$author_categorized_author->user_email];
                                            }
                                        }
                                        if (count($category_schema) > 0) {
                                            $data[$index][$author_category['schema_property']] = count($category_schema) > 1 ? $category_schema : $category_schema[0];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

            }

            return $data;
        }

        /**
         * Builds our SameAs array.
         *
         * @param array   $data         The Person schema data.
         * @param WP_User $author The user data object.
         *
         * @return array The Person schema data.
         */
        protected function add_author_schema_property($data, $author)
        {

            $author_fields = get_posts(
                [
                    'post_type' => PPAuthorFields::POST_TYPE_CUSTOM_FIELDS,
                    'posts_per_page' => 100,
                    'post_status' => 'publish',
                    'meta_query' => [
                        'relation' => 'OR',
                        [
                            'relation' => 'AND',
                            [
                                'key' => 'ppmacf_social_profile',
                                'value' => 1,
                                'type' => 'NUMERIC',
                                'compare' => '='
                            ],
                            [
                                'key' => 'ppmacf_type',
                                'value' => 'url',
                                'compare' => '='
                            ]
                        ],
                        [
                            'key' => 'ppmacf_schema_property',
                            'value' => '',
                            'compare' => '!='
                        ]
                    ],
                ]
            );

            $same_as_urls = [];

            if (! empty($author->user_url)) {
                $same_as_urls[] = $author->user_url;
            }

            if (!empty($author_fields)) {
                foreach ($author_fields as $author_field) {

                    $field_value = isset($author->{$author_field->post_name}) ? $author->{$author_field->post_name} : '';
                    if (! empty(trim($field_value))) {
                        $ppmacf_social_profile = get_post_meta($author_field->ID, 'ppmacf_social_profile', true);
                        $ppmacf_type = get_post_meta($author_field->ID, 'ppmacf_type', true);
                        $ppmacf_schema_property = get_post_meta($author_field->ID, 'ppmacf_schema_property', true);

                        if ($ppmacf_type == 'url' && (int) $ppmacf_social_profile === 1) {
                            $same_as_urls[] = $field_value;
                        }

                        if (!empty($ppmacf_schema_property)) {
                            if (isset($data[$ppmacf_schema_property])) {
                                $schema_property_value = \array_values(\array_unique((array)$data[$ppmacf_schema_property]));
                                $schema_property_value[] = strip_tags($field_value);
                                $data[$ppmacf_schema_property] = $schema_property_value;
                            } else {
                                $data[$ppmacf_schema_property] = strip_tags($field_value);
                            }

                        }
                    }
                }
            }

            // When CAP adds it, add the social profiles here.
            if (! empty($same_as_urls)) {
                $same_as_urls   = \array_values(\array_unique($same_as_urls));
                $data['sameAs'] = $same_as_urls;
            }

            return $data;
        }
    }
}
