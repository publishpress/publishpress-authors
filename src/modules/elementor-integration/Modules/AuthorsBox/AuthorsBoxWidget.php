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

namespace PublishPressAuthors\ElementorIntegration\Modules\AuthorsBox;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use MultipleAuthors\Factory;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AuthorsBoxWidget
 *
 * An Elementor widget that renders a PublishPress Authors box,
 * mirroring the "PublishPress Authors Box" Gutenberg block: it offers a
 * select of the saved author boxes (Authors > Author Boxes) and renders
 * the chosen box through the [publishpress_authors_box] shortcode.
 *
 * @package PublishPressAuthors\ElementorIntegration\Modules\AuthorsBox
 */
class AuthorsBoxWidget extends Widget_Base
{
    /**
     * @inheritDoc
     */
    public function get_name()
    {
        return 'publishpress_authors_box';
    }

    /**
     * @inheritDoc
     */
    public function get_title()
    {
        return __('PublishPress Authors Box', 'publishpress-authors');
    }

    /**
     * @inheritDoc
     */
    public function get_icon()
    {
        return 'eicon-user-card';
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
        return ['author', 'authors', 'publishpress', 'box', 'bio', 'byline', 'profile'];
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
                'label' => __('Authors Box', 'publishpress-authors'),
            ]
        );

        $this->add_control(
            'selected_box_id',
            [
                'label'       => __('Select an author box', 'publishpress-authors'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $this->get_author_boxes_options(),
                'default'     => $this->get_default_box_id(),
                'description' => __(
                    'Choose one of the author boxes created under Authors > Author Boxes.',
                    'publishpress-authors'
                ),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Build the select options from the saved author boxes.
     *
     * @return array
     */
    private function get_author_boxes_options()
    {
        $options = [];

        $boxes = $this->get_author_boxes();

        foreach ($boxes as $boxId => $boxTitle) {
            $options[$boxId] = $boxTitle;
        }

        if (empty($options)) {
            $options['boxed'] = __('Boxed (default)', 'publishpress-authors');
        }

        return $options;
    }

    /**
     * The saved author boxes as "author_boxes_<id>" => title.
     *
     * @return array
     */
    private function get_author_boxes()
    {
        $boxes = [];

        try {
            $legacyPlugin = Factory::getLegacyPlugin();
        } catch (\Exception $e) {
            $legacyPlugin = null;
        }

        if ($legacyPlugin && isset($legacyPlugin->modules->author_boxes)
            && class_exists('\MA_Author_Boxes')) {
            $boxes = \MA_Author_Boxes::getAuthorBoxes(false);
        }

        return is_array($boxes) ? $boxes : [];
    }

    /**
     * Default to the first saved box, like the Gutenberg block does.
     *
     * @return string
     */
    private function get_default_box_id()
    {
        $boxes = $this->get_author_boxes();

        foreach ($boxes as $boxId => $boxTitle) {
            return (string)$boxId;
        }

        return 'boxed';
    }

    /**
     * Render the author box using the plugin shortcode.
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();

        $layout = !empty($settings['selected_box_id'])
            ? sanitize_text_field($settings['selected_box_id'])
            : 'boxed';

        echo do_shortcode(
            sprintf('[publishpress_authors_box layout="%s"]', esc_attr($layout))
        );
    }
}