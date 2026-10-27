<?php

declare(strict_types=1);

namespace MultipleAuthors\Mcp;

use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;

\defined('ABSPATH') || exit;

/** Author category configuration through shared native persistence. @since 4.18.0 */
class Categories
{
    public static function register()
    {
        $id = ['id' => ['type' => 'integer', 'minimum' => 1]];
        Registry::add(
            'list-author-categories',
            __('List Author Categories', 'publishpress-authors'),
            __('Read native author category IDs, singular/plural names, order, enabled state and schema settings. These group a post byline and differ from post categories. Pro adds per-post-type targeting. Results are paginated.', 'publishpress-authors'),
            Registry::object(Registry::page()),
            ['items' => ['type' => 'array', 'items' => ['type' => 'object']], 'page' => ['type' => 'integer'], 'total_pages' => ['type' => 'integer']],
            [__CLASS__, 'listing'],
            [__CLASS__, 'canManage']
        );
        Registry::add(
            'get-author-category-fields',
            __('Discover Author Category fields', 'publishpress-authors'),
            __('Read category identifiers, label and grouping settings, and active-edition targeting choices. Categories describe author byline roles such as Writer or Reviewer, not WordPress post categories.', 'publishpress-authors'),
            Registry::object(),
            ['schema' => ['type' => 'object'], 'pro_active' => ['type' => 'boolean']],
            [__CLASS__, 'fields'],
            [__CLASS__, 'canManage']
        );
        Registry::add(
            'get-author-category',
            __('Read Author Category', 'publishpress-authors'),
            __('Read one verified native author category and revision hash. Use this hash when updating or deleting.', 'publishpress-authors'),
            Registry::object($id, ['id']),
            ['category' => ['type' => 'object'], 'hash' => ['type' => 'string'], 'dry_run' => ['type' => 'boolean']],
            [__CLASS__, 'get'],
            [__CLASS__, 'canManage']
        );
        Registry::add(
            'save-author-category',
            __('Create or update Author Category', 'publishpress-authors'),
            __('Create a category with category_name and plural_name or patch an existing ID using expected_hash. Preserves omitted fields, validates post types and requires Pro for post-type targeting. dry_run previews without saving. Category order is an integer position.', 'publishpress-authors'),
            Registry::object(array_merge($id, [
                'category_name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 200],
                'plural_name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 200],
                'schema_property' => ['type' => 'string', 'maxLength' => 100],
                'post_types' => ['type' => 'array', 'maxItems' => 100, 'uniqueItems' => true, 'items' => ['type' => 'string']],
                'enabled' => ['type' => 'boolean'], 'order' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10000],
                'expected_hash' => Registry::hash(), 'dry_run' => Registry::preview(),
            ])),
            ['category' => ['type' => 'object'], 'hash' => ['type' => 'string'], 'dry_run' => ['type' => 'boolean']],
            [__CLASS__, 'save'],
            [__CLASS__, 'canManage'],
            true,
            false
        );
        Registry::add(
            'delete-author-category',
            __('Delete unused Author Category', 'publishpress-authors'),
            __('Delete an unused native category and its metadata. Existing post category relationships or author-profile defaults must be reassigned first. Requires expected_hash; dry_run checks without deleting.', 'publishpress-authors'),
            Registry::object(array_merge($id, ['expected_hash' => Registry::hash(), 'dry_run' => Registry::preview()]), ['id', 'expected_hash']),
            ['id' => ['type' => 'integer'], 'dry_run' => ['type' => 'boolean']],
            [__CLASS__, 'delete'],
            [__CLASS__, 'canManage'],
            true
        );
    }

    public static function canManage(): bool
    {
        $module = Factory::getLegacyPlugin()->modules->author_categories ?? null;
        return current_user_can(apply_filters('pp_multiple_authors_manage_categories_cap', 'ppma_manage_author_categories'))
            && $module && ($module->options->enabled ?? 'off') === 'on';
    }

    public static function fields(): array
    {
        $properties = [
            'category_name' => ['type' => 'string', 'title' => __('Singular name', 'publishpress-authors')],
            'plural_name' => ['type' => 'string', 'title' => __('Plural name', 'publishpress-authors')],
            'schema_property' => ['type' => 'string', 'title' => __('Schema property', 'publishpress-authors')],
            'enabled' => ['type' => 'boolean', 'default' => false],
            'order' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10000],
        ];
        if (Utils::isAuthorsProActive()) {
            $properties['post_types'] = ['type' => 'array', 'maxItems' => 100, 'uniqueItems' => true, 'items' => ['type' => 'string', 'enum' => array_values(get_post_types(['public' => true]))]];
        }
        return ['schema' => Registry::object($properties), 'pro_active' => Utils::isAuthorsProActive()];
    }

    public static function listing(array $input): array
    {
        $limit = $input['per_page'] ?? 20;
        $page = $input['page'] ?? 1;
        $items = get_ppma_author_categories(['paged' => $page, 'limit' => $limit, 'no_cache' => true]);
        $total = get_ppma_author_categories(['count_only' => true, 'no_cache' => true]);
        $items = array_map([self::class, 'publicCategory'], $items);
        return ['items' => array_values($items), 'page' => $page, 'total_pages' => (int) ceil((int) $total / $limit)];
    }

    public static function get(array $input)
    {
        $category = get_ppma_author_categories(['id' => $input['id'], 'no_cache' => true]);
        if (!$category) {
            return Registry::error(__('Author Category not found.', 'publishpress-authors'), 404);
        }
        return ['category' => (object) self::publicCategory($category), 'hash' => Registry::fingerprint($category), 'dry_run' => false];
    }

    private static function publicCategory(array $category): array
    {
        $public = array_intersect_key($category, array_flip(['id', 'category_name', 'plural_name', 'slug', 'category_order', 'category_status', 'created_at', 'schema_property']));
        if (Utils::isAuthorsProActive()) {
            $types = maybe_unserialize($category['post_types'] ?? []);
            $public['post_types'] = is_array($types) ? $types : [];
        }
        return $public;
    }

    public static function save(array $input)
    {
        $current = !empty($input['id']) ? self::get($input) : ['category' => (object) [], 'hash' => '', 'dry_run' => false];
        if (is_wp_error($current)) {
            return $current;
        }
        if (!empty($input['id']) && ($input['expected_hash'] ?? '') !== $current['hash']) {
            return Registry::error(__('The category changed. Read it again before saving.', 'publishpress-authors'), 409);
        }
        if ((isset($input['enabled']) && !is_bool($input['enabled'])) || (isset($input['order']) && !is_int($input['order']))) {
            return Registry::error(__('Use exact category field types from discovery.', 'publishpress-authors'));
        }
        $category = (array) $current['category'];
        $category['meta_data'] = [];
        foreach (['category_name', 'plural_name'] as $key) {
            if (isset($input[$key])) {
                $category[$key] = sanitize_text_field($input[$key]);
            }
        }
        if (empty($category['category_name']) || empty($category['plural_name'])) {
            return Registry::error(__('Singular and plural category names are required.', 'publishpress-authors'));
        }
        if (isset($input['post_types'])) {
            if (!Utils::isAuthorsProActive()) {
                return Registry::error(__('Category post-type targeting requires Authors Pro.', 'publishpress-authors'));
            }
            foreach ($input['post_types'] as $type) {
                if (!in_array($type, self::fields()['schema']['properties']['post_types']['items']['enum'], true)) {
                    return Registry::error(__('Choose registered post types.', 'publishpress-authors'));
                }
            }
            $category['meta_data']['post_types'] = $input['post_types'];
        }
        if (isset($input['schema_property'])) {
            $category['meta_data']['schema_property'] = sanitize_text_field($input['schema_property']);
        }
        $category['slug'] = ppma_generate_slug($category['category_name']);
        $duplicate = get_ppma_author_categories(['slug' => $category['slug'], 'no_cache' => true]);
        if ($duplicate && (int) $duplicate['id'] !== ($input['id'] ?? 0)) {
            return Registry::error(__('An Author Category with this slug already exists.', 'publishpress-authors'), 409);
        }
        $category['category_status'] = isset($input['enabled']) ? (int) $input['enabled'] : ($category['category_status'] ?? 0);
        $category['category_order'] = $input['order'] ?? ($category['category_order'] ?? 0);
        $category['created_at'] = $category['created_at'] ?? current_time('mysql', true);
        if (!empty($input['dry_run'])) {
            return ['category' => (object) $category, 'hash' => $current['hash'], 'dry_run' => true];
        }
        $module = Factory::getLegacyPlugin()->author_categories;
        $data = array_intersect_key($category, array_flip(['category_name', 'plural_name', 'slug', 'category_status', 'category_order', 'created_at', 'meta_data']));
        $result = !empty($input['id']) ? $module->editAuthorCategory($data, $input['id']) : $module->addAuthorCategory($data);
        if (!$result) {
            return Registry::error(__('Could not save the native Author Category.', 'publishpress-authors'), 500);
        }
        $savedId = (int) $result['id'];
        if (!self::matches($data, get_ppma_author_categories(['id' => $savedId, 'no_cache' => true]))) {
            if (empty($input['id'])) {
                $restored = (bool) $module->deleteAuthorCategory($savedId);
            } else {
                $restore = array_intersect_key((array) $current['category'], array_flip(['category_name', 'plural_name', 'slug', 'category_status', 'category_order', 'created_at']));
                $restore['meta_data'] = [];
                foreach ($data['meta_data'] as $key => $unused) {
                    $restore['meta_data'][$key] = $current['category']->$key ?? '';
                }
                $module->editAuthorCategory($restore, $savedId);
                $restored = self::matches($restore, get_ppma_author_categories(['id' => $savedId, 'no_cache' => true]));
            }
            return Registry::error($restored
                ? __('The category save failed; the original state was restored.', 'publishpress-authors')
                : __('The category save failed and restoration was incomplete. Read it before retrying.', 'publishpress-authors'), 500);
        }
        return self::get(['id' => $savedId]);
    }

    private static function matches(array $expected, $stored): bool
    {
        if (!is_array($stored)) {
            return false;
        }
        foreach ($expected as $key => $value) {
            if ($key === 'meta_data') {
                foreach ($value as $name => $meta) {
                    if (!empty($meta) || !empty($stored[$name])) {
                        if (maybe_unserialize($stored[$name] ?? '') == $meta) {
                            continue;
                        }
                        return false;
                    }
                }
            } elseif (($stored[$key] ?? null) != $value) {
                return false;
            }
        }
        return true;
    }

    public static function delete(array $input)
    {
        $current = self::get($input);
        if (is_wp_error($current)) {
            return $current;
        }
        if ($input['expected_hash'] !== $current['hash']) {
            return Registry::error(__('The category changed. Read it again before deleting.', 'publishpress-authors'), 409);
        }
        if (Factory::getLegacyPlugin()->author_categories->categoryHasReferences($input['id'])) {
            return Registry::error(__('Reassign category references before deleting.', 'publishpress-authors'));
        }
        if (empty($input['dry_run']) && !Factory::getLegacyPlugin()->author_categories->deleteAuthorCategory($input['id'])) {
            return Registry::error(__('The category could not be deleted.', 'publishpress-authors'), 500);
        }
        return ['id' => $input['id'], 'dry_run' => !empty($input['dry_run'])];
    }
}
