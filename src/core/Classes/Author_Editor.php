<?php
/**
 * @package     MultipleAuthors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.1.0
 */

namespace MultipleAuthors\Classes;

use MultipleAuthors\Classes\Legacy\Util;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Factory;
use WP_Error;

/**
 * Class Author_Editor
 *
 * Based on Bylines' class Byline_Editor.
 *
 * @package MultipleAuthors\Classes
 */
class Author_Editor
{

    const AUTHOR_EDITOR_DEFAULT_TAB = 'general';

    /**
     * Customize the term table to look more like the users table.
     *
     * @param array $columns Columns to render in the list table.
     *
     * @return array
     */
    public static function filter_manage_edit_author_columns($columns)
    {
        // Reserve the description for internal use.
        if (isset($columns['description'])) {
            unset($columns['description']);
        }

        // Add our own columns too.
        $new_columns = [];
        foreach ($columns as $key => $title) {
            if ('name' === $key) {
                $new_columns['author_name']       = __('Display name publicly as', 'publishpress-authors');
                $new_columns['author_user_email'] = __('Email', 'publishpress-authors');
                $new_columns['author_type'] = __('Author Type', 'publishpress-authors');
            } else {
                $new_columns[$key] = $title;
            }
        }

        if (isset($new_columns['posts'])) {
            unset($new_columns['posts']);
        }

        return $new_columns;
    }

    /**
     * Set our custom name column as the primary column
     *
     * @return string
     */
    public static function filter_list_table_primary_column()
    {
        return 'author_name';
    }

    /**
     * Render and return custom column
     *
     * @param string $retval Value being returned.
     * @param string $column_name Name of the column.
     * @param int $term_id Term ID.
     */
    public static function filter_manage_author_custom_column($retval, $column_name, $term_id)
    {
        if ('author_name' === $column_name) {
            $author = Author::get_by_term_id($term_id);

            $retval = $author->get_avatar(32);
            $retval .= '<strong><a class="row-title" aria-label="' . $author->display_name . '" href="' . get_edit_term_link(
                    $author->term_id,
                    'author'
                ) . '">' . $author->display_name . '</a>';

            /*if (!$author->is_guest()) {
                $retval .= ' — <span class="post-state">' . __('User', 'publishpress-authors') . '</span>';
            } else {
                $retval .= ' — <span class="post-state">' . __(
                        'Guest Author',
                        'publishpress-authors'
                    ) . '</span>';
            }*/

            $retval .= '</strong>';

            // Inline edit data (quick edit)
            $retval .= '<div class="hidden" id="inline_' . $term_id . '">';
            $retval .= '<div class="name">' . $author->display_name . '</div>';
            $retval .= '<div class="slug">' . $author->slug . '</div>';
            $retval .= '<div class="parent">0</div></div>';
        } elseif ('author_user_email' === $column_name) {
            $author = Author::get_by_term_id($term_id);
            if ($author->user_email) {
                $retval = '<a href="' . esc_url('mailto:' . $author->user_email) . '">' . esc_html(
                        $author->user_email
                    ) . '</a>';
            }
        } elseif ('author_type' === $column_name) {
            $author = Author::get_by_term_id($term_id);

            if (is_object($author) && isset($author->roles) && !empty($author->roles) && in_array('ppma_guest_author', $author->roles)) {
                $retval = __('Guest Author With User Account', 'publishpress-authors');
            } elseif (is_object($author) && isset($author->roles) && !empty($author->roles) && !in_array('ppma_guest_author', $author->roles)) {
                $retval = __('Registered Author With User Account', 'publishpress-authors');
            } else {
                $retval = __('Guest Author With No User Account', 'publishpress-authors');
            }
        }

        return $retval;
    }

    /**
     * Add "Create author" and "Edit author" links for users
     *
     * @param array $actions Existing user action links.
     * @param WP_User $user User object.
     *
     * @return array
     */
    public static function filter_user_row_actions($actions, $user)
    {
        if (is_network_admin()
            || !current_user_can(get_taxonomy('author')->cap->manage_terms)) {
            return $actions;
        }

        // Over hide the string Edit
        if (isset($actions['edit'])) {
            $actions['edit'] = str_replace(
                '>Edit<',
                '>' . __('Edit User', 'publishpress-authors') . '<',
                $actions['edit']
            );
        }

        $new_actions = [];
        $author      = Author::get_by_user_id($user->ID);

        foreach ($actions as $key => $action) {
            $new_actions[$key] = $action;

            if ($key === 'edit') {
                if ($author) {
                    $link                       = get_edit_term_link($author->term_id, 'author');
                    $new_actions['edit-author'] = '<a href="' . esc_url($link) . '">' . esc_html__(
                            'Edit Author Profile',
                            'publishpress-authors'
                        ) . '</a>';
                } else {
                    $args                         = [
                        'action'  => 'author_create_from_user',
                        'user_id' => $user->ID,
                        'nonce'   => wp_create_nonce('author_create_from_user' . $user->ID),
                    ];
                    $link                         = add_query_arg(
                        array_map('rawurlencode', $args),
                        admin_url('admin-ajax.php')
                    );
                    $new_actions['create-author'] = '<a href="' . esc_url($link) . '">' . esc_html__(
                            'Create Author Profile',
                            'publishpress-authors'
                        ) . '</a>';
                }
            }
        }

        return $new_actions;
    }

    /**
     * Add "Edit user" links for authors mapped to user
     *
     * @param array $actions Existing user action links.
     * @param WP_Term $author_term Author term object.
     *
     * @return array
     */
    public static function filter_author_row_actions($actions, $author_term)
    {
        if (is_network_admin()
            || !current_user_can('edit_users')) {
            return $actions;
        }

        if (isset($actions['inline hide-if-no-js'])) {
            unset($actions['inline hide-if-no-js']);
        }

        // Override the string Edit
        if (isset($actions['edit'])) {
            $actions['edit'] = str_replace(
                '>Edit<',
                '>' . __('Edit Author Profile', 'publishpress-authors') . '<',
                $actions['edit']
            );
        }

        $author = Author::get_by_term_id($author_term->term_id);

        if (!empty($author->user_id)) {
            $new_actions = [];

            foreach ($actions as $key => $action) {
                $new_actions[$key] = $action;

                if ($key === 'edit') {
                    $link                     = get_edit_user_link($author->user_id);
                    $new_actions['edit-user'] = '<a href="' . esc_url($link) . '">' . esc_html__(
                            'Edit User',
                            'publishpress-authors'
                        ) . '</a>';
                }
            }

            $actions = $new_actions;
        }

        return $actions;
    }

    /**
     * Render fields tabs for the author profile editor
     *
     * @param WP_Term $term Author term being edited.
     */
    public static function action_author_edit_form_fields_tab($term)
    {
        $author = Author::get_by_term_id($term->term_id);

        /**
         * Filter the fields tabs on the Author's profile.
         *
         * @param array $tabs
         * @param Author $author
         *
         * @return array
         */
        $fields_tabs  = apply_filters('multiple_authors_author_fields_tabs', self::get_fields_tabs($author), $author);

        echo '<h2 class="ppma-editor-tabs nav-tab-wrapper">';

        foreach ($fields_tabs as $key => $args) {
            $active_tab = ($key === self::AUTHOR_EDITOR_DEFAULT_TAB) ? ' nav-tab-active' : '';
        ?>
        <a data-tab="<?php esc_attr_e($key); ?>"
            class="<?php esc_attr_e('tab-link nav-tab' . $active_tab ); ?>"
            href="#"
            >
            <?php esc_html_e($args['label']); ?>
        </a>
        <?php
        }
        echo '</h2>';
    }

    /**
     * Render fields for the author profile editor
     *
     * @param WP_Term $term Author term being edited.
     */
    public static function action_author_edit_form_fields($term)
    {
        $author = Author::get_by_term_id($term->term_id);

        /**
         * Filter the fields on the Author's profile.
         *
         * @param array $fields
         * @param Author $author
         *
         * @return array
         */
        $fields = apply_filters('multiple_authors_author_fields', self::get_fields($author), $author);

        foreach ($fields as $key => $args) {
            $args['key']   = $key;
            $args['value'] = $author->$key;
            echo self::get_rendered_author_partial($args); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        //add validation modal
        Utils::loadThickBoxModal('ppma-required-field-thickbox-botton', 500, 150);

        wp_nonce_field('author-edit', 'author-edit-nonce');
    }

    /**
     * Get the fields tabs to be rendered in the author editor
     *
     * @param Author $author Author to be rendered.
     *
     * @return array
     */
    public static function get_fields_tabs($author)
    {
        $fields_tabs = [
            'general'     => [
                'label'    => __('General', 'publishpress-authors'),
            ],
            'image'  => [
                'label' => __('Avatar', 'publishpress-authors'),
            ],
        ];

        /**
         * Customize fields tabs presented in the author editor.
         *
         * @param array $fields_tabs Existing fields tabs to display.
         * @param Author $author Author to be rendered.
         */
        $fields_tabs = apply_filters('authors_editor_fields_tabs', $fields_tabs, $author);

        return $fields_tabs;
    }

    /**
     * Get the fields to be rendered in the author editor
     *
     * @param Author $author Author to be rendered.
     *
     * @return array
     */
    public static function get_fields($author)
    {
        $legacyPlugin = Factory::getLegacyPlugin();
        $enable_guest_author_user = $legacyPlugin->modules->multiple_authors->options->enable_guest_author_user === 'yes';
        $author_categories = get_ppma_author_categories(['category_status' => 1]);
        $author_category_options = [];
        if (empty($author_categories)) {
            $author_category_options['author'] = esc_html__('Author', 'publishpress-authors');
        } else {
            foreach ($author_categories as $author_category) {
                $author_category_options[$author_category['id']] = $author_category['category_name'];
            }
        }

        $author_boxes_opt_out = $legacyPlugin->modules->multiple_authors->options->author_boxes_opt_out === 'yes';
        $fields = [
            'user_id'     => [
                'label'    => esc_html__('Registered User', 'publishpress-authors'),
                'type'     => 'ajax_user_select',
                'sanitize' => 'intval',
                'tab'      => 'general',
                'requirement' => !$enable_guest_author_user ? 'required' : ''
            ],
            'description' => [
                'label'    => esc_html__('Biographical Info', 'publishpress-authors'),
                'type'     => 'textarea',
                'sanitize' => 'wp_kses_post',
                'tab'      => 'general',
            ],
            'first_name'  => [
                'label' => esc_html__('First Name', 'publishpress-authors'),
                'type'  => 'text',
                'tab'   => 'general',
            ],
            'last_name'   => [
                'label' => esc_html__('Last Name', 'publishpress-authors'),
                'type'  => 'text',
                'tab'   => 'general',
            ],
            'user_email'  => [
                'label'       => esc_html__('Email', 'publishpress-authors'),
                'type'        => 'email',
                'tab'         => 'general',
            ],
            'avatar'      => [
                'label'       => __('Avatar Source', 'publishpress-authors'),
                'type'        => 'avatar',
                'sanitize'    => 'intval',
                'tab'         => 'image',
                'options'     => [
                    'gravatar'     => [
                        'label'         => __('Default Avatar', 'publishpress-authors'),
                        'description'   => sprintf(
                                            esc_html__(
                                                '(Uses the %1s Email field %2s to find the Gravatar account if default avatar is not uploaded in Authors Settings)',
                                                'publishpress-authors'
                                            ),
                                            '<a href="#" class="ppma-image-general-author-focus">', '</a>'
                                        )
                    ],
                    'custom_image'  => [
                        'label'         => __('Custom image', 'publishpress-authors'),
                    ],
                ],
            ],
            'user_url'    => [
                'label'    => esc_html__('Website', 'publishpress-authors'),
                'type'     => 'url',
                'sanitize' => 'esc_url_raw',
                'tab'      => 'general',
            ],
            'author_category'    => [
                'label'    => esc_html__('Author Category', 'publishpress-authors'),
                'description' => esc_html__('Select the default Category for this Author. This will be used when the Author is added to new posts.', 'publishpress-authors'),
                'type'     => 'select',
                'options'  => $author_category_options,
                'sanitize' => 'intval',
                'tab'      => 'general',
            ],
            'exclude_author'    => [
                'label'    => esc_html__('Do not show this profile publicly', 'publishpress-authors'),
                'type'     => 'checkbox',
                'sanitize' => 'sanitize_text_field',
                'tab'      => 'general',
            ],
        ];

        if (!$author_boxes_opt_out) {
            unset($fields['exclude_author']);
        }

        /**
         * Customize fields presented in the author editor.
         *
         * @param array $fields Existing fields to display.
         * @param Author $author Author to be rendered.
         */
        $fields = apply_filters('authors_editor_fields', $fields, $author);

        //Move author_category to the bottom if not filtered out
        if (isset($fields['author_category'])) {
            $author_category_field = [
                'author_category' => $fields['author_category']
            ];
            unset($fields['author_category']);
            $fields = array_merge($fields, $author_category_field);
        }
        if (isset($fields['exclude_author'])) {
            $exclude_author_field = [
                'exclude_author' => $fields['exclude_author']
            ];
            unset($fields['exclude_author']);
            $fields = array_merge($fields, $exclude_author_field);
        }

        return $fields;
    }

    /**
     * Get a rendered field partial
     *
     * @param array $args Arguments to render in the partial.
     */
    private static function get_rendered_author_partial($args)
    {
        $defaults = [
            'type'        => 'text',
            'tab'         => self::AUTHOR_EDITOR_DEFAULT_TAB,
            'options'     => [],
            'value'       => '',
            'label'       => '',
            'requirement' => '',
            'description' => '',
        ];
        $args      = array_merge($defaults, $args);
        $key       = 'authors-' . $args['key'];
        $tab_class = 'ppma-tab-content ppma-' . $args['tab'] . '-tab';
        $tab_style = ($args['tab'] === self::AUTHOR_EDITOR_DEFAULT_TAB) ? '' : 'display:none;';
        if ($args['requirement'] === 'required') {
            $required  = true;
            $tab_class .= ' required-tab form-required';
        } else {
            $required  = false;
        }
        ob_start();
        ?>
        <tr
            class="<?php echo esc_attr('form-field term-' . $key . '-wrap '. $tab_class); ?>"
            data-tab="<?php echo esc_attr($args['tab']); ?>"
            style="<?php echo esc_attr($tab_style); ?>"
            >
            <th scope="row">
                <?php if (!empty($args['label'])) : ?>
                    <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($args['label']); ?></label>
                    <?php if ($required) : ?>
                        <span class="required">*</span>
                    <?php endif; ?>
                <?php endif; ?>
            </th>
            <td>
                <?php
                if ('image' === $args['type']) :
                    $author_image = wp_get_attachment_image_url($args['value'], 'thumbnail');
                    ?>
                    <div class="author-image-field-wrapper">
                        <div class="author-image-field-container">
                            <?php if ($author_image) : ?>
                                <img src="<?php echo esc_url($author_image); ?>" alt=""/>
                            <?php endif; ?>
                        </div>
                        <p class="hide-if-no-js">
                            <a class="select-author-image-field <?php echo $author_image ? 'hidden' : ''; ?>" href="#">
                                <?php esc_html_e('Select image', 'publishpress-authors'); ?>
                            </a>
                            <a class="delete-author-image-field <?php echo !$author_image ? 'hidden' : ''; ?>"
                               href="#">
                                <?php esc_html_e('Remove this image', 'publishpress-authors'); ?>
                            </a>
                        </p>
                        <input name="<?php echo esc_attr($key); ?>" class="author-image-field-id" type="hidden"
                               value="<?php echo esc_attr($args['value']); ?>"/>
                    </div>
                <?php
                elseif ('avatar' === $args['type']) :

                    $author_image = wp_get_attachment_image_url($args['value'], 'thumbnail');
                    $avatar_options = $args['options'];

                    if ($author_image) {
                        $checked_option    = 'custom_image';
                        $image_field_style = '';
                    } else {
                        $checked_option    = 'gravatar';
                        $image_field_style = 'display:none;';
                    }
                    ?>

                    <?php foreach ($avatar_options as $avatar_option_key => $avatar_option_data) { ?>
                        <p>
                            <input name="<?php echo esc_attr($key.'-options'); ?>" type="radio"
                                value="<?php echo esc_attr($avatar_option_key); ?>"
                                id="<?php echo esc_attr($avatar_option_key.'-'.$key.'-options'); ?>"
                                <?php checked($avatar_option_key, $checked_option); ?> />

                            <label for="<?php echo esc_attr($avatar_option_key.'-'.$key.'-options'); ?>">
                                <?php echo esc_html($avatar_option_data['label']); ?>
                                <?php if (isset($avatar_option_data['description'])) : ?>
                                    <span class="description"><?php echo $avatar_option_data['description']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                <?php endif; ?>
                            </label>
                        </p>
                    <?php } ?>

                    <div class="author-image-field-wrapper" style="<?php echo esc_attr($image_field_style); ?>">
                        <div class="author-image-field-container">
                            <?php if ($author_image) : ?>
                                <img src="<?php echo esc_url($author_image); ?>" alt=""/>
                            <?php endif; ?>
                        </div>
                        <p class="hide-if-no-js">
                            <a class="select-author-image-field <?php echo $author_image ? 'hidden' : ''; ?>" href="#">
                                <?php _e('Select image', 'publishpress-authors'); ?>
                            </a>
                            <a class="delete-author-image-field <?php echo !$author_image ? 'hidden' : ''; ?>"
                               href="#">
                                <?php _e('Remove this image', 'publishpress-authors'); ?>
                            </a>
                        </p>
                        <input name="<?php echo esc_attr($key); ?>" class="author-image-field-id" type="hidden"
                               value="<?php echo esc_attr($args['value']); ?>"/>
                    </div>
                <?php elseif ('textarea' === $args['type']) : ?>
                    <textarea
                            name="<?php echo esc_attr($key); ?>"><?php echo esc_textarea($args['value']); ?></textarea>
                <?php
                elseif ('ajax_user_select' === $args['type']) :
                    $user = !empty($args['value']) ? get_user_by('id', $args['value']) : false;
                    ?>
                    <select data-nonce="<?php echo esc_attr(wp_create_nonce('authors-user-search')); ?>"
                            placeholder="<?php esc_attr_e('Select a user', 'publishpress-authors'); ?>"
                            class="authors-select2-user-select" name="<?php echo esc_attr($key); ?>" style="width: 95%">
                        <option></option>
                        <?php if ($user) : ?>
                            <option value="<?php echo (int)$user->ID; ?>"
                                    selected="selected"><?php echo esc_html($user->display_name); ?></option>
                        <?php endif; ?>
                    </select>
                <?php elseif ('button_group' === $args['type']) :
                    $button_options = $args['options'];
                    $selected_button = !empty($args['value']) ? $args['value'] : 'existing_user'
                    ?>
                    <div class="ppma-group-wrap">
                        <div class="ppma-button-group">
                            <?php
                            $group_description = '';
                            foreach ($button_options as $group => $group_option) :
                                $group_option_style = $group === $selected_button ? '' : 'display: none;';
                                $group_description .= '<p class="ppma-button-description description '. esc_attr($group) .'" style="'. $group_option_style .'">'. $group_option['description'] .'</p>';
                            ?>
                                <label class="<?php echo $group === $selected_button ? 'selected' : ''; ?>">
                                    <input type="radio" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($group); ?>" <?php checked($group, $selected_button) ?>>
                                    <?php echo esc_attr($group_option['label']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php echo $group_description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php elseif ('wysiwyg' === $args['type']) : ?>
                    <?php wp_editor($args['value'], $key, []); ?>
                <?php elseif ('checkbox' === $args['type']) :
                    $checked = !empty($args['value']);
                    ?>
                    <input name="<?php echo esc_attr($key); ?>" type="<?php echo esc_attr($args['type']); ?>"
                           id="<?php echo esc_attr($key); ?>" value="1" <?php checked($checked, true); ?>/>

                <?php elseif ('select' === $args['type']) :
                    ?>
                    <select name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>"/>
                    <?php foreach ($args['options'] as $option_value => $option_text) :
                     ?>
                        <option value="<?php echo esc_attr($option_value); ?>" <?php selected($option_value, $args['value']); ?>><?php echo esc_html($option_text); ?></option>
                    <?php endforeach; ?>
                    </select>
                <?php else : ?>
                    <input name="<?php echo esc_attr($key); ?>" type="<?php echo esc_attr($args['type']); ?>"
                           id="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($args['value']); ?>"/>
                <?php endif; ?>

                <?php if (!empty($args['description'])) : ?>
                    <p class="description"><?php echo esc_html($args['description']); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle saving of term meta
     *
     * @param int $term_id ID for the term being edited.
     */
    public static function action_edited_author($term_id)
    {
        if (empty($_POST['author-edit-nonce'])
            || !is_user_logged_in()
            || !wp_verify_nonce(sanitize_key($_POST['author-edit-nonce']), 'author-edit')) {
            return;
        }
        $author = Author::get_by_term_id($term_id);
        $updated_args = [];

        $user_id = false;
        $user    = false;
        if (isset($_POST['authors-user_id']) && !empty($_POST['authors-user_id'])) {
            $user_id = (int)$_POST['authors-user_id'];
            $user = get_user_by('id', $user_id);
            if (!is_a($user, 'WP_User')) {
                $user = false;
                $user_id = false;
            }
        }

        /**
         * Make sure current user is set as user ID if user does not
         * have capability to edit other authors/users.
         *
         * Note: Prevent ability to edit administrator completely.
         */
        if ($user && (int)$user_id !== get_current_user_id()) {
            // Prevent editing administrators completely
            if (in_array('administrator', $user->roles)) {
                $user_id = false;
                $user = false;
            }
            // Check if the user lacks the necessary capabilities and prevent the user update
            elseif (!current_user_can(get_taxonomy('author')->cap->manage_terms)
                    || !current_user_can('edit_user', $user_id)) {
                $user_id = false;
                $user = false;
            }
        }

        if ($user) {
            $updated_args['ID'] = $user_id;
        }

        foreach (self::get_fields($author) as $key => $args) {
            if (!isset($_POST['authors-' . $key]) && $args['type'] !== 'checkbox') {
                continue;
            }
            $sanitize = isset($args['sanitize']) ? $args['sanitize'] : 'sanitize_text_field';
            if ($key == 'user_id') {
                $field_value = $user_id;
            } else {
                $field_value = isset($_POST['authors-' . $key]) ? $sanitize($_POST['authors-' . $key]) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            }
            update_term_meta($term_id, $key, $field_value);
            if ($user_id) {
                update_user_meta($user_id, $key, $field_value);
                $updated_args[$key] = $field_value;
            }

            if (in_array($args['type'], ['text', 'textarea'])) {
                /**
                 * Register strings for wpml translation
                 */
                do_action('wpml_register_single_string', 'PublishPress Authors', '#'.$term_id.' '.$args['label'], $sanitize($_POST['authors-' . $key]));
            }
        }

        // If there is a mapped user, make sure the author url (slug) is the same of the user.
        if ($user) {

            if (count($updated_args) > 1) {
                if (isset($_POST['name'])) {
                    $updated_args['display_name'] = sanitize_text_field($_POST['name']);
                }
                wp_update_user($updated_args);
            }

            // Do they have the same slug and nicename?
            if ($author->slug !== $user->user_nicename) {
                global $wpdb;

                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->terms} SET slug=%s WHERE term_id = %d",
                        $user->user_nicename,
                        $term_id
                    )
                );
            }
        }
    }

    /**
     * @param $user_id
     */
    public static function action_user_register($user_id)
    {
        $legacyPlugin = Factory::getLegacyPlugin();

        if (!isset($legacyPlugin->modules->multiple_authors)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(
                    sprintf(
                        "[PublishPress Authors] Warning: Module multiple_authors not loaded. %s [user_id=\"%s\"]\n  - %s",
                        __METHOD__,
                        $user_id,
                        implode("\n  - ", wp_debug_backtrace_summary(null, 0, false))
                    )
                );
            }

            return;
        }

        $roles = (array)$legacyPlugin->modules->multiple_authors->options->author_for_new_users;

        // Check if we have any role selected to create an author for the new user.
        if (empty($roles)) {
            return;
        }

        $user = get_user_by('ID', $user_id);

        if (is_array($user->roles) && !empty(array_intersect($roles, $user->roles))) {
            // Create author for this user
            Author::create_from_user($user_id);
        }
    }

    public static function action_new_form_tag()
    {
        // Close the form tag.
        echo '>';

        $legacyPlugin = Factory::getLegacyPlugin();
        $enable_guest_author_user = $legacyPlugin->modules->multiple_authors->options->enable_guest_author_user === 'yes';
        $enable_guest_author_acount = $legacyPlugin->modules->multiple_authors->options->enable_guest_author_acount === 'yes';

        $author_type_options = [];
        $author_type_options['existing_user'] = [
            'label'   => esc_html__('Registered Author With User Account', 'publishpress-authors'),
            'description' => esc_html__('Create an author profile for a current user account. You can select users who are allowed to edit posts. This user will be able to update their profile.', 'publishpress-authors'),
        ];

        if ($enable_guest_author_acount) {
            $author_type_options['new_user'] = [
                'label'   => esc_html__('Guest Author With User Account', 'publishpress-authors'),
                'description' => esc_html__('Create an author profile and a linked user account. This account will be in the "Guest Author" role and will not be able to login to the WordPress dashboard or update their profile.', 'publishpress-authors'),
            ];
        }

        if ($enable_guest_author_user) {
            $author_type_options['guest_author'] = [
                'label'   => esc_html__('Guest Author With No User Account', 'publishpress-authors'),
                'description' => esc_html__('Create an author profile with no linked user account. This option is lightweight but may have compatibility issues with other themes and plugins.', 'publishpress-authors'),
            ];
        }
        ?>
        <div>
            <div class="form-field term-author_type-wrap">
                <label class="ppma-account-type"></label>
                <?php
                echo static::get_rendered_author_partial( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    [
                        'type'        => 'button_group',
                        'value'       => '',
                        'key'         => 'author_type',
                        'options'     => $author_type_options,
                    ]
                );
            echo '</div>';
            ?>
            <div class="form-field term-author_email-wrap" style="display: none;">
                <label class="ppma-account-email"><?php echo esc_html__('Author Email', 'publishpress-authors'); ?> <span class="required">*</span></label>
                <?php
                echo static::get_rendered_author_partial( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    [
                        'type'        => 'email',
                        'value'       => '',
                        'key'         => 'author_email',
                    ]
                );
            echo '</div>';
            ?>
            <div class="form-field term-user_id-wrap">
                <label for="tag-user-id"><?php echo esc_html__('User Account', 'publishpress-authors'); ?> <span class="required">*</span></label>
                <?php
                echo static::get_rendered_author_partial( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    [
                        'type'        => 'ajax_user_select',
                        'value'       => '',
                        'key'         => 'new',
                    ]
                );
            echo '</div>';

        // It is missing the end of the tag by purpose, because there is a hardcoded > after the action is called.
        echo '</div';
    }

    /**
     * Filter the term data before add to the database. Used to make sure authors and mapped users have the same name
     * and slug when inserting.
     *
     * @param $data
     * @param $taxonomy
     * @param $args
     *
     * @return array
     */
    public static function filter_insert_term_data($data, $taxonomy, $args)
    {
        if ($taxonomy !== 'author' || !isset($args['authors-new']) || empty($args['authors-new'])) {
            return $data;
        }

        $user_id = (int)$args['authors-new'];

        $user = get_user_by('id', $user_id);

        $data['slug'] = $user->user_nicename;

        return $data;
    }

    /**
     * Called after create an author to check if we need to get properties from user, in case we have a mapped user.
     *
     * @param $term_id
     */
    public static function action_created_author($term_id)
    {
        if (!isset($_POST['authors-new']) || empty($_POST['authors-new'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return;
        }

        Author::update_author_from_user($term_id, (int)$_POST['authors-new']); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    }

    /**
     * Add bulk actions to the list of authors.
     *
     * @param $bulk_actions
     *
     * @return array
     */
    public static function filter_author_bulk_actions($bulk_actions)
    {
        $bulk_actions['update_mapped_author_data'] = __(
            'Sync author and user fields',
            'publishpress-authors'
        );
        $bulk_actions['convert_into_guest_author'] = __(
            'Convert to Guest Author With User Account',
            'publishpress-authors'
        );
        $bulk_actions['update_post_count'] = __(
            'Update post count',
            'publishpress-authors'
        );

        return $bulk_actions;
    }

    /**
     * Handle bulk actions from authors.
     *
     * @param string $redirect_to
     * @param string $do_action
     * @param array $terms_ids
     *
     * @return mixed
     */
    public static function handle_author_bulk_actions($redirect_to, $do_action, $terms_ids)
    {
        $bulkActions = [
            'update_mapped_author_data',
            'convert_into_guest_author',
            'update_post_count',
        ];

        if (empty($terms_ids) || !in_array($do_action, $bulkActions, true)) {
            return $redirect_to;
        }

        $updated = 0;

        foreach ($terms_ids as $term_id) {
            if ($do_action === 'update_mapped_author_data') {
                $author = Author::get_by_term_id($term_id);

                if (empty($author->user_id)) {
                    continue;
                }

                Author::update_author_from_user($term_id, $author->user_id);
            } elseif ($do_action === 'convert_into_guest_author') {
                Author::convert_into_guest_author($term_id);
            } elseif ($do_action === 'update_post_count') {
                wp_update_term_count($term_id, 'author');
            }

            $updated++;
        }

        $redirect_to = add_query_arg('bulk_update_author', $updated, $redirect_to);

        return $redirect_to;
    }

    /**
     * Show admin notices
     */
    public static function admin_notices()
    {
        if (!empty($_REQUEST['bulk_update_author'])) {
            $count = (int)$_REQUEST['bulk_update_author'];

            echo '<div id="message" class="updated fade"><p>';

            if (empty($count)) {
                esc_html__('No authors were updated', 'publishpress-authors');
            } else {
                printf(
                        esc_html__('Updated %d authors', 'publishpress-authors'),
                        esc_html($count)
                );
            }

            echo '</p></div>';
        }
    }

    /**
     * Author term mapped limit validation
     *
     * @param string|WP_Error $term The term name to add, or a WP_Error object if there's an error.
     * @param string $taxonomy Taxonomy slug.
     *
     * @return array|WP_Error
     */
    public static function filter_pre_insert_term($term, $taxonomy)
    {
        if ($taxonomy === 'author' && !empty($_POST['action']) && $_POST['action'] === 'add-tag') {

            $legacyPlugin = Factory::getLegacyPlugin();
            $author_id = (int)$_POST['authors-new'];
            $enable_guest_author_user = $legacyPlugin->modules->multiple_authors->options->enable_guest_author_user === 'yes';
            $author_type = !empty($_POST['authors-author_type']) ? sanitize_text_field($_POST['authors-author_type']) : '';
            /**
             * Check if term with this user exist
             */
            if (isset($_POST['authors-new'])
                && (int)$_POST['authors-new'] > 0
            ) {
                $author    = Author::get_by_user_id($author_id);
                $remove_single_user_map_restriction = publishpress_authors_remove_single_user_map_restriction();

                if (!$remove_single_user_map_restriction
                    && $author
                    && is_object($author)
                    && isset($author->term_id)
                    && (int)$author->term_id > 0
                ) {
                    return new WP_Error(
                        'publishpress_authors_duplicate_mapped_user',
                        esc_html__(
                            'Sorry, this WordPress user is already mapped to another Author. By default, each user can only be connected to one Author profile.',
                            'publishpress-authors'
                        )
                    );
                }
            }

            if (empty($_POST['slug'])) {
                $slug = sanitize_title($_POST['tag-name']);
            } else {
                $slug = sanitize_title($_POST['slug']);
            }
            /**
             * Check if user with term slug already exists
             */
            $author_slug_user = get_user_by('slug', $slug);
            if ($author_slug_user
                && is_object($author_slug_user)
                && isset($author_slug_user->ID)
            ) {
                if ((! isset($_POST['authors-new']))
                    || ((int)$author_slug_user->ID != (int)$_POST['authors-new'])
                ) {
                    return new WP_Error(
                        'publishpress_authors_slug_exists',
                        esc_html__(
                            'An author with the name provided already exists.',
                            'publishpress-authors'
                        )
                    );
                }
            }

            /**
             * Check if author with term slug already exists
             */
            $author_slug_term = get_term_by('slug', $slug, $taxonomy);
            if (isset($_POST['authors-new'])
                && is_object($author_slug_term)
                && isset($author_slug_term->term_id)
            ) {
                return new WP_Error(
                    'publishpress_authors_slug_exists',
                    esc_html__(
                        'An author with the name provided already exists.',
                        'publishpress-authors'
                    )
                );
            }

            // create guest author user
            if ($author_type === 'new_user') {
                if (empty($_POST['authors-author_email'])) {
                    return new WP_Error(
                        'publishpress_authors_email_required',
                        esc_html__(
                            'An author email is required when creating new User Author.',
                            'publishpress-authors'
                        )
                    );
                } else {
                    if (!get_role('ppma_guest_author')) {
                        //Make sure Guest authir role exist
                        add_role('ppma_guest_author', 'Guest Author', []);
                    }

                    // Add new user
                    $user_data = array(
                        'user_login'    => $slug,
                        'display_name'  => sanitize_text_field($_POST['tag-name']),
                        'user_email'    => sanitize_text_field($_POST['authors-author_email']),
                        'user_pass'     => wp_generate_password(),
                        'role'          => 'ppma_guest_author',
                    );
                    $user_id = wp_insert_user($user_data);

                    if (is_wp_error($user_id)) {
                        return new WP_Error(
                            'publishpress_authors_new_user_error',
                            $user_id->get_error_message()
                        );
                    } else {
                        $author_id = $user_id;
                        $_POST['authors-new'] = $author_id;
                    }
                }
            }

            if ((!$enable_guest_author_user || $author_type === 'existing_user') && $author_id === 0) {
                return new WP_Error(
                    'publishpress_authors_mapped_user_required',
                    esc_html__(
                        'Registered User is required.',
                        'publishpress-authors'
                    )
                );
            }
        }

        return $term;
    }
}
