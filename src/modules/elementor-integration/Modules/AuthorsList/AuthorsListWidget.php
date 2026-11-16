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
 * An Elementor widget that renders one of the saved author lists
 * (Authors > Author Lists), mirroring the [publishpress_authors_list]
 * shortcode with a list_id parameter.
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
        return ['author', 'authors', 'publishpress', 'list', 'byline', 'user', 'users'];
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
     * @inheritDoc
     */
    protected function register_controls()
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Authors List', 'publishpress-authors'),
            ]
        );

        $this->add_control(
            'list_id',
            [
                'label'       => __('Select an author list', 'publishpress-authors'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $this->get_author_list_options(),
                'default'     => $this->get_default_list_id(),
                'description' => __(
                    'Choose one of the author lists created under Authors > Author Lists.',
                    'publishpress-authors'
                ),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * The saved author lists as list_id => title.
     *
     * @return array
     */
    private function get_author_lists()
    {
        $lists = [];

        try {
            $legacyPlugin = Factory::getLegacyPlugin();
        } catch (\Exception $e) {
            $legacyPlugin = null;
        }

        if ($legacyPlugin && isset($legacyPlugin->modules->author_list->options->author_list_data)) {
            $lists = (array)$legacyPlugin->modules->author_list->options->author_list_data;
        }

        return $lists;
    }

    /**
     * Build the select options from the saved author lists.
     *
     * @return array
     */
    private function get_author_list_options()
    {
        $options = [];

        foreach ($this->get_author_lists() as $listId => $listData) {
            if (is_array($listData) && !empty($listData['title'])) {
                $options[$listId] = $listData['title'];
            }
        }

        if (empty($options)) {
            $options[''] = __('— No author lists found —', 'publishpress-authors');
        }

        return $options;
    }

    /**
     * Default to the first saved list.
     *
     * @return string
     */
    private function get_default_list_id()
    {
        foreach ($this->get_author_lists() as $listId => $listData) {
            return (string)$listId;
        }

        return '';
    }

    /**
     * Render the author list through the plugin shortcode.
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();

        if (empty($settings['list_id'])) {
            return;
        }

        echo do_shortcode(
            sprintf(
                '[publishpress_authors_list list_id="%s" show_title="false"]',
                esc_attr($settings['list_id'])
            )
        );
    }
}