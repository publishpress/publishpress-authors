<?php

/**
 * @package     MultipleAuthorBoxes
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.1.0
 */

namespace MultipleAuthorBoxes;

/**
 * Author boxes Ajax
 *
 * @package MultipleAuthorBoxes\Classes
 *
 */
class AuthorBoxesDefault
{

    /**
     * Get default template list.
     */
    public static function getAuthorBoxesDefaultList()
    {
        $defaultAuthorBoxes = [
            'author_boxes_boxed' => __('Boxed', 'publishpress-authors'),
            'author_boxes_boxed_right' => __('Boxed Right', 'publishpress-authors'),
            'author_boxes_centered' => __('Centered', 'publishpress-authors'),
            'author_boxes_inline' => __('Inline', 'publishpress-authors'),
            'author_boxes_inline_avatar' => __('Inline with Avatars', 'publishpress-authors'),
            'author_boxes_simple_list' => __('Simple List', 'publishpress-authors'),
            //'author_boxes_authors_index'  => __('Authors Index', 'publishpress-authors'),
            //'author_boxes_authors_recent' => __('Authors Recent', 'publishpress-authors'),
        ];

        return $defaultAuthorBoxes;
    }

    /**
     * Get a specific default author boxes
     *
     * @param string $default_slug
     * @return array
     */
    public static function getAuthorBoxesDefaultData($default_slug)
    {
        $editor_datas = [];

        //add boxed
        $editor_datas['author_boxes_boxed']         = self::getAuthorBoxesBoxedEditorData();
        //add boxed right
        $editor_datas['author_boxes_boxed_right']   = self::getAuthorBoxesBoxedRightEditorData();
        //add centered
        $editor_datas['author_boxes_centered']      = self::getAuthorBoxesCenteredEditorData();
        //add inline
        $editor_datas['author_boxes_inline']        = self::getAuthorBoxesInlineEditorData();
        //add inline avatar
        $editor_datas['author_boxes_inline_avatar'] = self::getAuthorBoxesInlineAvatarEditorData();
        //add simple list
        $editor_datas['author_boxes_simple_list']   = self::getAuthorBoxesSimpleListEditorData();

        if (array_key_exists($default_slug, $editor_datas)) {
            return $editor_datas[$default_slug];
        }

        return false;
    }

    /**
     * Boxed editor data
     *
     * @return array
     */
    public static function getAuthorBoxesBoxedEditorData()
    {
        $editor_data = [];
        //title default
        $editor_data['show_title'] = 1;
        $editor_data['title_text'] = esc_html__('Author');
        $editor_data['title_text_plural'] = esc_html__('Authors', 'publishpress-authors');
        $editor_data['title_html_tag'] = 'h2';
        $editor_data['box_tab_custom_wrapper_class'] = 'pp-multiple-authors-layout-boxed';
        //avatar default
        $editor_data['avatar_show'] = 1;
        $editor_data['avatar_size'] = 80;
        $editor_data['avatar_border_radius'] = 50;
        //name default
        $editor_data['name_show'] = 1;
        $editor_data['name_disable_link'] = 0;
        $editor_data['name_html_tag'] = 'div';
        //bio default
        $editor_data['author_bio_show'] = 1;
        $editor_data['author_bio_link'] = 0;
        $editor_data['author_bio_html_tag'] = 'p';
        //meta default
        $editor_data['meta_view_all_show'] = 0;
        $editor_data['meta_html_tag'] = 'span';
        $editor_data['meta_label'] = __('View all posts', 'publishpress-authors');
        $editor_data['meta_background_color'] = '#655997';
        $editor_data['meta_color'] = '#ffffff';
        $editor_data['meta_link_hover_color'] = '#ffffff';
        //recent posts default
        $editor_data['author_recent_posts_title_show'] = 1;
        $editor_data['author_recent_posts_post_types'] = [];
        $editor_data['author_recent_posts_empty_show'] = 1;
        $editor_data['author_recent_posts_limit'] = 5;
        $editor_data['author_recent_posts_orderby'] = 'date';
        $editor_data['author_recent_posts_order'] = 'DESC';
        $editor_data['author_recent_posts_html_tag'] = 'div';
        $editor_data['author_recent_posts_title_border_bottom_style'] = 'dotted';
        //box layout default
        $editor_data['box_layout_border_width'] = 1;
        $editor_data['box_layout_border_style'] = 'solid';
        $editor_data['box_layout_border_color'] = '#999';
        $editor_data['box_layout_shadow_horizontal_offset'] = 10;
        $editor_data['box_layout_shadow_vertical_offset'] = 10;
        $editor_data['box_layout_shadow_blur'] = 0;
        $editor_data['box_layout_shadow_speed'] = 0;
        $editor_data['box_layout_color'] = '#3c434a';
        // hide all default fields
        $editor_data['profile_fields_hide_first_name'] = 1;
        $editor_data['profile_fields_hide_last_name'] = 1;
        // email field
        $editor_data['profile_fields_user_email_html_tag'] = 'a';
        $editor_data['profile_fields_user_email_value_prefix'] = 'mailto:';
        $editor_data['profile_fields_user_email_display'] = 'icon';
        $editor_data['profile_fields_user_email_display_icon'] = '<span class="dashicons dashicons-email-alt"></span>';
        $editor_data['profile_fields_user_email_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_user_email_color'] = '#ffffff';
        $editor_data['profile_fields_user_email_display_icon_border_radius'] = '100';
        // website field
        $editor_data['profile_fields_user_url_html_tag'] = 'a';
        $editor_data['profile_fields_user_url_display'] = 'icon';
        $editor_data['profile_fields_user_url_display_icon'] = '<span class="dashicons dashicons-admin-links"></span>';
        $editor_data['profile_fields_user_url_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_user_url_color'] = '#ffffff';
        $editor_data['profile_fields_user_url_display_icon_border_radius'] = '100';

        // hide non essential author fields
        $profile_fields   = apply_filters('multiple_authors_author_fields', [], false);
        foreach ($profile_fields as $key => $data) {
            if (!in_array($key, ['user_email', 'user_url'])) {
                $editor_data['profile_fields_hide_' . $key] = 1;
            }
        }

        $editor_data = self::addEditorDataDefaultValues($editor_data);

        return $editor_data;
    }

    /**
     * Boxed right editor data
     *
     * @return array
     */
    public static function getAuthorBoxesBoxedRightEditorData()
    {
        $editor_data = [];
        //title default
        $editor_data['show_title'] = 0;
        $editor_data['title_text'] = esc_html__('Author');
        $editor_data['title_text_plural'] = esc_html__('Authors', 'publishpress-authors');
        $editor_data['title_html_tag'] = 'h2';
        $editor_data['box_tab_custom_wrapper_class'] = 'pp-multiple-authors-layout-boxed-right';
        //avatar default
        $editor_data['avatar_show'] = 1;
        $editor_data['avatar_size'] = 250;
        $editor_data['avatar_border_radius'] = '';
        //name default
        $editor_data['name_show'] = 1;
        $editor_data['name_disable_link'] = 0;
        $editor_data['display_name_prefix'] = 'Hi, I\'m ';
        $editor_data['name_size'] = 30;
        $editor_data['name_weight'] = 700;
        $editor_data['name_transform'] = 'uppercase';
        $editor_data['name_decoration'] = 'none';
        $editor_data['name_color'] = '#000000';
        $editor_data['name_html_tag'] = 'p';
        //bio default
        $editor_data['avatar_border_radius'] = 50;
        $editor_data['author_bio_show'] = 1;
        $editor_data['author_bio_html_tag'] = 'p';
        // email default
        $editor_data['profile_fields_user_email_html_tag'] = 'a';
        $editor_data['profile_fields_user_email_value_prefix'] = 'mailto:';
        $editor_data['profile_fields_user_email_display'] = 'icon';
        $editor_data['profile_fields_user_email_display_icon'] = '<span class="dashicons dashicons-email-alt"></span>';
        $editor_data['profile_fields_user_email_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_user_email_color'] = '#ffffff';
        $editor_data['profile_fields_user_email_display_icon_border_radius'] = 100;

        // website default
        $editor_data['profile_fields_user_url_html_tag'] = 'a';
        $editor_data['profile_fields_user_url_display'] = 'icon';
        $editor_data['profile_fields_user_url_display_icon'] = '<span class="dashicons dashicons-admin-links"></span>';
        $editor_data['profile_fields_user_url_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_user_url_color'] = '#ffffff';
        $editor_data['profile_fields_user_url_display_icon_border_radius'] = 100;
        // tiktok default
        $editor_data['profile_fields_tiktok_html_tag'] = 'a';
        $editor_data['profile_fields_tiktok_display'] = 'icon';
        $editor_data['profile_fields_tiktok_display_icon'] = '<span class="dashicons dashicons-admin-links"></span>';
        $editor_data['profile_fields_tiktok_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_tiktok_color'] = '#ffffff';
        $editor_data['profile_fields_tiktok_display_icon_border_radius'] = 100;
        // youtube default
        $editor_data['profile_fields_youtube_html_tag'] = 'a';
        $editor_data['profile_fields_youtube_display'] = 'icon';
        $editor_data['profile_fields_youtube_display_icon'] = '<span class="dashicons dashicons-youtube"></span>';
        $editor_data['profile_fields_youtube_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_youtube_color'] = '#ffffff';
        $editor_data['profile_fields_youtube_display_icon_border_radius'] = 100;
        // linkedin default
        $editor_data['profile_fields_linkedin_html_tag'] = 'a';
        $editor_data['profile_fields_linkedin_display'] = 'icon';
        $editor_data['profile_fields_linkedin_display_icon'] = '<span class="dashicons dashicons-linkedin"></span>';
        $editor_data['profile_fields_linkedin_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_linkedin_color'] = '#ffffff';
        $editor_data['profile_fields_linkedin_display_icon_border_radius'] = 100;
        // instagram default
        $editor_data['profile_fields_instagram_html_tag'] = 'a';
        $editor_data['profile_fields_instagram_display'] = 'icon';
        $editor_data['profile_fields_instagram_display_icon'] = '<span class="dashicons dashicons-instagram"></span>';
        $editor_data['profile_fields_instagram_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_instagram_color'] = '#ffffff';
        $editor_data['profile_fields_instagram_display_icon_border_radius'] = 100;
        // twitter default
        $editor_data['profile_fields_twitter_html_tag'] = 'a';
        $editor_data['profile_fields_twitter_display'] = 'icon';
        $editor_data['profile_fields_twitter_display_icon'] = '<span class="dashicons dashicons-twitter"></span>';
        $editor_data['profile_fields_twitter_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_twitter_color'] = '#ffffff';
        $editor_data['profile_fields_twitter_display_icon_border_radius'] = 100;
        // x default
        $editor_data['profile_fields_x_html_tag'] = 'a';
        $editor_data['profile_fields_x_display'] = 'icon';
        $editor_data['profile_fields_x_display_icon'] = '<span class="dashicons dashicons-twitter"></span>';
        $editor_data['profile_fields_x_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_x_color'] = '#ffffff';
        $editor_data['profile_fields_x_display_icon_border_radius'] = 100;
        // facebook default
        $editor_data['profile_fields_facebook_html_tag'] = 'a';
        $editor_data['profile_fields_facebook_display'] = 'icon';
        $editor_data['profile_fields_facebook_display_icon'] = '<span class="dashicons dashicons-facebook"></span>';
        $editor_data['profile_fields_facebook_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_facebook_color'] = '#ffffff';
        $editor_data['profile_fields_facebook_display_icon_border_radius'] = 100;
        //job title default
        $editor_data['profile_fields_job_title_display_position'] = 'name';
        $editor_data['profile_fields_job_title_html_tag'] = 'p';
        $editor_data['profile_fields_job_title_size'] = 13;
        $editor_data['profile_fields_job_title_weight'] = 'bold';
        $editor_data['profile_fields_job_title_color'] = '#000000';
        //meta default
        $editor_data['meta_view_all_show'] = 0;
        $editor_data['meta_html_tag'] = 'span';
        $editor_data['meta_label'] = __('View all posts', 'publishpress-authors');
        $editor_data['meta_background_color'] = '#655997';
        $editor_data['meta_color'] = '#ffffff';
        $editor_data['meta_link_hover_color'] = '#ffffff';
        //recent posts default
        $editor_data['author_recent_posts_title_show'] = 0;
        $editor_data['author_recent_posts_post_types'] = [];
        $editor_data['author_recent_posts_empty_show'] = 0;
        $editor_data['author_recent_posts_limit'] = 5;
        $editor_data['author_recent_posts_orderby'] = 'date';
        $editor_data['author_recent_posts_order'] = 'DESC';
        $editor_data['author_recent_posts_html_tag'] = 'div';
        $editor_data['author_recent_posts_title_border_bottom_style'] = 'dotted';
        //box layout default
        $editor_data['box_layout_padding_top'] = 5;
        $editor_data['box_layout_padding_bottom'] = 5;
        $editor_data['box_layout_padding_left'] = 10;
        $editor_data['box_layout_border_width'] = 1;
        $editor_data['box_layout_border_style'] = 'solid';
        $editor_data['box_layout_border_color'] = '#999';
        $editor_data['box_layout_shadow_horizontal_offset'] = 10;
        $editor_data['box_layout_shadow_vertical_offset'] = 10;
        $editor_data['box_layout_shadow_blur'] = 0;
        $editor_data['box_layout_shadow_speed'] = 0;
        $editor_data['box_layout_color'] = '#3c434a';
        // hide all default fields
        $editor_data['profile_fields_hide_first_name'] = 1;
        $editor_data['profile_fields_hide_last_name'] = 1;
        //default css
        $editor_data['box_tab_custom_css'] = '.pp-multiple-authors-wrapper.pp-multiple-authors-layout-boxed-right .pp-multiple-authors-boxes-li {
            display: flex;
            flex-flow: row-reverse;
            column-gap: 20px;
            width: 100%;
        }

        .pp-multiple-authors-wrapper.pp-multiple-authors-layout-boxed-right {
               min-width: min(calc(100vw - 8* 25px), 710px) !important;
        }

        .pp-multiple-authors-wrapper.pp-multiple-authors-layout-boxed-right .pp-author-boxes-description.multiple-authors-description {
            margin-bottom: 25px;
        }

        .pp-multiple-authors-wrapper.pp-multiple-authors-layout-boxed-right a.ppma-author-field-meta {
            margin-right: 10px !important;
        }

        .pp-multiple-authors-wrapper.pp-multiple-authors-layout-boxed-right .pp-multiple-authors-boxes-li .pp-author-boxes-avatar,
        .pp-multiple-authors-wrapper.pp-multiple-authors-layout-boxed-right .pp-multiple-authors-boxes-li .pp-author-boxes-avatar-details {
            flex: 1;
            margin: auto;
        }

        .pp-multiple-authors-wrapper.pp-multiple-authors-layout-boxed-right .pp-multiple-authors-boxes-li .pp-author-boxes-avatar .avatar-image {
            float: right;
            margin-right: 10px;
        }

        .pp-multiple-authors-wrapper.pp-multiple-authors-layout-boxed-right .pp-multiple-authors-boxes-li .pp-author-boxes-avatar-details {
            margin: auto 0;
        }

        @media screen and (max-width: 768px) {
            .pp-multiple-authors-wrapper.pp-multiple-authors-layout-boxed-right .pp-multiple-authors-boxes-li {
                display: block;
            }
            .pp-multiple-authors-wrapper.pp-multiple-authors-layout-boxed-right .pp-multiple-authors-layout-boxed-right .pp-author-boxes-avatar img {
                width: 95% !important;
                height: auto !important;
            }
        }';

        // hide non essential author fields
        $profile_fields   = apply_filters('multiple_authors_author_fields', [], false);
        foreach ($profile_fields as $key => $data) {
            if (!in_array($key, ['user_email', 'user_url', 'tiktok', 'youtube', 'linkedin', 'instagram', 'twitter', 'x', 'facebook', 'job_title'])) {
                $editor_data['profile_fields_hide_' . $key] = 1;
            }
        }

        $editor_data = self::addEditorDataDefaultValues($editor_data);

        return $editor_data;
    }

    /**
     * Centered editor data
     *
     * @return array
     */
    public static function getAuthorBoxesCenteredEditorData()
    {
        $editor_data = [];
        //title default
        $editor_data['show_title'] = 1;
        $editor_data['title_text'] = esc_html__('Author');
        $editor_data['title_text_plural'] = esc_html__('Authors', 'publishpress-authors');
        $editor_data['title_html_tag'] = 'h2';
        $editor_data['box_tab_custom_wrapper_class'] = 'pp-multiple-authors-layout-centered';
        //avatar default
        $editor_data['avatar_show'] = 1;
        $editor_data['avatar_size'] = 80;
        $editor_data['avatar_border_radius'] = 50;
        //name default
        $editor_data['name_show'] = 1;
        $editor_data['name_disable_link'] = 0;
        $editor_data['name_html_tag'] = 'div';
        //bio default
        $editor_data['avatar_border_radius'] = 50;
        $editor_data['author_bio_show'] = 1;
        $editor_data['author_bio_html_tag'] = 'p';
        //meta default
        $editor_data['meta_view_all_show'] = 0;
        $editor_data['meta_html_tag'] = 'span';
        $editor_data['meta_label'] = __('View all posts', 'publishpress-authors');
        $editor_data['meta_background_color'] = '#655997';
        $editor_data['meta_color'] = '#ffffff';
        $editor_data['meta_link_hover_color'] = '#ffffff';
        //recent posts default
        $editor_data['author_recent_posts_title_show'] = 1;
        $editor_data['author_recent_posts_post_types'] = [];
        $editor_data['author_recent_posts_empty_show'] = 1;
        $editor_data['author_recent_posts_limit'] = 5;
        $editor_data['author_recent_posts_orderby'] = 'date';
        $editor_data['author_recent_posts_order'] = 'DESC';
        $editor_data['author_recent_posts_html_tag'] = 'div';
        $editor_data['author_recent_posts_title_border_bottom_style'] = 'dotted';
        $editor_data['author_recent_posts_alignment'] = 'left';
        //box layout default
        $editor_data['box_layout_border_width'] = 1;
        $editor_data['box_layout_border_style'] = 'solid';
        $editor_data['box_layout_border_color'] = '#999';
        $editor_data['box_layout_shadow_horizontal_offset'] = 10;
        $editor_data['box_layout_shadow_vertical_offset'] = 10;
        $editor_data['box_layout_shadow_blur'] = 0;
        $editor_data['box_layout_shadow_speed'] = 0;
        $editor_data['box_layout_color'] = '#3c434a';
        // hide all default fields
        $editor_data['profile_fields_hide_first_name'] = 1;
        $editor_data['profile_fields_hide_last_name'] = 1;
        // email field
        $editor_data['profile_fields_user_email_html_tag'] = 'a';
        $editor_data['profile_fields_user_email_value_prefix'] = 'mailto:';
        $editor_data['profile_fields_user_email_display'] = 'icon';
        $editor_data['profile_fields_user_email_display_icon'] = '<span class="dashicons dashicons-email-alt"></span>';
        $editor_data['profile_fields_user_email_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_user_email_color'] = '#ffffff';
        $editor_data['profile_fields_user_email_display_icon_border_radius'] = '100';
        // website field
        $editor_data['profile_fields_user_url_html_tag'] = 'a';
        $editor_data['profile_fields_user_url_display'] = 'icon';
        $editor_data['profile_fields_user_url_display_icon'] = '<span class="dashicons dashicons-admin-links"></span>';
        $editor_data['profile_fields_user_url_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_user_url_color'] = '#ffffff';
        $editor_data['profile_fields_user_url_display_icon_border_radius'] = '100';

        // hide non essential author fields
        $profile_fields   = apply_filters('multiple_authors_author_fields', [], false);
        foreach ($profile_fields as $key => $data) {
            if (!in_array($key, ['user_email', 'user_url'])) {
                $editor_data['profile_fields_hide_' . $key] = 1;
            }
        }

        $editor_data = self::addEditorDataDefaultValues($editor_data);

        return $editor_data;
    }

    /**
     * Inline editor data
     *
     * @return array
     */
    public static function getAuthorBoxesInlineEditorData()
    {
        $editor_data = [];
        //title default
        $editor_data['title_text'] = esc_html__('Author');
        $editor_data['title_text_plural'] = esc_html__('Authors', 'publishpress-authors');
        $editor_data['title_html_tag'] = 'h2';
        $editor_data['box_tab_custom_wrapper_class'] = 'pp-multiple-authors-layout-inline';
        //avatar default
        $editor_data['avatar_size'] = 80;
        $editor_data['avatar_border_radius'] = 50;
        //name default
        $editor_data['name_show'] = 1;
        $editor_data['name_disable_link'] = 0;
        $editor_data['name_html_tag'] = 'div';
        //bio default
        $editor_data['author_bio_html_tag'] = 'p';
        //meta default
        $editor_data['meta_view_all_show'] = 0;
        $editor_data['meta_html_tag'] = 'span';
        $editor_data['meta_label'] = __('View all posts', 'publishpress-authors');
        $editor_data['meta_background_color'] = '#655997';
        $editor_data['meta_color'] = '#ffffff';
        $editor_data['meta_link_hover_color'] = '#ffffff';
        //recent posts default
        $editor_data['author_recent_posts_title_show'] = 1;
        $editor_data['author_recent_posts_post_types'] = [];
        $editor_data['author_recent_posts_empty_show'] = 1;
        $editor_data['author_recent_posts_limit'] = 5;
        $editor_data['author_recent_posts_orderby'] = 'date';
        $editor_data['author_recent_posts_order'] = 'DESC';
        $editor_data['author_recent_posts_html_tag'] = 'div';
        $editor_data['author_recent_posts_title_border_bottom_style'] = 'dotted';
        $editor_data['author_recent_posts_alignment'] = 'left';
        //box layout default
        $editor_data['box_tab_layout_author_separator'] = ', ';
        $editor_data['box_layout_border_style'] = 'none';
        $editor_data['box_layout_shadow_horizontal_offset'] = 10;
        $editor_data['box_layout_shadow_vertical_offset'] = 10;
        $editor_data['box_layout_shadow_blur'] = 0;
        $editor_data['box_layout_shadow_speed'] = 0;
        $editor_data['box_layout_color'] = '#3c434a';
        // hide all default fields
        $editor_data['profile_fields_hide_first_name'] = 1;
        $editor_data['profile_fields_hide_last_name'] = 1;
        // email field
        $editor_data['profile_fields_user_email_html_tag'] = 'a';
        $editor_data['profile_fields_user_email_value_prefix'] = 'mailto:';
        $editor_data['profile_fields_user_email_display'] = 'icon';
        $editor_data['profile_fields_user_email_display_icon'] = '<span class="dashicons dashicons-email-alt"></span>';
        $editor_data['profile_fields_user_email_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_user_email_color'] = '#ffffff';
        $editor_data['profile_fields_user_email_display_icon_border_radius'] = '100';
        // website field
        $editor_data['profile_fields_user_url_html_tag'] = 'a';
        $editor_data['profile_fields_user_url_display'] = 'icon';
        $editor_data['profile_fields_user_url_display_icon'] = '<span class="dashicons dashicons-admin-links"></span>';
        $editor_data['profile_fields_user_url_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_user_url_color'] = '#ffffff';
        $editor_data['profile_fields_user_url_display_icon_border_radius'] = '100';
        //default css
        $editor_data['box_tab_custom_css'] = '.pp-multiple-authors-layout-inline ul.pp-multiple-authors-boxes-ul {
            display: flex;
        }

        .pp-multiple-authors-layout-inline ul.pp-multiple-authors-boxes-ul li {
            margin-right: 10px
        }';

        // hide all author fields
        $profile_fields   = apply_filters('multiple_authors_author_fields', [], false);
        foreach ($profile_fields as $key => $data) {
            $editor_data['profile_fields_hide_' . $key] = 1;
        }

        $editor_data = self::addEditorDataDefaultValues($editor_data);

        return $editor_data;
    }

    /**
     * Inline with avatar editor data
     *
     * @return array
     */
    public static function getAuthorBoxesInlineAvatarEditorData()
    {
        $editor_data = [];
        //title default
        $editor_data['title_text'] = esc_html__('Author');
        $editor_data['title_text_plural'] = esc_html__('Authors', 'publishpress-authors');
        $editor_data['title_html_tag'] = 'h2';
        $editor_data['box_tab_custom_wrapper_class'] = 'pp-multiple-authors-layout-inline';
        //avatar default
        $editor_data['avatar_show'] = 1;
        $editor_data['avatar_size'] = 30;
        $editor_data['avatar_border_radius'] = 0;
        //name default
        $editor_data['name_show'] = 1;
        $editor_data['name_disable_link'] = 0;
        $editor_data['name_html_tag'] = 'div';
        //bio default
        $editor_data['author_bio_html_tag'] = 'p';
        //meta default
        $editor_data['meta_view_all_show'] = 0;
        $editor_data['meta_html_tag'] = 'span';
        $editor_data['meta_label'] = __('View all posts', 'publishpress-authors');
        $editor_data['meta_background_color'] = '#655997';
        $editor_data['meta_color'] = '#ffffff';
        $editor_data['meta_link_hover_color'] = '#ffffff';
        //recent posts default
        $editor_data['author_recent_posts_title_show'] = 1;
        $editor_data['author_recent_posts_post_types'] = [];
        $editor_data['author_recent_posts_empty_show'] = 1;
        $editor_data['author_recent_posts_limit'] = 5;
        $editor_data['author_recent_posts_orderby'] = 'date';
        $editor_data['author_recent_posts_order'] = 'DESC';
        $editor_data['author_recent_posts_html_tag'] = 'div';
        $editor_data['author_recent_posts_title_border_bottom_style'] = 'dotted';
        $editor_data['author_recent_posts_alignment'] = 'left';
        //box layout default
        $editor_data['box_tab_layout_author_separator'] = '';
        $editor_data['box_layout_border_style'] = 'none';
        $editor_data['box_layout_shadow_horizontal_offset'] = 10;
        $editor_data['box_layout_shadow_vertical_offset'] = 10;
        $editor_data['box_layout_shadow_blur'] = 0;
        $editor_data['box_layout_shadow_speed'] = 0;
        $editor_data['box_layout_color'] = '#3c434a';
        // hide all default fields
        $editor_data['profile_fields_hide_first_name'] = 1;
        $editor_data['profile_fields_hide_last_name'] = 1;
        // email field
        $editor_data['profile_fields_user_email_html_tag'] = 'a';
        $editor_data['profile_fields_user_email_value_prefix'] = 'mailto:';
        $editor_data['profile_fields_user_email_display'] = 'icon';
        $editor_data['profile_fields_user_email_display_icon'] = '<span class="dashicons dashicons-email-alt"></span>';
        $editor_data['profile_fields_user_email_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_user_email_color'] = '#ffffff';
        $editor_data['profile_fields_user_email_display_icon_border_radius'] = '100';
        // website field
        $editor_data['profile_fields_user_url_html_tag'] = 'a';
        $editor_data['profile_fields_user_url_display'] = 'icon';
        $editor_data['profile_fields_user_url_display_icon'] = '<span class="dashicons dashicons-admin-links"></span>';
        $editor_data['profile_fields_user_url_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_user_url_color'] = '#ffffff';
        $editor_data['profile_fields_user_url_display_icon_border_radius'] = '100';
        //default css
        $editor_data['box_tab_custom_css'] = '.pp-multiple-authors-layout-inline ul.pp-multiple-authors-boxes-ul {
            display: flex;
        }

        .pp-multiple-authors-layout-inline ul.pp-multiple-authors-boxes-ul li {
            margin-right: 10px
        }
        .pp-multiple-authors-layout-inline ul.pp-multiple-authors-boxes-ul li.has-avatar .pp-author-boxes-avatar,
        .pp-multiple-authors-layout-inline ul.pp-multiple-authors-boxes-ul li.has-avatar .pp-author-boxes-avatar-details {
            display: inline-block;
        }';

        // hide all author fields
        $profile_fields   = apply_filters('multiple_authors_author_fields', [], false);
        foreach ($profile_fields as $key => $data) {
            $editor_data['profile_fields_hide_' . $key] = 1;
        }

        $editor_data = self::addEditorDataDefaultValues($editor_data);

        return $editor_data;
    }

    /**
     * Simple list editor data
     *
     * @return array
     */
    public static function getAuthorBoxesSimpleListEditorData()
    {
        $editor_data = [];
        //title default
        $editor_data['title_text'] = esc_html__('Author');
        $editor_data['title_text_plural'] = esc_html__('Authors', 'publishpress-authors');
        $editor_data['title_html_tag'] = 'h2';
        $editor_data['box_tab_custom_wrapper_class'] = 'pp-multiple-authors-layout-simple_list';
        //avatar default
        $editor_data['avatar_show'] = 1;
        $editor_data['avatar_size'] = 35;
        $editor_data['avatar_border_radius'] = 0;
        //name default
        $editor_data['name_show'] = 1;
        $editor_data['name_disable_link'] = 0;
        $editor_data['name_html_tag'] = 'div';
        //bio default
        $editor_data['author_bio_html_tag'] = 'p';
        //meta default
        $editor_data['meta_view_all_show'] = 0;
        $editor_data['meta_html_tag'] = 'span';
        $editor_data['meta_label'] = __('View all posts', 'publishpress-authors');
        $editor_data['meta_background_color'] = '#655997';
        $editor_data['meta_color'] = '#ffffff';
        $editor_data['meta_link_hover_color'] = '#ffffff';
        //recent posts default
        $editor_data['author_recent_posts_title_show'] = 1;
        $editor_data['author_recent_posts_post_types'] = [];
        $editor_data['author_recent_posts_empty_show'] = 1;
        $editor_data['author_recent_posts_limit'] = 5;
        $editor_data['author_recent_posts_orderby'] = 'date';
        $editor_data['author_recent_posts_order'] = 'DESC';
        $editor_data['author_recent_posts_html_tag'] = 'div';
        $editor_data['author_recent_posts_title_border_bottom_style'] = 'dotted';
        $editor_data['author_recent_posts_alignment'] = 'left';
        //box layout default
        $editor_data['box_layout_border_style'] = 'solid';
        $editor_data['box_layout_border_color'] = '#999999';
        $editor_data['box_layout_shadow_horizontal_offset'] = 10;
        $editor_data['box_layout_shadow_vertical_offset'] = 10;
        $editor_data['box_layout_shadow_blur'] = 0;
        $editor_data['box_layout_shadow_speed'] = 0;
        $editor_data['box_layout_color'] = '#3c434a';
        // hide all default fields
        $editor_data['profile_fields_hide_first_name'] = 1;
        $editor_data['profile_fields_hide_last_name'] = 1;
        // email field
        $editor_data['profile_fields_user_email_html_tag'] = 'a';
        $editor_data['profile_fields_user_email_value_prefix'] = 'mailto:';
        $editor_data['profile_fields_user_email_display'] = 'icon';
        $editor_data['profile_fields_user_email_display_icon'] = '<span class="dashicons dashicons-email-alt"></span>';
        $editor_data['profile_fields_user_email_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_user_email_color'] = '#ffffff';
        $editor_data['profile_fields_user_email_display_icon_border_radius'] = '100';
        // website field
        $editor_data['profile_fields_user_url_html_tag'] = 'a';
        $editor_data['profile_fields_user_url_display'] = 'icon';
        $editor_data['profile_fields_user_url_display_icon'] = '<span class="dashicons dashicons-admin-links"></span>';
        $editor_data['profile_fields_user_url_display_icon_background_color'] = '#655997';
        $editor_data['profile_fields_user_url_color'] = '#ffffff';
        $editor_data['profile_fields_user_url_display_icon_border_radius'] = '100';
        //default css
        $editor_data['box_tab_custom_css'] = '.pp-multiple-authors-boxes-wrapper.pp-multiple-authors-layout-simple_list .pp-multiple-authors-boxes-ul li {
            border-left: none !important;
            border-right: none !important;
        }';

        // hide all author fields
        $profile_fields   = apply_filters('multiple_authors_author_fields', [], false);
        foreach ($profile_fields as $key => $data) {
            $editor_data['profile_fields_hide_' . $key] = 1;
        }

        $editor_data = self::addEditorDataDefaultValues($editor_data);

        return $editor_data;
    }

    /**
     * Add editor data default values
     *
     * @param array $editor_data
     *
     * @return array
     */
    public static function addEditorDataDefaultValues($editor_data) {
        $profile_fields   = apply_filters('multiple_authors_author_fields', [], false);
        $social_fields   = ['facebook', 'twitter', 'x', 'instagram', 'linkedin', 'youtube', 'user_url', 'user_email', 'tiktok'];

        foreach ($profile_fields as $key => $data) {
            if ($data['type'] === 'url' && !in_array($key, $social_fields)) {
                $editor_data['profile_fields_' . $key . '_html_tag'] = 'a';
                $editor_data['profile_fields_' . $key . '_display']  = 'value';
                $editor_data['profile_fields_' . $key . '_color']    = '#655997';
            }
        }
        return $editor_data;
    }
}
