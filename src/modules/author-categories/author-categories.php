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

use MultipleAuthors\Classes\Legacy\Module;
use MultipleAuthorCategories\AuthorCategoriesSchema;
use MultipleAuthorCategories\AuthorCategoriesTable;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;

/**
 * class MA_Author_Categories
 */
class MA_Author_Categories extends Module
{

    /**
     * Instance of the module
     *
     * @var stdClass
     */
    public $module;
    public $module_url;
    public $author_categories_table;

    public $module_name = 'author_categories';

    const MENU_SLUG = 'ppma-author-categories';

    /**
     * Construct the MA_Multiple_Authors class
     */
    public function __construct()
    {

        global $hook_suffix;

        if (!isset($hook_suffix)) {
            $hook_suffix = '';
        }

        $this->module_url = $this->get_module_url(__FILE__);

        // Register the module with PublishPress
        $args = [
            'title' => __('Author Categories', 'publishpress-authors'),
            'short_description' => __(
                'Add support for author categories.',
                'publishpress-authors'
            ),
            'extended_description' => __(
                'Add support for author categories.',
                'publishpress-authors'
            ),
            'module_url' => $this->module_url,
            'icon_class' => 'dashicons dashicons-edit',
            'slug' => 'author-categories',
            'default_options' => [
                'enabled' => 'on',
            ],
            'options_page' => false,
            'autoload' => true,
        ];

        // Apply a filter to the default options
        $args['default_options'] = apply_filters('MA_Author_Categories_default_options', $args['default_options']);

        $legacyPlugin = Factory::getLegacyPlugin();

        $this->module = $legacyPlugin->register_module($this->module_name, $args);

        parent::__construct();
    }

    /**
     * Initialize the module. Conditionally loads if the module is enabled
     */
    public function init()
    {
        add_action('pp_authors_install', [$this, 'runInstallTasks']);
        add_action('pp_authors_upgrade', [$this, 'runUpgradeTasks']);
        add_action('multiple_authors_admin_submenu', [$this, 'adminSubmenu'], 50);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_action('wp_ajax_save_ppma_author_category', [$this, 'handleNewCategory']);
        add_action('wp_ajax_edit_ppma_author_category', [$this, 'handleEditCategory']);
        add_action('wp_ajax_reorder_ppma_author_category', [$this, 'handleReOrderCategory']);
        add_filter('removable_query_args', [$this, 'removableQueryArgs']);
        add_action('delete_post', [$this, 'deleteAuthorCategoryRelation']);
        add_action('publishpress_author_categories_flush_cache', [$this, 'flush_cache'], 15);
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

        $moduleAssetsUrl = PP_AUTHORS_URL . 'src/modules/author-categories/assets';
        $proActive = Utils::isAuthorsProActive();
        // Load jquery and jquery ui for sortable
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-sortable');

        // Inline edit script
        wp_enqueue_script('author-categories-inline-edit', $moduleAssetsUrl . '/js/inline-edit.js', ['jquery'], PP_AUTHORS_VERSION);
        wp_localize_script(
            'author-categories-inline-edit',
            'authorCategoriesInlineEdit',
            [
                'proActive' => $proActive
            ]
        );

        // Drag and drop re-order script
        wp_enqueue_script('author-category-reorder', $moduleAssetsUrl . '/js/category-reorder.js', ['jquery', 'jquery-ui-sortable'], PP_AUTHORS_VERSION);
        wp_localize_script(
            'author-category-reorder',
            'authorCategoriesReorder',
            [
                'proActive' => $proActive,
                'nonce'     => wp_create_nonce('author-categories-reorder-nonce')
            ]
        );

        // Author category general script
        wp_enqueue_script('author-categories-js', $moduleAssetsUrl . '/js/author-categories.js', ['jquery'], PP_AUTHORS_VERSION);
        wp_localize_script(
            'author-categories-js',
            'authorCategories',
            [
                'proActive' => $proActive,
                'nonce'     => wp_create_nonce('author-categories-save-nonce')
            ]
        );

        // Author category styles
        wp_enqueue_style(
            'author-categories-css',
            $moduleAssetsUrl . '/css/author-categories.css',
            [],
            PP_AUTHORS_VERSION
        );
    }

    public function removableQueryArgs($args) {

        if (!isset($_GET['page']) || $_GET['page'] !== self::MENU_SLUG) {
            return $args;
        }

        return array_merge(
            $args,
            [
                'action',
                'author_categories',
                '_wpnonce'
            ]
        );

    }

    /**
     * Flush author category cache
     */
    public function flush_cache($category_id) {
        wp_cache_flush_group('author_categories_results_cache');
    }

    /**
     * Add the admin submenu.
     */
    public function adminSubmenu()
    {

        // Add the submenu to the PublishPress menu.
        $hook = add_submenu_page(
            \MA_Multiple_Authors::MENU_SLUG,
            esc_html__('Author Categories', 'publishpress-authors'),
            esc_html__('Author Categories', 'publishpress-authors'),
            apply_filters('pp_multiple_authors_manage_categories_cap', 'ppma_manage_author_categories'),
            self::MENU_SLUG,
            [$this, 'manageAuthorCategories'],
            11
        );

        add_action("load-$hook", [$this, 'screenOption']);
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
            'option'  => 'author_categories_per_page'
        ];

        add_screen_option($option, $args);

        $this->author_categories_table = new AuthorCategoriesTable();
    }

    /**
     * Handle a request to save author category
     *
     * @return mixed
     */
    public function handleNewCategory() {

        $response['status']     = 'error';
        $response['content']    = '';
        $response_message       = esc_html__('An error occured.', 'publishpress-authors');

        if (empty($_POST['nonce'])
            || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'author-categories-save-nonce')
        ) {
            $response_message = esc_html__(
                'Security error. Kindly reload this page and try again',
                'publishpress-authors'
            );
        } elseif (!current_user_can(apply_filters('pp_multiple_authors_manage_categories_cap', 'ppma_manage_author_categories'))) {
            $response_message = esc_html__(
                'You do not have permission to perform this action',
                'publishpress-authors'
            );
        } else {

            $category_name = isset($_POST['category_name']) ? sanitize_text_field($_POST['category_name']) : '';
            $plural_name = isset($_POST['plural_name']) ? sanitize_text_field($_POST['plural_name']) : '';
            $schema_property = isset($_POST['schema_property']) ? sanitize_text_field($_POST['schema_property']) : '';
            $post_types = isset($_POST['post_types']) && is_array($_POST['post_types']) ? array_map('sanitize_text_field', $_POST['post_types']) : [];
            $enabled_category = isset($_POST['enabled_category']) ? intval($_POST['enabled_category']) : 0;
            $slug = sanitize_title($category_name);

            if (empty($category_name)) {
                $response_message = esc_html__(
                    'Singular name is required.',
                    'publishpress-authors'
                );
            } elseif (empty($plural_name)) {
                $response_message = esc_html__(
                    'Plural name is required.',
                    'publishpress-authors'
                );
            } elseif(!empty(get_ppma_author_categories(['slug' => $slug]))) {
                $response_message = esc_html__(
                    'Author category with this name already exist.',
                    'publishpress-authors'
                );
            } else {
                $category_args = [
                    'category_name'     => $category_name,
                    'plural_name'       => $plural_name,
                    'meta_data'       => [
                        'schema_property' => $schema_property,
                        'post_types' => $post_types
                    ],
                    'slug'              => $slug,
                    'category_order'    => 0,
                    'category_status'   => $enabled_category,
                    'created_at'        => current_time('mysql', true)
                ];
                $added_category = $this->addAuthorCategory($category_args);

                if ($added_category && !empty($added_category)) {
                    $response_message = esc_html__(
                        'Category added.',
                        'publishpress-authors'
                    );
                    $response['status']     = 'success';

                    ob_start();

                    $ajax_author_categories_table = new AuthorCategoriesTable();
                    $ajax_author_categories_table->single_row($added_category);
                    $response['content'] = ob_get_clean();

                } else {
                    $response_message = esc_html__(
                        'Error inserting new author category.',
                        'publishpress-authors'
                    );
                }

            }

        }

        $response['message']    = '<div class="notice notice-'. $response['status'] .' is-dismissible"><p>'. $response_message .'</p></div>';
        wp_send_json($response);
    }

    /**
     * Handle a request to save author category.
     * Copied from WordPress wp_ajax_inline_save_tax
     * to match the Quick Edit
     *
     * @return mixed
     */
    public function handleEditCategory() {

        if (empty($_POST['nonce'])
            || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'ppma-category-inline-edit-nonce')
        ) {
            wp_die(esc_html__(
                'Security error. Kindly reload this page and try again',
                'publishpress-authors'
            ));
        } elseif (!current_user_can(apply_filters('pp_multiple_authors_manage_categories_cap', 'ppma_manage_author_categories'))) {
            wp_die(esc_html__(
                'You do not have permission to perform this action',
                'publishpress-authors'
            ));
        } else {

            $category_name = isset($_POST['singular_name']) ? sanitize_text_field($_POST['singular_name']) : '';
            $plural_name = isset($_POST['plural_name']) ? sanitize_text_field($_POST['plural_name']) : '';
            $schema_property = isset($_POST['schema_property']) ? sanitize_text_field($_POST['schema_property']) : '';
            $post_types = isset($_POST['post_types']) && is_array($_POST['post_types']) ? array_map('sanitize_text_field', $_POST['post_types']) : [];
            $category_status = isset($_POST['enabled_category']) ? intval($_POST['enabled_category']) : 0;
            $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            $slug = sanitize_title($category_name);
            $existing_category = get_ppma_author_categories(['slug' => $slug]);
            if (empty($category_name) || empty($plural_name) || empty($category_id)) {
                wp_die(esc_html__(
                    'All fields are required.',
                    'publishpress-authors'
                ));
            } elseif (!empty($existing_category) && (int)$existing_category['id'] !== (int)$category_id) {
                wp_die(esc_html__(
                    'Author category with this name already exist.',
                    'publishpress-authors'
                ));
            } else {

                $category_args = [
                    'category_name'     => $category_name,
                    'plural_name'       => $plural_name,
                    'meta_data'       => [
                        'schema_property' => $schema_property,
                        'post_types' => $post_types
                    ],
                    'slug'              => $slug,
                    'category_status'   => $category_status
                ];
                $edited_category = $this->editAuthorCategory($category_args, $category_id);
                if ($edited_category && !empty($edited_category)) {
                    ob_start();

                    $ajax_author_categories_table = new AuthorCategoriesTable();
                    $ajax_author_categories_table->single_row($edited_category);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo ob_get_clean();
                    wp_die();
                } else {
                    wp_die(esc_html__(
                        'Error updating category data.',
                        'publishpress-authors'
                    ));
                }
            }

        }
        wp_die(esc_html__('An error occured.', 'publishpress-authors'));
    }

    public function handleReOrderCategory() {


        $response['status']     = 'error';
        $response['content']    = '';
        $response_message       = esc_html__('An error occured.', 'publishpress-authors');

        if (empty($_POST['nonce'])
            || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'author-categories-reorder-nonce')
        ) {
            $response_message = esc_html__(
                'Security error. Kindly reload this page and try again.',
                'publishpress-authors'
            );
        } elseif (empty($_POST['categories'])) {
            $response_message = esc_html__(
                'Invalid form data.',
                'publishpress-authors'
            );
        } else {
            $categories = array_map('intval', $_POST['categories']);

            foreach($categories as $key => $category) {
                $this->editAuthorCategory(['category_order' => ($key + 1)], $category);
            }

            $response_message = esc_html__(
                'Category order updated.',
                'publishpress-authors'
            );
            $response['status']     = 'success';
        }

        $response['message']    = '<div class="notice notice-'. $response['status'] .' is-dismissible"><p>'. $response_message .'</p></div>';
        wp_send_json($response);
    }

    /**
     * Add new author category
     *
     * @param array $insert_args
     *
     * @return array|bool
     */
    private function addAuthorCategory($insert_args) {
        global $wpdb;

        $table_name = AuthorCategoriesSchema::tableName();

        $meta_data = [];
        if (isset($insert_args['meta_data'])) {
            $meta_data = $insert_args['meta_data'];
            unset($insert_args['meta_data']);
        }

        // Check if the slug already exists
        if (isset($insert_args['slug'])) {
            $slug = $insert_args['slug'];
            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE slug = %s",
                $slug
            ));

            if ($existing_id) {
                return get_ppma_author_categories(['id' => $existing_id]);
            }
        }

        // Proceed with insertion if slug doesn't exist
        $result = $wpdb->insert($table_name, $insert_args);

        if ($result === false) {
            return false;
        }

        $category_id = $wpdb->insert_id;

        if ((int) $category_id > 0) {
            foreach ($meta_data as $meta_data_key => $meta_data_value) {
                self::updateAuthorCategoryMeta($category_id, $meta_data_key, $meta_data_value);
            }
            do_action('publishpress_author_categories_flush_cache', $category_id);
            return get_ppma_author_categories(['id' => $category_id, 'no_cache' => true]);
        } else {
            return false;
        }
    }

    /**
     * Edit author category
     *
     * @param array $edit_args
     * @param integer $id
     *
     * @return array|bool
     */
    private function editAuthorCategory($edit_args, $id) {
        global $wpdb;

        if (!current_user_can(apply_filters('pp_multiple_authors_manage_categories_cap', 'ppma_manage_author_categories'))) {
            return false;
        }

        $table_name     = AuthorCategoriesSchema::tableName();

        $meta_data = [];
        if (isset($edit_args['meta_data'])) {
            $meta_data = $edit_args['meta_data'];
            unset($edit_args['meta_data']);
        }

        $wpdb->update(
            $table_name,
            $edit_args,
            [
                'id' => $id
            ]
        );

        foreach ($meta_data as $meta_data_key => $meta_data_value) {
            self::updateAuthorCategoryMeta($id, $meta_data_key, $meta_data_value);
        }

        do_action('publishpress_author_categories_flush_cache', $id);

        return get_ppma_author_categories(['id' => $id]);
    }

    public function updateAuthorCategoryMeta($category_id, $meta_key, $meta_value) {
        global $wpdb;

        $table_name     = AuthorCategoriesSchema::metaTableName();

        if (empty($meta_value)) {
            $result = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$table_name} WHERE meta_key = %s AND category_id = %d",
                    $meta_key, $category_id
                )
            );
        } else {
            $meta_ids = $wpdb->get_col($wpdb->prepare("SELECT meta_id FROM $table_name WHERE meta_key = %s AND category_id = %d", $meta_key, $category_id));

            if (empty($meta_ids)) {
                $result = $wpdb->insert(
                    $table_name,
                    [
                        'category_id'   => $category_id,
                        'meta_key'      => $meta_key,
                        'meta_value'    => maybe_serialize($meta_value)
                    ]
                );
            } else {
                $_meta_value = $meta_value;
                $meta_value  = maybe_serialize( $meta_value );

                $data  = compact('meta_value');
                $where = [
                    'category_id'   => $category_id,
                    'meta_key'      => $meta_key,
                ];

                $result = $wpdb->update(
                    $table_name,
                    $data,
                    $where
                );
            }
        }

        return $result ? true : false;
    }

    /**
     * Author categories callback
     *
     * @return void
     */
    public function manageAuthorCategories() {

        $this->author_categories_table->prepare_items();
        $proActive = Utils::isAuthorsProActive();
        ?>

        <div class="wrap">

            <h1 class="wp-heading-inline"><?php esc_html_e('Author Categories', 'publishpress-authors'); ?></h1>
            <?php
                if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) {
                    echo '<span class="subtitle">';
                    printf(
                        /* translators: %s: Search query. */
                        esc_html__( 'Search results for: %s' ),
                        '<strong>' . esc_html( wp_unslash( $_REQUEST['s'] ) ) . '</strong>'
                    );
                    echo '</span>';
                }
            ?>
            <hr class="wp-header-end">
            <div id="ajax-response"></div>


            <form class="search-form wp-clearfix" method="get">
                <?php $this->author_categories_table->search_box(esc_html__('Search Author Categories', 'publishpress-authors'), 'author-categories'); ?>
            </form>

            <div id="col-container" class="wp-clearfix">
                <div id="col-left">
                    <div class="col-wrap">
                        <div class="form-wrap">
                            <h2><?php esc_html_e('Add Author Category', 'publishpress-authors'); ?></h2>
                            <form id="addauthorcategory" method="post" action="#" class="validate">
                                <div class="form-field form-required category-name-wrap">
                                    <label for="category-name"><?php esc_html_e( 'Singular Name', 'publishpress-authors' ); ?> <span class="required">*</span></label>
                                    <input name="category-name" id="category-name" type="text" value="" size="40" required autocomplete="off" />
                                    <p id="category-name-description"><?php esc_html_e('Enter the Author Category name when it\'s a single author', 'publishpress-authors'); ?></p>
                                </div>
                                <div class="form-field form-required category-plural-name-wrap">
                                    <label for="category-plural-name"><?php esc_html_e( 'Plural Name', 'publishpress-authors' ); ?> <span class="required">*</span></label>
                                    <input name="category-plural-name" id="category-plural-name" type="text" value="" size="40" required autocomplete="off" />
                                    <p id="category-plural-description"><?php esc_html_e('Enter the Author Category name when there are more than 1 author', 'publishpress-authors'); ?></p>
                                </div>
                                <div class="form-field category-schema-property-wrap">
                                    <label for="category-schema-property"><?php esc_html_e( 'Schema Property', 'publishpress-authors' ); ?></label>
                                    <input name="category-schema-property" id="category-schema-property" type="text" value="" size="40" autocomplete="off" />
                                    <p id="category-plural-description"><?php printf(
                                        esc_html__(
                                            'For example, when this value is set to reviewedBy, all users under this category will be added to post reviewedBy property. You can read more %1$s in this guide.%2$s',
                                            'publishpress-authors'
                                        ),
                                        '<a target="_blank" href="https://publishpress.com/knowledge-base/author-categories-schema/">',
                                        '</a>'
                                    ); ?></p>
                                </div>
                                <?php
                                $promoClass = ! $proActive ? 'ppma-blur' : '';
                                ?>
                                <div class="form-field category-post-types-wrap">
                                    <label class="<?php echo esc_attr($promoClass); ?>" for="category-post-types"><?php esc_html_e('Post Types', 'publishpress-authors'); ?></label>
                                    <?php if (! $proActive) : ?>
                                        <div class="<?php echo esc_attr($promoClass); ?>">
                                    <?php endif; ?>
                                    <select
                                        style="width: 95%;"
                                        class="authors-select2-default-select"
                                        <?php if ($proActive) : ?>
                                        name="category-post-types[]"
                                        id="category-post-types"
                                        <?php endif; ?>
                                        data-placeholder="<?php esc_attr_e('Select Post Type...', 'publishpress-authors'); ?>"
                                        multiple
                                    >
                                        <?php
                                        if ($proActive) {
                                            $enabled_post_types = Utils::get_enabled_post_types();
                                            $post_type_objects = get_post_types(['public' => true], 'objects');
                                            foreach ($enabled_post_types as $post_type_name) {
                                                if (isset($post_type_objects[$post_type_name])) {
                                                    $post_type = $post_type_objects[$post_type_name];
                                                    printf(
                                                        '<option value="%s">%s</option>',
                                                        esc_attr($post_type->name),
                                                        esc_html($post_type->label)
                                                    );
                                                }
                                            }
                                        }
                                        ?>
                                    </select>
                                    <?php if (! $proActive) : ?>
                                        </div>
                                        <div class="ppma-promo-overlay-row">
                                            <div class="ppma-promo-upgrade-notice no-bg" style="margin-top: -50px;">
                                                <p>
                                                    <a class="upgrade-link" href="https://publishpress.com/links/authors-menu" target="__blank">
                                                        <span class="dashicons dashicons-lock"></span>
                                                        <?php echo esc_html__('Upgrade to Pro', 'publishpress-authors'); ?>
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <p class="<?php echo esc_attr($promoClass); ?>"><?php esc_html_e('Select which post types this category applies to. Leave empty for all post types.', 'publishpress-authors'); ?></p>
                                </div>
                                <div class="form-field category-enabled-category-wrap">
                                    <label for="category-enabled-category">
                                        <input name="category-enabled-category" id="category-enabled-category" type="checkbox" value="1" checked />
                                        <?php esc_html_e( 'Enable Category', 'publishpress-authors' ); ?>
                                    </label>
                                </div>
                            <p class="submit">
                                <?php submit_button( __('Add New Author Category', 'publishpress-authors'), 'primary', 'submit', false ); ?>
                                <span class="spinner"></span>
                            </p>
                            </form>
                        </div>
                    </div>
                </div><!-- /col-left -->

                <div id="col-right">
                    <div class="col-wrap">
                        <form action="<?php echo esc_url(add_query_arg('', '')); ?>" method="post">
                            <?php $this->author_categories_table->display(); ?>
                        </form>
                    </div>
                </div><!-- /col-right -->
            </div><!-- /col-container -->

        </div><!-- /wrap -->
        <?php $this->author_categories_table->inline_edit(); ?>
        <?php
    }

    /**
     * Insert default author Categories
     *
     * @return void
     */
    private function insertDefaultCategories() {

        $default_categories = [];

        $default_categories[] = [
            'category_name'     => esc_html__('Author', 'publishpress-authors'),
            'plural_name'       => esc_html__('Authors', 'publishpress-authors'),
            'slug'              => sanitize_title(esc_html__('Author', 'publishpress-authors')),
            'category_order'    => 1,
            'category_status'   => 1,
            'created_at'        => current_time('mysql', true)
        ];

        $default_categories[] = [
            'category_name'     => esc_html__('Coauthor', 'publishpress-authors'),
            'plural_name'       => esc_html__('Coauthors', 'publishpress-authors'),
            'slug'              => sanitize_title(esc_html__('Coauthor', 'publishpress-authors')),
            'category_order'    => 2,
            'category_status'   => 0,
            'created_at'        => current_time('mysql', true)
        ];

        $default_categories[] = [
            'category_name'     => esc_html__('Reviewer', 'publishpress-authors'),
            'plural_name'       => esc_html__('Reviewers', 'publishpress-authors'),
            'slug'              => sanitize_title(esc_html__('Reviewer', 'publishpress-authors')),
            'category_order'    => 3,
            'category_status'   => 0,
            'created_at'        => current_time('mysql', true)
        ];

        $default_categories[] = [
            'category_name'     => esc_html__('Editor', 'publishpress-authors'),
            'plural_name'       => esc_html__('Editors', 'publishpress-authors'),
            'slug'              => sanitize_title(esc_html__('Editor', 'publishpress-authors')),
            'category_order'    => 4,
            'category_status'   => 0,
            'created_at'        => current_time('mysql', true)
        ];

        foreach ($default_categories as $default_category) {
            $this->addAuthorCategory($default_category);
        }
    }

    /**
     * Delete author category relation for post id
     */
    public function deleteAuthorCategoryRelation($post_id) {
        global $wpdb;

        $table_name     = AuthorCategoriesSchema::relationTableName();

        $wpdb->delete($table_name, ['post_id' => $post_id], ['%d']);
    }

    /**
     * Runs methods when the plugin is running for the first time.
     *
     * @param string $currentVersion
     *
     * @param void
     */
    public function runInstallTasks($currentVersion) {

        // add new table
        AuthorCategoriesSchema::createTableIfNotExists();
        AuthorCategoriesSchema::createMetaTableIfNotExists();
        AuthorCategoriesSchema::createRelationTableIfNotExists();

        // add default author category
        $this->insertDefaultCategories();

        // add author category capability
        $capability_roles = ['administrator'];
        foreach ($capability_roles as $capability_role) {
             $role = get_role($capability_role);
             if ($role instanceof \WP_Role) {
                 $role->add_cap('ppma_manage_author_categories');
             }
         }
         update_option('ppma_author_categories_installed', 1);
         update_option('ppma_author_categories_meta_installed', 1);
         update_option('ppma_author_categories_cap_upgrade', 1);
    }

    /**
     * Runs methods when the plugin is being upgraded to a most recent version.
     *
     * @param string $currentVersion
     *
     * @param void
     */
    public function runUpgradeTasks($currentVersion) {
        if (empty(get_option('ppma_author_categories_installed'))) {
            $this->runInstallTasks($currentVersion);
        } elseif (empty(get_option('ppma_author_categories_meta_installed'))) {
            AuthorCategoriesSchema::createMetaTableIfNotExists();
            update_option('ppma_author_categories_meta_installed', 1);
        }

        if (empty(get_option('ppma_author_categories_cap_upgrade'))) {
            $capability_roles = ['editor', 'author', 'contributor'];
            foreach ($capability_roles as $capability_role) {
                $role = get_role($capability_role);
                if ($role instanceof \WP_Role && $role->has_cap('ppma_manage_author_categories')) {
                    $role->remove_cap('ppma_manage_author_categories');
                }
            }
            update_option('ppma_author_categories_cap_upgrade', 1);
        }
    }
}
