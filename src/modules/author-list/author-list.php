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

use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Legacy\Module;
use MultipleAuthorList\AuthorListTable;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Capability;
use MultipleAuthors\Factory;

/**
 * class MA_Author_List
 */
class MA_Author_List extends Module
{

    /**
     * Instance of the module
     *
     * @var stdClass
     */
    public $module;
    public $module_url;

    public $module_name = 'author_list';

    // WP_List_Table object
    public $author_list_table;

    const MENU_SLUG = 'ppma-author-list';

    /**
     * Construct the MA_Multiple_Authors class
     */
    public function __construct()
    {

        $this->module_url = $this->get_module_url(__FILE__);

        parent::__construct();

        // Register the module with PublishPress
        $args = [
            'title' => __('Author List', 'publishpress-authors'),
            'short_description' => __(
                'Add support for author list.',
                'publishpress-authors'
            ),
            'extended_description' => __(
                'Add support for author list.',
                'publishpress-authors'
            ),
            'module_url' => $this->module_url,
            'icon_class' => 'dashicons dashicons-edit',
            'slug' => 'author-list',
            'default_options' => [
                'enabled' => 'on',
                'author_list_data'    => [],
                'author_list_last_id' => 0,
            ],
            'options_page' => false,
            'autoload' => true,
        ];

        // Apply a filter to the default options
        $args['default_options'] = apply_filters('MA_Author_List_default_options', $args['default_options']);

        $legacyPlugin = Factory::getLegacyPlugin();

        $this->module = $legacyPlugin->register_module($this->module_name, $args);

        parent::__construct();
    }

    /**
     * Initialize the module. Conditionally loads if the module is enabled
     */
    public function init()
    {
        add_action('multiple_authors_admin_submenu', [$this, 'adminSubmenu'], 50);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_filter('set-screen-option', [$this, 'setScreen'], 10, 3);
        add_filter('removable_query_args', [$this, 'removableQueryArgs']);
        add_action('wp_ajax_author_list_editor_do_shortcode', [$this, 'handle_author_list_do_shortcode']);
    }

    public static function setScreen($status, $option, $value)
    {
        return $value;
    }

    public function removableQueryArgs($args) {

        if (!isset($_GET['page']) || $_GET['page'] !== self::MENU_SLUG) {
            return $args;
        }

        return array_merge(
            $args,
            [
                'action',
                'author_list_id',
                'update_message',
                '_wpnonce'
            ]
        );

    }

    /**
     * Enqueue Admin Scripts
     *
     * @return void
     */
    public function enqueueAdminScripts()
    {

        if (!isset($_GET['page']) || $_GET['page'] !== self::MENU_SLUG) {
            return;
        }

        $moduleAssetsUrl = PP_AUTHORS_URL . 'src/modules/author-list/assets';

        wp_enqueue_script(
            'author-list-js',
            $moduleAssetsUrl . '/js/author-list.js',
            [
                'jquery'
            ],
            PP_AUTHORS_VERSION
        );

        $localized_data = [
            'nonce' => wp_create_nonce('author-list-request-nonce'),
            'isAuthorsProActive' => Utils::isAuthorsProActive()
        ];

        wp_localize_script(
            'author-list-js',
            'authorList',
            $localized_data
        );

        wp_enqueue_script(
            'multiple-authors-widget',
            PP_AUTHORS_ASSETS_URL . 'js/multiple-authors-widget.js',
            ['jquery'],
            PP_AUTHORS_VERSION
        );

        wp_enqueue_style(
            'multiple-authors-widget-css',
            PP_AUTHORS_ASSETS_URL . 'css/multiple-authors-widget.css',
            ['wp-edit-blocks'],
            PP_AUTHORS_VERSION,
            'all'
        );

        wp_enqueue_style(
            'author-list-css',
            $moduleAssetsUrl . '/css/author-list.css',
            [],
            PP_AUTHORS_VERSION
        );
    }

    /**
     * Add the admin submenu.
     */
    public function adminSubmenu()
    {

        // Add the submenu to the PublishPress menu.
        $hook = add_submenu_page(
            \MA_Multiple_Authors::MENU_SLUG,
            esc_html__('Author Lists', 'publishpress-authors'),
            esc_html__('Author Lists', 'publishpress-authors'),
            Capability::getManageOptionsCapability(),
            self::MENU_SLUG,
            [$this, 'manageAuthorList'],
            11
        );

        if(!isset($_GET['author_list_edit'])){
            add_action("load-$hook", [$this, 'screenOption']);
        }
        add_action("load-$hook", [$this, 'authorListAction']);
    }

    /**
     * Screen options
     */
    public function screenOption()
    {
        $option = 'per_page';
        $args   = [
            'label'   => esc_html__('Number of items per page', 'publishpress-authors'),
            'default' => 20,
            'option'  => 'author_list_data_per_page'
        ];

        add_screen_option($option, $args);

        $this->author_list_table = new AuthorListTable();
    }

    /**
     * Manage Author List
     */
    public function manageAuthorList()
    {

        if (!empty($_REQUEST['update_message'])) {
            $update_message = $_REQUEST['update_message'];
            switch ($update_message) {
                case 1:
                case 2:
                    $success = true;
                    $message = esc_html__('Settings updated successfully.', 'publishpress-authors');
                break;
                case 3:
                    $success = true;
                    $message = esc_html__('Author List deleted successfully.', 'publishpress-authors');
                break;
                case 4:
                    $success = true;
                    $message = esc_html__('Author List restored from the Trash.', 'publishpress-authors');
                break;
                case 5:
                    $success = true;
                    $message = esc_html__('Author List moved to the Trash.', 'publishpress-authors');
                break;
                default:
            }
            if ($message) {
                 // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo Utils::admin_notices_helper($message, $success);
            }
        }
        if (isset($_GET['author_list_edit'])) {
            $this->edit_author_list();
        } else {
            $this->all_author_list();
        }
    }

    /**
     * Show all author lists
     */
    public function all_author_list() {
        ?>
        <div class="wrap author-list-wrap all">
            <h1 class="wp-heading-inline"><?php esc_html_e('Author Lists', 'publishpress-authors'); ?></h1>

            <a
                href="<?php echo esc_url(admin_url('admin.php?page='. self::MENU_SLUG .'&author_list_edit=0')); ?>"
                class="page-title-action"
            >
                <?php esc_html_e('Add New', 'publishpress-authors'); ?>
            </a>
            <?php
            if (isset($_REQUEST['s']) && $search = sanitize_text_field(wp_unslash($_REQUEST['s']))) {
                /* translators: %s: search keywords */
                printf(' <span class="subtitle">' . esc_html__('Search results for &#8220;%s&#8221;',
                        'publishpress-authors') . '</span>', esc_html($search));
            }
            ?>
            <?php $this->author_list_table->prepare_items(); //the terms table instance ?>
            <hr class="wp-header-end">
            <div id="ajax-response"></div>

            <form class="search-form wp-clearfix" method="get">
                <?php $this->author_list_table->search_box(__('Search Author Lists', 'publishpress-authors'), 'term'); ?>
            </form>
            <div class="clear"></div>
            <div id="col-container" class="wp-clearfix">
                <div class="col-wrap">
                    <form action="<?php echo esc_url(add_query_arg('', '')); ?>" method="post">
                        <?php $this->author_list_table->display(); //Display the table ?>
                    </form>
                </div>
            </div>
        <?php
    }

    /**
     * Author list fields tabs
     */
    public function author_list_fields_tabs() {
        $fields_tabs = [
            'preview' => [
                'label' => __('Preview', 'publishpress-authors'),
                'icon'  => 'dashicons-before dashicons-welcome-view-site'
            ],
            'general' => [
                'label' => __('General', 'publishpress-authors'),
                'icon'  => 'dashicons-before dashicons-admin-tools'
            ],
            'users' => [
                'label' => __('Users', 'publishpress-authors'),
                'icon'  => 'dashicons-before dashicons-admin-users'
            ],
            'options' => [
                'label' => __('Options', 'publishpress-authors'),
                'icon'  => 'dashicons-before dashicons-screenoptions'
            ],
            'search' => [
                'label' => __('Search', 'publishpress-authors'),
                'icon'  => 'dashicons-before dashicons-search'
            ],
        ];

        return $fields_tabs;
    }

    /**
     * Author list fields
     */
    public function author_list_fields() {

        global $_wp_additional_image_sizes;

        $pro_active = Utils::isAuthorsProActive();

        $fields = [];

        $author_fields = [
            '' => esc_html__('Select an option', 'publishpress-authors')
        ];
        foreach (\MA_Author_Custom_Fields::getAuthorCustomFields() as $field_name => $field_options) {
            $author_fields[$field_name] = $field_options['label'];
        }

        // featured image size options
        $sizes = array_reverse(array_merge(
            get_intermediate_image_sizes(),
            array('full')
        ));

        $know_sizes = [
            'full' => esc_html__('The original size of the uploaded image', 'publishpress-authors'),
            'large' => esc_html__('Large-sized image (1024px by 1024px)', 'publishpress-authors'),
            'medium_large' => esc_html__('Medium-large image (768px)', 'publishpress-authors'),
            'medium' => esc_html__('Medium-sized image (300px by 300px)', 'publishpress-authors'),
            'thumbnail' => esc_html__('Small-sized image (150px by 150px)', 'publishpress-authors')
        ];

        $featured_image_options = [];

        foreach ($sizes as $size) {
            if (isset($_wp_additional_image_sizes[$size])) {
                $width = $_wp_additional_image_sizes[$size]['width'];
                $height = $_wp_additional_image_sizes[$size]['height'];
                $featured_image_options[$size] = "$size: {$width}x{$height}";
            } elseif (isset($know_sizes[$size])) {
                $featured_image_options[$size] = $know_sizes[$size];
            } else {
                $featured_image_options[$size] = $size;
            }
        }

        // add general fields
        $fields['title'] = [
            'label'             => esc_html__('Title', 'publishpress-authors'),
            'description'       => '',
            'type'              => 'text',
            'sanitize'          => 'sanitize_text_field',
            'field_visibility'  => [],
            'tab'               => 'general',
            'required'          => true,
        ];
        $fields['layout'] = [
            'label'             => esc_html__('Layout', 'publishpress-authors'),
            'description'       => '',
            'type'              => 'select',
            'options'           => apply_filters('pp_multiple_authors_author_layouts', []),
            'sanitize'          => 'sanitize_text_field',
            'field_visibility'  => [],
            'tab'               => 'general',
            'required'          => true,
        ];
        $fields['layout_columns'] = [
            'label'             => esc_html__('Layout Columns', 'publishpress-authors'),
            'description'       => '',
            'type'              => 'number',
            'min'               => 1,
            'max'               => 9999,
            'sanitize'          => 'sanitize_text_field',
            'field_visibility'  => [],
            'tab'               => 'general',
        ];
        $fields['group_by'] = [
            'label'             => esc_html__('Group By', 'publishpress-authors'),
            'description'       => esc_html__('For authors_index layout, you can group user by profile fields.', 'publishpress-authors'),
            'type'              => 'select',
            'options'           => $author_fields,
            'sanitize'          => 'sanitize_text_field',
            'field_visibility'  => [
                'layout' => ['authors_index']
            ],
            'tab'               => 'general',
        ];
        $fields['featured_image_size'] = [
            'label'             => esc_html__('Featured Image Size', 'publishpress-authors'),
            'description'       => esc_html__('For authors_recent layout, you can select the featured image size..', 'publishpress-authors'),
            'type'              => 'select',
            'options'           => $featured_image_options,
            'sanitize'          => 'sanitize_text_field',
            'field_visibility'  => [
                'layout' => ['authors_recent']
            ],
            'tab'               => 'general',
        ];

        // add users fields
        $fields['author_type'] = [
            'label'             => esc_html__('Show Authors', 'publishpress-authors'),
            'description'       => esc_html__('Select an option to limit the results to selected user roles, author types, specific authors or author Categories.', 'publishpress-authors'),
            'type'              => 'tab',
            'options'           => [
                'roles'         => esc_html__('Roles', 'publishpress-authors'),
                'authors'       => esc_html__('Author Type', 'publishpress-authors'),
                'term_id'       => esc_html__('Authors', 'publishpress-authors'),
                'category_id'   => esc_html__('Author Categories', 'publishpress-authors')
            ],
            'sanitize'          => 'sanitize_text_field',
            'field_visibility'  => [],
            'tab'               => 'users',
        ];
        $fields['author_type_exclude'] = [
            'label'             => esc_html__('Exclude Authors', 'publishpress-authors'),
            'description'       => esc_html__('Select an option to exclude selected user roles, author types, specific authors or author Categories from Author list.', 'publishpress-authors'),
            'type'              => 'tab',
            'options'           => [
                'exclude_roles'         => esc_html__('Roles', 'publishpress-authors'),
                'exclude_authors'       => esc_html__('Author Type', 'publishpress-authors'),
                'exclude_term_id' => esc_html__('Authors', 'publishpress-authors'),
                'exclude_category_id'   => esc_html__('Author Categories', 'publishpress-authors')
            ],
            'sanitize'          => 'sanitize_text_field',
            'field_visibility'  => [],
            'tab'               => 'users',
        ];

        // add options fields
        if (!$pro_active) {
            $fields['limit_per_page_promo'] = [
                'label'             => esc_html__('Authors Per Page', 'publishpress-authors-pro'),
                'description'       => esc_html__('You can set the number of authors to show per page.', 'publishpress-authors-pro'),
                'type'              => 'number',
                'min'               => 1,
                'max'               => 9999,
                'sanitize'          => 'sanitize_text_field',
                'field_visibility'  => [],
                'tab'               => 'options',
                'promo'             => true,
            ];
            $fields['show_empty_promo']   = [
                'label'             => esc_html__('Show Empty', 'publishpress-authors-pro'),
                'description'       => esc_html__('Enable this option to show all authors, including those without any posts. Disable this option to show only authors who are assigned to posts.', 'publishpress-authors-pro'),
                'type'              => 'checkbox',
                'sanitize'          => 'absint',
                'field_visibility'  => [],
                'tab'               => 'options',
                'promo'             => true
            ];
            $fields['options_promo'] = [
                'label'             => esc_html__('Configure Author List Options', 'publishpress-authors'),
                'description'       => esc_html__('Authors Pro allows you to add extra features to the Authors List. These features include pagination, choose the order of authors, and much more.', 'publishpress-authors'),
                'type'              => 'promo',
                'tab'               => 'options',
            ];
            $fields['orderby_promo']   = [
                'label'             => esc_html__('Order By', 'publishpress-authors-pro'),
                'description'       => '',
                'type'              => 'select',
                'options'           => [
                    'name'          => esc_html__('Name', 'publishpress-authors-pro'),
                    'count'         => esc_html__('Post Counts', 'publishpress-authors-pro'),
                    'first_name'    => esc_html__('First Name', 'publishpress-authors-pro'),
                    'last_name'     => esc_html__('Last Name', 'publishpress-authors-pro')
                ],
                'sanitize'          => 'sanitize_text_field',
                'field_visibility'  => [],
                'tab'               => 'options',
                'promo'             => true,
            ];
            $fields['order_promo']   = [
                'label'             => esc_html__('Order', 'publishpress-authors-pro'),
                'description'       => '',
                'type'              => 'select',
                'options'           => [
                    'asc'   => esc_html__('Ascending', 'publishpress-authors-pro'),
                    'desc'  => esc_html__('Descending', 'publishpress-authors-pro')
                ],
                'sanitize'          => 'sanitize_text_field',
                'field_visibility'  => [],
                'tab'               => 'options',
                'promo'             => true,
            ];
            $fields['last_article_date_promo']   = [
                'label'             => esc_html__('Last Article Date', 'publishpress-authors-pro'),
                'description'       => esc_html__('You can limit the author list to users with a published post within a specific time. This option accepts date values such as 1 week ago, 1 month ago, 6 months ago, 1 year ago etc.', 'publishpress-authors-pro'),
                'type'              => 'text',
                'sanitize'          => 'sanitize_text_field',
                'field_visibility'  => [],
                'tab'               => 'options',
                'promo'             => true,
            ];
        }

        // add search fields
        if (!$pro_active) {
            $fields['search_box_promo']   = [
                'label'             => esc_html__('Show Search Box', 'publishpress-authors-pro'),
                'description'       => '',
                'type'              => 'checkbox',
                'sanitize'          => 'absint',
                'field_visibility'  => [],
                'tab'               => 'search',
                'promo'             => true,
            ];
            $fields['search_promo'] = [
                'label'             => esc_html__('Add Search Box to Author Lists', 'publishpress-authors'),
                'description'       => esc_html__('Author Pro allows you to add a search box to the Authors List. You can also show a dropdown menu that allows users to search on specific author fields.', 'publishpress-authors'),
                'type'              => 'promo',
                'tab'               => 'search',
            ];
            $fields['search_field_promo']   = [
                'label'             => esc_html__('Search Field Dropdown', 'publishpress-authors-pro'),
                'description'       => esc_html__('You can also show a dropdown menu that allows users to search on specific author fields.', 'publishpress-authors-pro'),
                'type'              => 'select',
                'multiple'          => true,
                'options'           => [],
                'sanitize'          => 'sanitize_text_field',
                'field_visibility'  => [],
                'tab'               => 'search',
                'promo'             => true,
            ];
        }

        // add preview fields
        $fields['preview']   = [
            'label'             => esc_html__('Preview', 'publishpress-authors'),
            'description'       => '',
            'type'              => 'preview',
            'sanitize'          => 'sanitize_text_field',
            'field_visibility'  => [],
            'tab'               => 'preview',
        ];

        /**
         * Customize author lists fields.
         *
         * @param array $fields Existing fields.
         * @param array $author_fields Author fields options.
         */
        $fields = apply_filters('authors_lists_editor_fields', $fields, $author_fields);

        return $fields;
    }

    public static function createDefaultList() {

        $legacyPlugin       = Factory::getLegacyPlugin();

        $author_list_last_id = $legacyPlugin->modules->author_list->options->author_list_last_id;
        $author_lists        = $legacyPlugin->modules->author_list->options->author_list_data;

        if (!empty($author_lists)) {
            return;
        }

        $pro_active = Utils::isAuthorsProActive();

        // Add author recent list
        $author_list_last_id++;
        $author_recent_list = [
            'ID'                    => $author_list_last_id,
            'title'                 => esc_html__('Author Recent List', 'publishpress-authors'),
            'layout'                => 'authors_recent',
            'layout_columns'        => 2,
            'group_by'              => '',

            'author_type'           => 'roles',
            'authors'               => '',
            'roles'                 => '',
            'term_id'               => '',
            'category_id'           => [],
            'author_type_exclude'   => 'exclude_term_id',
            'exclude_authors'       => '',
            'exclude_roles'         => '',
            'exclude_term_id'       => '',
            'exclude_category_id'   => [],

            'limit_per_page'        => $pro_active ? 20 : '',
            'show_empty'            => $pro_active ? 1 : '',
            'orderby'               => $pro_active ? 'name' : '',
            'order'                 => $pro_active ? 'asc' : '',
            'last_article_date'     => '',
            'search_box'            => $pro_active ? 1 : '',
            'search_field'          => $pro_active ? ['first_name', 'last_name', 'user_email'] : [],
            'dynamic_shortcode'     => '[publishpress_authors_list list_id="'. $author_list_last_id .'"]',
        ];
        if ($pro_active) {
            $author_recent_list['static_shortcode'] = '[publishpress_authors_list layout="authors_recent" authors_recent_col="2" limit_per_page="20" show_empty="1" orderby="name" order="asc" search_box="true" search_field="first_name,last_name,user_email"]';
            $author_recent_list['shortcode_args'] = [
                'layout'                => 'authors_recent',
                'authors_recent_col'    => 2,
                'limit_per_page'        => 20,
                'show_empty'            => 1,
                'orderby'               => 'name',
                'order'                 => 'asc',
                'search_box'            => true,
                'search_field'          => 'first_name,last_name,user_email'
            ];
        } else {
            $author_recent_list['static_shortcode'] = '[publishpress_authors_list layout="authors_recent" authors_recent_col="2"]';
            $author_recent_list['shortcode_args'] = [
                'layout'                => 'authors_recent',
                'authors_recent_col'    => 2,
            ];
        }
        $author_lists[$author_list_last_id] = $author_recent_list;

        // add author index list
        $author_list_last_id++;
        $author_index_list = [
            'ID'                    => $author_list_last_id,
            'title'                 => esc_html__('Author Index List', 'publishpress-authors'),
            'layout'                => 'authors_index',
            'layout_columns'        => 1,
            'group_by'              => '',

            'author_type'           => 'roles',
            'authors'               => '',
            'roles'                 => '',
            'term_id'               => '',
            'category_id'           => [],
            'author_type_exclude'   => 'exclude_term_id',
            'exclude_authors'       => '',
            'exclude_roles'         => '',
            'exclude_term_id'       => '',
            'exclude_category_id'   => [],

            'limit_per_page'        => $pro_active ? 20 : '',
            'show_empty'            => $pro_active ? 1 : '',
            'orderby'               => $pro_active ? 'name' : '',
            'order'                 => $pro_active ? 'asc' : '',
            'last_article_date'     => '',
            'search_box'            => $pro_active ? 1 : '',
            'search_field'          => $pro_active ? ['first_name', 'last_name', 'user_email'] : [],
            'dynamic_shortcode'     => '[publishpress_authors_list list_id="'. $author_list_last_id .'"]',
        ];
        if ($pro_active) {
            $author_index_list['static_shortcode'] = '[publishpress_authors_list layout="authors_index" limit_per_page="20" show_empty="1" orderby="name" order="asc" search_box="true" search_field="first_name,last_name,user_email"]';
            $author_index_list['shortcode_args'] = [
                'layout'                => 'authors_index',
                'limit_per_page'        => 20,
                'show_empty'            => 1,
                'orderby'               => 'name',
                'order'                 => 'asc',
                'search_box'            => true,
                'search_field'          => 'first_name,last_name,user_email'
            ];
        } else {
            $author_index_list['static_shortcode'] = '[publishpress_authors_list layout="authors_index"]';
            $author_index_list['shortcode_args'] = [
                'layout'                => 'authors_index',
            ];
        }

        $author_lists[$author_list_last_id] = $author_index_list;

        $legacyPlugin->update_module_option('author_list', 'author_list_last_id', $author_list_last_id);
        $legacyPlugin->update_module_option('author_list', 'author_list_data', $author_lists);
    }

    /**
     * Extract shortcode parameters into array
     */
    public function extract_shortcode_params($shortcode) {
        // Use regular expression to extract the attributes part of the shortcode
        preg_match('/\[(\w+)([^\]]*)\]/', $shortcode, $matches);

        // Check if we have matches and the second element (attributes part) exists
        if (isset($matches[2])) {
            // Parse the attributes string into an associative array
            $attributes = shortcode_parse_atts($matches[2]);
            return $attributes;
        }

        return [];
    }

    /**
     * Update author list
     */
    public function authorListAction() {

        if (!empty($_POST['form_type'])
            && !empty($_POST['nonce'])
            && wp_verify_nonce(sanitize_key($_POST['nonce']), 'author-list-request-nonce')
            && current_user_can(Capability::getManageOptionsCapability())
        ) {
            $legacyPlugin       = Factory::getLegacyPlugin();

            $author_list_id = absint($_POST['author_list_id']);
            $form_type      = sanitize_text_field($_POST['form_type']);
            $author_list    = stripslashes_deep(map_deep($_POST['author_list'], 'sanitize_text_field'));
            $author_list_last_id = $legacyPlugin->modules->author_list->options->author_list_last_id;
            $author_lists   = $legacyPlugin->modules->author_list->options->author_list_data;
            if ($form_type == 'new' || empty($author_list_id)) {
                $author_list_id = (int) $author_list_last_id + 1;
                $author_list_last_id = $author_list_id;
                $update_message = 1;
            } else {
                $update_message = 1;
            }
            // add ID
            $author_list['ID'] = $author_list_id;
            // add shortcode parameters
            $author_list['shortcode_args'] = $this->extract_shortcode_params($author_list['static_shortcode']);
            // update status as active: TODO: Should this come from the from?
            $author_list['status'] = 'active';

            $author_lists[$author_list_id] = $author_list;

            $legacyPlugin->update_module_option($this->module_name, 'author_list_last_id', $author_list_last_id);
            $legacyPlugin->update_module_option($this->module_name, 'author_list_data', $author_lists);

            wp_safe_redirect(admin_url('admin.php?page='. self::MENU_SLUG .'&author_list_edit='. $author_list_id .'&update_message='. $update_message .''));
            exit();
        } else if (!empty($_REQUEST['action'])
        && !empty($_REQUEST['_wpnonce'])
        && wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'author-list-request-nonce')
        && in_array($_REQUEST['action'], ['ppma-trash-author-list', 'ppma-restore-author-list', 'ppma-delete-author-list'])
        && current_user_can(Capability::getManageOptionsCapability())
    ) {
        $legacyPlugin       = Factory::getLegacyPlugin();
        $request_action = sanitize_key($_REQUEST['action']);
        $author_list_id = absint($_REQUEST['author_list_id']);
        $author_lists   = $legacyPlugin->modules->author_list->options->author_list_data;
        if (array_key_exists($author_list_id, $author_lists)) {
            if ($request_action == 'ppma-trash-author-list') {
                $update_message = 5;
                $author_lists[$author_list_id]['status'] = 'trash';
            } elseif ($request_action == 'ppma-restore-author-list') {
                $update_message = 4;
                $author_lists[$author_list_id]['status'] = 'active';
            } else {
                $update_message = 3;
                unset($author_lists[$author_list_id]);
            }

            $legacyPlugin->update_module_option($this->module_name, 'author_list_data', $author_lists);
            wp_safe_redirect(admin_url('admin.php?page='. self::MENU_SLUG .'&update_message=' . $update_message));
            exit();
        }
    }

    }

    /**
     * Edit author list
     */
    public function edit_author_list() {
        $legacyPlugin       = Factory::getLegacyPlugin();
        $pro_active         = Utils::isAuthorsProActive();
        $author_list_id     = !empty($_GET['author_list_edit']) ? absint($_GET['author_list_edit']) : 0;
        $author_list_data   = false;

        if (!empty($author_list_id)) {
            $author_lists       = $legacyPlugin->modules->author_list->options->author_list_data;
            $author_list_data   = isset($author_lists[$author_list_id]) ? $author_lists[$author_list_id] : false;
        }
        $form_type  = $author_list_data ? 'edit' : 'new';

        if ($form_type == 'new') {
            //show default options
            $author_list_data = [
                'title'                 => esc_html__('Author List', 'publishpress-authors'),
                'layout'                => 'authors_index',
                'layout_columns'        => 1,
                'group_by'              => '',

                'author_type'           => 'roles',
                'authors'               => '',
                'roles'                 => '',
                'term_id'               => '',
                'category_id'           => [],
                'author_type_exclude'   => 'exclude_term_id',
                'exclude_authors'       => '',
                'exclude_roles'         => '',
                'exclude_term_id'       => '',
                'exclude_category_id'   => [],

                'limit_per_page'        => $pro_active ? 20 : '',
                'show_empty'            => $pro_active ? 1 : '',
                'orderby'               => $pro_active ? 'name' : '',
                'order'                 => $pro_active ? 'asc' : '',
                'last_article_date'     => '',
                'search_box'            => $pro_active ? 1 : '',
                'search_field'          => '',
            ];

            if ($pro_active) {
                $author_list_data['static_shortcode'] = '[publishpress_authors_list layout="authors_index" limit_per_page="20" show_empty="1" orderby="name" order="asc" search_box="true"]';
            } else {
                $author_list_data['static_shortcode'] = '[publishpress_authors_list layout="authors_index"]';
            }
        }


        $shortcode_id       = $form_type == 'edit' ? $author_list_id : (int) $legacyPlugin->modules->author_list->options->author_list_last_id + 1;
        $static_shortcode   = isset($author_list_data['static_shortcode']) ? $author_list_data['static_shortcode'] : '';

        $form_title = $form_type == 'edit' ? esc_html__('Edit Author List', 'publishpress-authors') : esc_html__('Add Author List', 'publishpress-authors');
        $active_tab = !empty($_REQUEST['active_tab']) ? sanitize_text_field($_REQUEST['active_tab']) : 'preview';

        $fields_tabs = $this->author_list_fields_tabs();
        $list_fields = $this->author_list_fields();
        $grouped_fields = array_reduce(array_keys($list_fields), function($carry, $key) use ($list_fields) {
            $tab = $list_fields[$key]['tab'];
            if (!isset($carry[$tab])) {
                $carry[$tab] = array();
            }
            $carry[$tab][$key] = $list_fields[$key];
            return $carry;
        }, array());
        ?>
        <div class="wrap author-list-wrap form <?php echo esc_attr($form_type); ?>">
            <h1 class="wp-heading-inline"><?php esc_html_e('Author Lists', 'publishpress-authors'); ?></h1>

            <form method="post" action="">
                <input type="hidden" name="form_type" value="<?php echo esc_attr($form_type); ?>">
                <input type="hidden" name="author_list_id" value="<?php echo esc_attr($author_list_id); ?>">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('author-list-request-nonce')); ?>">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content" style="position: relative;">
                            <div class="author-list-section postbox">
                                <div class="postbox-header">
                                        <h2 class="hndle ui-sortable-handle"><?php echo esc_html($form_title); ?></h2>
                                </div>
                                <div class="inside">
                                    <div class="main">
                                        <ul class="author-list-tab">
                                            <?php foreach ($fields_tabs as $tab_name => $tab_options) :
                                                $active_class = $tab_name == $active_tab ? 'active' : ''; ?>
                                                <li
                                                    class="<?php echo esc_attr($tab_name); ?>_tab <?php echo esc_attr($active_class); ?>"
                                                    data-tab="<?php echo esc_attr($tab_name); ?>">
                                                    <a href="#<?php echo esc_attr($tab_name); ?>"
                                                        class="<?php echo esc_html($tab_options['icon']); ?>">
                                                        <span><?php echo esc_html($tab_options['label']); ?></span>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <div class="author-list-tab-content">
                                            <?php foreach ($fields_tabs as $tab_name => $tab_options) :
                                                $none_active_style = $tab_name == $active_tab ? '' : 'display:none;'; ?>
                                                <table class="form-table author-list-table <?php echo esc_attr($tab_name); ?> fixed" style="<?php echo esc_attr($none_active_style); ?>" role="presentation">
                                                    <tbody>
                                                        <?php
                                                        $tab_options = isset($grouped_fields[$tab_name]) ? $grouped_fields[$tab_name] : [];
                                                        foreach ($tab_options as $option_name => $option_options) :
                                                            $option_args          = $option_options;
                                                            $option_args['key']   = $option_name;
                                                            $option_args['value'] = isset($author_list_data[$option_name]) ? $author_list_data[$option_name] : '';
                                                            echo self::get_rendered_author_list_editor_partial($option_args, $author_list_data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        endforeach;
                                                        ?>
                                                    </tbody>
                                                </table>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div id="postbox-container-1" class="postbox-container">
                            <div id="side-sortables" class="meta-box-sortables ui-sortable" style="">
                                <div id="submitdiv" class="postbox">
                                    <div class="postbox-header">
                                        <h2 class="hndle ui-sortable-handle"><?php esc_html_e('Save Changes', 'publishpress-authors'); ?></h2>
                                    </div>
                                    <div class="inside">
                                        <div id="minor-publishing"></div>
                                        <div id="major-publishing-actions">
                                            <div id="publishing-action">
                                                <input type="submit"
                                                    value="<?php esc_attr_e('Save Changes', 'publishpress-authors'); ?>"
                                                    class="button-primary" id="" name="publish"
                                                >
                                            </div>
                                            <div class="clear"></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="submitdiv" class="postbox">
                                    <div class="postbox-header">
                                        <h2 class="hndle ui-sortable-handle"><?php esc_html_e('Shortcode', 'publishpress-authors'); ?></h2>
                                    </div>
                                    <div class="inside">
                                        <div id="minor-publishing"></div>
                                        <div id="major-publishing-actions">
                                            <div>
                                                <label style="display: none;"><strong><?php esc_html_e('Dynamic Shortcode', 'publishpress-authors'); ?>:</strong></label>
                                                <textarea name="author_list[dynamic_shortcode]" class="shortcode-textarea dynamic" readonly="">[publishpress_authors_list list_id="<?php echo esc_attr($shortcode_id); ?>"]</textarea>
                                                <label style="display: none;"><strong><?php esc_html_e('Static Shortcode', 'publishpress-authors'); ?>:</strong></label>
                                                <textarea style="display: none;" name="author_list[static_shortcode]" class="shortcode-textarea static" readonly=""><?php echo esc_html($static_shortcode) ?></textarea>
                                            </div>
                                            <div class="clear"></div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!Utils::isAuthorsProActive()) : ?>
                                    <?php Utils::ppma_pro_sidebar(); ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <br class="clear">
                </div>
            </form>
        </div>

        <?php
    }

    /**
     * Get a rendered field partial
     *
     * @param array $args Arguments to render in the partial.
     * @param array $option_values Current value for the options.
     */
    private static function get_rendered_author_list_editor_partial($args, $option_values)
    {
        $defaults = [
            'type'        => 'text',
            'tab'         => 'preview',
            'options'     => [],
            'value'       => '',
            'label'       => '',
            'description' => '',
            'min'         => '',
            'max'         => '',
            'placeholder' => '',
            'rows'        => '20',
            'readonly'    => false,
            'multiple'    => false,
            'required'    => false,
            'field_visibility'  => [],
            'promo'             => false,
        ];

        $name_group = 'author_list';
        $args      = array_merge($defaults, $args);
        $key       = $args['key'];
        $promo     = $args['promo'];
        $tab_class = 'ppma-author-list-editor-tab-content ppma-' . $args['tab'] . '-tab ' . $args['type'] . ' ppma-editor-'.$key;
        if ('range' === $args['type'] && $args['show_input']) {
            $tab_class .= ' double-input';
        }


        $pro_active = Utils::isAuthorsProActive();

        if ($promo) {
            $tab_class .= ' ppma-blur';
            $name_group = 'promo';
        }

        if (in_array($args['type'], ['promo'])) {
            $tab_class .= ' ppma-promo-overlay-row';
        }

        ob_start();
        $generate_tab_title = false;
        if (in_array($args['type'], ['textarea', 'preview', 'tab', 'promo', 'multiple_authors'])) {
            $th_style = 'display: none;';
            $colspan  = 2;
        } else {
            $th_style = '';
            $colspan  = '';
        }

        $tr_style = '';
        if ($key === 'group_by' && $option_values['layout'] !== 'authors_index') {
            $tr_style = 'display: none;';
        }
        if ($key === 'featured_image_size' && $option_values['layout'] !== 'authors_recent') {
            $tr_style = 'display: none;';
        }
        ?>
        <tr
            class="<?php echo esc_attr($tab_class); ?>"
            data-tab="<?php echo esc_attr($args['tab']); ?>"
            style="<?php echo esc_attr($tr_style); ?>"
            >
            <?php if (!empty($args['label'])) : ?>
                <th scope="row" style="<?php echo esc_attr($th_style); ?>">
                    <label for="<?php echo esc_attr($key); ?>">
                        <?php echo esc_html($args['label']); ?>
                        <?php if (isset($args['required']) && $args['required'] === true) : ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                    </label>
                </th>
            <?php endif; ?>
            <td class="input" colspan="<?php echo esc_attr($colspan); ?>">
                <?php
                if ('number' === $args['type']) :
                    ?>
                    <input name="<?php echo esc_attr($name_group); ?>[<?php echo esc_attr($key); ?>]"
                        id="<?php echo esc_attr($key); ?>"
                        type="<?php echo esc_attr($args['type']); ?>"
                        value="<?php echo esc_attr($args['value']); ?>"
                        min="<?php echo esc_attr($args['min']); ?>"
                        max="<?php echo esc_attr($args['max']); ?>"
                        placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                        <?php echo (isset($args['readonly']) && $args['readonly'] === true) ? 'readonly' : ''; ?>
                         />
                        <?php
                elseif ('checkbox' === $args['type']) :
                    ?>
                    <input name="<?php echo esc_attr($name_group); ?>[<?php echo esc_attr($key); ?>]"
                        id="<?php echo esc_attr($key); ?>"
                        type="<?php echo esc_attr($args['type']); ?>"
                        value="1"
                        <?php echo (isset($args['readonly']) && $args['readonly'] === true) ? 'readonly' : ''; ?>
                        <?php checked($args['value'], 1); ?> />
                <?php
                elseif ('select' === $args['type']) :
                    ?>
                    <select name="<?php echo esc_attr($name_group); ?>[<?php echo esc_attr($key); ?>]<?php echo ($args['multiple'] === true) ? '[]' : ''; ?>"
                        class="chosen-select"
                        id="<?php echo esc_attr($key); ?>"
                        data-placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                        <?php echo (isset($args['readonly']) && $args['readonly'] === true) ? 'readonly' : ''; ?>
                        <?php echo ($args['multiple'] === true) ? 'multiple' : ''; ?>
                        />
                        <?php foreach ($args['options'] as $key => $label) :
                            if ($key == '' && $args['multiple'] === true) {
                                continue;
                            }
                            ?>
                            <option value="<?php echo esc_attr($key); ?>"
                                <?php $args['multiple'] === true && $args['value'] !== '' ? selected(true, in_array($key, (array)$args['value'])) : selected($key, $args['value']); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php
                elseif ('optgroup_select' === $args['type']) :
                    ?>
                    <select name="<?php echo esc_attr($name_group); ?>[<?php echo esc_attr($key); ?>]"
                        id="<?php echo esc_attr($key); ?>"
                        placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                        <?php echo (isset($args['readonly']) && $args['readonly'] === true) ? 'readonly' : ''; ?>
                        />
                        <?php foreach ($args['options'] as $group_key => $group_option) : ?>
                            <optgroup label="<?php echo esc_attr($group_option['title']); ?>">
                                <?php foreach ($group_option['options'] as $key => $label) : ?>
                                    <option value="<?php echo esc_attr($key); ?>"
                                        <?php selected($key, $args['value']); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                <?php
                elseif ('tab' === $args['type']) :
                    $restrict_pro = false;
                    if ($key === 'author_type_exclude') {
                        $restrict_pro = true;
                        if (! $pro_active || empty($args['value'])) {
                            $args['value'] = 'exclude_term_id';
                        }
                    }
                    ?>
                    <h3 style="margin-top: 0;">
                        <label for="<?php echo esc_attr($key); ?>">
                            <strong>
                                <?php echo esc_html($args['label']); ?>
                                <?php if (isset($args['required']) && $args['required'] === true) : ?>
                                    <span class="required">*</span>
                                <?php endif; ?>
                            </strong>
                        </label>
                    </h3>
                    <div class="ppma-group-wrap">
                        <div class="ppma-button-group">
                            <?php foreach ($args['options'] as $option_key => $options_label) :
                                    $selected_button = $option_key == $args['value'] ? 'selected' : ''; ?>
                                    <label class="<?php echo esc_attr($selected_button); ?>">
                                        <input type="radio"
                                            name="<?php echo esc_attr($name_group); ?>[<?php echo esc_attr($key); ?>]"
                                            value="<?php echo esc_attr($option_key); ?>"
                                            <?php checked($option_key, $args['value']); ?>
                                        >
                                            <?php echo esc_html($options_label); ?>
                                        </label>
                            <?php endforeach; ?>
                        </div>
                        <?php
                            foreach ($args['options'] as $option_key => $options_label) :
                                $show_promo = false;
                                if ($restrict_pro && ! $pro_active && in_array($option_key, ['exclude_roles', 'exclude_authors', 'exclude_category_id'])) {
                                    $show_promo = true;
                                    $name_group = 'promo';
                                } else {
                                    $name_group = 'author_list';
                                }
                                $non_selected_style = $option_key == $args['value'] ? '' : 'display: none;'; ?>
                                <p class="ppma-button-description description <?php echo esc_attr($option_key); ?>" style="<?php echo esc_attr($non_selected_style); ?>">
                                    <?php if ($show_promo) : ?>
                                        <span class="ppma-promo-overlay-row">
                                        <span class="ppma-blur">
                                    <?php endif;?>
                                    <?php
                                    $option_value = isset($option_values[$option_key]) ? (array) $option_values[$option_key] : [];
                                    $option_value = array_filter($option_value);
                                    switch ($option_key) {
                                        case 'roles':
                                        case 'exclude_roles':
                                            ?>
                                            <select name="<?php echo esc_attr($name_group); ?>[<?php echo esc_attr($option_key); ?>][]"
                                                class="chosen-select"
                                                id="<?php echo esc_attr($key); ?>-<?php echo esc_attr($option_key); ?>"
                                                data-placeholder="<?php echo esc_attr__('Select some options', 'publishpress-authors'); ?>"
                                                multiple
                                                />
                                                <?php
                                                if (!$show_promo) :
                                                    foreach (get_ppma_get_all_user_roles() as $role => $data) :
                                                    ?>
                                                        <option value="<?php echo esc_attr($role); ?>"
                                                            <?php selected(true, in_array($role, $option_value)); ?>>
                                                            <?php echo esc_html($data['name']); ?>
                                                        </option>
                                                    <?php endforeach;
                                                endif; ?>
                                            </select>
                                            <?php
                                        break;
                                        case 'authors':
                                        case 'exclude_authors':
                                            $authors_options = [
                                                'users' => esc_html__('Registered Author With User Account', 'publishpress-authors'),
                                                'guests_users' => esc_html__('Guest Author With User Account', 'publishpress-authors'),
                                                'guests' => esc_html__('Guest Author With No User Account', 'publishpress-authors'),
                                            ];
                                            ?>
                                            <select name="<?php echo esc_attr($name_group); ?>[<?php echo esc_attr($option_key); ?>][]"
                                                class="chosen-select"
                                                id="<?php echo esc_attr($key); ?>-<?php echo esc_attr($option_key); ?>"
                                                data-placeholder="<?php echo esc_attr__('Select some options', 'publishpress-authors'); ?>"
                                                multiple
                                                />
                                                <?php
                                                if (!$show_promo) :
                                                    foreach ($authors_options as $sub_key => $sub_label) :
                                                        ?>
                                                        <option value="<?php echo esc_attr($sub_key); ?>"
                                                            <?php selected(true, in_array($sub_key, $option_value)); ?>>
                                                            <?php echo esc_html($sub_label); ?>
                                                        </option>
                                                    <?php endforeach;
                                                endif; ?>
                                            </select>
                                            <?php
                                        break;
                                        case 'term_id':
                                        case 'exclude_term_id':
                                            ?>
                                            <select name="<?php echo esc_attr($name_group); ?>[<?php echo esc_attr($option_key); ?>][]"
                                                data-nonce="<?php echo esc_attr(wp_create_nonce('authors-user-search')); ?>"
                                                data-placeholder="<?php esc_html_e('Select Users', 'publishpress-authors'); ?>"
                                                class="authors-user-term-id-search"
                                                id="<?php echo esc_attr($key); ?>-<?php echo esc_attr($option_key); ?>"
                                                multiple
                                                style="width: 99%;"
                                                />
                                                <?php
                                                if (!$show_promo && !empty($option_value)) {
                                                    foreach ($option_value as $term_id) :
                                                        $author = Author::get_by_term_id((int)$term_id);
                                                        if (is_object($author) && isset($author->display_name)) {
                                                    ?>
                                                        <option value="<?php echo esc_attr($term_id); ?>" selected>
                                                            <?php echo esc_html($author->display_name); ?>
                                                        </option>
                                                <?php }
                                                    endforeach;
                                                } ?>
                                            </select>
                                            <?php
                                        break;
                                        case 'category_id':
                                        case 'exclude_category_id':
                                            $option_value = (array) $option_value;
                                            $author_categories = get_ppma_author_categories(['category_status' => 1]);
                                            ?>
                                            <select name="<?php echo esc_attr($name_group); ?>[<?php echo esc_attr($option_key); ?>][]"
                                                data-placeholder="<?php esc_html_e('Select Author Category', 'publishpress-authors'); ?>"
                                                class="chosen-select"
                                                id="<?php echo esc_attr($key); ?>-<?php echo esc_attr($option_key); ?>"
                                                multiple
                                                style="width: 99%;"
                                                />
                                                <?php
                                                if (!$show_promo && !empty($author_categories)) {
                                                    foreach ($author_categories as $author_category) :
                                                    ?>
                                                        <option value="<?php echo esc_attr($author_category['id']); ?>" <?php selected(in_array($author_category['id'], $option_value), true); ?>>
                                                            <?php echo esc_html($author_category['category_name']); ?>
                                                        </option>
                                                <?php endforeach;
                                                } ?>
                                            </select>
                                            <?php
                                        break;
                                        default:
                                    }
                                    ?>
                                    <?php if ($show_promo) : ?> </span> <?php endif;?>
                                    <?php if ($show_promo) : ?>
                                        <span class="ppma-promo-upgrade-notice no-bg" style="margin-top: -10px;">
                                            <a class="upgrade-link" href="https://publishpress.com/links/authors-menu" target="__blank">
                                                <span class="dashicons dashicons-lock"></span>
                                                <?php echo esc_html__('Upgrade to Pro', 'publishpress-authors'); ?>
                                            </a>
                                        </span>
                                    <?php endif;?>
                                    <?php if ($show_promo) : ?> </span> <?php endif;?>
                                </p>
                        <?php endforeach; ?>
                    </div>
                <?php
                elseif ('textarea' === $args['type']) :
                    ?>
                    <textarea name="<?php echo esc_attr($name_group); ?>[<?php echo esc_attr($key); ?>]"
                        id="<?php echo esc_attr($key); ?>"
                        type="<?php echo esc_attr($args['type']); ?>"
                        rows="<?php echo esc_attr($args['rows']); ?>"
                        placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                        <?php echo (isset($args['readonly']) && $args['readonly'] === true) ? 'readonly' : ''; ?>
                        ><?php echo esc_html($args['value']); ?></textarea>
                <?php
                elseif ('preview' === $args['type']) :
                    $shortcode_content = !empty($option_values['static_shortcode']) ? do_shortcode($option_values['static_shortcode']) : '';
                    ?>
                    <p class="description" style="margin-bottom: 20px;"><?php esc_html_e('This is a quick preview of this Author List. Test on frontend pages to see exactly how it looks with your theme.', 'publishpress-authors'); ?></p>
                    <div class="preview-shortcode-wrap"><?php echo $shortcode_content; ?></div>
                    <div class="preview-skeleton" style="display: none;">
                        <div class="skeleton skeleton-header"></div>
                        <div class="skeleton skeleton-sub-header"></div>
                        <div class="skeleton skeleton-content"></div>
                        <div class="skeleton skeleton-content"></div>
                        <div class="skeleton skeleton-content"></div>
                    </div>
                <?php
                elseif ('promo' === $args['type']) :
                    ?>
                    <div class="ppma-promo-upgrade-notice no-bg" style="margin-top: -10px;">
                        <p>
                            <a class="upgrade-link" href="https://publishpress.com/links/authors-menu" target="__blank">
                                <span class="dashicons dashicons-lock"></span>
                                <?php echo esc_html__('Upgrade to Pro', 'publishpress-authors'); ?>
                            </a>
                        </p>
                    </div>
                <?php
                elseif ('multiple_authors' === $args['type']) :
                    ?>
                    <h3 style="margin-top: 0;">
                        <label for="<?php echo esc_attr($key); ?>">
                            <strong>
                                <?php echo esc_html($args['label']); ?>
                                <?php if (isset($args['required']) && $args['required'] === true) : ?>
                                    <span class="required">*</span>
                                <?php endif; ?>
                            </strong>
                        </label>
                    </h3>
                    <select name="<?php echo esc_attr($name_group); ?>[<?php echo esc_attr($key); ?>][]"
                        data-nonce="<?php echo esc_attr(wp_create_nonce('authors-user-search')); ?>"
                        data-placeholder="<?php esc_html_e('Select Users', 'publishpress-authors'); ?>"
                        class="authors-user-term-id-search"
                        id="<?php echo esc_attr($key); ?>-<?php echo esc_attr($key); ?>"
                        multiple
                        style="width: 99%;"
                        />
                        <?php
                        if (!empty($args['value']) && is_array($args['value'])) {
                            foreach ($args['value'] as $term_id) :
                                $author = Author::get_by_term_id((int)$term_id);
                                if (is_object($author) && isset($author->display_name)) {
                            ?>
                                <option value="<?php echo esc_attr($term_id); ?>" selected>
                                    <?php echo esc_html($author->display_name); ?>
                                </option>
                        <?php }
                            endforeach;
                        } ?>
                    </select>
                <?php else : ?>
                    <input name="<?php echo esc_attr($name_group); ?>[<?php echo esc_attr($key); ?>]"
                        id="<?php echo esc_attr($key); ?>"
                        type="<?php echo esc_attr($args['type']); ?>"
                        value="<?php echo esc_attr($args['value']); ?>"
                        placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                        <?php echo (isset($args['readonly']) && $args['readonly'] === true) ? 'readonly' : ''; ?>
                        <?php echo (isset($args['required']) && $args['required'] === true) ? 'required' : ''; ?>
                         />
                <?php endif; ?>
                <?php if (!in_array($args['type'], ['promo']) && isset($args['description']) && !empty($args['description'])) : ?>
                        <?php if($args['type'] !== 'checkbox') : ?>
                            <br />
                        <?php endif; ?>
                        <span class="field-description description">
                            <?php echo $args['description']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        </span>
                <?php endif; ?>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle a request to do author list shortcode.
     */
    public static function handle_author_list_do_shortcode()
    {

        $response['status']  = 'success';
        $response['content'] = esc_html__('An error occured.', 'publishpress-authors');

        //do not process request if nonce validation failed
        if (empty($_POST['nonce'])
            || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'author-list-request-nonce')
        ) {
            $response['status']  = 'error';
            $response['content'] = esc_html__(
                'Security error. Kindly reload this page and try again',
                'publishpress-authors'
            );
        } elseif (empty($_POST['shortcode'])) {
            $response['status']  = 'error';
            $response['content'] = esc_html__(
                'Invalid form',
                'publishpress-authors'
            );
        } else {
            $shortcode = stripslashes_deep(sanitize_text_field($_POST['shortcode']));

            $response['content'] = do_shortcode($shortcode);
        }

        wp_send_json($response);
        exit;
    }
}
