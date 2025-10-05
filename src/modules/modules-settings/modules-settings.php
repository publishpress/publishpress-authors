<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2018 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;
use PublishPress\WordPressBanners\BannersMain;

if (!class_exists('MA_Modules_Settings')) {
    /**
     * class MA_Modules_Settings
     * Threaded commenting in the admin for discussion between writers and editors
     *
     * @author batmoo
     */
    class MA_Modules_Settings extends Module
    {
        const SETTINGS_SLUG = 'ppma-modules-settings';

        protected $options_group_name = 'modules_settings';

        public $module_url;
        public $module;

        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);
            // Register the module with PublishPress
            $args = [
                'title'                => __('Authors Settings', 'publishpress-authors'),
                'short_description'    => false,
                'extended_description' => false,
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-admin-settings',
                'slug'                 => 'modules-settings',
                'default_options'      => [
                    'enabled' => 'on',
                ],
                'configure_page_cb'    => 'print_configure_view',
                'autoload'             => false,
                'options_page'         => false,
            ];

            $legacyPlugin = Factory::getLegacyPlugin();

            $this->module = $legacyPlugin->register_module($this->options_group_name, $args);
        }

        /**
         * Initialize the rest of the stuff in the class if the module is active
         */
        public function init()
        {
            if (is_admin()) {
                add_action('admin_init', [$this, 'register_settings']);
                add_action('admin_enqueue_scripts', [$this, 'add_admin_scripts']);
            }
        }

        /**
         * Load any of the admin scripts we need but only on the pages we need them
         */
        public function add_admin_scripts()
        {
            global $pagenow;

            wp_enqueue_style(
                'publishpress-modules-css',
                $this->module_url . 'lib/modules-settings.css',
                false,
                PP_AUTHORS_VERSION,
                'all'
            );

            if (isset($_GET['page']) && in_array($_GET['page'], ['ppma-modules-settings', 'ppma-author-pages'])) {
                wp_enqueue_script('jquery-ui-core');
                wp_enqueue_script('jquery-ui-tabs');
            }
        }

        /**
         * Register settings for notifications so we can partially use the Settings API
         * (We use the Settings API for form generation, but not saving)
         *
         * @since 0.7
         * @uses  add_settings_section(), add_settings_field()
         */
        public function register_settings()
        {
        }

        /**
         * Validate data entered by the user
         *
         * @param array $new_options New values that have been entered by the user
         *
         * @return array $new_options Form values after they've been sanitized
         * @since 0.7
         *
         */
        public function settings_validate($new_options)
        {
            return $new_options;
        }

        /**
         * Save the custom settings
         *
         * @param array $new_options New values that have been entered by the user
         */
        public function settings_save($new_options)
        {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if (!isset($_POST['multiple_authors_options']) || !isset($_POST['multiple_authors_options']['features'])) {
                return true;
            }

            $legacyPlugin = Factory::getLegacyPlugin();

            $enabledFeatures = Utils::sanitizeArray($_POST['multiple_authors_options']['features']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing

            // Run through all the modules updating their statuses
            foreach ($legacyPlugin->modules as $mod_data) {
                if ($mod_data->autoload
                    || $mod_data->slug === $this->module->slug) {
                    continue;
                }

                $status = array_key_exists($mod_data->slug, $enabledFeatures) ? 'on' : 'off';
                $legacyPlugin->update_module_option($mod_data->name, 'enabled', $status);
            }

            return true;
        }

        /**
         * Settings page for editorial comments
         *
         * @since 0.7
         */
        public function print_configure_view()
        {
            global $ppma_custom_settings;

            $legacyPlugin = Factory::getLegacyPlugin();

            /**
             * @param array $tabs
             *
             * @return array
             */
            $tabs = apply_filters('publishpress_multiple_authors_settings_tabs', []);

            if (is_array($ppma_custom_settings)) {
                $ppma_settings_modules      = $ppma_custom_settings['modules'];
                $ppma_settings_class_names  = $ppma_custom_settings['class_names'];
                $ppma_settings_class_names  = $ppma_custom_settings['class_names'];
                $tabs                       = $ppma_custom_settings['tabs'];
                $ppma_active_tab            = $ppma_custom_settings['active_tabs'];
            } else {
                $ppma_settings_modules      = $legacyPlugin->modules;
                $ppma_settings_class_names  = $legacyPlugin->class_names;
                $ppma_active_tab            = '#ppma-tab-general';
            }

            ?>

            <div class="pp-columns-wrapper<?php echo (!Utils::isAuthorsProActive()) ? ' pp-enable-sidebar' : '' ?>">
                <div class="pp-column-left">
                    <form class="basic-settings"
                          action="<?php echo esc_url(menu_page_url($this->module->settings_slug, false)); ?>" method="post">

                        <?php
                        if (!empty($tabs)) {
                            echo '<ul id="publishpress-authors-settings-tabs" class="nav-tab-wrapper">';
                            foreach ($tabs as $tabLink => $tabLabel) {
                                $li_style = '';

                                // Hide author-pages tab
                                if ($tabLink === '#ppma-tab-author-pages') {
                                    $li_style = 'display: none;';
                                }

                                // Hide integration tab if no active integrations
                                if ($tabLink === '#ppma-tab-integration' && !apply_filters('publishpress_authors_has_active_integrations', false)) {
                                    $li_style = 'display: none;';
                                }

                                echo '<li style="'. esc_attr($li_style) .'" class="nav-tab ' . ($tabLink === $ppma_active_tab ? 'nav-tab-active' : '') . '">';
                                echo '<a href="' . esc_url($tabLink) . '" data-tab-content="' . esc_attr($tabLink) . '">' . esc_html($tabLabel) . '</a>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        }
                        ?>

                        <?php settings_fields($this->module->options_group_name); ?>
                        <?php do_settings_sections($this->module->options_group_name); ?>

                        <?php
                        foreach ($ppma_settings_class_names as $slug => $class_name) {
                            $mod_data = $legacyPlugin->$slug->module;

                            if ($mod_data->autoload
                                || $mod_data->slug === $this->module->slug
                                || !isset($mod_data->general_options)
                                || $mod_data->options->enabled != 'on') {
                                continue;
                            }

                            echo sprintf('<h3>%s</h3>', esc_html($mod_data->title));
                            echo sprintf('<p>%s</p>', esc_html($mod_data->short_description));

                            echo '<input name="multiple_authors_module_name[]" type="hidden" value="'
                                . esc_attr($mod_data->name) . '" />';

                            $legacyPlugin->$slug->print_configure_view();
                        }

                        // Print the main module's settings page
                        $legacyPlugin->multiple_authors->print_configure_view();

                        // Check if we have any feature user can toggle.
                        $featuresCount = 0;

                        foreach ($ppma_settings_modules as $mod_name => $mod_data) {
                            if (!$mod_data->autoload && $mod_data->slug !== $this->module->slug) {
                                $featuresCount++;
                            }
                        }
                        ?>

                        <?php if ($featuresCount > 0) : ?>
                            <div id="modules-wrapper">
                                <h3><?php echo esc_html__('Features', 'publishpress-authors'); ?></h3>
                                <p><?php echo esc_html__(
                                        'Feel free to select only the features you need.',
                                        'publishpress-authors'
                                    ); ?></p>

                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th scope="row"><?php echo esc_html__(
                                                'Enabled features',
                                                'publishpress-authors'
                                            ); ?></th>
                                        <td>
                                            <?php foreach ($ppma_settings_modules as $mod_name => $mod_data) : ?>

                                                <?php if ($mod_data->autoload || $mod_data->slug === $this->module->slug) {
                                                    continue;
                                                } ?>

                                                <label for="feature-<?php echo esc_attr($mod_data->slug); ?>">
                                                    <input id="feature-<?php echo esc_attr($mod_data->slug); ?>"
                                                           name="multiple_authors_options[features][<?php echo esc_attr(
                                                               $mod_data->slug
                                                           ); ?>]" <?php echo ($mod_data->options->enabled == 'on') ? "checked=\"checked\"" : ""; ?>
                                                           type="checkbox">
                                                    &nbsp;&nbsp;&nbsp;<?php echo esc_html($mod_data->title); ?>
                                                </label>
                                                <br>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>

                                <?php echo '<input name="multiple_authors_module_name[]" type="hidden" value="'
                                    . esc_attr($this->module->name) . '" />'; ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        wp_nonce_field('edit-publishpress-settings');

                        submit_button(null, 'primary', 'submit', false); ?>
                    </form>
                </div><!-- .pp-column-left -->
                <?php if (!Utils::isAuthorsProActive()) { ?>
                    <div class="pp-column-right">
                        <?php Utils::ppma_pro_sidebar(); ?>
                        <div style="display: none;">
                        <?php
                        $banners = new BannersMain;
                        $banners->pp_display_banner(
                            esc_html__( 'Recommendations for you', 'publishpress-authors' ),
                            esc_html__( 'Showcase your Authors with PublishPress Blocks', 'publishpress-authors' ),
                            array(
                                esc_html__( 'PublishPress Blocks is a free plugin with full support for PublishPress Authors.', 'publishpress-authors' ),
                                esc_html__( 'Install this plugin to showcase content by your Authors.', 'publishpress-authors' ),
                                esc_html__( 'Use the Content Display block to show your posts in many beautiful layouts.', 'publishpress-authors' ),
                                esc_html__( 'PublishPress Blocks has over 20 extra Gutenberg blocks including accordions, galleries, tables, and more.', 'publishpress-authors' ),
                            ),
                            esc_url(admin_url( 'plugin-install.php?s=publishpress-advg-install&tab=search&type=term' )),
                            esc_html__( 'Click here to install PublishPress Blocks', 'publishpress-authors' ),
                            'install-blocks.jpg'
                        );
                        ?>
                    </div>
                    </div><!-- .pp-column-right -->
                <?php } ?>
            </div><!-- .pp-columns-wrapper -->
            <?php
        }
    }
}
