<?php
/**
 * @package     MultipleAuthors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace MultipleAuthors;

use MultipleAuthors\Traits\Author_box;
use WP_Widget;

class Widget extends WP_Widget {

	use Author_box;

	/**
	 * Widget Title
	 *
	 * @var string
	 *
	 * @since 3.4.0
	 */
	protected $title;

    /**
     * Widget Title for Plural
     *
     * @var string
     *
     * @since 3.4.0
     */
    protected $titlePlural;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct()
	{
		$this->title = esc_html__( 'Post Author', 'publishpress-authors' );
		parent::__construct(
			'multiple_authors_widget',
			$this->title,
			array(
				'classname'   => 'multiple_authors_widget',
				'description' => esc_html__(
					'Display a list of authors for the current post.',
					'publishpress-authors'
				),
			)
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance )
	{
		$legacyPlugin = Factory::getLegacyPlugin();

		$instance = wp_parse_args(
			(array) $instance,
			array(
                'title'        => $this->title,
                'title_plural' => !empty($this->titlePlural) ? $this->titlePlural : $this->title,
			)
		);

		$output = '';

		if ( $this->should_display_author_box() ) {
			$layout = isset( $instance['layout'] ) ? $instance['layout'] : $legacyPlugin->modules->multiple_authors->options->layout;

			$output .= $this->get_author_box_markup( 'widget', false, $layout );

            /** This filter is documented in core/src/wp-includes/default-widgets.php */
            if ($this->authorsCount <= 1) {
                $title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
            } else {
                $title = apply_filters('widget_title', $instance['title_plural'], $instance, $this->id_base);
            }

			if ( ! empty( $output ) ) {
				echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $args['before_title'] . apply_filters('widget_title', esc_html($title)) . $args['after_title'];  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $output;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $args['after_widget'];  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance )
	{
		$legacyPlugin = Factory::getLegacyPlugin();

		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title'  => $this->title,
				'title_plural'  => $this->titlePlural,
				'layout' => $legacyPlugin->modules->multiple_authors->options->layout,
			)
		);

        $titleSingle = strip_tags($instance['title']);
        $titlePlural = strip_tags($instance['title_plural']);
        $layout      = strip_tags($instance['layout']);

        $layouts = apply_filters('pp_multiple_authors_author_layouts', array());
        foreach (['authors_index', 'authors_recent', 'authors_grid', 'authors_table'] as $author_list_layout) {
            if (isset($layouts[$author_list_layout])) {
                unset($layouts[$author_list_layout]);
            }
        }

		$context = array(
			'labels'  => array(
				'title'  => esc_html__( 'Single Title', 'publishpress-authors' ),
				'title_plural'  => esc_html__( 'Plural Title', 'publishpress-authors' ),
				'layout' => esc_html__( 'Layout', 'publishpress-authors' ),
			),
			'ids'     => array(
				'title'  => esc_html($this->get_field_id( 'title' )),
				'title_plural'  => esc_html($this->get_field_id( 'title_plural' )),
				'layout' => esc_html($this->get_field_id( 'layout' )),
				'nonce' => esc_html($this->get_field_id( 'nonce' )),
			),
			'names'   => array(
				'title'  => esc_html($this->get_field_name( 'title' )),
				'title_plural'  => esc_html($this->get_field_name( 'title_plural' )),
				'layout' => esc_html($this->get_field_name( 'layout' )),
				'nonce' => esc_html($this->get_field_name( 'nonce' )),
			),
			'values'  => array(
				'title'  => esc_html($titleSingle),
				'title_plural'  => esc_html($titlePlural),
				'layout' => esc_html($layout),
                'nonce' => wp_create_nonce('pp_multiple_authors_widget_form'),
            ),
            'layouts' => $layouts,
		);

		$container = Factory::get_container();

		echo $container['view']->render( 'widget-form', $context );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update($new_instance, $old_instance)
	{

		$legacyPlugin = Factory::getLegacyPlugin();

		$instance = [];

        $instance['title']        = sanitize_text_field($new_instance['title']);
        $instance['title_plural'] = isset($new_instance['title_plural']) ? sanitize_text_field($new_instance['title_plural']) : '';
        $instance['layout']       = sanitize_text_field($new_instance['layout']);

		$layouts = apply_filters('pp_multiple_authors_author_layouts', []);
        foreach (['authors_index', 'authors_recent', 'authors_grid', 'authors_table'] as $author_list_layout) {
            if (isset($layouts[$author_list_layout])) {
                unset($layouts[$author_list_layout]);
            }
        }

		if (! array_key_exists($instance['layout'], $layouts)) {
			$instance['layout'] = $legacyPlugin->modules->multiple_authors->options->layout;
		}

		return $instance;
	}
}
