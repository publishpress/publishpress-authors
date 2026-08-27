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

namespace PublishPressAuthors\ElementorIntegration\Modules\AuthorsList;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use MultipleAuthors\Factory;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AuthorsListWidget
 *
 * An Elementor widget that renders the PublishPress Authors list,
 * reusing the same layouts as the [publishpress_authors_list] shortcode.
 *
 * @package PublishPressAuthors\ElementorIntegration\Modules\AuthorsList
 */
class AuthorsListWidget extends Widget_Base
{
    /**
     * @inheritDoc
     */
    public function get_name()
    {
        return 'publishpress_authors_list';
    }

    /**
     * @inheritDoc
     */
    public function get_title()
    {
        return __('PublishPress Authors List', 'publishpress-authors');
    }

    /**
     * @inheritDoc
     */
    public function get_icon()
    {
        return 'eicon-user-circle-o';
    }

    /**
     * @inheritDoc
     */
    public function get_categories()
    {
        return ['general'];
    }

    /**
     * @inheritDoc
     */
    public function get_keywords()
    {
        return ['author', 'authors', 'publishpress', 'byline', 'user', 'users'];
    }

    /**
     * Make sure the authors layout CSS is loaded in the editor and frontend.
     *
     * @return array
     */
    public function get_style_depends()
    {
        return ['multiple-authors-widget-css'];
    }

    /**
     * List of author list layouts available.
     *
     * @return array
     */
    private function get_layouts()
    {
        $layouts = [
            'authors_index'  => __('Authors Index', 'publishpress-authors'),
            'authors_recent' => __('Authors Recent', 'publishpress-authors'),
            'boxed'          => __('Boxed', 'publishpress-authors'),
            'inline'         => __('Inline', 'publishpress-authors'),
            'inline_avatars' => __('Inline Avatars', 'publishpress-authors'),
            'simple_list'    => __('Simple List', 'publishpress-authors'),
        ];

        return apply_filters('publishpress_authors_elementor_widget_layouts', $layouts);
    }

    /**
     * @inheritDoc
     */
    protected function register_controls()
    {
        // ------------------------------------------
        // Content section
        // ------------------------------------------
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Authors List', 'publishpress-authors'),
            ]
        );

        $this->add_control(
            'list_id',
            [
                'label'       => __('Author List', 'publishpress-authors'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $this->get_saved_author_lists(),
                'description' => __(
                    'Optionally pick a saved author list created under Authors > Author Lists. Its settings override the options below.',
                    'publishpress-authors'
                ),
            ]
        );

        $this->add_control(
            'layout',
            [
                'label'   => __('Layout', 'publishpress-authors'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'authors_index',
                'options' => $this->get_layouts(),
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label'   => __('Order By', 'publishpress-authors'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'name',
                'options' => [
                    'name'       => __('Name', 'publishpress-authors'),
                    'count'      => __('Post Count', 'publishpress-authors'),
                    'first_name' => __('First Name', 'publishpress-authors'),
                    'last_name'  => __('Last Name', 'publishpress-authors'),
                ],
            ]
        );

        $this->add_control(
            'order',
            [
                'label'   => __('Order', 'publishpress-authors'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'asc',
                'options' => [
                    'asc'  => __('Ascending', 'publishpress-authors'),
                    'desc' => __('Descending', 'publishpress-authors'),
                ],
            ]
        );

        $this->add_control(
            'limit_per_page',
            [
                'label'       => __('Limit Per Page', 'publishpress-authors'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'default'     => 20,
                'description' => __('Leave 0 to show all authors.', 'publishpress-authors'),
            ]
        );

        $this->add_control(
            'show_empty',
            [
                'label'     => __('Show Authors Without Posts', 'publishpress-authors'),
                'type'      => Controls_Manager::SWITCHER,
                'label_on'  => __('Yes', 'publishpress-authors'),
                'label_off' => __('No', 'publishpress-authors'),
                'default'   => 'yes',
            ]
        );

        $this->add_control(
            'search_box',
            [
                'label'     => __('Show Search Box', 'publishpress-authors'),
                'type'      => Controls_Manager::SWITCHER,
                'label_on'  => __('Yes', 'publishpress-authors'),
                'label_off' => __('No', 'publishpress-authors'),
                'default'   => '',
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label'     => __('Show Title', 'publishpress-authors'),
                'type'      => Controls_Manager::SWITCHER,
                'label_on'  => __('Yes', 'publishpress-authors'),
                'label_off' => __('No', 'publishpress-authors'),
                'default'   => '',
            ]
        );

        $this->add_control(
            'title',
            [
                'label'       => __('Title', 'publishpress-authors'),
                'type'        => Controls_Manager::TEXT,
                'default'     => __('Authors List', 'publishpress-authors'),
                'condition'   => ['show_title' => 'yes'],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get the saved author lists to offer as a select option.
     *
     * @return array
     */
    private function get_saved_author_lists()
    {
        $options = ['' => __('— Use options below —', 'publishpress-authors')];

        try {
            $legacyPlugin = Factory::getLegacyPlugin();
        } catch (\Exception $e) {
            $legacyPlugin = null;
        }

        if ($legacyPlugin && isset($legacyPlugin->modules->author_list->options->author_list_data)) {
            $authorLists = (array)$legacyPlugin->modules->author_list->options->author_list_data;

            foreach ($authorLists as $listId => $listData) {
                if (!empty($listData['list_name'])) {
                    $options[$listId] = $listData['list_name'];
                }
            }
        }

        return $options;
    }

    /**
     * Render the authors list using the plugin shortcode.
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();

        $attributes = [
            'layout'         => !empty($settings['layout']) ? $settings['layout'] : 'authors_index',
            'orderby'        => !empty($settings['orderby']) ? $settings['orderby'] : 'name',
            'order'          => !empty($settings['order']) ? $settings['order'] : 'asc',
            'limit_per_page' => !empty($settings['limit_per_page']) ? (int)$settings['limit_per_page'] : 20,
            'show_empty'     => ('yes' === $settings['show_empty']) ? '1' : '0',
            'search_box'     => ('yes' === $settings['search_box']) ? 'true' : 'false',
            'show_title'     => ('yes' === $settings['show_title']),
        ];

        if (!empty($settings['title']) && 'yes' === $settings['show_title']) {
            $attributes['title'] = $settings['title'];
        }

        if (!empty($settings['list_id'])) {
            $attributes = ['list_id' => $settings['list_id'], 'show_title' => ('yes' === $settings['show_title'])];
        }

        echo do_shortcode(
            sprintf('[publishpress_authors_list %s]', $this->build_shortcode_string($attributes))
        );
    }

    /**
     * Convert an attribute array into a shortcode attribute string.
     *
     * @param array $attributes
     *
     * @return string
     */
    private function build_shortcode_string($attributes)
    {
        $parts = [];

        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            if (null === $value || '' === $value) {
                continue;
            }

            $parts[] = sprintf('%s="%s"', $key, esc_attr($value));
        }

        return implode(' ', $parts);
    }
}
