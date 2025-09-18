<?php
/**
 * Plugin Name: PublishPress Authors
 * Plugin URI:  https://wordpress.org/plugins/publishpress-authors/
 * Description: PublishPress Authors allows you to add multiple authors and guest authors to WordPress posts
 * Author:      PublishPress
 * Author URI:  https://publishpress.com
 * Version: 4.9.1
 * Text Domain: publishpress-authors
 * Domain Path: /languages
 * Requires at least: 5.5
 * Requires PHP: 7.2.5
 *
 * ------------------------------------------------------------------------------
 * Based on Co-Authors Plus.
 * Authors: Mohammad Jangda, Daniel Bachhuber, Automattic
 * Copyright: 2008-2015 Shared and distributed between  Mohammad Jangda, Daniel Bachhuber, Weston Ruter
 * ------------------------------------------------------------------------------
 *
 * GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link        https://publishpress.com/authors/
 * @package     MultipleAuthors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2020 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

use MultipleAuthors\Factory;
use MultipleAuthors\Plugin;

global $wp_version;

$min_php_version = '7.2.5';
$min_wp_version  = '5.5';

// If the PHP or WP version is not compatible, terminate the plugin execution.
$invalid_php_version = version_compare(phpversion(), $min_php_version, '<');
$invalid_wp_version = version_compare($wp_version, $min_wp_version, '<');

if ($invalid_php_version || $invalid_wp_version) {
    return;
}

if (! defined('PP_AUTHORS_LOADED')) {

    if (! defined('PP_AUTHORS_LIB_VENDOR_PATH')) {
        define('PP_AUTHORS_LIB_VENDOR_PATH', __DIR__ . '/lib/vendor');
    }

    $instanceProtectionIncPath = PP_AUTHORS_LIB_VENDOR_PATH . '/publishpress/instance-protection/include.php';
    if (is_file($instanceProtectionIncPath) && is_readable($instanceProtectionIncPath)) {
        require_once $instanceProtectionIncPath;
    }

    if (class_exists('PublishPressInstanceProtection\\Config')) {
        $pluginCheckerConfig = new PublishPressInstanceProtection\Config();
        $pluginCheckerConfig->pluginSlug = 'publishpress-authors';
        $pluginCheckerConfig->pluginName = 'PublishPress Authors';

        $pluginChecker = new PublishPressInstanceProtection\InstanceChecker($pluginCheckerConfig);
    }

    if (! defined('PP_AUTHORS_PRO_LIB_VENDOR_PATH')) {
        $autoloadFilePath = PP_AUTHORS_LIB_VENDOR_PATH . '/autoload.php';
        if (! class_exists('ComposerAutoloaderInitPPAuthors')
            && is_file($autoloadFilePath)
            && is_readable($autoloadFilePath)
        ) {
            require_once $autoloadFilePath;
        }
    }

    if (defined('PP_AUTHORS_PRO_LIB_VENDOR_PATH')) {
        add_filter(
            'plugin_row_meta',
            function ($links, $file) {
                if ($file == plugin_basename(__FILE__)) {
                    $links[]= '<strong>' . esc_html__('This plugin can be deleted.', 'publishpress-authors') . '</strong>';
                }

                return $links;
            },
            10,
            2
        );
    }

    add_action('plugins_loaded', function () {
        require_once __DIR__ . '/includes.php';

        global $multiple_authors_addon;

        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('publishpress-authors', 'MultipleAuthors\\WP_Cli');
        }

        // Init the legacy plugin instance
        $legacyPlugin = Factory::getLegacyPlugin();

        $multiple_authors_addon = new Plugin();

        /**
         * This installer is currently not running due to it been
         * inside plugins_loaded and we need to find a way around it
         */
        /*
        register_activation_hook(
            PP_AUTHORS_FILE,
            function () {
                require_once PP_AUTHORS_BASE_PATH . 'activation.php';
            }
        );
        */

        include_once __DIR__ . '/src/functions/notify.php';

        do_action('plublishpress_authors_loaded');
    }, -10);

    register_activation_hook(
        __FILE__,
        function () {
            update_option('ppma_activated', true);
        }
    );
}
