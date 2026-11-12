<?php

declare(strict_types=1);

namespace MultipleAuthors\Mcp;

\defined('ABSPATH') || exit;

/** Project native editor fields to typed discovery without executable callbacks. @since 4.18.0 */
class Fields
{
    public static function sensitive(string $name): bool
    {
        $name = strtolower($name);
        return \MultipleAuthors\Classes\Author_Editor::is_sensitive_user_field($name)
            || in_array($name, ['_application_passwords', 'application_passwords', 'user_level', 'user_activation_key'], true)
            || substr($name, -11) === '_user_level';
    }

    public static function schema(array $definitions): array
    {
        $properties = [];
        foreach ($definitions as $name => $field) {
            $type = $field['type'] ?? 'text';
            if (!empty($field['promo'])) {
                continue;
            }
            if (in_array($type, ['tab', 'promo', 'preview', 'hidden', 'profile_header', 'button_group', 'template_action', 'import_action', 'export_action', 'ajax_user_select'], true)) {
                continue;
            }
            if ($type === 'multiselect_pro' && !\MultipleAuthors\Classes\Utils::isAuthorsProActive()) {
                continue;
            }
            $schema = ['type' => 'string', 'maxLength' => 50000];
            if ($type === 'shortcodes') {
                $schema = Registry::object([
                    'shortcode' => ['type' => 'array', 'maxItems' => 50, 'items' => ['type' => 'string', 'maxLength' => 5000]],
                    'position' => ['type' => 'array', 'maxItems' => 50, 'items' => ['type' => 'string', 'enum' => ['meta', 'name', 'bio', 'authors']]],
                ], ['shortcode', 'position']);
            } elseif ($type === 'checkbox') {
                $schema = ['type' => 'boolean'];
            } elseif ($type === 'number' || $type === 'avatar') {
                $schema = ['type' => $type === 'avatar' ? 'integer' : ['integer', 'string'], 'pattern' => '^$', 'minimum' => isset($field['min']) ? (int) $field['min'] : ($type === 'avatar' ? 0 : -100000),
                    'maximum' => isset($field['max']) ? (int) $field['max'] : 100000];
            } elseif (in_array($type, ['select', 'multiselect', 'multiselect_pro', 'optgroup_select'], true)) {
                $choices = self::choices($field['options'] ?? []);
                $schema = ['type' => 'string', 'enum' => array_map('strval', array_keys($choices))];
                if (!$choices) {
                    continue;
                }
                if (!empty($field['multiple']) || in_array($type, ['multiselect', 'multiselect_pro'], true)) {
                    $schema = ['type' => 'array', 'maxItems' => 100, 'uniqueItems' => true, 'items' => $schema];
                }
            } elseif (in_array($type, ['email', 'text_email'], true)) {
                $schema['format'] = 'email';
            } elseif ($type === 'color') {
                $schema['pattern'] = '^(|#[a-fA-F0-9]{3,8}|rgba?\([0-9., %]+\)|transparent|inherit)$';
            }
            $schema['title'] = wp_strip_all_tags($field['label'] ?? $name);
            $schema['description'] = wp_strip_all_tags($field['description'] ?? $field['desc'] ?? '');
            $properties[$name] = $schema;
        }
        return Registry::object($properties);
    }

    public static function publicValues(array $values, array $definitions): array
    {
        $result = [];
        foreach ((array) (self::schema($definitions)['properties'] ?? []) as $name => $field) {
            if (!array_key_exists($name, $values)) {
                continue;
            }
            $value = $values[$name];
            if (is_array($field['type'])) {
                $value = $value === '' ? '' : (int) $value;
            } elseif ($field['type'] === 'boolean') {
                $value = in_array($value, ['on', 'yes', '1', 1, true], true);
            } elseif ($field['type'] === 'integer') {
                $value = (int) $value;
            } elseif ($field['type'] === 'array') {
                $value = is_array($value) ? $value : ($value === '' ? [] : explode(',', (string) $value));
                $value = array_map('strval', array_values($value));
            } elseif ($field['type'] === 'object') {
                $value = (object) $value;
            } elseif ($field['type'] === 'string') {
                $value = is_scalar($value) ? (string) $value : '';
            }
            $result[$name] = $value;
        }
        return $result;
    }

    private static function choices(array $options): array
    {
        $choices = [];
        foreach ($options as $key => $option) {
            if (is_array($option) && isset($option['options'])) {
                $choices += self::choices($option['options']);
            } elseif (is_array($option) && !isset($option['label'])) {
                $choices += self::choices($option);
            } else {
                $choices[$key] = is_array($option) ? $option['label'] : $option;
            }
        }
        return $choices;
    }

    public static function prepare(array $values, array $definitions)
    {
        $schema = self::schema($definitions);
        $valid = rest_validate_value_from_schema($values, $schema, 'fields');
        if (is_wp_error($valid)) {
            return $valid;
        }
        $result = [];
        foreach ($values as $name => $value) {
            $expected = $schema['properties'][$name]['type'];
            if (
                ($expected === 'boolean' && !is_bool($value)) || ($expected === 'integer' && !is_int($value))
                || ($expected === 'string' && !is_string($value))
                || (is_array($expected) && !is_int($value) && $value !== '')
            ) {
                return Registry::error(__('Use the exact native field type shown in discovery.', 'publishpress-authors'));
            }
            if (is_array($expected) && $value === '') {
                $result[$name] = '';
                continue;
            }
            if ($expected === 'array') {
                if (!is_array($value) || ($value && array_keys($value) !== range(0, count($value) - 1))) {
                    return Registry::error(__('Choose a list of values from field discovery.', 'publishpress-authors'));
                }
                foreach ($value as $item) {
                    if (!is_string($item)) {
                        return Registry::error(__('Field choice lists require string values from discovery.', 'publishpress-authors'));
                    }
                }
            }
            $field = $definitions[$name];
            if ($field['type'] === 'shortcodes') {
                $value = (array) $value;
                foreach (['shortcode', 'position'] as $part) {
                    if (!isset($value[$part]) || !is_array($value[$part])
                        || ($value[$part] && array_keys($value[$part]) !== range(0, count($value[$part]) - 1))) {
                        return Registry::error(__('Box shortcodes require parallel lists of shortcode and position strings.', 'publishpress-authors'));
                    }
                    foreach ($value[$part] as $item) {
                        if (!is_string($item)) {
                            return Registry::error(__('Box shortcodes require string values.', 'publishpress-authors'));
                        }
                    }
                }
                if (count($value['shortcode']) !== count($value['position'])) {
                    return Registry::error(__('Each box shortcode needs one matching position.', 'publishpress-authors'));
                }
            }
            if ($expected === 'boolean') {
                $value = $value ? '1' : '';
            }
            $sanitize = $field['sanitize'] ?? (in_array($field['type'], ['wysiwyg', 'textarea'], true) ? 'wp_kses_post' : 'sanitize_text_field');
            if (is_callable($sanitize)) {
                $result[$name] = is_array($value) ? map_deep($value, $sanitize) : call_user_func($sanitize, $value);
            } elseif (is_array($sanitize)) {
                foreach ($sanitize as $step) {
                    if (!is_callable($step)) {
                        return Registry::error(__('The native field sanitizer is unavailable.', 'publishpress-authors'));
                    }
                    $value = is_array($value) ? map_deep($value, $step) : call_user_func($step, $value);
                }
                $result[$name] = $value;
            } else {
                return Registry::error(__('The native field sanitizer is unavailable.', 'publishpress-authors'));
            }
        }
        return $result;
    }
}
