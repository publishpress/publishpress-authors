<?php

/**
 * @package     MultipleAuthorBoxes
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.1.0
 */

namespace MultipleAuthorBoxes;

use MultipleAuthors\Classes\Utils;
use MA_Author_Boxes;

/**
 * Author boxes field styles
 *
 * Based on Bylines.
 *
 * @package MultipleAuthorBoxes\Classes
 *
 */
class AuthorBoxesStyles
{
    
    /**
     * Get title field styles based on editor settings
     *
     * @param array $args
     * @param string $custom_styles
     * @return string
     */
    public static function getTitleFieldStyles($args, $custom_styles) {

        if ($args['title_bottom_space']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .box-header-title {
        margin-bottom: '. $args['title_bottom_space']['value'] .'px !important; 
} ';
        }
        if ($args['title_size']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .box-header-title {
        font-size: '. $args['title_size']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if ($args['title_line_height']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .box-header-title {
        line-height: '. $args['title_line_height']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['title_weight']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .box-header-title {
        font-weight: '. $args['title_weight']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['title_transform']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .box-header-title {
        text-transform: '. $args['title_transform']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['title_style']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .box-header-title {
        font-style: '. $args['title_style']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['title_decoration']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .box-header-title {
        text-decoration: '. $args['title_decoration']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['title_alignment']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .box-header-title {
        text-align: '. $args['title_alignment']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['title_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .box-header-title {
        color: '. $args['title_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }

        return $custom_styles;
    }
    
    /**
     * Get avatar field styles based on editor settings
     *
     * @param array $args
     * @param string $custom_styles
     * @return string
     */
    public static function getAvatarFieldStyles($args, $custom_styles) {

        if (!empty($args['avatar_size']['value'])) {
            $custom_styles .= '.pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-avatar img { 
        width: '. $args['avatar_size']['value'] .'px !important; 
        height: '. $args['avatar_size']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['avatar_border_style']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-avatar img {
        border-style: '. $args['avatar_border_style']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if ($args['avatar_border_width']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-avatar img {
        border-width: '. $args['avatar_border_width']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['avatar_border_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-avatar img {
        border-color: '. $args['avatar_border_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (isset($args['avatar_border_radius']['value']) && $args['avatar_border_radius']['value'] >= 0) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-avatar img {
        border-radius: '. $args['avatar_border_radius']['value'] .'% !important; 
    }' . "\n" . "\n";
        }

        return $custom_styles;
    }
    
    /**
     * Get name field styles based on editor settings
     *
     * @param array $args
     * @param string $custom_styles
     * @return string
     */
    public static function getNameFieldStyles($args, $custom_styles) {

        if ($args['name_size']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-name a {
        font-size: '. $args['name_size']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if ($args['name_line_height']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-name a {
        line-height: '. $args['name_line_height']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['name_weight']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-name a {
        font-weight: '. $args['name_weight']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['name_transform']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-name a {
        text-transform: '. $args['name_transform']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['name_style']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-name a {
        font-style: '. $args['name_style']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['name_decoration']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-name a {
        text-decoration: '. $args['name_decoration']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['name_alignment']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-name {
        text-align: '. $args['name_alignment']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['name_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-name a {
        color: '. $args['name_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }

        return $custom_styles;
    }
    
    /**
     * Get bio field styles based on editor settings
     *
     * @param array $args
     * @param string $custom_styles
     * @return string
     */
    public static function getBioFieldStyles($args, $custom_styles) {

        if ($args['author_bio_size']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-description {
        font-size: '. $args['author_bio_size']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if ($args['author_bio_line_height']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-description {
        line-height: '. $args['author_bio_line_height']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_bio_weight']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-description {
        font-weight: '. $args['author_bio_weight']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_bio_transform']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-description {
        text-transform: '. $args['author_bio_transform']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_bio_style']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-description {
        font-style: '. $args['author_bio_style']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_bio_decoration']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-description {
        text-decoration: '. $args['author_bio_decoration']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_bio_alignment']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-description {
        text-align: '. $args['author_bio_alignment']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_bio_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-description {
        color: '. $args['author_bio_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }

        return $custom_styles;
    }
    
    /**
     * Get meta field styles based on editor settings
     *
     * @param array $args
     * @param string $custom_styles
     * @return string
     */
    public static function getMetaFieldStyles($args, $custom_styles) {

        if ($args['meta_size']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-meta a span {
        font-size: '. $args['meta_size']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if ($args['meta_line_height']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-meta a span {
        line-height: '. $args['meta_line_height']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['meta_weight']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-meta a span {
        font-weight: '. $args['meta_weight']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['meta_transform']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-meta a span {
        text-transform: '. $args['meta_transform']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['meta_style']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-meta a span {
        font-style: '. $args['meta_style']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['meta_decoration']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-meta a span {
        text-decoration: '. $args['meta_decoration']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['meta_alignment']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-meta {
        text-align: '. $args['meta_alignment']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['meta_background_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-meta a {
        background-color: '. $args['meta_background_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['meta_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-meta a {
        color: '. $args['meta_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['meta_link_hover_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-meta a:hover {
        color: '. $args['meta_link_hover_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }

        return $custom_styles;
    }
    
    /**
     * Get author categories field styles based on editor settings
     *
     * @param array $args
     * @param string $custom_styles
     * @return string
     */
    public static function getAuthorCategoriesFieldStyles($args, $custom_styles) {

        if (!Utils::isAuthorsProActive()) {
            return $custom_styles;
        }

        if (!empty($args['author_categories_group_display_style_laptop']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-category-wrap {
        display: '. $args['author_categories_group_display_style_laptop']['value'] .' !important; 
    }' . "\n" . "\n";
            if ($args['author_categories_group_display_style_laptop']['value'] == 'flex') {
                $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-category-wrap .ppma-category-group {
        flex: 1; 
} ';
            }
        }
        if (!empty($args['author_categories_group_display_style_mobile']['value'])) {
            $custom_styles .= ' @media screen and (max-width: 768px) {
        .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-category-wrap { display: '. $args['author_categories_group_display_style_mobile']['value'] .' !important; 
} } ';
            if ($args['author_categories_group_display_style_mobile']['value'] == 'flex') {
                $custom_styles .= ' @media screen and (max-width: 768px) {
        .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-category-wrap .ppma-category-group { flex: 1; 
} } ';
            }
        }
        if (isset($args['author_categories_bottom_space']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-category-wrap .ppma-category-group {
        margin-bottom: '. $args['author_categories_bottom_space']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if (isset($args['author_categories_right_space']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-category-wrap .ppma-category-group {
        margin-right: '. $args['author_categories_right_space']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if (isset($args['author_categories_font_size']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-category-wrap {
        font-size: '. $args['author_categories_font_size']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if (isset($args['author_categories_title_font_weight']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-category-wrap .ppma-category-group-title {
        font-weight: '. $args['author_categories_title_font_weight']['value'] .' !important; 
    }' . "\n" . "\n";
        }

        return $custom_styles;
    }
    
    /**
     * Get profile field styles based on editor settings
     *
     * @param array $args
     * @param string $custom_styles
     * @return string
     */
    public static function getRProfileFieldStyles($args, $custom_styles) {

        $profile_fields   = MA_Author_Boxes::get_profile_fields($args['post_id']);

        foreach ($profile_fields as $key => $data) {
            if (!in_array($key, MA_Author_Boxes::AUTHOR_BOXES_EXCLUDED_FIELDS)) {
                if ($args['profile_fields_' . $key . '_size']['value']) {
                    $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-'. $key .'-profile-data {
        font-size: '. $args['profile_fields_' . $key . '_size']['value'] .'px !important; 
    }' . "\n" . "\n";
                }
                if ($args['profile_fields_' . $key . '_display_icon_size']['value']) {
                    $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-'. $key .'-profile-data span, 
    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-'. $key .'-profile-data i {
        font-size: '. $args['profile_fields_' . $key . '_display_icon_size']['value'] .'px !important; 
    }' . "\n" . "\n";
                }
                if ($args['profile_fields_' . $key . '_display_icon']['value'] && !empty($args['profile_fields_' . $key . '_display_icon']['value'])) {
                    if ($args['profile_fields_' . $key . '_display_icon_background_color']['value']) {
                        $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-'. $key .'-profile-data {
        background-color: '. $args['profile_fields_' . $key . '_display_icon_background_color']['value'] .' !important; 
    }' . "\n" . "\n";
                    }
                    if (isset($args['profile_fields_' . $key . '_display_icon_border_radius']['value']) && $args['profile_fields_' . $key . '_display_icon_border_radius']['value'] >= 0) {
                        $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-'. $key .'-profile-data {
        border-radius: '. $args['profile_fields_' . $key . '_display_icon_border_radius']['value'] .'% !important; 
    }' . "\n" . "\n";
                    }
                }
                if ($args['profile_fields_' . $key . '_line_height']['value']) {
                    $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-'. $key .'-profile-data {
        line-height: '. $args['profile_fields_' . $key . '_line_height']['value'] .'px !important; 
    }' . "\n" . "\n";
                }
                if (!empty($args['profile_fields_' . $key . '_weight']['value'])) {
                    $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-'. $key .'-profile-data {
        font-weight: '. $args['profile_fields_' . $key . '_weight']['value'] .' !important; 
    }' . "\n" . "\n";
                }
                if (!empty($args['profile_fields_' . $key . '_transform']['value'])) {
                    $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-'. $key .'-profile-data {
        text-transform: '. $args['profile_fields_' . $key . '_transform']['value'] .' !important; 
    }' . "\n" . "\n";
                }
                if (!empty($args['profile_fields_' . $key . '_style']['value'])) {
                    $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-'. $key .'-profile-data {
        font-style: '. $args['profile_fields_' . $key . '_style']['value'] .' !important; 
    }' . "\n" . "\n";
                }
                if (!empty($args['profile_fields_' . $key . '_decoration']['value'])) {
                    $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-'. $key .'-profile-data {
        text-decoration: '. $args['profile_fields_' . $key . '_decoration']['value'] .' !important; 
    }' . "\n" . "\n";
                }
                if (!empty($args['profile_fields_' . $key . '_alignment']['value'])) {
                    $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-'. $key .'-profile-data {
        text-align: '. $args['profile_fields_' . $key . '_alignment']['value'] .' !important; 
    }' . "\n" . "\n";
                }
                if (!empty($args['profile_fields_' . $key . '_color']['value'])) {
                    $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-'. $key .'-profile-data {
        color: '. $args['profile_fields_' . $key . '_color']['value'] .' !important; 
    }' . "\n" . "\n";
                    $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .ppma-author-'. $key .'-profile-data:hover {
        color: '. $args['profile_fields_' . $key . '_color']['value'] .' !important; 
    }' . "\n" . "\n";
                }
            }
        }

        return $custom_styles;
    }
    
    /**
     * Get recent posts field styles based on editor settings
     *
     * @param array $args
     * @param string $custom_styles
     * @return string
     */
    public static function getRecentPostsFieldStyles($args, $custom_styles) {

        if (!empty($args['author_recent_posts_title_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-title {
        color: '. $args['author_recent_posts_title_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_recent_posts_title_border_bottom_style']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-title {
        border-bottom-style: '. $args['author_recent_posts_title_border_bottom_style']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if ($args['author_recent_posts_title_border_width']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-title {
        border-width: '. $args['author_recent_posts_title_border_width']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_recent_posts_title_border_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-title {
        border-color: '. $args['author_recent_posts_title_border_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if ($args['author_recent_posts_size']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item a {
        font-size: '. $args['author_recent_posts_size']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if ($args['author_recent_posts_line_height']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item a {
        line-height: '. $args['author_recent_posts_line_height']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_recent_posts_weight']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item a {
        font-weight: '. $args['author_recent_posts_weight']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_recent_posts_transform']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item a {
        text-transform: '. $args['author_recent_posts_transform']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_recent_posts_style']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item a {
        font-style: '. $args['author_recent_posts_style']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_recent_posts_decoration']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item a {
        text-decoration: '. $args['author_recent_posts_decoration']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_recent_posts_alignment']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item {
        text-align: '. $args['author_recent_posts_alignment']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_recent_posts_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item a {
        color: '. $args['author_recent_posts_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['author_recent_posts_icon_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item span.dashicons,
    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item i.dashicons,
    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item i.fa,
    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item i.fas,
    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item i.far,
    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-author-boxes-recent-posts-item i.fab {
        color: '. $args['author_recent_posts_icon_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }

        return $custom_styles;
    }
    
    /**
     * Get box layout field styles based on editor settings
     *
     * @param array $args
     * @param string $custom_styles
     * @return string
     */
    public static function getBoxLayoutFieldStyles($args, $custom_styles) {

        if ($args['box_layout_margin_top']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        margin-top: '. $args['box_layout_margin_top']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if ($args['box_layout_margin_bottom']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        margin-bottom: '. $args['box_layout_margin_bottom']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if ($args['box_layout_margin_left']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        margin-left: '. $args['box_layout_margin_left']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if ($args['box_layout_margin_right']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        margin-right: '. $args['box_layout_margin_right']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if ($args['box_layout_padding_top']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        padding-top: '. $args['box_layout_padding_top']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if ($args['box_layout_padding_bottom']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        padding-bottom: '. $args['box_layout_padding_bottom']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if ($args['box_layout_padding_left']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        padding-left: '. $args['box_layout_padding_left']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if ($args['box_layout_padding_right']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        padding-right: '. $args['box_layout_padding_right']['value'] .'px !important; 
    }' . "\n" . "\n";
        }

        if (!empty($args['box_layout_border_style']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        border-style: '. $args['box_layout_border_style']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if ($args['box_layout_border_width']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        border-width: '. $args['box_layout_border_width']['value'] .'px !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['box_layout_border_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        border-color: '. $args['box_layout_border_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if ($args['box_layout_box_width']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        width: '. $args['box_layout_box_width']['value'] .'% !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['box_layout_background_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        background-color: '. $args['box_layout_background_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['box_layout_color']['value'])) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        color: '. $args['box_layout_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (!empty($args['box_layout_shadow_color']['value']) && $args['box_layout_shadow_horizontal_offset']['value'] && $args['box_layout_shadow_vertical_offset']['value'] && $args['box_layout_shadow_blur']['value']) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        box-shadow: '. $args['box_layout_shadow_horizontal_offset']['value'] .'px '. $args['box_layout_shadow_vertical_offset']['value'] .'px '. $args['box_layout_shadow_blur']['value'] .'px '. $args['box_layout_shadow_speed']['value'] .'px '. $args['box_layout_shadow_color']['value'] .' !important; 
    }' . "\n" . "\n";
        }
        if (isset($args['box_layout_border_radius']['value']) && $args['box_layout_border_radius']['value'] >= 0) {
            $custom_styles .= '    .pp-multiple-authors-boxes-wrapper.'.$args['additional_class'].' .pp-multiple-authors-boxes-li {
        border-radius: '. $args['box_layout_border_radius']['value'] .'px !important; 
    }' . "\n" . "\n";
        }

        return $custom_styles;
    }
    
    /**
     * Get custom css field styles based on editor settings
     *
     * @param array $args
     * @param string $custom_styles
     * @return string
     */
    public static function getCustomCssFieldStyles($args, $custom_styles) {

        if (!empty($args['box_tab_custom_css']['value'])) {
            $custom_styles .= $args['box_tab_custom_css']['value'];
        }

        return $custom_styles;
    }

}
