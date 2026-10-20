<?php
/**
 * Registers read-only WordPress Abilities API integrations.
 *
 * @package MultipleAuthors
 */

use MultipleAuthors\Classes\Author_Editor;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Capability;

defined('ABSPATH') or die('No direct script access allowed.');

// WordPress 6.9 introduced the Abilities API. Keep older supported sites working.
if (!function_exists('wp_register_ability')) {
    return;
}

class PP_Authors_Abilities
{
    const CATEGORY = 'publishpress-authors';

    /**
     * Register hooks for the Abilities API.
     *
     * @return void
     */
    public static function init()
    {
        add_action('wp_abilities_api_categories_init', [__CLASS__, 'register_category']);
        add_action('wp_abilities_api_init', [__CLASS__, 'register_abilities']);
    }

    /**
     * Register the PublishPress Authors ability category.
     *
     * @return void
     */
    public static function register_category()
    {
        wp_register_ability_category(
            self::CATEGORY,
            [
                'label'       => __('PublishPress Authors', 'publishpress-authors'),
                'description' => __('Read author profiles, available profile fields, and post bylines.', 'publishpress-authors'),
            ]
        );
    }

    /**
     * Register read-only abilities.
     *
     * @return void
     */
    public static function register_abilities()
    {
        wp_register_ability('publishpress-authors/list-authors', self::get_list_authors_ability());
        wp_register_ability('publishpress-authors/get-author', self::get_author_ability());
        wp_register_ability('publishpress-authors/get-post-authors', self::get_post_authors_ability());
        wp_register_ability('publishpress-authors/get-author-fields', self::get_author_fields_ability());
    }

    /**
     * Return the ability registration arguments shared by all read abilities.
     *
     * @param string $label Ability label.
     * @param string $description Ability description.
     * @param array  $input_schema Ability input schema.
     * @param array  $output_schema Ability output schema.
     * @param array  $callback Ability execute callback.
     * @param array  $permission_callback Ability permission callback.
     *
     * @return array
     */
    private static function get_ability_args($label, $description, $input_schema, $output_schema, $callback, $permission_callback)
    {
        return [
            'label'               => $label,
            'description'         => $description,
            'category'            => self::CATEGORY,
            'input_schema'        => $input_schema,
            'output_schema'       => $output_schema,
            'execute_callback'    => $callback,
            'permission_callback' => $permission_callback,
            'meta'                => [
                // Core REST discovery and execution.
                'show_in_rest' => true,
                // Discovery hint for MCP adapters.
                'mcp'          => ['public' => true],
                'annotations'  => [
                    'readonly'    => true,
                    'destructive' => false,
                    'idempotent'  => true,
                ],
            ],
        ];
    }

    /**
     * Get list-authors registration arguments.
     *
     * @return array
     */
    private static function get_list_authors_ability()
    {
        return self::get_ability_args(
            __('List Authors', 'publishpress-authors'),
            __('Lists PublishPress Authors. Results never include author email addresses.', 'publishpress-authors'),
            [
                'type'                 => 'object',
                'properties'           => [
                    'search'   => ['type' => 'string', 'maxLength' => 100],
                    'type'     => ['type' => 'string', 'enum' => ['all', 'guest', 'user']],
                    'page'     => ['type' => 'integer', 'minimum' => 1],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
                'additionalProperties' => false,
            ],
            self::get_authors_output_schema(),
            [__CLASS__, 'list_authors'],
            [__CLASS__, 'can_manage_authors']
        );
    }

    /**
     * Get get-author registration arguments.
     *
     * @return array
     */
    private static function get_author_ability()
    {
        return self::get_ability_args(
            __('Get Author', 'publishpress-authors'),
            __('Gets one PublishPress Author by term ID. The response never includes an email address.', 'publishpress-authors'),
            [
                'type'                 => 'object',
                'properties'           => [
                    'author_id' => ['type' => 'integer', 'minimum' => 1],
                ],
                'required'             => ['author_id'],
                'additionalProperties' => false,
            ],
            self::get_author_output_schema(),
            [__CLASS__, 'get_author'],
            [__CLASS__, 'can_manage_authors']
        );
    }

    /**
     * Get get-post-authors registration arguments.
     *
     * @return array
     */
    private static function get_post_authors_ability()
    {
        return self::get_ability_args(
            __('Get Post Authors', 'publishpress-authors'),
            __('Gets the ordered PublishPress Authors assigned to one editable post. The response never includes author email addresses.', 'publishpress-authors'),
            [
                'type'                 => 'object',
                'properties'           => [
                    'post_id' => ['type' => 'integer', 'minimum' => 1],
                ],
                'required'             => ['post_id'],
                'additionalProperties' => false,
            ],
            self::get_authors_output_schema('authors'),
            [__CLASS__, 'get_post_authors'],
            [__CLASS__, 'can_read_post_authors']
        );
    }

    /**
     * Get get-author-fields registration arguments.
     *
     * @return array
     */
    private static function get_author_fields_ability()
    {
        return self::get_ability_args(
            __('Get Author Fields', 'publishpress-authors'),
            __('Lists enabled author-profile field definitions without exposing profile values.', 'publishpress-authors'),
            [
                'type'                 => 'object',
                'properties'           => [],
                'additionalProperties' => false,
            ],
            [
                'type'       => 'object',
                'properties' => [
                    'fields' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'name'     => ['type' => 'string'],
                                'label'    => ['type' => 'string'],
                                'type'     => ['type' => 'string'],
                                'required' => ['type' => 'boolean'],
                            ],
                        ],
                    ],
                ],
            ],
            [__CLASS__, 'get_author_fields'],
            [__CLASS__, 'can_manage_authors']
        );
    }

    /**
     * Return the standard Author output schema.
     *
     * @return array
     */
    private static function get_author_output_schema()
    {
        return [
            'type'       => 'object',
            'properties' => [
                'term_id'      => ['type' => 'integer'],
                'user_id'      => ['type' => 'integer'],
                'is_guest'     => ['type' => 'boolean'],
                'display_name' => ['type' => 'string'],
                'slug'         => ['type' => 'string'],
                'avatar_url'   => ['type' => 'string'],
                'post_count'   => ['type' => 'integer'],
                'profile_url'  => ['type' => 'string'],
            ],
        ];
    }

    /**
     * Return an output schema containing an array of Author objects.
     *
     * @param string $property Property name containing authors.
     *
     * @return array
     */
    private static function get_authors_output_schema($property = 'authors')
    {
        return [
            'type'       => 'object',
            'properties' => [
                $property => [
                    'type'  => 'array',
                    'items' => self::get_author_output_schema(),
                ],
            ],
        ];
    }

    /**
     * Check whether current user may manage author profiles.
     *
     * @return bool
     */
    public static function can_manage_authors()
    {
        return Capability::currentUserCanManageAuthors();
    }

    /**
     * Check whether current user may read authors assigned to a post.
     *
     * @param array $input Ability input.
     *
     * @return bool
     */
    public static function can_read_post_authors($input = [])
    {
        $post_id = isset($input['post_id']) ? absint($input['post_id']) : 0;

        return $post_id
            && Capability::currentUserCanEditPostAuthors()
            && current_user_can('edit_post', $post_id);
    }

    /**
     * List author profiles.
     *
     * @param array $input Ability input.
     *
     * @return array
     */
    public static function list_authors($input = [])
    {
        $page     = isset($input['page']) ? max(1, absint($input['page'])) : 1;
        $per_page = isset($input['per_page']) ? min(100, max(1, absint($input['per_page']))) : 20;
        $args     = [
            'taxonomy'   => 'author',
            'hide_empty' => false,
            'number'     => $per_page,
            'offset'     => ($page - 1) * $per_page,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ];

        if (!empty($input['search'])) {
            $args['search'] = sanitize_text_field($input['search']);
        }

        $terms = get_terms($args);
        if (is_wp_error($terms)) {
            return ['authors' => []];
        }

        $type    = isset($input['type']) ? $input['type'] : 'all';
        $authors = [];
        foreach ($terms as $term) {
            $author = Author::get_by_term_id($term->term_id);
            if (!$author || ('all' !== $type && ('guest' === $type) !== $author->is_guest())) {
                continue;
            }

            $authors[] = self::format_author($author, $term);
        }

        return ['authors' => $authors];
    }

    /**
     * Get one author profile.
     *
     * @param array $input Ability input.
     *
     * @return array|WP_Error
     */
    public static function get_author($input = [])
    {
        $author_id = isset($input['author_id']) ? absint($input['author_id']) : 0;
        $term      = $author_id ? get_term($author_id, 'author') : false;

        if (!$term || is_wp_error($term)) {
            return new WP_Error(
                'publishpress_authors_author_not_found',
                __('Author not found.', 'publishpress-authors'),
                ['status' => 404]
            );
        }

        return self::format_author(Author::get_by_term_id($term->term_id), $term);
    }

    /**
     * Get authors assigned to a post without triggering legacy synchronization.
     *
     * @param array $input Ability input.
     *
     * @return array|WP_Error
     */
    public static function get_post_authors($input = [])
    {
        $post_id = isset($input['post_id']) ? absint($input['post_id']) : 0;
        $post    = $post_id ? get_post($post_id) : false;

        if (!$post) {
            return new WP_Error(
                'publishpress_authors_post_not_found',
                __('Post not found.', 'publishpress-authors'),
                ['status' => 404]
            );
        }

        // $updateAuthors = false prevents legacy reads from writing author terms.
        $post_authors = get_post_authors($post_id, false, false);
        $authors      = [];
        foreach ($post_authors as $author) {
            if (!($author instanceof Author)) {
                continue;
            }

            $term = $author->getTerm();
            if (!$term || is_wp_error($term)) {
                continue;
            }

            $authors[] = self::format_author($author, $term);
        }

        return ['authors' => $authors];
    }

    /**
     * List enabled author editor field definitions.
     *
     * @return array
     */
    public static function get_author_fields()
    {
        $definitions = Author_Editor::get_fields(false);
        $fields      = [];
        foreach ($definitions as $name => $definition) {
            $fields[] = [
                'name'     => $name,
                'label'    => isset($definition['label']) ? wp_strip_all_tags($definition['label']) : $name,
                'type'     => isset($definition['type']) ? $definition['type'] : 'text',
                'required' => isset($definition['requirement']) && 'required' === $definition['requirement'],
            ];
        }

        return ['fields' => $fields];
    }

    /**
     * Format an Author for an ability response without email or arbitrary meta.
     *
     * @param Author  $author Author object.
     * @param WP_Term $term Author term.
     *
     * @return array
     */
    private static function format_author($author, $term)
    {
        $profile_url = get_term_link($term, 'author');

        return [
            'term_id'      => (int)$author->term_id,
            'user_id'      => (int)$author->user_id,
            'is_guest'     => $author->is_guest(),
            'display_name' => (string)$author->display_name,
            'slug'         => (string)$author->slug,
            'avatar_url'   => (string)$author->get_avatar_url(),
            'post_count'   => (int)$term->count,
            'profile_url'  => is_wp_error($profile_url) ? '' : (string)$profile_url,
        ];
    }
}

PP_Authors_Abilities::init();
