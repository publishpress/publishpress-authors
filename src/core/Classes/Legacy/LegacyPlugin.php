<?php
/**
 * @package     MultipleAuthors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.1.0
 */

namespace MultipleAuthors\Classes\Legacy;

use MultipleAuthors\Classes\Utils;
use stdClass;

/**
 * Legacy plugin class, porting the PublishPress dependencies.
 */
#[\AllowDynamicProperties]
class LegacyPlugin
{
    /**
     * @var stdClass
     */
    public $modules;

    private $options_group = 'multiple_authors_';

    public function __construct()
    {
        $this->modules = new stdClass();

        $this->setup_actions();
    }

    /**
     * Setup the default hooks and actions
     *
     * @since  PublishPress 0.7.4
     * @access private
     * @uses   add_action() To add various actions
     */
    private function setup_actions()
    {
        add_action('init', [$this, 'action_init'], 1000);
        add_action('init', [$this, 'action_init_after'], 1100);

        if (
            is_admin()
            && (!defined('DOING_AJAX') || !DOING_AJAX)
            && (!defined('DOING_CRON') || !DOING_CRON)
            && (!defined('PUBLISHPRESS_AUTHORS_BYPASS_INSTALLER') || !PUBLISHPRESS_AUTHORS_BYPASS_INSTALLER)
        ) {
            add_action('admin_init', [$this, 'action_ini_for_admin']);
            add_action('admin_menu', [$this, 'action_admin_menu'], 9);
        }

        do_action_ref_array('multiple_authors_after_setup_actions', [$this]);

        add_filter('debug_information', [$this, 'filterDebugInformation']);
    }

    /**
     * Inititalizes the legacy plugin instance!
     * Loads options for each registered module and then initializes it if it's active
     */
    public function action_init()
    {
        $this->load_modules();

        // Load all of the module options
        $this->load_module_options();

        // Init all of the modules that are enabled.
        // Modules won't have an options value if they aren't enabled
        foreach ($this->modules as $mod_name => $mod_data) {
            if (isset($mod_data->options->enabled) && $mod_data->options->enabled == 'on') {
                $this->$mod_name->init();
            }
        }

        do_action('multiple_authors_init');
    }

    /**
     * Include the common resources to PublishPress and dynamically load the modules
     */
    private function load_modules()
    {
        // We use the WP_List_Table API for some of the table gen
        if (!class_exists('WP_List_Table')) {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
        }

        $module_dirs = $this->getModulesDirs();

        $class_names = [];

        foreach ($module_dirs as $module_slug => $base_path) {
            if (file_exists("{$base_path}/{$module_slug}/{$module_slug}.php")) {
                include_once "{$base_path}/{$module_slug}/{$module_slug}.php"; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath

                // Prepare the class name because it should be standardized
                $tmp        = explode('-', $module_slug);
                $class_name = '';
                $slug_name  = '';

                foreach ($tmp as $word) {
                    $class_name .= ucfirst($word) . '_';
                    $slug_name  .= $word . '_';
                }

                $slug_name               = rtrim($slug_name, '_');
                $class_names[$slug_name] = 'MA_' . rtrim($class_name, '_');
            }
        }

        // Instantiate all of our classes onto the PublishPress object
        // but make sure they exist too
        foreach ($class_names as $slug => $class_name) {
            if (class_exists($class_name)) {
                $slug            = Util::sanitize_module_name($slug);
                $module_instance = new $class_name();

                $this->$slug = $module_instance;

                // If there's a Help Screen registered for the module, make sure we auto-load it
                $args = null;
                if (isset($this->modules->$slug)) {
                    $args = $this->modules->$slug;
                }

                if (!is_null($args) && !empty($args->settings_help_tab)) {
                    add_action(
                        'load-multiple_authors_page_' . $args->settings_slug,
                        [$module_instance, 'action_settings_help_menu']
                    );
                }

                $this->loadedModules[] = $slug;
            }
        }

        $this->helpers = new Module();

        $this->class_names = $class_names;

        // Supplementary plugins can hook into this, include their own modules
        // and add them to the plugin instance
        do_action('multiple_authors_modules_loaded');
    }

    /**
     * @return array
     */
    private function getModulesDirs()
    {
        $defaultDirs = [
            'modules-settings'     => PP_AUTHORS_MODULES_PATH,
            'author-boxes'         => PP_AUTHORS_MODULES_PATH,
            'author-categories'    => PP_AUTHORS_MODULES_PATH,
            'author-custom-fields' => PP_AUTHORS_MODULES_PATH,
            'author-list'          => PP_AUTHORS_MODULES_PATH,
            'author-pages'         => PP_AUTHORS_MODULES_PATH,
            'settings'             => PP_AUTHORS_MODULES_PATH,
            'multiple-authors'     => PP_AUTHORS_MODULES_PATH,
            'default-layouts'      => PP_AUTHORS_MODULES_PATH,
            'rest-api'             => PP_AUTHORS_MODULES_PATH,
            'polylang-integration' => PP_AUTHORS_MODULES_PATH,
            'reviews'              => PP_AUTHORS_MODULES_PATH,
        ];

        if (Utils::isBylineInstalled()) {
            $defaultDirs['byline-migration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isBylinesInstalled()) {
            $defaultDirs['bylines-migration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isDebugActivated()) {
            $defaultDirs['debug'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isDiviInstalled()) {
            $defaultDirs['divi-integration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isEditflowInstalled()) {
            $defaultDirs['editflow-integration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isElementorInstalled()) {
            $defaultDirs['elementor-integration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isPolylangInstalled()) {
            $defaultDirs['polylang-integration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isGenesisInstalled()) {
            $defaultDirs['genesis-integration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isUltimateMemberInstalled()) {
            $defaultDirs['ultimatemember-integration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isMolonguiAuthorshipActivated()) {
            $defaultDirs['molongui-authorship-migration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isCompatibleYoastSeoInstalled()) {
            $defaultDirs['yoast-seo-integration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isWPEngineInstalled()) {
            $defaultDirs['wpengine-integration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isTheSEOFrameworkInstalled()) {
            $defaultDirs['seoframework-integration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isGeneratepressInstalled()) {
            $defaultDirs['generatepress-integration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isRankMathSeoInstalled()) {
            $defaultDirs['rank-math-seo-integration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isAllInOneSeoPackInstalled()) {
            $defaultDirs['all-in-one-seo-pack-integration'] = PP_AUTHORS_MODULES_PATH;
        }

        if (Utils::isUltimatePostInstalled()) {
            $defaultDirs['ultimate-post-integration'] = PP_AUTHORS_MODULES_PATH;
        }

        return apply_filters('ppma_module_dirs', $defaultDirs);
    }

    /**
     * Load all of the module options from the database
     * If a given option isn't yet set, then set it to the module's default (upgrades, etc.)
     */
    public function load_module_options()
    {
        foreach ($this->modules as $mod_name => $mod_data) {
            $this->modules->$mod_name->options = get_option(
                $this->options_group . $mod_name . '_options',
                new stdClass()
            );
            foreach ($mod_data->default_options as $default_key => $default_value) {
                if (!isset($this->modules->$mod_name->options->$default_key)) {
                    $this->modules->$mod_name->options->$default_key = $default_value;
                }
            }
            $this->$mod_name->module = $this->modules->$mod_name;
        }

        do_action('multiple_authors_module_options_loaded');
    }

    /**
     * Register a new module with Multiple Authors
     */
    public function register_module($name, $args = [])
    {
        // A title and name is required for every module
        if (!isset($args['title'], $name)) {
            return false;
        }

        $defaults = [
            'title'                => '',
            'short_description'    => '',
            'extended_description' => '',
            'icon_class'           => 'dashicons dashicons-admin-generic',
            'slug'                 => '',
            'post_type_support'    => '',
            'default_options'      => [],
            'options'              => false,
            'configure_page_cb'    => false,
            'configure_link_text'  => __('Configure', 'publishpress-authors'),
            // These messages are applied to modules and can be overridden if custom messages are needed
            'messages'             => [
                'form-error'          => __(
                    'Please correct your form errors below and try again.',
                    'publishpress-authors'
                ),
                'nonce-failed'        => __('Cheatin&#8217; uh?', 'publishpress-authors'),
                'invalid-permissions' => __(
                    'You do not have necessary permissions to complete this action.',
                    'publishpress-authors'
                ),
                'missing-post'        => __('Post does not exist', 'publishpress-authors'),
            ],
            'autoload'             => false, // autoloading a module will remove the ability to enable or disable it
        ];
        if (isset($args['messages'])) {
            $args['messages'] = array_merge((array)$args['messages'], $defaults['messages']);
        }
        $args                       = array_merge($defaults, $args);
        $args['name']               = $name;
        $args['options_group_name'] = $this->options_group . $name . '_options';

        if (!isset($args['settings_slug'])) {
            $args['settings_slug'] = 'ppma-' . $args['slug'] . '-settings';
        }

        if (empty($args['post_type_support'])) {
            $args['post_type_support'] = 'pp_ma_' . $name;
        }

        $this->modules->$name = (object)$args;
        do_action('multiple_authors_module_registered', $name);

        return $this->modules->$name;
    }

    /**
     * Initialize the plugin for the admin
     */
    public function action_ini_for_admin()
    {
        $versionOption = $this->options_group . 'version';

        // Upgrade if need be but don't run the upgrade if the plugin has never been used
        $previous_version = get_option($versionOption);
        if ($previous_version && version_compare($previous_version, PP_AUTHORS_VERSION, '<')) {
            foreach ($this->modules as $mod_name => $mod_data) {
                if (method_exists($this->$mod_name, 'upgrade')) {
                    $this->$mod_name->upgrade($previous_version);
                }
            }
        }

        if ($previous_version !== PP_AUTHORS_VERSION) {
            update_option($versionOption, PP_AUTHORS_VERSION);
        }

        // For each module that's been loaded, autoload data if it's never been run before
        foreach ($this->modules as $mod_name => $mod_data) {
            // If the module has never been loaded before, run the install method if there is one
            if (!isset($mod_data->options->loaded_once) || !$mod_data->options->loaded_once) {
                if (method_exists($this->$mod_name, 'install')) {
                    $this->$mod_name->install();
                }
                $this->update_module_option($mod_name, 'loaded_once', true);
            }
        }
    }

    /**
     * Update the $legacyPlugin object with new value and save to the database
     */
    public function update_module_option($mod_name, $key, $value)
    {
        if (false === $this->modules->$mod_name->options) {
            $this->modules->$mod_name->options = new stdClass();
        }

        $this->modules->$mod_name->options->$key = $value;
        $this->$mod_name->module                 = $this->modules->$mod_name;

        return update_option($this->options_group . $mod_name . '_options', $this->modules->$mod_name->options);
    }

    public function update_all_module_options($mod_name, $new_options)
    {
        if (is_array($new_options)) {
            $new_options = (object)$new_options;
        }

        $this->modules->$mod_name->options = $new_options;
        $this->$mod_name->module           = $this->modules->$mod_name;

        return update_option($this->options_group . $mod_name . '_options', $this->modules->$mod_name->options);
    }

    /**
     * Add the menu page and call an action for modules add submenus
     */
    public function action_admin_menu()
    {
        /**
         * Action for adding menu pages.
         */
        do_action('multiple_authors_admin_menu_page');

        /**
         * Action for adding submenus.
         */
        do_action('multiple_authors_admin_submenu');
    }

    /**
     * @param array $debugInfo
     *
     * @return array
     */
    public function filterDebugInformation($debugInfo)
    {
        $modules     = [];
        $modulesDirs = $this->getModulesDirs();

        foreach ($this->loadedModules as $module) {
            $dashCaseModule = str_replace('_', '-', $module);

            $status = isset($this->{$module}) && isset($this->{$module}->module->options->enabled) ? $this->{$module}->module->options->enabled : 'on';

            $modules[$module] = [
                'label' => $module,
                'value' => $status . ' [' . $modulesDirs[$dashCaseModule] . '/modules/' . $module . ']',
            ];
        }

        $debugInfo['publishpress-modules'] = [
            'label'       => 'PublishPress Modules',
            'description' => '',
            'show_count'  => true,
            'fields'      => $modules,
        ];

        return $debugInfo;
    }

    /**
     * Load the post type options again so we give add_post_type_support() a chance to work
     *
     * @see https://publishpress.com/2011/11/17/publishpress-v0-7-alpha2-notes/#comment-232
     */
    public function action_init_after()
    {
        foreach ($this->modules as $mod_name => $mod_data) {
            if (isset($this->modules->$mod_name->options->post_types)) {
                $this->modules->$mod_name->options->post_types = $this->helpers->clean_post_type_options(
                    $this->modules->$mod_name->options->post_types,
                    $mod_data->post_type_support
                );
            }

            $this->$mod_name->module = $this->modules->$mod_name;
        }
    }

    /**
     * Get a module by one of its descriptive values
     */
    public function get_module_by($key, $value)
    {
        $module = false;
        foreach ($this->modules as $mod_name => $mod_data) {
            if ($key == 'name' && $value == $mod_name) {
                $module = $this->modules->$mod_name;
            } else {
                foreach ($mod_data as $mod_data_key => $mod_data_value) {
                    if ($mod_data_key == $key && $mod_data_value == $value) {
                        $module = $this->modules->$mod_name;
                    }
                }
            }
        }

        return $module;
    }
}
