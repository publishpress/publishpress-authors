<?php

/**
 * @package   MultipleAuthors
 * @author    PublishPress <help@publishpress.com>
 * @copyright Copyright (C) 2018 PublishPress. All rights reserved.
 * @license   GPLv2 or later
 * @since     1.1.0
 */

namespace MultipleAuthors\Classes;

use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Factory;
use WP_Post;
use WP_REST_Response;


/**
 * Class Post_Editor
 *
 * Based on Byline' class Post_Editor.
 *
 * @package MultipleAuthors\Classes
 */
class Post_Editor
{
    /**
     * Register callbacks for managing custom columns
     */
    public static function action_admin_init()
    {
        foreach (Content_Model::get_author_supported_post_types() as $post_type) {
            if (Utils::is_post_type_enabled($post_type)) {
                add_filter("manage_{$post_type}_posts_columns", [__CLASS__, 'filter_manage_posts_columns']);
                add_action(
                    "manage_{$post_type}_posts_custom_column",
                    [__CLASS__, 'action_manage_posts_custom_column'],
                    10,
                    2
                );
            }
        }

        add_action('bulk_edit_custom_box', [__CLASS__, 'add_author_bulk_quick_edit_custom_box'], 10, 2);
        add_action('quick_edit_custom_box', [__CLASS__, 'add_author_bulk_quick_edit_custom_box'], 10, 2);
        add_action('wp_ajax_save_bulk_edit_authors', [__CLASS__, 'save_bulk_edit_authors'], 10, 2);
        add_action('publishpress_authors_flush_cache', [__CLASS__, 'flush_cache'], 15);
        add_action('publishpress_authors_flush_cache_for_post', [__CLASS__, 'flush_post_cache'], 15);
    }

    /**
     * Add author quick edit custom box.
     */
    public static function add_author_bulk_quick_edit_custom_box($column_name, $post_type)
    {
        if (Utils::is_post_type_enabled($post_type) && $column_name === 'authors') {
            $legacyPlugin      = Factory::getLegacyPlugin();
            $quick_edit_styles = isset($legacyPlugin->modules->multiple_authors->options->disable_quick_edit_author_box)
                && 'yes' === $legacyPlugin->modules->multiple_authors->options->disable_quick_edit_author_box
                ? 'display:none;' : '';
            ?>
            <fieldset class="inline-edit-col-left" style="<?php echo esc_attr($quick_edit_styles); ?>">
                <div class="inline-edit-col">
                    <label style="display: inline-flex">
                        <span class="title">Authors</span>
                    </label>
                    <?php
                    echo self::get_rendered_authors_selection([], false, true); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </div>
            </fieldset>
            <?php
        }
    }

    /**
     * Filter post columns to include the Author column
     *
     * @param array $columns All post columns with their titles.
     *
     * @return array
     */
    public static function filter_manage_posts_columns($columns)
    {
        if (!Utils::is_post_type_enabled()) {
            return $columns;
        }

        $new_columns = [];

        foreach ($columns as $key => $value) {
            if ('author' === $key) {
                $key   = 'authors';
                $value = __('Authors', 'publishpress-authors');
            }

            $new_columns[$key] = $value;
        }

        return $new_columns;
    }

    /**
     * Render the authors for a post in the table
     *
     * @param string $column Name of the column.
     * @param int $post_id ID of the post being rendered.
     */
    public static function action_manage_posts_custom_column($column, $post_id)
    {
        if ('authors' === $column) {
            // We need to ignore the cache for following call when this method were called after saved the post in a
            // quick edit operation, otherwise the authors column will show old values.
            $authors = get_post_authors($post_id, true);

            $post_type = get_post_type($post_id);
            $post      = get_post($post_id);

            $authors_str         = [];
            $showedPostAuthorUser = false;

            foreach ($authors as $author) {
                if (is_a($author, 'WP_User')) {
                    $author = Author::get_by_user_id($author->ID);
                }

                if (is_object($author)) {
                    $args = [
                        'author_name' => $author->slug,
                    ];
                    if ('post' !== $post_type) {
                        $args['post_type'] = $post_type;
                    }
                    $url = add_query_arg(array_map('rawurlencode', $args), admin_url('edit.php'));

                    $classes = [
                        'author_name'
                    ];

                    if ($author->user_id == $post->post_author) {
                        $classes[]           = 'author_in_post';
                        $showedPostAuthorUser = true;
                    }


                    $author_category  = get_ppma_author_relations(['post_id' => $post_id, 'author_term_id' => $author->term_id]);
                    if (!empty($author_category) && isset($author_category[0]['category_id'])) {
                        $category_id = $author_category[0]['category_id'];
                    } else {
                        $category_id = 0;
                    }

                    $authors_str[] = sprintf(
                        '<a href="%s" data-author-term-id="%d" data-author-slug="%s" data-author-display-name="%s" data-author-is-guest="%s" data-author-category-id="%s" class="%s">%s</a>',
                        esc_url($url),
                        esc_attr($author->term_id),
                        esc_attr($author->slug),
                        esc_attr($author->display_name),
                        esc_attr($author->is_guest() ? 1 : 0),
                        esc_attr($category_id),
                        esc_attr(implode(' ', $classes)),
                        esc_html($author->display_name)
                    );
                }
            }

            if (empty($authors_str)) {
                $authors_str[] = sprintf(
                    '<span class="current-post-author-warning">%s</span>',
                    esc_html__('No author term', 'publishpress-authors')
                );
            }

            echo implode(', ', $authors_str); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            if (!$showedPostAuthorUser) {
                if (empty($post->post_author)) {
                    echo sprintf('<span class="current-post-author-warning">[%s]</span>', esc_html__('"post_author" is empty', 'publishpress-authors'));
                } else {
                    $user = get_user_by('ID', $post->post_author);

                    if (is_a($user, 'WP_User')) {
                        echo sprintf('<span style="display:none;" class="current-post-author-off">[%s]</span>', esc_html($user->display_name));
                    }
                }
            }
        }
    }

    /**
     * Deregister the author meta box, and register Author meta boxes
     */
    public static function action_add_meta_boxes_late()
    {
        if (!Utils::is_valid_page()) {
            return;
        }

        $supportedPostTypes = Content_Model::get_author_supported_post_types();

        foreach ($supportedPostTypes as $post_type) {
            remove_meta_box('authordiv', $post_type, 'normal');
            // @todo only register meta box when user can assign authors
            add_meta_box(
                'ppma_authorsdiv',
                __('Authors', 'publishpress-authors'),
                [__CLASS__, 'render_authors_metabox'],
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Remove author metabox for gutenberg
     *
     * @param object $response
     * @param object $taxonomy
     * @param array $request
     *
     * @return object $response
     */
    public static function action_remove_gutenberg_author_metabox($response, $taxonomy, $request) {
        $context       = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $taxonomy_name = isset($taxonomy->name) ? $taxonomy->name : false;

        // Context is edit in the editor
        if ($taxonomy_name === 'author' && $context === 'edit' && $taxonomy->meta_box_cb === false) {
            $data_response = $response->get_data();
            $data_response['visibility']['show_ui'] = false;
            $response->set_data($data_response);
        }

        return $response;
    }

    /**
     * Render the Author meta box.
     */
    public static function render_authors_metabox()
    {
        if (!Utils::is_valid_page()) {
            return;
        }

        $authors = get_post_authors(0, true);

        echo self::get_rendered_authors_selection($authors, false);  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Group author into categories
     *
     * @param array $author_categories
     * @param array $author_relations
     * @param array $authors
     * @param bool $admin_preview
     *
     * @return array
     */
    public static function group_category_authors($author_categories, $author_relations, $authors, $admin_preview = false) {

        // group authors by category slug
        if (!$admin_preview) {
            $grouped_authors = array_reduce($author_relations, function ($result, $item) {
                $result[$item['category_slug']][] = $item;
                return $result;
            }, []);

            // List all authors attached to the post
            $remaining_authors = $authors;
        } else {
            $grouped_authors    = [];
            $remaining_authors  = [];
        }

        $authors_data = [];
        foreach ($author_categories as $author_category) {
            if (!$admin_preview) {
                if (!empty($remaining_authors) && !empty($grouped_authors) && isset($grouped_authors[$author_category['slug']])) {
                    // get current category term ids
                    $category_author_ids = array_column($grouped_authors[$author_category['slug']], 'author_term_id');
                    // get selected authors for the category terms
                    $selected_authors = array_filter($remaining_authors, function ($author) use ($category_author_ids) {
                        $term_id = is_object($author) ? $author->term_id : 0;
                        return in_array($term_id, $category_author_ids);
                    });
                    // update remaining authors
                    $remaining_authors = array_filter($remaining_authors, function ($author) use ($category_author_ids) {
                        $term_id = is_object($author) ? $author->term_id : 0;
                        return !in_array($term_id, $category_author_ids);
                    });
                } else {
                    $selected_authors = [];
                }
            } else {
                $selected_authors = $authors;
            }

            $authors_data[] = [
                'title'             => $author_category['plural_name'],
                'singular_title'    => $author_category['category_name'],
                'description'       => sprintf(esc_html__('Drag-and-drop Authors to add them to the %s category', 'publishpress-authors'), $author_category['category_name']),
                'slug'              => $author_category['slug'],
                'id'                => $author_category['id'],
                'authors'           => array_values($selected_authors)
            ];
        }

        // Add remaining author to default or first category
        if (!empty($remaining_authors)) {
            foreach ($remaining_authors as $remaining_author) {
                $author_default_category = (int) $remaining_author->author_category;

                $category_index = ($author_default_category > 0)
                    ? array_search($author_default_category, array_column($authors_data, 'id'))
                    : false;

                if ($category_index !== false) {
                    $authors_data[$category_index]['authors'][] = $remaining_author;
                } else {
                    $authors_data[0]['authors'][] = $remaining_author;
                }
            }
        }


        return $authors_data;
    }

    /**
     * Get rendered authors selection.
     */
    public static function get_rendered_authors_selection($authors, $showAvatars = true, $bulkEdit = false)
    {
        $post = get_post();

        $classes = [
            'authors-list',
        ];
        if (current_user_can(get_taxonomy('author')->cap->assign_terms)) {
            $classes[] = 'authors-current-user-can-assign';
        }

        $author_categories = get_ppma_author_categories([
            'category_status' => 1,
            'post_type_and_empty' => $post->post_type
        ]);

        if (!empty($author_categories)) {
            $author_relations  = get_ppma_author_relations(['post_id' => $post->ID]);
            $author_categories_data = self::group_category_authors($author_categories, $author_relations, $authors);
        } else {
            $author_categories_data = [];
            $author_categories_data[] = [
                'title'             => '',
                'singular_title'    => '',
                'description'       => '',
                'slug'              => '',
                'id'                => '',
                'authors'           => $authors
            ];
        }
        ?>
        <?php if (current_user_can(get_taxonomy('author')->cap->assign_terms)) : ?>
            <select data-nonce="<?php
            echo esc_attr(wp_create_nonce('authors-search')); ?>"
                    id="publishpress-authors-author-select"
                    class="authors-select2 authors-search"
                    data-placeholder="<?php
                    esc_attr_e('Search for an author', 'publishpress-authors'); ?>" style="width: 100%">
                <option></option>
            </select>
            <script type="text/html" id="tmpl-authors-author-partial">
                <?php
                echo self::get_rendered_author_partial(  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    [
                        'display_name' => '{{ data.display_name }}',
                        'term'         => '{{ data.id }}',
                        'is_guest'     => '{{ data.is_guest }}',
                        'category_id'  => '{{ data.author_category }}',
                    ]
                );
                ?>
            </script>
            <p class="description"> </p>
        <?php endif; ?>
        <?php foreach ($author_categories_data as $author_category_data) :
            $author_classes = $classes;
            $author_classes[] = 'authors-category-' . $author_category_data['id'];
             ?>
            <?php if (!empty($author_category_data['title'])) : ?>
                <div class="author-category-title"><?php echo esc_html($author_category_data['title']); ?></div>
            <?php endif; ?>
            <ul class="<?php echo esc_attr(implode(' ', $author_classes)); ?>" data-category_id="<?php echo esc_attr($author_category_data['id']); ?>">
                <?php if (!empty($author_category_data['description'])) : ?>
                    <li class="sortable-placeholder no-drag" style="<?php echo (!empty($author_category_data['authors']) ? 'display: none' : ''); ?>"><p class="description"><?php echo esc_html($author_category_data['description']); ?></p></li>
                <?php endif; ?>
                <?php
                if (!empty($author_category_data['authors'])) {
                    foreach ($author_category_data['authors'] as $author) {
                        if (!is_object($author) || is_wp_error($author)) {
                            continue;
                        }

                        $display_name = $author->display_name;
                        $term         = is_a($author, 'WP_User') ? 'u' . $author->ID : $author->term_id;

                        $isGuest = 0;
                        if (is_a($author, Author::class)) {
                            $isGuest = $author->is_guest() ? 1 : 0;
                        }

                        $args = [
                            'display_name' => $display_name,
                            'term'         => $term,
                            'is_guest'     => $isGuest,
                            'category_id'  => $author_category_data['id'],
                        ];

                        if ($showAvatars) {
                            $args['avatar'] = $author->get_avatar(20);
                        }

                        echo self::get_rendered_author_partial($args);  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                }
                ?>
        </ul>
        <?php endforeach; ?>
        <?php
        wp_nonce_field('authors-save', 'authors-save');

        if (current_user_can(get_taxonomy('author')->cap->assign_terms)) {
            ?>
            <?php
            $userAuthor   = get_user_by('ID', $post->post_author);
            $postAuthorId = $post->post_author;

            $legacyPlugin           = Factory::getLegacyPlugin();
            $fallbackAuthor         = isset($legacyPlugin->modules->multiple_authors->options->fallback_user_for_guest_post) ?
                (int)$legacyPlugin->modules->multiple_authors->options->fallback_user_for_guest_post : 0;

            if ($fallbackAuthor > 0) {
                $postAuthorId = $fallbackAuthor;
                $userAuthor   = Author::get_by_user_id($postAuthorId);

            }

            if (!$userAuthor) {
                $postAuthorId = get_current_user_id();
                $userAuthor   = Author::get_by_user_id($postAuthorId);
            }
            ?>
            <?php if (!$bulkEdit) : ?>
                <?php echo self::render_editor_author_box_settings($post->ID);  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <div class="ppma-authors-display-option-wrapper">
                    <input name="ppma_save_disable_author_box" type="hidden" value="1" />
                    <input name="ppma_disable_author_box"
                            id="ppma_disable_author_box"
                            value="1"
                            type="checkbox"
                            <?php checked((int)get_post_meta($post->ID, 'ppma_disable_author_box', true), 1); ?>
                        />
                    <label for="ppma_disable_author_box">
                        <?php echo esc_html_e('Disable the default author display under this post', 'publishpress-authors'); ?>
                    </label>
                </div>
            <?php endif; ?>
            <div style="display: none">
                <div id="publishpress-authors-user-author-wrapper">
                    <hr>
                    <label for="publishpress-authors-user-author-select"><?php
                        echo esc_html__(
                            'This option is showing because you do not have a WordPress user selected as an author. For some tasks, it can be helpful to have a user selected here. This user will not be visible on the front of your site.',
                            'publishpress-authors'
                        ); ?></label>
                    <select id="publishpress-authors-user-author-select" data-nonce="<?php
                    echo esc_attr(wp_create_nonce('authors-user-search')); ?>"
                            class="authors-select2 authors-user-search"
                            data-placeholder="<?php
                            esc_attr_e('Search for an user', 'publishpress-authors'); ?>" style="width: 100%"
                            name="fallback_author_user">
                        <option value="<?php echo (int)$postAuthorId; ?>">
                            <?php echo is_object($userAuthor) ? esc_html($userAuthor->display_name) : ''; ?>
                        </option>
                    </select>
                </div>
            </div>
            <?php
        }
    }

    public static function render_editor_author_box_settings($post_id) {
        ob_start();
        ?>
        <div class="ppma-promo-overlay-row">
            <div class="ppma-author-box-selection ppma-blur" style="margin-bottom: 15px;">
                <label><?php _e('Author Box Layout:', 'publishpress-authors'); ?></label>
                <select class="authors-select2-default-select" style="width: 100%;">
                    <option value=""><?php _e('Use Global Setting', 'publishpress-authors'); ?></option>
                </select>
            </div>
            <div class="ppma-promo-simply-overlay editor-area">
                <p>
                    <a class="upgrade-link" href="https://publishpress.com/links/authors-menu" target="__blank">
                        <span class="dashicons dashicons-lock"></span>
                    </a>
                </p>
            </div>
        </div>
        <?php

        $editor_author_box = ob_get_clean();

        return apply_filters('ppma_editor_author_box_settings', $editor_author_box, $post_id);
    }

    /**
     * Add author filter to admin post filter
     *
     * @return void
     */
    public static function post_author_filter_field()
    {
        $post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'post';

        if (Utils::is_post_type_enabled($post_type)) {

            $userAuthor = false;
            $authorSlug = false;
            if (isset($_GET['author_name'])) {
                $authorSlug = sanitize_key($_GET['author_name']);
            } elseif (isset($_GET['ppma_author'])) {
                $authorSlug = sanitize_key($_GET['ppma_author']);
            }

            if ($authorSlug) {
                $userAuthor = Author::get_by_term_slug($authorSlug);
                if (!$userAuthor) {
                    $userAuthor = get_user_by('slug', $authorSlug);
                }
            }
            ?>
            <select data-nonce="<?php
                echo esc_attr(wp_create_nonce('authors-user-search')); ?>"
                    class="authors-select2 authors-user-slug-search"
                    data-placeholder="<?php
                    esc_attr_e('All Authors', 'publishpress-authors'); ?>" style="width: 150px"
                    name="author_name">
                    <?php if ($userAuthor && is_object($userAuthor)) : ?>
                        <option value="<?php echo esc_attr($userAuthor->user_nicename); ?>">
                            <?php echo esc_html($userAuthor->display_name); ?>
                        </option>
                <?php else : ?>
                    <option></option>
                <?php endif; ?>
            </select>
            <?php
        }
    }

    /**
     * Get a rendered author partial
     *
     * @param array $args Arguments to render in the partial.
     */
    private static function get_rendered_author_partial($args = [])
    {
        $defaults = [
            'display_name' => '',
            'avatar'       => '',
            'term'         => '',
            'is_guest'     => 0,
            'category_id'  => 0,
        ];

        $args     = array_merge($defaults, $args);
        ob_start();
        ?>
        <li id="publishpress-authors-author-<?php
        echo esc_attr($args['term']); ?>" data-term-id="<?php
        echo esc_attr($args['term']); ?>" data-is-guest="<?php
        echo esc_attr($args['is_guest']); ?>" class="ui-sortable-handle publishpress-authors-author">
            <span class="author-remove">
                <span class="dashicons dashicons-no-alt"></span>
            </span>
            <?php
            if (!empty($args['avatar'])) : ?>
                <?php
                echo $args['avatar'];  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php
            endif; ?>
            <span class="display-name"><?php echo esc_html($args['display_name']); ?></span>
            <input type="hidden" name="authors[]" value="<?php echo esc_attr($args['term']); ?>" class="author_term">
            <input type="hidden" name="author_categories[<?php echo esc_attr($args['term']); ?>]" class="author_categories" value="<?php echo esc_attr($args['category_id']); ?>">
        </li>
        <?php
        return ob_get_clean();
    }

    /**
     * Save bulk edit authors via ajax
     */
    public static function save_bulk_edit_authors()
    {
        if (!isset($_POST['post_ids'])) {
            return;
        }

        $post_ids = array_map('sanitize_key', $_POST['post_ids']);
        if (!isset($_POST['bulkEditNonce'])
            || !wp_verify_nonce(sanitize_key($_POST['bulkEditNonce']), 'bulk-edit-nonce')
            || !current_user_can(get_taxonomy('author')->cap->assign_terms)
        ) {
            return;
        }

        $firstPost = get_post($post_ids[0]);
        if (!Utils::is_post_type_enabled($firstPost->post_type)) {
            return;
        }

        $authors = isset($_POST['authors_ids']) ? array_map('sanitize_text_field', $_POST['authors_ids']) : [];
        $author_categories = isset($_POST['author_categories']) ? Utils::sanitizeArray($_POST['author_categories']) : []; // phpcs:ignore WordPress.Security.
        $authors = self::remove_dirty_authors_from_authors_arr($authors);

        $fallbackUserId = isset($_POST['fallback_author_user']) ? (int)$_POST['fallback_author_user'] : null;

        if (!empty($post_ids) && !empty($authors)) {
            foreach ($post_ids as $post_id) {
                Utils::set_post_authors($post_id, $authors, true, $fallbackUserId);
                Utils::set_post_authors($post_id, $authors, true, $fallbackUserId, $author_categories);
            }

            do_action('publishpress_authors_flush_cache_for_post', $post_ids);
        }

        wp_send_json_success(true, 200);
    }

    /**
     * Handle saving of the Author meta box
     *
     * @param int $post_id ID for the post being saved.
     * @param WP_Post $post Object for the post being saved.
     *
     * @return mixed
     */
    public static function action_save_post_authors_metabox($post_id, $post)
    {
        global $wpdb;

        if (!in_array($post->post_type, Content_Model::get_author_supported_post_types(), true)) {
            return;
        }

        if (
            !isset($_POST['authors-save'])
            || !wp_verify_nonce(sanitize_key($_POST['authors-save']), 'authors-save')
            || !current_user_can(get_taxonomy('author')->cap->assign_terms)
        ) {
            return;
        }

        $authors = isset($_POST['authors']) ? Utils::sanitizeArray($_POST['authors']) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $author_categories = isset($_POST['author_categories']) ? Utils::sanitizeArray($_POST['author_categories']) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $authors = self::remove_dirty_authors_from_authors_arr($authors);

        $fallbackUserId = isset($_POST['fallback_author_user']) ? (int)$_POST['fallback_author_user'] : null;
        $disableAuthorBox = isset($_POST['ppma_disable_author_box']) ? (int)$_POST['ppma_disable_author_box'] : 0;

        Utils::set_post_authors($post_id, $authors, true, $fallbackUserId, $author_categories);

        if (isset($_POST['ppma_save_disable_author_box']) && (int)$_POST['ppma_save_disable_author_box'] > 0) {
            update_post_meta($post_id, 'ppma_disable_author_box', $disableAuthorBox);
        }

        do_action('publishpress_authors_post_authors_metabox_action_saved', $post_id);

        do_action('publishpress_authors_flush_cache_for_post', $post_id);
    }

    /**
     * Remove dirty authors from authors array
     *
     * @access private
     *
     * @param array $authors_arr The authors array that should
     *                           be filtered from dirty authors.
     *
     * @return array The filtered authors array
     */
    private static function remove_dirty_authors_from_authors_arr($authors_arr)
    {
        $dirty_authors = $authors_arr;
        $authors       = [];
        foreach ($dirty_authors as $dirty_author) {
            if (is_numeric($dirty_author)) {
                $authors[] = Author::get_by_term_id($dirty_author);
            } elseif ('u' === $dirty_author[0]) {
                $user_id = (int)substr($dirty_author, 1);
                $author  = Author::get_by_user_id($user_id);
                if (!$author) {
                    $author = Author::create_from_user($user_id);
                    if (is_wp_error($author)) {
                        continue;
                    }
                }
                $authors[] = $author;
            }
        }
        return $authors;
    }

    /**
     * Assign a author term when a post is initially created
     *
     * @param int $post_id Post ID.
     * @param WP_Post $post Post object.
     * @param bool $update Whether this is an update.
     */
    public static function action_save_post_set_initial_author($post_id, $post, $update)
    {
        if ($update) {
            return;
        }

        if (!in_array($post->post_type, Content_Model::get_author_supported_post_types(), true)) {
            return;
        }

        $defaultAuthor = false;

        $legacyPlugin           = Factory::getLegacyPlugin();
        $defaultAuthorSetting = isset($legacyPlugin->modules->multiple_authors->options->default_author_for_new_posts) ?
            $legacyPlugin->modules->multiple_authors->options->default_author_for_new_posts : '';

        if (Utils::is_post_type_enabled($post->post_type) && !empty($defaultAuthorSetting)) {
            $defaultAuthor = Author::get_by_term_id($defaultAuthorSetting);
        } elseif ($post->post_author) {
            $defaultAuthor = Author::get_by_user_id($post->post_author);
        }

        /**
         * Filter the default author assigned to the post.
         *
         * @param mixed $defaultAuthor Default author, as calculated by plugin.
         * @param WP_Post $post Post object.
         */
        $defaultAuthor = apply_filters('authors_default_author', $defaultAuthor, $post);

        if (empty($defaultAuthor) || ! is_object($defaultAuthor)) {
            return;
        }

        /*
         * If the user can't edit others posts, we shouldn't set another user as author for the post.
         * That could bring back a bug that blocks them to create new posts.
         */
        if (! current_user_can('edit_others_posts') && ! $defaultAuthor->is_guest()) {
            return;
        }

        Utils::set_post_authors($post_id, [$defaultAuthor]);

        do_action('publishpress_authors_flush_cache_for_post', $post_id);
    }

    public static function remove_core_author_field()
    {
        $postTypes = Content_Model::get_author_supported_post_types();

        foreach ($postTypes as $postType) {
            if (Utils::is_post_type_enabled($postType)) {
                add_filter("rest_prepare_{$postType}", [__CLASS__, 'rest_remove_action_assign_author']);
            }
        }
    }

    /**
     * Filters the post data for a REST API response removing the
     * `wp:action-assign-author` rel from the response so the
     * default post author control doesn't get shown on the block
     * editor post editing screen.
     *
     * Based on code from humanmade/authorship.
     *
     * @param WP_REST_Response $response
     *
     * @return WP_REST_Response
     */
    public static function rest_remove_action_assign_author($response)
    {
        $links = $response->get_links();

        if (isset($links['https://api.w.org/action-assign-author'])) {
            $response->remove_link('https://api.w.org/action-assign-author');
        }

        return $response;
    }

    /**
     * Flush cache
     * @return void
     */
    public static function flush_cache()
    {
        wp_cache_flush_group('get_post_authors');
        wp_cache_flush_group('author_categories_relation_cache');
    }

    /**
     * Flush post cache
     *
     * @param array|integer $post_ids
     *
     * @return array
     */
    public static function flush_post_cache($post_ids = [])
    {
        if (empty($post_ids)) {
            self::flush_cache();
            return;
        }

        if (!is_array($post_ids)) {
            $post_ids = [$post_ids];
        }

        foreach ($post_ids as $post_id) {
            // author categories relation for the post
            $args = [
                'post_id'  => $post_id,
                'author_term_id' => ''
            ];
            $cache_key = 'author_categories_relation_' . md5(serialize($args));
            wp_cache_delete($cache_key, 'author_categories_relation_cache');

            // post authors
            wp_cache_delete($post_id, 'get_post_authors:authors');
        }
    }
}
