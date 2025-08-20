<?php
/**
 * @package     MultipleAuthors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace MultipleAuthors\Traits;

use MultipleAuthors\Classes\Authors_Iterator;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Legacy\Util;
use MultipleAuthors\Classes\Objects\Post;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;

trait Author_box
{
    /**
     * @var array
     */
    protected $postCache = [];

    /**
     * @var int
     */
    protected $authorsCount = 0;

    /**
     * Returns true if the post type and current page is valid.
     *
     * @return bool
     */
    protected function should_display_author_box()
    {
        $display = !$this->is_post_author_box_disabled()
            && $this->is_valid_page_to_display_author_box()
            && $this->is_valid_post_type_to_display_author_box();

        // Apply a filter
        $display = apply_filters('pp_multiple_authors_filter_should_display_author_box', $display);

        return $display;
    }

    /**
     * Return true if author box display is disabled
     * for current global $post.
     *
     * @return bool
     */
    protected function is_post_author_box_disabled()
    {
        global $post;

        $disabled = (is_object($post)
            && isset($post->ID)
            && (int) get_post_meta($post->ID, 'ppma_disable_author_box', true) > 0
        ) ? true : false;

        return $disabled;
    }

    /**
     * Returns true if the current page is valid to display. Basically,
     * we should display only if is a post's page.
     *
     * @return bool
     */
    protected function is_valid_page_to_display_author_box()
    {
        return !is_home() && !is_category() && (is_single() || is_page());
    }

    /**
     * Returns true if the current post type is valid, selected in the options.
     *
     * @return bool
     */
    protected function is_valid_post_type_to_display_author_box()
    {
        $legacyPlugin = Factory::getLegacyPlugin();

        $supported_post_types = Util::get_post_types_for_module($legacyPlugin->modules->multiple_authors);
        $post_type            = Util::get_current_post_type();

        return in_array($post_type, $supported_post_types);
    }

    /**
     * Returns the HTML markup for the author box.
     *
     * @param string $target
     * @param bool $show_title
     * @param string $layout
     * @param bool $archive
     * @param int $post_id
     * @param int $term_id
     * @param int $user_id
     * @param string $author_categories
     *
     * @return string
     */
    protected function get_author_box_markup(
        $target = null,
        $show_title = true,
        $layout = null,
        $archive = false,
        $post_id = null,
        $term_id = false,
        $user_id = false,
        $author_categories = ''
    ) {
        $legacyPlugin = Factory::getLegacyPlugin();

        $html = '';

        if (apply_filters('publishpress_authors_load_style_in_frontend', PUBLISHPRESS_AUTHORS_LOAD_STYLE_IN_FRONTEND)) {
            Utils::loadLayoutFrontCss();
        }

        if (!function_exists('get_post_authors') || !function_exists('get_archive_author')) {
            require_once PP_AUTHORS_BASE_PATH . 'src/functions/template-tags.php';
        }

        if (!empty($author_categories)) {
            $author_categories = explode(",", $author_categories);
            $author_categories = array_map('trim', $author_categories);
            $author_categories = array_filter($author_categories);
        }

        $css_class = '';
        if (!empty($target)) {
            $css_class = 'multiple-authors-target-' . str_replace('_', '-', $target);
        }

        if (empty($layout)) {
            $layout = isset($legacyPlugin->modules->multiple_authors->options->layout)
                ? $legacyPlugin->modules->multiple_authors->options->layout : Utils::getDefaultLayout();
        }

        if (empty($color_scheme)) {
            $color_scheme = isset($legacyPlugin->modules->multiple_authors->options->color_scheme)
                ? $legacyPlugin->modules->multiple_authors->options->color_scheme : '#655997';
        }

        $show_email = isset($legacyPlugin->modules->multiple_authors->options->show_email_link)
            ? 'yes' === $legacyPlugin->modules->multiple_authors->options->show_email_link : true;

        $show_site = isset($legacyPlugin->modules->multiple_authors->options->show_site_link)
            ? 'yes' === $legacyPlugin->modules->multiple_authors->options->show_site_link : true;

        if (!isset($this->postCache[$post_id])) {
            $post = new Post($post_id);

            $this->postCache[$post_id] = $post;
        } else {
            $post = $this->postCache[$post_id];
        }

        if ($target == 'the_content') {
            $post_specific_box = get_post_meta($post->ID, 'ppma_selected_author_box', true);
            if (!empty($post_specific_box)) {
                $layout = $post_specific_box;
            }
        }

        if ($term_id) {
            $authorsList = [Author::get_by_term_id($term_id)];
        } elseif ($user_id) {
            $authorsList = [Author::get_by_user_id($user_id)];
        } elseif ($archive) {
            $authorsList = [get_archive_author()];
        } else {
            if (empty($author_categories)) {
                $authorsList = get_post_authors($post_id, true, $archive);
            } else {
                $authorsList = ppma_post_authors_categorized($post_id, $author_categories);
                if (!empty($authorsList)) {
                    $authorsList = call_user_func_array('array_merge_recursive', array_values($authorsList));
                }
            }
        }

        $authorsList = \MA_Author_Boxes::removeExcludedAuthors($authorsList);

        if (empty($authorsList)) {
            return '';
        }

        $this->authorsCount = count($authorsList);

        if ($this->authorsCount === 1) {
            $title = isset($legacyPlugin->modules->multiple_authors->options->title_appended_to_content)
                ? $legacyPlugin->modules->multiple_authors->options->title_appended_to_content : esc_html__(
                    'Author'
                );
        } else {
            $title = isset($legacyPlugin->modules->multiple_authors->options->title_appended_to_content_plural)
                ? $legacyPlugin->modules->multiple_authors->options->title_appended_to_content_plural : esc_html__(
                    'Authors',
                    'publishpress-authors'
                );
        }

        $title = esc_html($title);

        $args = [
            'show_title'    => $show_title,
            'css_class'     => $css_class,
            'title'         => $title,
            'authors'       => $authorsList,
            'target'        => $target,
            'item_class'    => 'author url fn',
            'layout'        => $layout,
            'color_scheme'  => $color_scheme,
            'show_email'    => $show_email,
            'show_site'     => $show_site,
            'post'          => $post,
        ];

        /**
         * Filter the author box arguments before sending to the renderer.
         *
         * @param array $args
         */
        $args = apply_filters('pp_multiple_authors_author_box_args', $args);

        /**
         * Filter the author box HTML code, allowing to use custom rendered layouts.
         *
         * @param string $html
         * @param array $args
         */
        $html = apply_filters('pp_multiple_authors_author_box_html', null, $args);

        $authors_iterator = new Authors_Iterator($post_id ?? 0, $archive);

        /**
         * Filter the rendered markup of the author box.
         *
         * @param string $html
         * @param Authors_Iterator $authors_iterator
         * @param string $target
         *
         * @deprecated since 2.4.0, use pp_multiple_authors_author_box_rendered_markup instead.
         */
        $html = apply_filters(
            'pp_multiple_authors_filter_author_box_markup',
            $html,
            $authors_iterator,
            $target
        );

        /**
         * Filter the rendered markup of the author box.
         *
         * @param string $html
         * @param Authors_Iterator $authors_iterator
         * @param string $target
         */
        $html = apply_filters(
            'pp_multiple_authors_author_box_rendered_markup',
            $html,
            $authors_iterator,
            $target
        );

        return $html;
    }

    /**
     * Returns the authors data.
     *
     * @param int $post_id
     * @param string $field
     * @param mixed $separator
     * @param mixed $user_objects
     * @param mixed $term_id
     * @param bool $archive
     * @param string $author_categories
     *
     * @return string
     */
    protected function get_authors_data(
        $post_id = false,
        $field = 'display_name',
        $separator = ',',
        $user_objects = false,
        $term_id = false,
        $archive = false,
        $author_categories = ''
    ) {
        global $post;

        $output = [];

        if (!function_exists('get_post_authors')) {
            require_once PP_AUTHORS_BASE_PATH . 'src/functions/template-tags.php';
        }

        if (!$post_id && is_object($post) && isset($post->ID)) {
            $post_id = $post->ID;
        } else {
            $post_id = (int) $post_id;
        }

        if (!empty($author_categories)) {
            $author_categories = explode(",", $author_categories);
            $author_categories = array_map('trim', $author_categories);
            $author_categories = array_filter($author_categories);
        }

        if ($term_id && (int)$term_id > 0) {
            $authors = [];
            $term_author = Author::get_by_term_id($term_id);
            if ($term_author) {
                $authors[] = $term_author;
            }
        } else {
            if ($archive) {
                $authors = [get_archive_author()];
            } else {
                if (empty($author_categories)) {
                    $authors = get_post_authors($post_id, true, $archive);
                } else {
                    $authors = ppma_post_authors_categorized($post_id, $author_categories);
                    if (!empty($authors)) {
                        $authors = call_user_func_array('array_merge_recursive', array_values($authors));
                    }
                }
            }
        }

        if (!$user_objects) {
            if (!empty($authors)) {
                $profile_fields   = apply_filters('multiple_authors_author_fields', [], false);
                foreach ($authors as $author) {
                    if ($field === 'avatar') {
                        $avatar_url = $author->get_avatar_url();
                        if (is_array($avatar_url) && isset($avatar_url['url'])) {
                            $avatar_url = $avatar_url['url'];
                        }
                        $output[] = $avatar_url;
                    } else {
                        $field_output = (isset($author->$field) && !empty(trim($author->$field))) ? $author->$field : $author->display_name;
                        if (isset($profile_fields[$field]) && $profile_fields[$field]['type'] === 'url' && !empty(trim($author->$field))) {
                            $field_output = '<a href="'. esc_url($field_output).'"> '. esc_html($field_output) .'</a>';
                        }
                        $output[] = $field_output;
                    }
                }
            }
            $output  = array_filter($output);
            $authors = join($separator, $output);
        }

        return $authors;
    }
}
