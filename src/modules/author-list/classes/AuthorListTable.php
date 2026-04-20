<?php
/**
 * @package     MultipleAuthorList
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.1.0
 */

namespace MultipleAuthorList;

use MultipleAuthors\Factory;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Author list table
 *
 * @package MultipleAuthorList\Classes
 *
 */
class AuthorListTable extends \WP_List_Table
{

    /**
     * The current view.
     *
     * @access public
     * @var    string
     */
    public $list_view = 'active';

    /** Class constructor */
    public function __construct()
    {

        parent::__construct([
            'singular' =>  esc_html__('AuthorList', 'publishpress-authors'),
            'plural'   =>  esc_html__('AuthorList', 'publishpress-authors'),
            'ajax'     => false
        ]);

        // Get the current view.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['view'])) {
            $this->list_view = sanitize_key($_GET['view']);
        }
    }

    /**
     * Returns an array of views for the list table.
     *
     * @access protected
     * @return array
     */
    protected function get_views()
    {
        $views = [];
        $current = ' class="current"';

        $list_view_filters = [
            'active'    => _n_noop('All %s', 'All %s', 'publishpress-authors'),
            'trash'     => _n_noop('Trash %s', 'Trash %s', 'publishpress-authors'),
        ];

        foreach ($list_view_filters as $view => $noop) {

            $count = count($this->get_author_list_data($view));

            // Add the view link.
            $views[$view] = sprintf(
                '<a%s href="%s">%s</a>',
                $view === $this->list_view ? $current : '',
                esc_url(
                    add_query_arg(
                        [
                            'page' => 'ppma-author-list',
                            'view' => esc_attr($view)
                        ],
                        admin_url('admin.php')
                    )
                ),
                sprintf(
                    translate_nooped_plural($noop, $count, $noop['domain']),
                    sprintf('<span class="count">(%s)</span>', number_format_i18n($count))
                )
            );
        }

        return $views;
    }

    /**
     * Retrieve author_list_data data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_author_list_data($view = 'active')
    {
        global $author_list_grouped_data;

        if (!is_array($author_list_grouped_data) || !isset($author_list_grouped_data[$view])) {
            if (!is_array($author_list_grouped_data)) {
                $author_list_grouped_data = [];
            }
            $legacyPlugin = Factory::getLegacyPlugin();

            $author_list_data = $legacyPlugin->modules->author_list->options->author_list_data;

            if (!empty($author_list_data)) {
                foreach ($author_list_data as $item_id => $item) {
                    if (isset($item['status']) && $item['status'] == $view) {
                        $author_list_grouped_data[$view][$item_id] = $item;
                    } elseif (!isset($item['status'])) {
                        $author_list_grouped_data['active'][$item_id] = $item;
                    }
                }
            }
        }
        return isset($author_list_grouped_data[$view]) ? $author_list_grouped_data[$view] : [];
    }

    /**
     * Show single row item
     *
     * @param array $item
     */
    public function single_row($item)
    {
        $class = ['author-list-tr'];
        $id    = 'author-list-' . md5($item['ID']);
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
            'title'     =>  esc_html__('Title', 'publishpress-authors'),
            'layout'    =>  esc_html__('Layout', 'publishpress-authors'),
            'dynamic_shortcode' =>  esc_html__('Shortcode', 'publishpress-authors')
        ];

        return $columns;
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
        _e('No author list avaliable in the selected view.', 'publishpress-authors');
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
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }

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
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s"
                   value="<?php _admin_search_query(); ?>"/>
            <?php submit_button($text, '', '', false, ['id' => 'search-submit']); ?>
        </p>
        <?php
    }

    /**
     * Sets up the items (roles) to list.
     */
    public function prepare_items()
    {

        $this->_column_headers = $this->get_column_info();

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = $this->get_items_per_page('author_list_data_per_page', 20);

        /**
         * Fetch the data
         */
        $data = self::get_author_list_data($this->list_view);

        /**
         * Handle search
         */
        if ((!empty($_REQUEST['s'])) && $search = sanitize_text_field($_REQUEST['s'])) {
            $data_filtered = [];
            foreach ($data as $item) {
                if ($this->str_contains($item['title'], $search, false)) {
                    $data_filtered[] = $item;
                }
            }
            $data = $data_filtered;
        }

        usort($data, [$this, 'usort_reorder']);

        /**
         * Pagination.
         */
        $current_page = $this->get_pagenum();
        $total_items  = count($data);


        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to
         */
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

        /**
         * Now we can add the data to the items property, where it can be used by the rest of the class.
         */
        $this->items = $data;

        /**
         * We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    /**
     * This checks for sorting input and sorts the data in our array accordingly.
     */
    public function usort_reorder($a, $b)
    {
        $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'ID';
        $order   = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'desc';
        $result  = strnatcasecmp($a[$orderby], $b[$orderby]);

        return ($order === 'asc') ? $result : -$result;
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     * @param bool $sensitive Use case sensitive search
     *
     * @return bool
     */
    public function str_contains($haystack, $needles, $sensitive = true)
    {
        foreach ((array)$needles as $needle) {
            $function = $sensitive ? 'mb_strpos' : 'mb_stripos';
            if ($needle !== '' && $function($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    protected function get_sortable_columns()
    {
        $sortable_columns = [
            'title'    => ['title', true]
        ];

        return $sortable_columns;
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
        if ($this->list_view === 'active') {
            $actions = [
                'edit'   => sprintf(
                    '<a href="%s">%s</a>',
                    add_query_arg(
                        [
                            'page'              => 'ppma-author-list',
                            'author_list_edit'  => $item['ID'],
                        ],
                        admin_url('admin.php')
                    ),
                    esc_html__('Edit', 'publishpress-authors')
                ),
                'delete' => sprintf(
                    '<a href="%s" class="delete-author-list">%s</a>',
                    add_query_arg([
                            'page'              => 'ppma-author-list',
                            'action'            => 'ppma-trash-author-list',
                            'author_list_id'    => esc_attr($item['ID']),
                            '_wpnonce'          => wp_create_nonce('author-list-request-nonce')
                        ],
                        admin_url('admin.php')
                    ),
                    esc_html__('Trash', 'publishpress-authors')
                ),
            ];
        } else {
            $actions = [
                'edit'   => sprintf(
                    '<a href="%s">%s</a>',
                    add_query_arg(
                        [
                            'page'              => 'ppma-author-list',
                            'action'            => 'ppma-restore-author-list',
                            'author_list_id'    => esc_attr($item['ID']),
                            '_wpnonce'          => wp_create_nonce('author-list-request-nonce')
                        ],
                        admin_url('admin.php')
                    ),
                    esc_html__('Restore', 'publishpress-authors')
                ),
                'delete' => sprintf(
                    '<a href="%s" class="delete-author-list">%s</a>',
                    add_query_arg([
                            'page'              => 'ppma-author-list',
                            'action'            => 'ppma-delete-author-list',
                            'author_list_id'    => esc_attr($item['ID']),
                            '_wpnonce'          => wp_create_nonce('author-list-request-nonce')
                        ],
                        admin_url('admin.php')
                    ),
                    esc_html__('Delete Permanently', 'publishpress-authors')
                ),
            ];
        }

        return $column_name === $primary ? $this->row_actions($actions, false) : '';
    }

    /**
     * Method for title column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_title($item)
    {
        $title = sprintf(
            '<a href="%1$s"><strong><span class="row-title">%2$s</span></strong></a>',
            add_query_arg(
                [
                    'page'              => 'ppma-author-list',
                    'author_list_edit'  => $item['ID'],
                ],
                admin_url('admin.php')
            ),
            esc_html($item['title'])
        );

        return $title;
    }

    /**
     * Method for layout column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_layout($item)
    {
        $layouts = apply_filters('pp_multiple_authors_author_layouts', []);

        $layout = $layouts[$item['layout']] ? $layouts[$item['layout']] : $item['layout'];

        return $layout;
    }

    /**
     * The dynamic_shortcode column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_dynamic_shortcode($item)
    {

        return '<input readonly type="text" value=\'' . $item['dynamic_shortcode'] . '\' />';
    }

    /**
     * The static_shortcode column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_static_shortcode($item)
    {

        return '<textarea style="resize:none; width: 99%;" readonly>' . $item['static_shortcode'] . '</textarea>';
    }

    /**
     * Display the list table.
     *
     * @access public
     * @return void
     */
    public function display()
    {
        $this->views();
        parent::display();
    }

}
