<?php
/**
 * @package PublishPress Authors Pro
 * @author  PublishPress
 *
 * Copyright (C) 2018 PublishPress
 *
 * This file is part of PublishPress Authors Pro
 *
 * PublishPress Authors Pro is free software: you can redistribute it
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
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;
use MultipleAuthors\CustomFieldsModel;

/**
 * class MA_Author_Custom_Fields
 */
class MA_Author_Custom_Fields extends Module
{
    /**
     * Post Type.
     */
    const POST_TYPE_CUSTOM_FIELDS = 'ppmacf_field';

    /**
     * Meta data prefix.
     */
    const META_PREFIX = 'ppmacf_';

    public $module_name = 'author_custom_fields';

    /**
     * Instance of the module
     *
     * @var stdClass
     */
    public $module;
    public $module_url;

    /**
     * Construct the MA_Multiple_Authors class
     */
    public function __construct()
    {
        $this->module_url = $this->get_module_url(__FILE__);

        // Register the module with PublishPress
        $args = [
            'title' => __('Author Fields', 'publishpress-authors'),
            'short_description' => __(
                'Add support for custom fields in the author profiles.',
                'publishpress-authors'
            ),
            'extended_description' => __(
                'Add support for custom fields in the author profiles.',
                'publishpress-authors'
            ),
            'module_url' => $this->module_url,
            'icon_class' => 'dashicons dashicons-edit',
            'slug' => 'author-custom-fields',
            'default_options' => [
                'enabled' => 'on',
            ],
            'options_page' => false,
            'autoload' => true,
        ];

        // Apply a filter to the default options
        $args['default_options'] = apply_filters('MA_Author_Custom_Fields_default_options', $args['default_options']);

        $legacyPlugin = Factory::getLegacyPlugin();

        $this->module = $legacyPlugin->register_module($this->module_name, $args);

        parent::__construct();
    }

    /**
     * Initialize the module. Conditionally loads if the module is enabled
     */
    public function init()
    {
        add_action('add_meta_boxes', [$this, 'addBannerMetabox']);
        add_action('multiple_authors_admin_submenu', [$this, 'adminSubmenu'], 50);
        add_filter('post_updated_messages', [$this, 'setPostUpdateMessages']);
        add_filter('bulk_post_updated_messages', [$this, 'setPostBulkUpdateMessages'], 10, 2);
        add_action('cmb2_admin_init', [$this, 'renderMetaboxes']);
        add_filter('multiple_authors_author_fields', [$this, 'filterAuthorFields'], 10, 2);
        add_action('created_author', [$this, 'saveTermCustomField']);
        add_action('edited_author', [$this, 'saveTermCustomField']);
        add_filter('cmb2_field_new_value', [$this, 'sanitizeFieldName'], 10, 3);
        add_filter('cmb2_override_' . self::META_PREFIX . 'slug_meta_remove', [$this, 'removePostCustomFieldSlug']);
        add_filter(
            'cmb2_override_' . self::META_PREFIX . 'slug_meta_value',
            [$this, 'overridePostSlugMetaValue'],
            10,
            2
        );
        add_filter('cmb2_override_' . self::META_PREFIX . 'slug_meta_save', [$this, 'overridePostSlugMetaSave'], 10, 2);
        add_filter('pp_multiple_authors_author_properties', [$this, 'filterAuthorProperties']);
        add_filter('pp_multiple_authors_author_attribute', [$this, 'filterAuthorAttribute'], 10, 3);
        add_filter('manage_edit-' . self::POST_TYPE_CUSTOM_FIELDS . '_columns', [$this, 'filterFieldColumns']);
        add_action(
            'manage_' . self::POST_TYPE_CUSTOM_FIELDS . '_posts_custom_column',
            [$this, 'manageFieldColumns'],
            10,
            2
        );
        add_filter('wp_unique_post_slug', [$this, 'fixPostSlug'], 10, 4);
        add_action('admin_head', [$this, 'addInlineScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_action('wp_ajax_author_custom_fields_save_order', [$this, 'handle_ajax_update_field_order']);
        add_action('pre_get_posts', [$this, 'filter_custom_fields_query']);
        add_filter('parent_file', [$this, 'setParentFile']);
        // Add promo virtual fields
        add_filter('the_posts', [$this, 'add_virtual_social_fields'], 10, 2);

        $this->registerPostType();
    }

    /**
     * Set authors menu as parent for post type so menu is shown
     * as active when on post type edit screen.
     *
     * @param string $parent_file
     *
     * @return string
     */
    public function setParentFile($parent_file)
    {
        global $submenu_file, $current_screen;

        // Check if the current screen is the User Code page
       if (!empty($current_screen->post_type) && $current_screen->post_type == self::POST_TYPE_CUSTOM_FIELDS) {
            $parent_file = \MA_Multiple_Authors::MENU_SLUG;
            $submenu_file = 'edit.php?post_type=' . self::POST_TYPE_CUSTOM_FIELDS;
        }

        return $parent_file;
    }

    /**
     * Register the post types.
     */
    private function registerPostType()
    {
        $labelSingular = __('Author Field', 'publishpress-authors');
        $labelPlural = __('Author Fields', 'publishpress-authors');

        $postTypeLabels = [
            'name' => _x('%2$s', 'Custom Field post type name', 'publishpress-authors'),
            'singular_name' => _x(
                '%1$s',
                'singular custom field post type name',
                'publishpress-authors'
            ),
            'add_new' => __('New %1s', 'publishpress-authors'),
            'add_new_item' => __('Add New %1$s', 'publishpress-authors'),
            'edit_item' => __('Edit %1$s', 'publishpress-authors'),
            'new_item' => __('New %1$s', 'publishpress-authors'),
            'all_items' => __('%2$s', 'publishpress-authors'),
            'view_item' => __('View %1$s', 'publishpress-authors'),
            'search_items' => __('Search %2$s', 'publishpress-authors'),
            'not_found' => __('No %2$s found', 'publishpress-authors'),
            'not_found_in_trash' => __('No %2$s found in Trash', 'publishpress-authors'),
            'parent_item_colon' => '',
            'menu_name' => _x('%2$s', 'custom field post type menu name', 'publishpress-authors'),
            'featured_image' => __('%1$s Image', 'publishpress-authors'),
            'set_featured_image' => __('Set %1$s Image', 'publishpress-authors'),
            'remove_featured_image' => __('Remove %1$s Image', 'publishpress-authors'),
            'use_featured_image' => __('Use as %1$s Image', 'publishpress-authors'),
            'filter_items_list' => __('Filter %2$s list', 'publishpress-authors'),
            'items_list_navigation' => __('%2$s list navigation', 'publishpress-authors'),
            'items_list' => __('%2$s list', 'publishpress-authors'),
        ];

        foreach ($postTypeLabels as $labelKey => $labelValue) {
            $postTypeLabels[$labelKey] = sprintf($labelValue, $labelSingular, $labelPlural);
        }

        $postTypeArgs = [
            'labels' => $postTypeLabels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'map_meta_cap' => true,
            'has_archive' => self::POST_TYPE_CUSTOM_FIELDS,
            'hierarchical' => false,
            'rewrite' => false,
            'supports' => ['title', 'slug'],
        ];
        register_post_type(self::POST_TYPE_CUSTOM_FIELDS, $postTypeArgs);
    }

    /**
     * Add the admin submenu.
     */
    public function adminSubmenu()
    {
        $legacyPlugin = Factory::getLegacyPlugin();

        // Add the submenu to the PublishPress menu.
        add_submenu_page(
            MA_Multiple_Authors::MENU_SLUG,
            esc_html__('Author Fields', 'publishpress-authors'),
            esc_html__('Author Fields', 'publishpress-authors'),
            apply_filters('pp_multiple_authors_manage_custom_fields_cap', 'ppma_manage_custom_fields'),
            'edit.php?post_type=' . self::POST_TYPE_CUSTOM_FIELDS
        );
    }

    /**
     * Enqueue Admin Scripts
     *
     * @return void
     */
    public function enqueueAdminScripts()
    {
        global $pagenow, $post_type;

        if (! in_array($pagenow, ['edit.php'])
            || $post_type !== self::POST_TYPE_CUSTOM_FIELDS
        ) {
            return;
        }

        $moduleAssetsUrl = PP_AUTHORS_URL . 'src/modules/author-custom-fields/assets';

        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_script(
            'author-custom-fields-js',
            $moduleAssetsUrl . '/js/author-custom-fields.js',
            [
                'jquery',
                'jquery-ui-sortable'
            ],
            PP_AUTHORS_VERSION
        );

        $localized_data = [
            'nonce'     => wp_create_nonce('author-custom-fields-request-nonce')
        ];

        wp_localize_script('author-custom-fields-js', 'authorCustomFields', $localized_data);

        wp_enqueue_style(
            'author-custom-fields-css',
            $moduleAssetsUrl . '/css/author-custom-fields.css',
            [],
            PP_AUTHORS_VERSION
        );
    }

    /**
     * Add custom update messages to the post_updated_messages filter flow.
     *
     * @param array $messages Post updated messages.
     *
     * @return  array   $messages
     */
    public function setPostUpdateMessages($messages)
    {
        $messages[self::POST_TYPE_CUSTOM_FIELDS] = [
            1 => __('Custom Field updated.', 'publishpress-authors'),
            4 => __('Custom Field updated.', 'publishpress-authors'),
            6 => __('Custom Field published.', 'publishpress-authors'),
            7 => __('Custom Field saved.', 'publishpress-authors'),
            8 => __('Custom Field submitted.', 'publishpress-authors'),
        ];

        return $messages;
    }

    /**
     * Add custom update messages to the bulk_post_updated_messages filter flow.
     *
     * @param array $messages Array of messages.
     * @param array $counts Array of item counts for each message.
     *
     * @return  array   $messages
     */
    public function setPostBulkUpdateMessages($messages, $counts)
    {
        $countsUpdated = (int)$counts['updated'];
        $countsLocked = (int)$counts['locked'];
        $countsDeleted = (int)$counts['deleted'];
        $countsTrashed = (int)$counts['trashed'];
        $countsUntrashed = (int)$counts['untrashed'];

        $postTypeNameSingular = __('Custom Field', 'publishpress-authors');
        $postTypeNamePlural = __('Custom Fields', 'publishpress-authors');

        $messages[self::POST_TYPE_CUSTOM_FIELDS] = [
            'updated' => sprintf(
                _n('%1$s %2$s updated.', '%1$s %3$s updated.', $countsUpdated),
                $countsUpdated,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
            'locked' => sprintf(
                _n(
                    '%1$s %2$s not updated, somebody is editing it.',
                    '%1$s %3$s updated, somebody is editing them.',
                    $countsLocked
                ),
                $countsLocked,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
            'deleted' => sprintf(
                _n('%1$s %2$s permanently deleted.', '%1$s %3$s permanently deleted.', $countsDeleted),
                $countsDeleted,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
            'trashed' => sprintf(
                _n('%1$s %2$s moved to the Trash.', '%1$s %3$s moved to the Trash.', $countsTrashed),
                $countsTrashed,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
            'untrashed' => sprintf(
                _n('%1$s %2$s restored from the Trash.', '%1$s %3$s restored from the Trash.', $countsUntrashed),
                $countsUntrashed,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
        ];

        return $messages;
    }

    /**
     * Render che Custom Field admin page.
     */
    public function renderMetaboxes()
    {
        if (!Utils::isAuthorsProActive()
            && isset($_GET['post_type'])
            && $_GET['post_type'] === self::POST_TYPE_CUSTOM_FIELDS
        ) {
            return;
        }

        $metabox = new_cmb2_box(
            [
                'id' => self::META_PREFIX . 'details',
                'title' => __('Details', 'publishpress-authors'),
                'object_types' => [self::POST_TYPE_CUSTOM_FIELDS],
                'context' => 'normal',
                'priority' => 'high',
                'show_names' => true,
            ]
        );

        $metabox->add_field(
            [
                'name' => __('Field Status', 'publishpress-authors'),
                'id' => self::META_PREFIX . 'field_status',
                'type' => 'select',
                'options' => CustomFieldsModel::getFieldStatus(),
            ]
        );

        $metabox->add_field(
            [
                'name' => __('Field Slug', 'publishpress-authors'),
                'id' => self::META_PREFIX . 'slug',
                'type' => 'text',
                'desc' => __(
                    'The slug is used in code to reference this author field. It is all lowercase and contains only letters, numbers, and hyphens.',
                    'publishpress-authors'
                ),
            ]
        );

        $metabox->add_field(
            [
                'name' => __('Field Type', 'publishpress-authors'),
                'id' => self::META_PREFIX . 'type',
                'type' => 'select',
                'options' => CustomFieldsModel::getFieldTypes(),
            ]
        );

        $metabox->add_field(
            [
                'name' => __('Social Profile?', 'publishpress-authors'),
                'id' => self::META_PREFIX . 'social_profile',
                'type' => 'select',
                'options' => CustomFieldsModel::getFieldSocialProfile(),
                'desc' => __(
                    'This feature will add the SameAs property to this link so that search engines realize that the social profile is connected to this author.',
                    'publishpress-authors'
                ),
            ]
        );

        $metabox->add_field(
            [
                'name' => __('Schema Property', 'publishpress-authors'),
                'id' => self::META_PREFIX . 'schema_property',
                'type' => 'text',
                'desc' => sprintf(
                    esc_html__(
                        'Add a Schema.org property to this field. Examples include alumniOf, worksFor, and birthPlace. %1$sClick here for documentation.%2$s',
                        'publishpress-authors'
                    ),
                    '<a target="_blank" href="https://publishpress.com/knowledge-base/author-fields-schema/">',
                    '</a>'
                ),
            ]
        );

        $metabox->add_field(
            [
                'name' => __('Open Link in New Tab', 'publishpress-authors'),
                'id' => self::META_PREFIX . 'target',
                'type' => 'checkbox',
                'desc' => __(
                    'This feature will add the target=”_blank” attribute to your link.',
                    'publishpress-authors'
                ),
            ]
        );

        $metabox->add_field(
            [
                'name' => __('Link Rel', 'publishpress-authors'),
                'id' => self::META_PREFIX . 'rel',
                'type' => 'select',
                'options' => CustomFieldsModel::getFieldRelOptions(),
                'desc' => '',
            ]
        );

        $metabox->add_field(
            [
                'name' => __('Requirement', 'publishpress-authors'),
                'id' => self::META_PREFIX . 'requirement',
                'type' => 'select',
                'options' => CustomFieldsModel::getFieldRequirment(),
            ]
        );

        $metabox->add_field(
            [
                'name' => __('Description', 'publishpress-authors'),
                'desc' => __(
                    'This description appears under the fields and helps users understand their choice.',
                    'publishpress-authors'
                ),
                'id' => self::META_PREFIX . 'description',
                'type' => 'textarea_small',
            ]
        );
    }

    /**
     * @param array $fields
     * @param Author $author
     *
     * @return mixed
     */
    public function filterAuthorFields($fields, $author)
    {
        $customFields = self::getAuthorCustomFields(true);
        foreach ($fields as $field_key => $field_data) {
            if (isset($customFields[$field_key])) {
                unset($fields[$field_key]);
            }
        }

        $author_fields = array_merge($fields, self::getAuthorCustomFields());

        //Move Biographical Info to the bottom
        if (isset($author_fields['description'])) {
            $author_fields['description']['type'] = 'wysiwyg';
            $description_field = [
                'description' => $author_fields['description']
            ];
            unset($author_fields['description']);
            $author_fields = array_merge($author_fields, $description_field);
        }

        return $author_fields;
    }

    public static function getAuthorCustomFields($include_disabled = false)
    {
        $posts = get_posts(
            [
                'post_type' => self::POST_TYPE_CUSTOM_FIELDS,
                'posts_per_page' => 100,
                'post_status' => 'publish',
                'orderby' => 'menu_order',
                'order' => 'ASC',
            ]
        );

        $fields = [];

        if (! empty($posts)) {
            foreach ($posts as $post) {
                if ($include_disabled || self::getFieldMeta($post->ID, 'field_status') !== 'off') {
                    $fields[$post->post_name] = [
                        'name'        => $post->post_name,
                        'label'       => $post->post_title,
                        'type'        => self::getFieldMeta($post->ID, 'type'),
                        'social_profile' => self::getFieldMeta($post->ID, 'social_profile'),
                        'schema_property' => self::getFieldMeta($post->ID, 'schema_property'),
                        'rel'           => self::getFieldMeta($post->ID, 'rel'),
                        'target'        => self::getFieldMeta($post->ID, 'target'),
                        'field_status' => self::getFieldMeta($post->ID, 'field_status'),
                        'requirement' => self::getFieldMeta($post->ID, 'requirement'),
                        'description' => self::getFieldMeta($post->ID, 'description'),
                        'post_id'     => $post->ID,
                    ];
                }
            }
        }

        return $fields;
    }

    /**
     * @param $postId
     * @param $field
     *
     * @return mixed
     */
    public static function getFieldMeta($postId, $field)
    {
        global $post;

        // Handle virtual posts
        if (is_numeric($postId) && $postId < 0) {
            $field_data = $post->virtual_field_data ?? [];
            switch ($field) {
                case 'field_status':
                    return $field_data['field_status'] ?? '';
                    break;
                case 'type':
                    return $field_data['type'] ?? '';
                    break;
                case 'slug':
                    return $field_data['post_name'] ?? '';
                    break;
                case 'requirement':
                    return $field_data['requirement'] ?? '';
                    break;
            }
            return;
        }

        return get_post_meta($postId, self::META_PREFIX . $field, true);
    }

    /**
     * @param int $termId
     */
    public function saveTermCustomField($termId)
    {
        // Get a list of custom fields to save them.
        $fields = self::getAuthorCustomFields();

        if (! empty($fields)) {
            foreach ($fields as $field) {
                if (isset($_POST['authors-' . $field['name']])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $value = $_POST['authors-' . $field['name']]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

                    if ($field['type'] === 'url') {
                        $value = esc_url_raw($value);
                    } elseif ($field['type'] === 'email') {
                        $value = sanitize_email($value);
                    } elseif ($field['type'] === 'wysiwyg') {
                        $value = wp_kses_post($value);
                    } else {
                        $value = sanitize_text_field($value);
                        // Remove any HTML code.
                        $value = strip_tags($value);
                    }

                    update_term_meta($termId, $field['name'], $value);
                }
            }
        }

        return;
    }

    /**
     * @param $newValue
     * @param $single
     * @param $args
     *
     * @return mixed|string|string[]|null
     */
    public function sanitizeFieldName($newValue, $single, $args)
    {
        if ($args['id'] === self::META_PREFIX . 'slug') {
            $newValue = $this->slugify($newValue);
        }

        return $newValue;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function slugify($string)
    {
        $string = strtolower($string);
        $string = str_replace('-', '_', $string);
        $string = preg_replace('/[^a-z0-9_]/', '', $string);

        return $string;
    }

    /**
     * Short circuit in the remove action on CMB2, to avoid removing the
     * field name automatically set when the field is empty.
     *
     * @param $override
     *
     * @return bool
     */
    public function removePostCustomFieldSlug($override)
    {
        return true;
    }

    /**
     * Override the CMB2 meta field, to retrieve the field slug from post's post_name,
     * instead from a post meta.
     *
     * @param $data
     * @param $postId
     *
     * @return string
     */
    public function overridePostSlugMetaValue($data, $postId)
    {
        $post = get_post($postId);

        return $post->post_name;
    }

    /**
     * Save the field slug in the post_name instead of in a meta data.
     *
     * @param $override
     * @param $args
     *
     * @return bool
     */
    public function overridePostSlugMetaSave($override, $args)
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'posts',
            [
                'post_name' => sanitize_title($args['value']),
            ],
            [
                'ID' => (int)$args['id'],
            ],
            [
                '%s',
            ]
        );

        return true;
    }

    /**
     * @param array $properties
     *
     * @return array
     */
    public function filterAuthorProperties($properties)
    {
        $customFields = self::getAuthorCustomFields();

        if (! empty($customFields)) {
            foreach ($customFields as $customField) {
                $properties[$customField['name']] = true;
            }
        }

        return $properties;
    }

    /**
     * @param mixed $return
     * @param Author $authorId
     * @param string $attribute
     *
     * @return mixed
     */
    public function filterAuthorAttribute($return, $authorId, $attribute)
    {
        $customFields = self::getAuthorCustomFields();

        if (! empty($customFields) && isset($customFields[$attribute])) {
            return $this->getCustomFieldValue($authorId, $attribute);
        }

        return $return;
    }

    /**
     * @param int $authorId
     * @param string $customField
     *
     * @return mixed
     */
    protected function getCustomFieldValue($authorId, $customField)
    {
        return get_term_meta($authorId, $customField, true);
    }

    /**
     * Add banner metabox
     *
     * @return void
     */
    public function addBannerMetabox()
    {
        if (!Utils::isAuthorsProActive()) {
            add_meta_box(
                self::META_PREFIX . 'sidebar_banner',
                __('Banner', 'publishpress-authors'),
                [$this, 'renderBannerMetabox'],
                self::POST_TYPE_CUSTOM_FIELDS,
                'side',
                'low'
            );
        }
    }

    /**
     * Render box metaboxes
     *
     * @param \WP_Post $post
     * @return void
     */
    public function renderBannerMetabox(\WP_Post $post)
    {
        Utils::ppma_pro_sidebar();
    }

    /**
     * @param $columns
     *
     * @return array
     */
    public function filterFieldColumns($columns)
    {
        // Add the first columns.
        $newColumns = [
            'cb' => $columns['cb'],
            'title' => $columns['title'],
            'field_status' => __('Status', 'publishpress-authors'),
            'requirement' => __('Requirement', 'publishpress-authors'),
            'type' => __('Type', 'publishpress-authors'),
            'slug' => __('Slug', 'publishpress-authors'),
        ];

        unset($columns['cb'], $columns['title'], $columns['date']);

        // Add the remaining columns.
        $newColumns = array_merge($newColumns, $columns);

        unset($columns);

        return $newColumns;
    }

    /**
     * @param $column
     * @param $postId
     */
    public function manageFieldColumns($column, $postId)
    {
        global $post;
        if ($column === 'slug') {
            echo esc_html($post->post_name);
        } elseif ($column === 'field_status') {
            if (self::getFieldMeta($post->ID, 'field_status') !== 'off') {
                ?>
                <div style="color: green;">
                    <?php echo esc_html_e('Active', 'publishpress-authors'); ?>
                </div>
            <?php } else { ?>
                <div style="color: red;">
                    <?php echo esc_html_e('Disabled', 'publishpress-authors'); ?>
                </div>
            <?php
            }
        } elseif ($column === 'requirement') {
            if (self::getFieldMeta($post->ID, 'requirement') !== 'required') {
                ?>
                <?php echo esc_html_e('Optional', 'publishpress-authors'); ?>
            <?php } else { ?>
                <?php echo esc_html_e('Required', 'publishpress-authors'); ?>
            <?php
            }
        } elseif ($column === 'type') {
            $type = self::getFieldMeta($post->ID, 'type');
            $type_options = CustomFieldsModel::getFieldTypes();
            $field_type = array_key_exists($type, $type_options) ? $type_options[$type] : $type;
            echo esc_html($field_type);
        }
    }

    /**
     * Make sure the layout name has not a '-' char.
     *
     * @param $slug
     * @param $postID
     * @param $postStatus
     * @param $postType
     *
     * @return string
     */
    public function fixPostSlug($slug, $postID, $postStatus, $postType)
    {
        if (self::POST_TYPE_CUSTOM_FIELDS === $postType) {
            $slug = str_replace('-', '_', $slug);
        }

        return $slug;
    }

    /**
     * Create default custom fields.
     */
    public static function createDefaultCustomFields()
    {
        $defaultCustomFields = self::getDefaultCustomFields();

        $position = 0;
        foreach ($defaultCustomFields as $name => $data) {
            self::creatCustomFieldsPost($name, $data, $position);
            $position++;
            sleep(2);
        }
    }

    /**
     * Create default custom fields.
     *
     * @param string $name
     * @param string $title
     */
    protected static function creatCustomFieldsPost($name, $data, $position = 0)
    {
        // Check if we already have the field based on the slug.
        $existingCustomField = Utils::get_page_by_title($data['post_title'], OBJECT, self::POST_TYPE_CUSTOM_FIELDS);
        if ($existingCustomField) {
            return;
        }

        $post_id = wp_insert_post(
            [
                'post_type' => self::POST_TYPE_CUSTOM_FIELDS,
                'post_title' => $data['post_title'],
                'post_content' => $data['post_title'],
                'post_status' => 'publish',
                'post_name' => $data['post_name'],
                'menu_order' => $position,
            ]
        );
        update_post_meta($post_id, self::META_PREFIX . 'slug', $data['post_name']);
        update_post_meta($post_id, self::META_PREFIX . 'type', $data['type']);
        update_post_meta($post_id, self::META_PREFIX . 'field_status', $data['field_status']);
        update_post_meta($post_id, self::META_PREFIX . 'requirement', isset($data['requirement']) ? $data['requirement'] : '' );
        update_post_meta($post_id, self::META_PREFIX . 'social_profile', isset($data['social_profile']) ? $data['social_profile'] : '' );
        update_post_meta($post_id, self::META_PREFIX . 'schema_property', isset($data['schema_property']) ? $data['schema_property'] : '' );
        update_post_meta($post_id, self::META_PREFIX . 'rel', isset($data['rel']) ? $data['rel'] : '' );
        update_post_meta($post_id, self::META_PREFIX . 'target', !empty($data['target']) ? 1 : '' );
        update_post_meta($post_id, self::META_PREFIX . 'description', $data['description']);
        update_post_meta($post_id, self::META_PREFIX . 'inbuilt', 1);
    }

    /**
     * Get default custom fields.
     */
    public static function getDefaultCustomFields()
    {
        $defaultCustomFields = [];
        //add first name
        $defaultCustomFields['first_name'] = [
            'post_title'   => __('First Name', 'publishpress-authors'),
            'post_name'    => 'first_name',
            'type'         => 'text',
            'field_status'  => 'on',
            'description'  => '',
        ];
        //add last name
        $defaultCustomFields['last_name'] = [
            'post_title'   => __('Last Name', 'publishpress-authors'),
            'post_name'    => 'last_name',
            'type'         => 'text',
            'field_status'  => 'on',
            'description'  => '',
        ];
        //add user email
        $defaultCustomFields['user_email'] = [
            'post_title'   => __('Email', 'publishpress-authors'),
            'post_name'    => 'user_email',
            'type'         => 'email',
            'field_status'  => 'on',
            'description'  => '',
        ];
        //add user url
        $defaultCustomFields['user_url'] = [
            'post_title'   => __('Website', 'publishpress-authors'),
            'post_name'    => 'user_url',
            'type'         => 'url',
            'field_status'  => 'on',
            'description'  => '',
        ];
        //add job title
        $defaultCustomFields['job_title'] = [
            'post_title'   => __('Job Title', 'publishpress-authors'),
            'post_name'    => 'job_title',
            'type'         => 'text',
            'field_status'  => 'on',
            'description'  => '',
            'schema_property'  => 'jobTitle',
        ];

        return $defaultCustomFields;
    }

    /**
     * Add inline script
     *
     * @return void
     */
    public function addInlineScripts()
    {
        global $pagenow, $current_screen;

        if (!Utils::isAuthorsProActive()) {
            $custom_field_page = (isset($_GET['post_type']) && $_GET['post_type'] === self::POST_TYPE_CUSTOM_FIELDS) ? true : false;
            if ($custom_field_page) {
                $modal_content = '';
                $modal_content .= '<div class="new-cf-upgrade-notice">';
                $modal_content .= '<p>';
                $modal_content .= __('With PublishPress Authors Pro, you can add new fields for social networks and more.', 'publishpress-authors');
                $modal_content .= '</p>';
                $modal_content .= '<p>';
                $modal_content .= '<a class="upgrade-link" href="https://publishpress.com/links/authors-banner" target="_blank">'. __('Upgrade to Pro', 'publishpress-authors') .'</a>';
                $modal_content .= '</p>';
                $modal_content .= '</div>';
                Utils::loadThickBoxModal('ppma-new-cf-thickbox-botton', 'initial', 'initial', $modal_content);
                ?>
                <style>
                    .post-new-php.post-type-ppmacf_field,
                    body.edit-php.post-type-ppmacf_field .tablenav .alignleft.actions,
                    body.edit-php.post-type-ppmacf_field table.wp-list-table .check-column {
                        display: none !important;
                    }
                </style>
                <script>
                jQuery(document).ready(function ($) {
                    // Remove new post link value
                    $(".page-title-action")
                        .attr('href', '#');
                    // Remove post editor area incase of direct access
                    $(".post-new-php.post-type-ppmacf_field #poststuff").remove();

                    // wrap the add new inside a new div with promo inside
                    var $action = $('.page-title-action');
                    if ($action.length) {
                        var $extra = $('<span class="dashicons dashicons-lock" style="vertical-align: sub;margin-right: 5px;"></span>');
                        $action.prepend($extra);
                    }

                    $(document).on('click', '.page-title-action', function (e) {
                        e.preventDefault();
                        $('.ppma-new-cf-thickbox-botton').trigger('click');
                        return;
                    });
                    // promo field handler
                    var virtualRowCounter = 0;
                    $('table.wp-list-table tbody tr[id^="post--"]').each(function() {
                        virtualRowCounter++;
                        var $row = $(this);
                        // add blur class
                        $row.addClass('ppma-blur');
                        // Replace the span content with a proper row-title link
                        var $titleCell = $(this).find('td.column-title strong span');
                        if ($titleCell.length > 0) {
                            var titleText = $titleCell.text();
                            $titleCell.html('<a class="row-title" href="#" onclick="return false;">' + titleText + '</a>');
                        }
                         // Add promo notice to the third virtual post
                        if (virtualRowCounter === 4) {
                            $row.addClass('ppma-promo-overlay-row');
                            $row.css('cursor', 'default');
                            $row.removeClass('ppma-blur');
                            $row.find('td').addClass('ppma-blur');
                            var promoHtml = '<div class="ppma-promo-upgrade-notice">' +
                                '<p><?php echo esc_html__('With PublishPress Authors Pro, you can add new fields for social networks and more.', 'publishpress-authors'); ?></p>' +
                                '<p><a class="upgrade-link" href="https://publishpress.com/links/authors-banner" target="_blank"><?php echo esc_html__('Upgrade to Pro', 'publishpress-authors'); ?></a></p>' +
                                '</div>';
                            $row.find('td.column-requirement')
                                .removeClass('ppma-blur')
                                .append(promoHtml);
                        }
                    });
                });
                </script>
            <?php
            }
        }

        if (in_array($pagenow, ['post.php'])
            && $current_screen
            && !empty($current_screen->post_type)
            && $current_screen->post_type === self::POST_TYPE_CUSTOM_FIELDS
        ) {
            //add validation modal
            Utils::loadThickBoxModal('ppma-general-thickbox-botton', 500, 150);
        }
    }



    /**
     * Handle a request to update author fields order.
     */
    public function handle_ajax_update_field_order()
    {

        $response['status']  = 'error';
        $response['content'] = esc_html__('An error occured.', 'publishpress-authors');

        //do not process request if nonce validation failed
        if (empty($_POST['nonce'])
            || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'author-custom-fields-request-nonce')
        ) {
            $response['status']  = 'error';
            $response['content'] = esc_html__(
                'Security error. Kindly reload this page and try again',
                'publishpress-authors'
            );
        } elseif (!current_user_can(apply_filters('pp_multiple_authors_manage_custom_fields_cap', 'ppma_manage_custom_fields'))) {
            $response['status']  = 'error';
            $response['content'] = esc_html__(
                'You do not have permission to perform this action',
                'publishpress-authors'
            );
        } else {

            $posts_order = (!empty($_POST['posts_order']) && is_array($_POST['posts_order'])) ? array_map('sanitize_text_field', $_POST['posts_order']) : false;

            if ($posts_order) {

                foreach ($posts_order as $position =>  $post_order) {
                    $post_id = intval(preg_replace('/[^0-9]/', '', $post_order));
                    wp_update_post(array(
                        'ID' => $post_id,
                        'menu_order' => $position,
                    ));
                }
                $response['status']  = 'success';
                $response['content'] = esc_html__('Field Order updated.', 'publishpress-authors');
            }
        }

        wp_send_json($response);
        exit;
    }

    /**
     * Sort custom fields by order
     *
     * @param object $query
     * @return void
     */
    public function filter_custom_fields_query($query)
    {

        if (is_admin() && $query->is_main_query() && $query->get('post_type') == self::POST_TYPE_CUSTOM_FIELDS) {
            // Sort custom fields by order
            if (!$query->get('orderby')) {
                $query->set('orderby', 'menu_order');
                $query->set('order', 'ASC');
            }
            // filter out social fields if pro is not active
            if (!Utils::isAuthorsProActive() && isset($_GET['post_type']) && $_GET['post_type'] == self::POST_TYPE_CUSTOM_FIELDS) {
                $social_field_names = ['facebook', 'x', 'instagram', 'linkedIn', 'youtube', 'tiktok'];

                $meta_query = $query->get('meta_query') ?: [];
                $meta_query[] = [
                    'key' => 'ppmacf_slug',
                    'value' => $social_field_names,
                    'compare' => 'NOT IN'
                ];
                $query->set('meta_query', $meta_query);
            }
        }
    }

    /**
     * Add promo virtual fields
     *
     * @param mixed $posts
     * @param mixed $query
     *
     * @return mixed
     */
    public function add_virtual_social_fields($posts, $query) {
        if (!is_admin()
            || !$query->is_main_query()
            || $query->get('post_type') !== self::POST_TYPE_CUSTOM_FIELDS
            || Utils::isAuthorsProActive()
            || ! isset($_GET['post_type'])
            || $_GET['post_type'] !== self::POST_TYPE_CUSTOM_FIELDS
        ) {
            return $posts;
        }

        $social_custom_fields = $this->get_social_custom_fields();

        // Create virtual post objects for display
         $virtual_id_counter = -999999999;
        foreach ($social_custom_fields as $field_key => $field_data) {
            $post_data = array(
                'ID' => $virtual_id_counter,
                'post_author' => get_current_user_id(),
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1),
                'post_content' => '',
                'post_title' => $field_data['post_title'],
                'post_excerpt' => '',
                'post_status' => 'publish',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_password' => '',
                'post_name' => $field_data['post_name'],
                'to_ping' => '',
                'pinged' => '',
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1),
                'post_content_filtered' => '',
                'post_parent' => 0,
                'guid' => '',
                'menu_order' => 0,
                'post_type' => 'ppmacf_field',
                'post_mime_type' => '',
                'comment_count' => 0,
                'filter' => 'raw'
            );

            $virtual_post = new WP_Post((object) $post_data);
            $virtual_post->virtual_field_data = $field_data;
            $virtual_post->virtual_field_key = $field_key;

            $posts[] = $virtual_post;
            $virtual_id_counter--;
        }

        return $posts;
    }

    private function get_social_custom_fields() {
        $social_custom_fields = [];

        //add Facebook
        $social_custom_fields['facebook'] = [
            'post_title'   => __('Facebook', 'publishpress-authors'),
            'post_name'    => 'facebook',
            'type'         => 'url',
            'social_profile'  => 1,
            'field_status'  => 'off',
            'description'  => __('Please enter the full URL to your Facebook profile.', 'publishpress-authors'),
        ];
        //add X
        $social_custom_fields['x'] = [
            'post_title'   => __('X', 'publishpress-authors'),
            'post_name'    => 'x',
            'type'         => 'url',
            'social_profile'  => 1,
            'field_status'  => 'off',
            'description'  => __('Please enter the full URL to your X profile.', 'publishpress-authors'),
        ];
        //add Instagram
        $social_custom_fields['instagram'] = [
            'post_title'   => __('Instagram', 'publishpress-authors'),
            'post_name'    => 'instagram',
            'type'         => 'url',
            'social_profile'  => 1,
            'field_status'  => 'off',
            'description'  => __('Please enter the full URL to your Instagram page.', 'publishpress-authors'),
        ];
        //add LinkedIn
        $social_custom_fields['linkedIn'] = [
            'post_title'   => __('LinkedIn', 'publishpress-authors'),
            'post_name'    => 'linkedIn',
            'type'         => 'url',
            'social_profile'  => 1,
            'field_status'  => 'off',
            'description'  => __('Please enter the full URL to your LinkedIn profile.', 'publishpress-authors'),
        ];
        //add YouTube
        $social_custom_fields['youtube'] = [
            'post_title'   => __('YouTube', 'publishpress-authors'),
            'post_name'    => 'youtube',
            'type'         => 'url',
            'social_profile'  => 1,
            'field_status'  => 'off',
            'description'  => __('Please enter the full URL to your YouTube channel.', 'publishpress-authors'),
        ];
        //add TikTok
        $social_custom_fields['tiktok'] = [
            'post_title'   => __('TikTok', 'publishpress-authors'),
            'post_name'    => 'tiktok',
            'type'         => 'url',
            'social_profile'  => 1,
            'field_status'  => 'off',
            'description'  => __('Please enter the full URL to your TikTok profile.', 'publishpress-authors'),
        ];

        return $social_custom_fields;
    }
}
