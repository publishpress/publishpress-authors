<?php

declare(strict_types=1);

namespace MultipleAuthors\Mcp;

use MultipleAuthors\Classes\Author_Editor;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\CustomFieldsModel;
use MultipleAuthors\Factory;

\defined('ABSPATH') || exit;

/** Native Author Boxes and Pro field definitions, with object revisions. @since 4.18.0 */
class Objects
{
    private static function type(string $kind): string
    {
        return $kind === 'author-box' ? 'ppma_boxes' : 'ppmacf_field';
    }

    public static function register()
    {
        foreach (['author-box', 'custom-field'] as $kind) {
            $labels = $kind === 'author-box'
                ? [__('List Author Boxes', 'publishpress-authors'), __('Read Author Box', 'publishpress-authors'), __('Discover Author Box fields', 'publishpress-authors'), __('Save Author Box', 'publishpress-authors'), __('Trash Author Box', 'publishpress-authors')]
                : [__('List author field definitions', 'publishpress-authors'), __('Read author field definition', 'publishpress-authors'), __('Discover custom field options', 'publishpress-authors'), __('Save author field definition', 'publishpress-authors'), __('Trash author field definition', 'publishpress-authors')];
            $id = ['id' => ['type' => 'integer', 'minimum' => 1]];
            $saveId = $id;
            $output = ['id' => ['type' => 'integer'], 'title' => ['type' => 'string'], 'slug' => ['type' => 'string'], 'menu_order' => ['type' => 'integer'], 'status' => ['type' => 'string'],
                'fields' => ['type' => 'object'], 'hash' => ['type' => 'string'], 'dry_run' => ['type' => 'boolean']];
            if ($kind === 'author-box') {
                $saveId['template'] = ['type' => 'string', 'maxLength' => 100, 'description' => __('New boxes only: choose a native Free or Pro template from field discovery.', 'publishpress-authors')];
                $output['parent_layout'] = ['type' => 'string'];
                $saveId['field_order'] = ['type' => 'array', 'maxItems' => 200, 'uniqueItems' => true, 'items' => ['type' => 'string']];
                $output['field_order'] = ['type' => 'array', 'items' => ['type' => 'string']];
            }
            $permission = $kind === 'author-box' ? 'canBox' : 'canField';
            $writePermission = $kind === 'author-box' ? 'canBox' : 'canWriteField';
            Registry::add(
                'list-' . ($kind === 'author-box' ? 'author-boxes' : 'custom-fields'),
                $labels[0],
                __('List a bounded page of native objects with IDs, titles, slugs and status. Only editable objects are returned. Read the exact object and its field schema before editing. Follow total_pages even if permissions filter a page to empty.', 'publishpress-authors'),
                Registry::object(array_merge(Registry::page(), ['search' => ['type' => 'string', 'maxLength' => 200]])),
                ['items' => ['type' => 'array', 'items' => ['type' => 'object']], 'page' => ['type' => 'integer'], 'total_pages' => ['type' => 'integer']],
                [__CLASS__, $kind === 'author-box' ? 'boxes' : 'customFields'],
                [__CLASS__, $permission]
            );
            Registry::add(
                'get-' . $kind,
                $labels[1],
                __('Read a verified native object with its editable settings and revision hash. Does not expose arbitrary post metadata. Custom field definitions are distinct from author profile values.', 'publishpress-authors'),
                Registry::object(['id' => $id['id']], ['id']),
                $output,
                [__CLASS__, $kind === 'author-box' ? 'box' : 'customField'],
                [__CLASS__, $permission]
            );
            Registry::add(
                'get-' . $kind . '-fields',
                $labels[2],
                __('Discover native editor labels, field types, supported choices and edition-specific fields. Optional id selects an existing object. Unknown or inactive-edition fields are rejected during saves.', 'publishpress-authors'),
                Registry::object(['id' => $id['id']]),
                ['schema' => ['type' => 'object'], 'pro_active' => ['type' => 'boolean'], 'templates' => ['type' => 'object'], 'field_order_choices' => ['type' => 'array', 'items' => ['type' => 'string']]],
                [__CLASS__, $kind === 'author-box' ? 'boxFields' : 'fieldFields'],
                [__CLASS__, $permission]
            );
            if ($kind === 'custom-field' && !class_exists('PPAuthorsPro\\Plugin')) {
                continue;
            }
            Registry::add(
                'save-' . $kind,
                $labels[3],
                __('Create a native object or patch a verified ID using discovered typed fields. New objects need a title and default to draft. Updates require expected_hash, preserve omitted fields and use native sanitizers. dry_run validates without saving. menu_order controls native list ordering. Managing custom field definitions requires Pro.', 'publishpress-authors'),
                Registry::object(array_merge($saveId, ['title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 200],
                    'slug' => ['type' => 'string', 'pattern' => '^[a-z0-9][a-z0-9_-]*$', 'maxLength' => 200],
                    'menu_order' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100000], 'status' => ['type' => 'string', 'enum' => ['draft', 'publish']], 'expected_hash' => Registry::hash(),
                    'fields' => ['type' => 'object', 'maxProperties' => 500, 'additionalProperties' => ['type' => ['string', 'integer', 'boolean', 'array', 'object'], 'items' => ['type' => 'string'], 'additionalProperties' => ['type' => 'array', 'maxItems' => 50, 'items' => ['type' => 'string']]]],
                    'dry_run' => Registry::preview()])),
                $output,
                [__CLASS__, $kind === 'author-box' ? 'saveBox' : 'saveField'],
                [__CLASS__, $writePermission],
                true,
                false
            );
            Registry::add(
                'delete-' . $kind,
                $labels[4],
                __('Trash a verified native object with delete_post permission and expected_hash. Does not permanently delete it when Trash is disabled. Existing references are not rewritten. dry_run checks without deleting; custom field deletion requires Pro.', 'publishpress-authors'),
                Registry::object(array_merge($id, ['expected_hash' => Registry::hash(), 'dry_run' => Registry::preview()]), ['id', 'expected_hash']),
                ['id' => ['type' => 'integer'], 'dry_run' => ['type' => 'boolean']],
                [__CLASS__, $kind === 'author-box' ? 'deleteBox' : 'deleteField'],
                [__CLASS__, $writePermission],
                true
            );
        }
    }

    private static function access(array $input, string $kind): bool
    {
        $legacy = Factory::getLegacyPlugin();
        $module = $legacy->modules->{$kind === 'author-box' ? 'author_boxes' : 'author_custom_fields'} ?? null;
        $native = $kind === 'author-box'
            ? current_user_can(apply_filters('pp_multiple_authors_manage_layouts_cap', 'ppma_manage_layouts'))
            : current_user_can(apply_filters('pp_multiple_authors_manage_custom_fields_cap', 'ppma_manage_custom_fields'));
        $type = get_post_type_object(self::type($kind));
        return $native && $module && $type && ($module->options->enabled ?? 'off') === 'on'
            && (empty($input['id']) || (get_post_type($input['id']) === self::type($kind) && current_user_can('edit_post', $input['id']) && current_user_can($type->cap->edit_post, $input['id'])));
    }

    public static function canBox(array $input = []): bool
    {
        return self::access($input, 'author-box');
    }
    public static function canField(array $input = []): bool
    {
        return self::access($input, 'custom-field');
    }
    public static function canWriteField(array $input = []): bool
    {
        return Utils::isAuthorsProActive() && self::canField($input);
    }
    public static function boxes(array $input): array
    {
        return self::listing($input, 'author-box');
    }
    public static function customFields(array $input): array
    {
        return self::listing($input, 'custom-field');
    }
    public static function box(array $input)
    {
        return self::get($input, 'author-box');
    }
    public static function customField(array $input)
    {
        return self::get($input, 'custom-field');
    }
    public static function boxFields(array $input): array
    {
        return self::fields($input, 'author-box');
    }
    public static function fieldFields(array $input): array
    {
        return self::fields($input, 'custom-field');
    }
    public static function saveBox(array $input)
    {
        return self::save($input, 'author-box');
    }
    public static function saveField(array $input)
    {
        return self::save($input, 'custom-field');
    }
    public static function deleteBox(array $input)
    {
        return self::delete($input, 'author-box');
    }
    public static function deleteField(array $input)
    {
        return self::delete($input, 'custom-field');
    }

    private static function definitions(array $input, string $kind): array
    {
        if ($kind === 'author-box') {
            $post = !empty($input['id']) ? get_post($input['id']) : new \WP_Post((object) ['ID' => 0, 'post_type' => 'ppma_boxes']);
            return apply_filters('multiple_authors_author_boxes_fields', \MA_Author_Boxes::get_fields($post), $post);
        }
        return [
            'type' => ['type' => 'select', 'label' => __('Field Type', 'publishpress-authors'), 'options' => CustomFieldsModel::getFieldTypes()],
            'field_status' => ['type' => 'select', 'label' => __('Field Status', 'publishpress-authors'), 'options' => CustomFieldsModel::getFieldStatus()],
            'requirement' => ['type' => 'select', 'label' => __('Required or optional', 'publishpress-authors'), 'options' => CustomFieldsModel::getFieldRequirment()],
            'social_profile' => ['type' => 'select', 'label' => __('Social Profile', 'publishpress-authors'), 'options' => CustomFieldsModel::getFieldSocialProfile()],
            'rel' => ['type' => 'select', 'label' => __('Link relationship', 'publishpress-authors'), 'options' => CustomFieldsModel::getFieldRelOptions()],
            'target' => ['type' => 'checkbox', 'label' => __('Open link in a new tab', 'publishpress-authors'), 'sanitize' => static function ($value) {
                return $value ? 'on' : '';
            }],
            'show_in_rest' => ['type' => 'select', 'label' => __('Show in REST API', 'publishpress-authors'), 'options' => CustomFieldsModel::getFieldStatus()],
            'schema_property' => ['type' => 'text', 'label' => __('Schema property', 'publishpress-authors')],
            'description' => ['type' => 'textarea', 'label' => __('Description', 'publishpress-authors'), 'sanitize' => 'sanitize_textarea_field'],
        ];
    }

    private static function fields(array $input, string $kind): array
    {
        $templates = $kind === 'author-box' ? \MultipleAuthorBoxes\AuthorBoxesDefault::getAuthorBoxesDefaultList() : [];
        if ($kind === 'author-box' && Utils::isAuthorsProActive()) {
            $templates += \MA_Author_Boxes_Pro::getAuthorBoxesDefaultList();
        }
        return ['schema' => Fields::schema(self::definitions($input, $kind)), 'pro_active' => Utils::isAuthorsProActive(), 'templates' => (object) $templates, 'field_order_choices' => $kind === 'author-box' ? self::boxOrder($input['id'] ?? 0) : []];
    }

    private static function boxOrder(int $id): array
    {
        return array_values(array_diff(array_keys(\MA_Author_Boxes::get_profile_fields($id)), \MA_Author_Boxes::AUTHOR_BOXES_EXCLUDED_FIELDS));
    }

    private static function listing(array $input, string $kind): array
    {
        $page = $input['page'] ?? 1;
        $query = new \WP_Query(['post_type' => self::type($kind), 'post_status' => ['publish', 'draft', 'private'],
            'posts_per_page' => $input['per_page'] ?? 20, 'paged' => $page, 's' => $input['search'] ?? '', 'orderby' => ['menu_order' => 'ASC', 'ID' => 'ASC']]);
        $items = [];
        foreach ($query->posts as $post) {
            if (self::access(['id' => $post->ID], $kind)) {
                $items[] = ['id' => $post->ID, 'title' => $post->post_title, 'slug' => $post->post_name, 'menu_order' => (int) $post->menu_order, 'status' => $post->post_status];
            }
        }
        return ['items' => $items, 'page' => $page, 'total_pages' => (int) $query->max_num_pages];
    }

    private static function values(int $id, string $kind): array
    {
        if ($kind === 'author-box') {
            return (array) get_post_meta($id, 'ppma_boxes_layout_meta_value', true);
        }
        $values = [];
        foreach (self::definitions([], $kind) as $name => $field) {
            $values[$name] = \MA_Author_Custom_Fields::getFieldMeta($id, $name);
        }
        return $values;
    }

    private static function get(array $input, string $kind)
    {
        $post = get_post($input['id']);
        if (!$post || $post->post_type !== self::type($kind)) {
            return Registry::error(__('Native object not found.', 'publishpress-authors'), 404);
        }
        $values = self::values($post->ID, $kind);
        $result = ['id' => $post->ID, 'title' => $post->post_title, 'slug' => $post->post_name, 'menu_order' => (int) $post->menu_order, 'status' => $post->post_status,
            'fields' => (object) Fields::publicValues($values, self::definitions($input, $kind)),
            'hash' => Registry::fingerprint([$post->post_title, $post->post_name, $post->post_status, $post->menu_order, $values, $kind === 'author-box' ? get_post_meta($post->ID, 'ppma_boxes_layout_parent_author_box', true) : '']), 'dry_run' => false];
        if ($kind === 'author-box') {
            $result['parent_layout'] = (string) get_post_meta($post->ID, 'ppma_boxes_layout_parent_author_box', true);
            $result['field_order'] = self::boxOrder($post->ID);
            $result['hash'] = Registry::fingerprint([$result['hash'], get_post_meta($post->ID, 'ppma_boxes_author_fields_order', true)]);
        }
        return $result;
    }

    private static function save(array $input, string $kind)
    {
        $post = !empty($input['id']) ? get_post($input['id']) : null;
        $current = $post ? self::get($input, $kind) : ['id' => 0, 'title' => '', 'slug' => '', 'menu_order' => 0, 'status' => 'draft', 'fields' => (object) [], 'hash' => '', 'dry_run' => false];
        if (is_wp_error($current)) {
            return $current;
        }
        if ($post && ($input['expected_hash'] ?? '') !== $current['hash']) {
            return Registry::error(__('This object changed. Read it again before editing.', 'publishpress-authors'), 409);
        }
        if (!$post && empty($input['title'])) {
            return Registry::error(__('A title is required for a new object.', 'publishpress-authors'));
        }
        $type = get_post_type_object(self::type($kind));
        if (!$type || (!$post && !current_user_can($type->cap->create_posts)) || (($input['status'] ?? '') === 'publish' && !current_user_can($type->cap->publish_posts))) {
            return Registry::error(__('You cannot create or publish this native object.', 'publishpress-authors'), 403);
        }
        $slug = $input['slug'] ?? ($post ? $post->post_name : sanitize_title($input['title']));
        if ($kind === 'custom-field' && (Fields::sensitive($slug) || in_array($slug, ['user_id', 'user_login', 'avatar'], true))) {
            return Registry::error(__('This field name is reserved for account identity or security.', 'publishpress-authors'));
        }
        if ($kind === 'custom-field') {
            $duplicate = get_posts(['post_type' => self::type($kind), 'name' => $slug, 'post_status' => ['publish', 'draft', 'private'], 'posts_per_page' => 1]);
            if ($duplicate && (!$post || $duplicate[0]->ID !== $post->ID)) {
                return Registry::error(__('Choose a unique author field slug.', 'publishpress-authors'), 409);
            }
        }
        if (isset($input['field_order']) && array_diff($input['field_order'], self::boxOrder($post ? $post->ID : 0))) {
            return Registry::error(__('Choose discovered profile field names for the box order.', 'publishpress-authors'));
        }
        $values = Fields::prepare($input['fields'] ?? [], self::definitions($input, $kind));
        if (is_wp_error($values)) {
            return $values;
        }
        $defaults = [];
        $parent = '';
        if (isset($input['template'])) {
            if ($post || $kind !== 'author-box' || !isset(self::fields([], $kind)['templates']->{$input['template']})) {
                return Registry::error(__('Choose a discovered template when creating a new Author Box.', 'publishpress-authors'));
            }
            $proTemplates = Utils::isAuthorsProActive() ? \MA_Author_Boxes_Pro::getAuthorBoxesDefaultList() : [];
            if (isset($proTemplates[$input['template']])) {
                $defaults = \MA_Author_Boxes_Pro::getAuthorBoxesDefaultData($input['template']);
                $parent = $input['template'];
            } else {
                $defaults = \MultipleAuthorBoxes\AuthorBoxesDefault::getAuthorBoxesDefaultData($input['template']);
            }
        }
        $stored = $post ? self::values($post->ID, $kind) : [];
        $changes = $values;
        $values = array_merge($defaults, $stored, $values);
        $current = array_merge($current, array_intersect_key($input, array_flip(['title', 'slug', 'status', 'menu_order'])));
        if ($kind === 'author-box') {
            $current['parent_layout'] = $post ? $current['parent_layout'] : $parent;
            $order = self::boxOrder($post ? $post->ID : 0);
            $current['field_order'] = isset($input['field_order']) ? array_values(array_merge(array_diff($order, $input['field_order']), $input['field_order'])) : $order;
        }
        $current['title'] = sanitize_text_field($current['title']);
        $current['fields'] = (object) Fields::publicValues($values, self::definitions($input, $kind));
        if (!empty($input['dry_run'])) {
            $current['dry_run'] = true;
            return $current;
        }
        $beforeMeta = $post ? get_post_meta($post->ID) : [];
        $id = wp_insert_post(wp_slash(['ID' => $post ? $post->ID : 0, 'post_type' => self::type($kind),
            'menu_order' => $current['menu_order'], 'post_title' => $current['title'], 'post_name' => $current['slug'], 'post_status' => $current['status']]), true);
        if (is_wp_error($id)) {
            return $id;
        }
        if ($kind === 'author-box') {
            update_post_meta($id, 'ppma_boxes_layout_meta_value', $values);
            if (!$post && $parent !== '') {
                update_post_meta($id, 'ppma_boxes_layout_parent_author_box', $parent);
            }
        } else {
            foreach ($changes as $name => $value) {
                update_post_meta($id, 'ppmacf_' . $name, $value);
            }
        }
        if ($kind === 'author-box' && isset($input['field_order'])) {
            update_post_meta($id, 'ppma_boxes_author_fields_order', $input['field_order']);
            if (get_post_meta($id, 'ppma_boxes_author_fields_order', true) !== $input['field_order']) {
                self::restore($post, $id, $kind, $beforeMeta, array_keys($changes));
                return Registry::error(__('The field order could not be saved. Read the object before retrying.', 'publishpress-authors'), 500);
            }
        }
        $saved = self::values($id, $kind);
        foreach ($changes as $name => $value) {
            if (($saved[$name] ?? '') != $value) {
                $restored = self::restore($post, $id, $kind, $beforeMeta, array_keys($changes));
                return Registry::error($restored
                    ? __('Native metadata save failed; the original object was restored.', 'publishpress-authors')
                    : __('Native metadata save failed and the original object could not be fully restored. Read it before retrying.', 'publishpress-authors'), 500);
            }
        }
        do_action('publishpress_authors_flush_cache');
        return self::get(['id' => $id], $kind);
    }

    private static function restore($post, int $id, string $kind, array $beforeMeta, array $names): bool
    {
        if (!$post) {
            return (bool) wp_delete_post($id, true);
        }
        $updated = wp_update_post(wp_slash(['ID' => $id, 'post_title' => $post->post_title, 'post_name' => $post->post_name, 'post_status' => $post->post_status, 'menu_order' => $post->menu_order]), true);
        $keys = $kind === 'author-box' ? ['ppma_boxes_layout_meta_value', 'ppma_boxes_author_fields_order'] : array_map(static function ($name) {
            return 'ppmacf_' . $name;
        }, $names);
        foreach ($keys as $key) {
            if (array_key_exists($key, $beforeMeta)) {
                update_post_meta($id, $key, maybe_unserialize($beforeMeta[$key][0]));
            } else {
                delete_post_meta($id, $key);
            }
            if (get_post_meta($id, $key) != array_map('maybe_unserialize', $beforeMeta[$key] ?? [])) {
                return false;
            }
        }
        return !is_wp_error($updated);
    }

    private static function delete(array $input, string $kind)
    {
        $current = self::get($input, $kind);
        if (is_wp_error($current)) {
            return $current;
        }
        if ($input['expected_hash'] !== $current['hash']) {
            return Registry::error(__('This object changed. Read it again before deleting.', 'publishpress-authors'), 409);
        }
        if (!current_user_can('delete_post', $input['id']) || !current_user_can(get_post_type_object(self::type($kind))->cap->delete_post, $input['id']) || !EMPTY_TRASH_DAYS) {
            return Registry::error(__('You cannot trash this object, or Trash is disabled.', 'publishpress-authors'), 403);
        }
        if (empty($input['dry_run']) && !wp_trash_post($input['id'])) {
            return Registry::error(__('The native object could not be trashed.', 'publishpress-authors'), 500);
        }
        return ['id' => $input['id'], 'dry_run' => !empty($input['dry_run'])];
    }
}
