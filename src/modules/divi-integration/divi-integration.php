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
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MA_Divi_Integration')) {
    /**
     * class MA_Divi_Integration
     */
    class MA_Divi_Integration extends Module
    {
        public $module_name = 'divi_integration';

        /**
         * Instance for the module
         *
         * @var stdClass
         */
        public $module;
        public $module_url;

        /**
         * Construct the MA_Divi_Integration class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'             => __('Divi Integration', 'publishpress-authors'),
                'short_description' => __('Add compatibility with the Divi Theme Builder', 'publishpress-authors'),
                'module_url'        => $this->module_url,
                'icon_class'        => 'dashicons dashicons-feedback',
                'slug'              => 'divi-integration',
                'default_options'   => [
                    'enabled' => 'on',
                ],
                'options_page'      => false,
                'autoload'          => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_divi_integration_default_options',
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
            try {
                add_filter('et_builder_resolve_dynamic_content', [$this, 'resolveDefaultDynamicContent'], 15, 6);
            } catch (Exception $e) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(
                    sprintf('[PublishPress Authors] Method [%s] caught the exception %s', __METHOD__, $e->getMessage())
                );
            }
            add_filter('the_author_posts_link', [$this, 'filter_author_posts_link'], 11);
            add_filter('et_pb_blog_image_width', [$this, 'filter_et_pb_blog_image_width'], 11);
        }

        /**
         * Filter author post link to support multiple and guest author
         *
         * @param string $link
         *
         * @return string
         */
        public function filter_author_posts_link($link) {
            global $authordata, $post;

            $authors = get_post_authors($post->ID);

            if ($post && ! Utils::is_post_type_enabled($post->post_type)) {
                return $link;
            }

            $author_links = [];
            foreach ($authors as $index => $author) {
                $author_links[] = sprintf(
                    '<a href="%1$s" title="%2$s" rel="author">%3$s</a>',
                    esc_url( $author->link ),
                    // translators: post author name.
                    esc_attr( sprintf( __( 'Posts by %s', 'et_builder' ), $author->display_name ) ),
                    $author->display_name
                );
            }
            $link = join(", ",$author_links);

            return $link;
        }

        public function filter_et_pb_blog_image_width($width) {
            global $authordata, $post;

            if (empty($authordata)) {
                // the only way we're able to set author data for situation where post author/fallback author user has been deleted
                $legacyPlugin           = Factory::getLegacyPlugin();
                $fallbackAuthor         = isset($legacyPlugin->modules->multiple_authors->options->fallback_user_for_guest_post) ?
                    (int)$legacyPlugin->modules->multiple_authors->options->fallback_user_for_guest_post : 0;

                $authordata = get_userdata($fallbackAuthor);
            }

            return $width;
        }

        /**
         * Resolve built-in dynamic content fields.
         *
         * @param string $content
         * @param string $name
         * @param array $settings
         * @param string $context
         * @param integer $post_id
         *
         * @return string
         * @copyright Based on the function et_builder_filter_resolve_default_dynamic_content found in the DIVI theme.
         *
         */
        public function resolveDefaultDynamicContent($content, $name, $settings, $post_id, $context, $overrides)
        {
            $_                 = ET_Core_Data_Utils::instance();
            $def               = 'et_builder_get_dynamic_attribute_field_default';
            $post              = get_post($post_id);
            $author            = null;
            $contentOverridden = false;

            if (is_author()) {
                $author = get_queried_object();

                if ($author instanceof WP_User) {
                    $author = Author::get_by_user_id($author->ID);
                }
            } elseif ($post && is_object($post) && isset($post->ID)) {
                $author = get_post_authors($post->ID);
                if (is_array($author) && isset($author[0])) {
                    $author = $author[0];
                } elseif (is_object($author) && isset($author->display_name)) {
                    $author = $author;
                } else {
                    $author            = null;
                }
            }

            switch ($name) {
                case 'post_author':
                    $name_format      = $_->array_get($settings, 'name_format', $def($post_id, $name, 'name_format'));
                    $link             = $_->array_get($settings, 'link', $def($post_id, $name, 'link'));
                    $link             = 'on' === $link;
                    $link_destination = $_->array_get(
                        $settings,
                        'link_destination',
                        $def($post_id, $name, 'link_destination')
                    );
                    $link_target      = 'author_archive' === $link_destination ? '_self' : '_blank';
                    $label            = '';
                    $url              = '';

                    if (!$author) {
                        $content = '';
                        break;
                    }

                    switch ($name_format) {
                        case 'display_name':
                            $label = $author->display_name;
                            break;
                        case 'first_last_name':
                            $label = $author->first_name . ' ' . $author->last_name;
                            break;
                        case 'last_first_name':
                            $label = $author->last_name . ', ' . $author->first_name;
                            break;
                        case 'first_name':
                            $label = $author->first_name;
                            break;
                        case 'last_name':
                            $label = $author->last_name;
                            break;
                        case 'nickname':
                            $label = $author->nickname;
                            break;
                        case 'username':
                            $label = $author->user_login;
                            break;
                    }

                    switch ($link_destination) {
                        case 'author_archive':
                            $url = $author->link;
                            break;
                        case 'author_website':
                            $url = $author->user_url;
                            break;
                    }

                    $content = esc_html($label);

                    if ($link && !empty($url)) {
                        $content = sprintf(
                            '<a href="%1$s" target="%2$s">%3$s</a>',
                            esc_url($url),
                            esc_attr($link_target),
                            et_core_esc_previously($content)
                        );
                    }
                    $contentOverridden = true;
                    break;

                case 'post_author_bio':
                    if (!$author) {
                        break;
                    }

                    $content = et_core_intentionally_unescaped($author->description, 'cap_based_sanitized');
                    $contentOverridden = true;
                    break;

                case 'post_author_url':
                    if (!$author) {
                        break;
                    }

                    $content = esc_url($author->link);
                    $contentOverridden = true;
                    break;

                case 'post_author_profile_picture':
                    if (!$author) {
                        break;
                    }

                    $content = $author->get_avatar_url();
                    if (is_array($content)) {
                        $content = $content['url'];
                    }
                    $contentOverridden = true;
                    break;

                default:
                    return $content;
                    break;
            }

            if (!$contentOverridden) {
                return $content;
            }

            return et_builder_wrap_dynamic_content($post_id, $name, $content, $settings);
        }
    }
}
