<?php

declare(strict_types=1);

namespace MultipleAuthors\Mcp;

\defined('ABSPATH') || exit;

/** Shared private ability registration and bounded schemas. @since 4.18.0 */
class Registry
{
    public static function object(array $properties = [], array $required = []): array
    {
        $schema = ['type' => 'object', 'required' => $required, 'additionalProperties' => false];
        if ($properties) {
            $schema['properties'] = $properties;
        }
        return $schema;
    }

    public static function author(int $id)
    {
        $term = get_term($id, 'author');
        return $term && !is_wp_error($term) ? \MultipleAuthors\Classes\Objects\Author::get_by_term_id($id) : false;
    }

    public static function postAuthors(int $postId)
    {
        $terms = wp_get_object_terms($postId, 'author', ['orderby' => 'term_order']);
        if (is_wp_error($terms)) {
            return $terms;
        }
        $authors = [];
        foreach ($terms as $term) {
            $author = self::author((int) $term->term_id);
            if ($author) {
                $authors[] = $author;
            }
        }
        return $authors;
    }

    public static function page(): array
    {
        return ['page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10000, 'default' => 1],
            'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20]];
    }

    public static function ids(): array
    {
        return ['type' => 'array', 'maxItems' => 100, 'uniqueItems' => true,
            'items' => ['type' => 'integer', 'minimum' => 1]];
    }

    public static function preview(): array
    {
        return ['type' => 'boolean', 'default' => false];
    }

    public static function hash(): array
    {
        return ['type' => 'string', 'pattern' => '^[a-f0-9]{64}$'];
    }

    public static function fingerprint($value): string
    {
        return hash('sha256', wp_json_encode(self::canonical($value)));
    }

    private static function canonical($value)
    {
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (!is_array($value)) {
            return $value;
        }
        if ($value && array_keys($value) !== range(0, count($value) - 1)) {
            ksort($value);
        }
        return array_map([self::class, 'canonical'], $value);
    }

    /** Persist through the native service and verify storage rather than its in-memory mirror. */
    public static function saveModuleOptions(string $module, array $options)
    {
        $legacy = \MultipleAuthors\Factory::getLegacyPlugin();
        $key = $legacy->modules->$module->options_group_name;
        $legacy->update_all_module_options($module, $options);
        $stored = get_option($key);
        if (self::fingerprint((array) $stored) !== self::fingerprint($options)) {
            $legacy->modules->$module->options = $stored;
            $legacy->$module->module = $legacy->modules->$module;
            return self::error(__('The settings could not be saved. Read them again before retrying.', 'publishpress-authors'), 500);
        }
        return true;
    }

    public static function error(string $message, int $status = 400): \WP_Error
    {
        return new \WP_Error('publishpress_authors_invalid_operation', $message, ['status' => $status]);
    }

    public static function add(string $name, string $label, string $description, array $input, array $properties, array $callback, array $permission, bool $write = false, bool $idempotent = true)
    {
        wp_register_ability('publishpress-authors/' . $name, [
            'label' => $label, 'description' => $description, 'category' => 'publishpress',
            'input_schema' => $input, 'output_schema' => self::object($properties, array_keys($properties)),
            'execute_callback' => $callback, 'permission_callback' => $permission,
            'meta' => ['public' => false, 'show_in_rest' => false, 'mcp' => ['public' => false],
                'annotations' => ['readonly' => !$write, 'destructive' => $write, 'idempotent' => $idempotent]],
        ]);
    }
}
