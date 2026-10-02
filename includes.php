<?php
/**
 * File responsible for defining basic general constants used by the plugin.
 *
 * @package     MultipleAuthors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

use PPVersionNotices\Module\MenuLink\Module;

defined('ABSPATH') or die('No direct script access allowed.');

if (!defined('PP_AUTHORS_LOADED')) {
    require_once __DIR__ . '/defines.php';

    if (file_exists(PP_AUTHORS_LIB_VENDOR_PATH . '/cmb2/cmb2/init.php')) {
        require_once PP_AUTHORS_LIB_VENDOR_PATH . '/cmb2/cmb2/init.php';
    }

    if (PUBLISHPRESS_AUTHORS_LOAD_DEPRECATED_LEGACY_CODE) {
        require_once PP_AUTHORS_BASE_PATH . 'deprecated.php';
    }

    require_once PP_AUTHORS_BASE_PATH . 'src/functions/template-tags.php';
    require_once PP_AUTHORS_BASE_PATH . 'src/functions/amp.php';

    if (is_admin() && !defined('PUBLISHPRESS_AUTHORS_SKIP_VERSION_NOTICES')) {
        add_action(
            'plublishpress_authors_loaded',
            function () {
                if (current_user_can('install_plugins')) {
                    add_filter(
                        \PPVersionNotices\Module\TopNotice\Module::SETTINGS_FILTER,
                        function ($settings) {
                            $settings['publishpress-authors'] = [
                                'message' => esc_html__("You're using PublishPress Authors Free. The Pro version has more features and support. %1$sUpgrade to Pro%2$s", 'publishpress-authors'),
                                'link'    => 'https://publishpress.com/links/authors-banner',
                                'screens' => [
                                    ['base' => 'edit-tags', 'id' => 'edit-author', 'taxonomy' => 'author'],
                                    ['base' => 'term', 'id' => 'edit-author', 'taxonomy' => 'author'],
                                    ['base' => 'edit', 'id' => 'edit-ppma_boxes', 'post_type' => 'ppma_boxes'],
                                    ['base' => 'post', 'id' => 'ppma_boxes', 'post_type' => 'ppma_boxes'],
                                    ['base' => 'edit', 'id' => 'edit-ppmacf_field', 'post_type' => 'ppmacf_field'],
                                    ['base' => 'post', 'id' => 'ppmacf_field', 'post_type' => 'ppmacf_field'],
                                    ['base' => 'authors_page_ppma-modules-settings', 'id' => 'authors_page_ppma-modules-settings'],
                                    ['base' => 'authors_page_ppma-author-list', 'id' => 'authors_page_ppma-author-list'],
                                    ['base' => 'authors_page_ppma-author-pages', 'id' => 'authors_page_ppma-author-pages'],
                                ]
                            ];

                            return $settings;
                        }
                    );

                    add_filter(
                        Module::SETTINGS_FILTER,
                        function ($settings) {
                            $settings['publishpress-authors'] = [
                                'parent' => 'ppma-authors',
                                'label'  => __( 'Upgrade to Pro', 'publishpress-authors' ),
                                'link'   => 'https://publishpress.com/links/authors-menu',
                            ];

                            return $settings;
                        }
                    );
                }
            }
        );
    }

    require_once PP_AUTHORS_MODULES_PATH . 'multiple-authors/multiple-authors.php';
}
