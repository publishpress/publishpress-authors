<?php

/**
 * @package     MultipleAuthorCategories
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       4.3.0
 */

namespace MultipleAuthorCategories;

use MultipleAuthorCategories\AuthorCategoriesSchema;
use MultipleAuthors\Classes\Utils;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class AuthorCategoriesTable extends \WP_List_Table
{

    /** Class constructor */
    public function __construct()
    {

        parent::__construct([
            'singular' => esc_html__('Author Category', 'publishpress-authors'),
            'plural' => esc_html__('Author Categories', 'publishpress-authors'),
            'ajax' => true
        ]);
    }

    public function author_categories_data($count = false)
    {

        $search = (!empty($_REQUEST['s'])) ? sanitize_text_field($_REQUEST['s']) : '';
        $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'category_order';
        $order = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'ASC';

        $items_per_page = $this->get_items_per_page('author_categories_per_page', 20);
        $page = $this->get_pagenum();


        if ($count) {
            $author_categories = get_ppma_author_categories(['count_only' => true]);
        } else {
            $author_categories = get_ppma_author_categories(['paged' => $page, 'limit' => $items_per_page, 'search' => $search, 'orderby' => $orderby, 'order' => $order]);
        }

        return $author_categories;
    }

    /**
     * Show single row item
     *
     * @param array $item
     */
    public function single_row($item)
    {
        $class = ['ppma-category-tr'];
        $id = 'authorcategory-' . $item['id'] . '';
        echo sprintf('<tr id="%s" class="%s">', esc_attr($id), esc_attr(implode(' ', $class)));
        $this->single_row_columns($item);
        echo '</tr>';
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns()
    {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'category_name' => esc_html__('Name', 'publishpress-authors'),
            'plural_name' => esc_html__('Plural Name', 'publishpress-authors'),
            'post_types' => esc_html__('Post Types', 'publishpress-authors'),
            'slug' => esc_html__('Slug', 'publishpress-authors'),
            'category_status' => esc_html__('Enable Category', 'publishpress-authors'),
        ];

        return $columns;
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    protected function get_sortable_columns()
    {
        $sortable_columns = [
            'category_name' => ['category_name', true],
            'plural_name' => ['plural_name', true],
            'slug' => ['slug', true],
            'category_status' => ['category_status', true],
        ];

        return $sortable_columns;
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', 'author_categories', $item['id']);
    }

    /**
     * Get the bulk actions to show in the top page dropdown
     *
     * @return array
     */
    protected function get_bulk_actions()
    {
        $actions = [
            'ppma-enable-author-categories' => esc_html__('Enable Categories', 'publishpress-authors'),
            'ppma-disable-author-categories' => esc_html__('Disable Categories', 'publishpress-authors'),
            'ppma-delete-author-categories' => esc_html__('Delete', 'publishpress-authors')
        ];

        return $actions;
    }

    /**
     * Process bulk actions
     */
    public function process_bulk_action()
    {

        $query_arg = '_wpnonce';
        $action = 'bulk-' . $this->_args['plural'];
        $checked = isset($_REQUEST[$query_arg]) ? wp_verify_nonce(sanitize_key($_REQUEST[$query_arg]), $action) : false;

        if (!$checked || !current_user_can(apply_filters('pp_multiple_authors_manage_categories_cap', 'ppma_manage_author_categories'))) {
            return;
        }

        if ($this->current_action() === 'ppma-delete-author-categories' && !empty($_REQUEST['author_categories'])) {
            $author_categories = array_map('sanitize_text_field', (array) $_REQUEST['author_categories']);
            if (!empty($author_categories)) {
                foreach ($author_categories as $author_category) {
                    $this->deleteAuthorCategory($author_category);
                }
                if (count($author_categories) > 1) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Utils::admin_notices_helper(esc_html__('Author categories deleted successfully.', 'publishpress-authors'), false);
                } else {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Utils::admin_notices_helper(esc_html__('Author category deleted successfully.', 'publishpress-authors'), false);
                }
            }
        } elseif ($this->current_action() === 'ppma-enable-author-categories' && !empty($_REQUEST['author_categories'])) {
            $author_categories = array_map('sanitize_text_field', (array) $_REQUEST['author_categories']);
            if (!empty($author_categories)) {
                foreach ($author_categories as $author_category) {
                    $this->editAuthorCategory(['category_status' => 1], $author_category);
                }
                if (count($author_categories) > 1) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Utils::admin_notices_helper(esc_html__('Author categories enabled.', 'publishpress-authors'), true);
                } else {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Utils::admin_notices_helper(esc_html__('Author category enabled.', 'publishpress-authors'), true);
                }
            }
        } elseif ($this->current_action() === 'ppma-disable-author-categories' && !empty($_REQUEST['author_categories'])) {
            $author_categories = array_map('sanitize_text_field', (array) $_REQUEST['author_categories']);
            if (!empty($author_categories)) {
                foreach ($author_categories as $author_category) {
                    $this->editAuthorCategory(['category_status' => 0], $author_category);
                }
                if (count($author_categories) > 1) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Utils::admin_notices_helper(esc_html__('Author categories disabled.', 'publishpress-authors'), true);
                } else {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Utils::admin_notices_helper(esc_html__('Author category disabled.', 'publishpress-authors'), true);
                }
            }
        }
    }

    /**
     * Delete author category
     *
     * @param integer $category_id
     *
     * @return mixed
     */
    private function deleteAuthorCategory($category_id)
    {
        global $wpdb;

        if (!current_user_can(apply_filters('pp_multiple_authors_manage_categories_cap', 'ppma_manage_author_categories'))) {
            return false;
        }

        $table_name = AuthorCategoriesSchema::tableName();
        $meta_table_name = AuthorCategoriesSchema::metaTableName();

        $delete = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE id = %d",
                $category_id
            )
        );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$meta_table_name} WHERE category_id = %d",
                $category_id
            )
        );

        return $delete;
    }

    /**
     * Edit author category
     *
     * @param array $edit_args
     * @param integer $id
     *
     * @return array|bool
     */
    private function editAuthorCategory($edit_args, $id)
    {
        global $wpdb;

        if (!current_user_can(apply_filters('pp_multiple_authors_manage_categories_cap', 'ppma_manage_author_categories'))) {
            return false;
        }

        $table_name = AuthorCategoriesSchema::tableName();

        $wpdb->update(
            $table_name,
            $edit_args,
            [
                'id' => $id
            ]
        );

        return get_ppma_author_categories(['id' => $id]);
    }

    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        return !empty($item[$column_name]) ? $item[$column_name] : '&mdash;';
    }

    /** Text displayed when no stterm data is available */
    public function no_items()
    {
        esc_html_e('No author categories.', 'publishpress-authors');
    }

    /**
     * Displays the search box.
     *
     * @param string $text The 'submit' button label.
     * @param string $input_id ID attribute value for the search input field.
     *
     *
     */
    public function search_box($text, $input_id)
    {

        $input_id = $input_id . '-search-input';

        if (!empty($_REQUEST['orderby'])) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr(sanitize_text_field($_REQUEST['orderby'])) . '" />';
        }
        if (!empty($_REQUEST['order'])) {
            echo '<input type="hidden" name="order" value="' . esc_attr(sanitize_text_field($_REQUEST['order'])) . '" />';
        }
        if (!empty($_REQUEST['page'])) {
            echo '<input type="hidden" name="page" value="' . esc_attr(sanitize_text_field($_REQUEST['page'])) . '" />';
        }
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php _admin_search_query(); ?>"/>
            <?php submit_button($text, '', '', false, ['id' => 'ppma-categories-search-submit']); ?>
        </p><?php
    }

    /**
     * Sets up the items (roles) to list.
     */
    public function prepare_items()
    {

        $this->_column_headers = $this->get_column_info();
        $this->process_bulk_action();

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = $this->get_items_per_page('author_categories_per_page', 20);

        /**
         * Fetch the data
         */
        $data = $this->author_categories_data();

        $total_items = $this->author_categories_data(true);

        /**
         * Now we can add the data to the items property, where it can be used by the rest of the class.
         */
        $this->items = $data;

        /**
         * We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args([
            'total_items' => $total_items,                      //calculate the total number of items
            'per_page' => $per_page,                         //determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page)   //calculate the total number of pages
        ]);
    }

    /**
     * Generates and display row actions links for the list table.
     *
     * @param object $item The item being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary Primary column name.
     *
     * @return string The row actions HTML, or an empty string if the current column is the primary column.
     */
    protected function handle_row_actions($item, $column_name, $primary)
    {
        //Build row actions
        $actions = [];
        if (current_user_can(apply_filters('pp_multiple_authors_manage_categories_cap', 'ppma_manage_author_categories'))) {
            $schema_property = !empty($item['schema_property']) ? $item['schema_property'] : '';
            $post_types_data = isset($item['post_types']) ? maybe_unserialize($item['post_types']) : [];
            $post_types_json = json_encode($post_types_data);
            $actions['inline hide-if-no-js'] = sprintf(
                '<button type="button" class="button-link editinline" aria-label="%s" aria-expanded="false" data-category_id="' . $item['id'] . '" data-category_name="' . $item['category_name'] . '" data-plural_name="' . $item['plural_name'] . '" data-schema_property="' . $schema_property . '" data-slug="' . $item['slug'] . '" data-category_status="' . $item['category_status'] . '"  data-post_types=\'' . esc_attr($post_types_json) . '\'>%s</button>',
                /* translators: %s: Taxonomy term name. */
                esc_attr(sprintf(esc_html__('Quick edit &#8220;%s&#8221; inline', 'publishpress-authors'), $item['category_name'])),
                esc_html__('Quick&nbsp;Edit', 'publishpress-authors')
            );

            $actions['delete'] = sprintf(
                '<a href="%s" class="delete-terms">%s</a>',
                add_query_arg(
                    [
                        'page' => \MA_Author_Categories::MENU_SLUG,
                        'action' => 'ppma-delete-author-categories',
                        'author_categories' => esc_attr($item['id']),
                        '_wpnonce' => wp_create_nonce('bulk-' . $this->_args['plural'])
                    ],
                    admin_url('admin.php')
                ),
                esc_html__('Delete', 'publishpress-authors')
            );
        }

        return $column_name === $primary ? $this->row_actions($actions, false) : '';
    }

    /**
     * Method for category_status column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_category_status($item)
    {
        if (!empty($item['category_status'])) {
            return '<div style="color: green;">' . esc_html__('Enabled', 'publishpress-authors') . '</div>';
        } else {
            return '<div style="color: red;">' . esc_html__('Disabled', 'publishpress-authors') . '</div>';
        }
    }

    /**
     * Method for category_name column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_category_name($item)
    {

        $title = sprintf(
            '<strong><span class="row-title">%1$s</span></strong>',
            esc_html($item['category_name'])
        );

        return $title;
    }

    /**
    * Render the post types column
    *
    * @param array $item
    *
    * @return string
    */
    protected function column_post_types($item)
    {
        $post_types = [];
        if (isset($item['post_types']) && !empty($item['post_types'])) {
            $stored_post_types = maybe_unserialize($item['post_types']);
            if (is_array($stored_post_types)) {
                $post_type_objects = get_post_types(['public' => true], 'objects');
                foreach ($stored_post_types as $post_type_name) {
                    if (isset($post_type_objects[$post_type_name])) {
                        $post_types[] = $post_type_objects[$post_type_name]->label;
                    }
                }
            }
        }

        return !empty($post_types) ? implode(', ', $post_types) : esc_html__('All', 'publishpress-authors');
    }

    /**
     * Outputs the hidden row displayed when inline editing
     *
     * @since 3.1.0
     */
    public function inline_edit()
    {
        $proActive = Utils::isAuthorsProActive();
        $promoClass = ! $proActive ? 'ppma-blur' : '';
        ?>

        <form method="get">
            <table style="display: none">
                <tbody id="inlineedit">

                    <tr id="inline-edit" class="inline-edit-row" style="display: none">
                        <td colspan="<?php echo esc_attr($this->get_column_count()); ?>" class="colspanchange">

                            <fieldset>
                                <legend class="inline-edit-legend"><?php esc_html_e('Quick Edit', 'publishpress-authors'); ?></legend>
                                <div class="inline-edit-col">
                                    <label>
                                        <span class="title"><?php esc_html_e('Singular Name', 'publishpress-authors'); ?></span>
                                        <span class="input-text-wrap"><input type="text" name="singular_name" class="singular_name" value=""/></span>
                                    </label>

                                    <label>
                                        <span class="title"><?php esc_html_e('Plural Name', 'publishpress-authors'); ?></span>
                                        <span class="input-text-wrap"><input type="text" name="plural_name" class="plural_name" value=""/></span>
                                    </label>

                                    <label>
                                        <span class="title"><?php esc_html_e('Schema Property', 'publishpress-authors'); ?></span>
                                        <span class="input-text-wrap"><input type="text" name="schema_property" class="schema_property" value=""/></span>
                                    </label>

                                    <label>
                                        <span class="title <?php echo esc_attr($promoClass); ?>"><?php esc_html_e('Post Types', 'publishpress-authors'); ?></span>
                                        <span class="input-text-wrap <?php echo esc_attr($promoClass); ?>">
                                            <select
                                                style="width: 70%;"
                                                class="post_types"
                                                <?php if ($proActive) : ?>
                                                        name="post_types[]"
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
                                        </span>
                                        <?php if (! $proActive) : ?>
                                            <div class="ppma-promo-overlay-row">
                                                <div class="ppma-promo-upgrade-notice no-bg" style="margin-top: -70px;">
                                                    <p>
                                                        <a class="upgrade-link" href="https://publishpress.com/links/authors-menu" target="__blank">
                                                            <span class="dashicons dashicons-lock"></span>
                                                            <?php echo esc_html__('Upgrade to Pro', 'publishpress-authors'); ?>
                                                        </a>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </label>


                                    <label>
                                        <span class="title"><?php esc_html_e('Enable Category', 'publishpress-authors'); ?></span>
                                        <span class="input-text-wrap"><input type="checkbox" name="enabled_category" class="enabled_category" value="1"/></span>
                                    </label>
                                </div>
                            </fieldset>

                            <div class="inline-edit-save submit">
                                <?php wp_nonce_field('ppma-category-inline-edit-nonce', 'nonce', false); ?>
                                <button type="button" class="ppma-inline-category-save button button-primary alignright"><?php esc_html_e('Update', 'publishpress-authors'); ?></button>
                                <button type="button" class="cancel button alignleft"><?php esc_html_e('Cancel', 'publishpress-authors'); ?></button>
                                <span class="spinner"></span>
                                <br class="clear"/>
                                <div class="notice notice-error notice-alt inline hidden">
                                    <p class="error"></p>
                                </div>
                            </div>

                        </td>
                    </tr>

                </tbody>
            </table>
        </form><?php
    }
}
