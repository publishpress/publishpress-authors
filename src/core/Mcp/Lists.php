<?php

declare(strict_types=1);

namespace MultipleAuthors\Mcp;

use MultipleAuthors\Capability;
use MultipleAuthors\Factory;
use MultipleAuthors\Classes\Utils;

\defined('ABSPATH') || exit;

/** Native Author List presets and generated shortcode parameters. @since 4.18.0 */
class Lists
{
    public static function register()
    {
        $id = ['id' => ['type' => 'integer', 'minimum' => 1]];
        $output = ['id' => ['type' => 'integer'], 'fields' => ['type' => 'object'], 'shortcode' => ['type' => 'string'],
            'status' => ['type' => 'string'], 'hash' => ['type' => 'string'], 'dry_run' => ['type' => 'boolean']];
        Registry::add(
            'list-author-lists',
            __('List saved Author Lists', 'publishpress-authors'),
            __('Read a bounded page of saved Author List presets. Each preset has its own ID and shortcode. This is configuration, not a list of author profiles. Includes native active/trash status.', 'publishpress-authors'),
            Registry::object(Registry::page()),
            ['items' => ['type' => 'array', 'items' => ['type' => 'object']], 'page' => ['type' => 'integer'], 'total_pages' => ['type' => 'integer']],
            [__CLASS__, 'listing'],
            [__CLASS__, 'canManage']
        );
        Registry::add(
            'get-author-list',
            __('Read saved Author List', 'publishpress-authors'),
            __('Read one verified list preset, its shortcode and revision hash. Editing a list does not change author profiles, post bylines or Author Boxes.', 'publishpress-authors'),
            Registry::object($id, ['id']),
            $output,
            [__CLASS__, 'get'],
            [__CLASS__, 'canManage']
        );
        Registry::add(
            'get-author-list-fields',
            __('Discover Author List fields', 'publishpress-authors'),
            __('Read native layout, display-field, grouping, image, filter and active-edition search/pagination choices. Pro-only controls appear only with Pro active; promotional controls are excluded.', 'publishpress-authors'),
            Registry::object(),
            ['schema' => ['type' => 'object'], 'pro_active' => ['type' => 'boolean']],
            [__CLASS__, 'fields'],
            [__CLASS__, 'canManage']
        );
        Registry::add(
            'save-author-list',
            __('Create or update Author List', 'publishpress-authors'),
            __('Create a list with native title and layout fields, or patch a verified preset using its latest hash. Generates a native publishpress_authors_list shortcode from validated fields. Preserves omitted fields and other lists. Use status active to restore a trashed preset. dry_run previews without saving.', 'publishpress-authors'),
            Registry::object(array_merge($id, ['fields' => ['type' => 'object', 'minProperties' => 1, 'maxProperties' => 100,
                'additionalProperties' => ['type' => ['string', 'integer', 'boolean', 'array'], 'items' => ['type' => 'string']]], 'status' => ['type' => 'string', 'enum' => ['active', 'trash']],
            'expected_hash' => Registry::hash(),
            'dry_run' => Registry::preview()])),
            $output,
            [__CLASS__, 'save'],
            [__CLASS__, 'canManage'],
            true,
            false
        );
        Registry::add(
            'delete-author-list',
            __('Trash or delete Author List', 'publishpress-authors'),
            __('Trash a verified saved list, or explicitly use permanent true to remove its preset. Requires the latest hash. Does not delete posts or author profiles. dry_run checks without saving.', 'publishpress-authors'),
            Registry::object(array_merge($id, ['expected_hash' => Registry::hash(), 'permanent' => ['type' => 'boolean', 'default' => false], 'dry_run' => Registry::preview()]), ['id', 'expected_hash']),
            ['id' => ['type' => 'integer'], 'dry_run' => ['type' => 'boolean']],
            [__CLASS__, 'delete'],
            [__CLASS__, 'canManage'],
            true
        );
    }

    public static function canManage(): bool
    {
        $module = Factory::getLegacyPlugin()->modules->author_list ?? null;
        return Capability::currentUserCanManageSettings() && $module && ($module->options->enabled ?? 'off') === 'on';
    }

    private static function stored(): array
    {
        return (array) Factory::getLegacyPlugin()->modules->author_list->options->author_list_data;
    }

    private static function definitions(): array
    {
        return Factory::getLegacyPlugin()->author_list->author_list_fields();
    }

    public static function fields(): array
    {
        return ['schema' => Fields::schema(self::definitions()), 'pro_active' => Utils::isAuthorsProActive()];
    }

    public static function listing(array $input): array
    {
        $all = array_values(self::stored());
        $page = $input['page'] ?? 1;
        $limit = $input['per_page'] ?? 20;
        $items = [];
        foreach (array_slice($all, ($page - 1) * $limit, $limit) as $item) {
            $items[] = ['id' => (int) $item['ID'], 'title' => (string) ($item['title'] ?? ''), 'status' => $item['status'] ?? 'active'];
        }
        return ['items' => $items, 'page' => $page, 'total_pages' => (int) ceil(count($all) / $limit)];
    }

    public static function get(array $input)
    {
        $stored = self::stored()[$input['id']] ?? null;
        if (!$stored) {
            return Registry::error(__('Author List not found.', 'publishpress-authors'), 404);
        }
        return self::format($stored);
    }

    private static function format(array $stored): array
    {
        return ['id' => (int) $stored['ID'], 'fields' => (object) Fields::publicValues($stored, self::definitions()),
            'shortcode' => $stored['static_shortcode'] ?? '', 'status' => $stored['status'] ?? 'active', 'hash' => Registry::fingerprint($stored), 'dry_run' => false];
    }

    public static function save(array $input)
    {
        $all = self::stored();
        $stored = !empty($input['id']) ? ($all[$input['id']] ?? null) : [];
        if ($stored === null) {
            return Registry::error(__('Author List not found.', 'publishpress-authors'), 404);
        }
        if ($stored && ($input['expected_hash'] ?? '') !== Registry::fingerprint($stored)) {
            return Registry::error(__('The Author List changed. Read it again before saving.', 'publishpress-authors'), 409);
        }
        $fields = Fields::prepare($input['fields'] ?? [], self::definitions());
        if (is_wp_error($fields)) {
            return $fields;
        }
        $stored = array_merge($stored, $fields);
        if (empty($stored['title']) || empty($stored['layout'])) {
            return Registry::error(__('An Author List requires a title and native layout.', 'publishpress-authors'));
        }
        $legacy = Factory::getLegacyPlugin();
        $options = (array) $legacy->modules->author_list->options;
        $id = $input['id'] ?? ((int) $options['author_list_last_id'] + 1);
        $stored['ID'] = $id;
        $stored['dynamic_shortcode'] = '[publishpress_authors_list list_id="' . $id . '"]';
        $stored['status'] = $input['status'] ?? ($stored['status'] ?? 'active');
        $shortcode = '[publishpress_authors_list';
        foreach (array_intersect_key($stored, (array) self::fields()['schema']['properties']) as $name => $value) {
            if ($name !== 'title') {
                $value = is_array($value) ? implode(',', $value) : (string) $value;
                $shortcode .= ' ' . $name . '="' . esc_attr($value) . '"';
            }
        }
        $stored['static_shortcode'] = $shortcode . ']';
        $stored['shortcode_args'] = $legacy->author_list->extract_shortcode_params($stored['static_shortcode']);
        if (empty($input['dry_run'])) {
            $all[$id] = $stored;
            $options['author_list_data'] = $all;
            $options['author_list_last_id'] = max((int) $options['author_list_last_id'], $id);
            $saved = Registry::saveModuleOptions('author_list', $options);
            if (is_wp_error($saved)) {
                return $saved;
            }
        }
        return array_merge(self::format($stored), ['dry_run' => !empty($input['dry_run'])]);
    }

    public static function delete(array $input)
    {
        $all = self::stored();
        $current = self::get($input);
        if (is_wp_error($current)) {
            return $current;
        }
        if ($input['expected_hash'] !== $current['hash']) {
            return Registry::error(__('The Author List changed. Read it again before deleting.', 'publishpress-authors'), 409);
        }
        if (!empty($input['permanent'])) {
            unset($all[$input['id']]);
        } else {
            $all[$input['id']]['status'] = 'trash';
        }
        if (empty($input['dry_run'])) {
            $options = (array) Factory::getLegacyPlugin()->modules->author_list->options;
            $options['author_list_data'] = $all;
            $saved = Registry::saveModuleOptions('author_list', $options);
            if (is_wp_error($saved)) {
                return $saved;
            }
        }
        return ['id' => $input['id'], 'dry_run' => !empty($input['dry_run'])];
    }
}
