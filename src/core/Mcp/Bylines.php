<?php

declare(strict_types=1);

namespace MultipleAuthors\Mcp;

use MultipleAuthors\Capability;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;

\defined('ABSPATH') || exit;

/** Ordered post authors and per-post display with native object checks. @since 4.18.0 */
class Bylines
{
    public static function register()
    {
        $post = ['post_id' => ['type' => 'integer', 'minimum' => 1]];
        $change = Registry::object(array_merge($post, [
            'expected_hash' => Registry::hash(), 'author_ids' => Registry::ids(),
            'categories' => ['type' => 'object', 'maxProperties' => 100, 'additionalProperties' => Registry::ids()],
            'fallback_user_id' => ['type' => 'integer', 'minimum' => 1],
            'author_box' => ['type' => 'string', 'maxLength' => 200],
            'disable_author_box' => ['type' => 'boolean'], 'dry_run' => Registry::preview(),
        ]), ['post_id', 'expected_hash']);
        $output = ['post_id' => ['type' => 'integer'], 'author_ids' => Registry::ids(), 'categories' => ['type' => 'object'],
            'fallback_user_id' => ['type' => 'integer'], 'author_box' => ['type' => 'string'], 'disable_author_box' => ['type' => 'boolean'],
            'hash' => ['type' => 'string'], 'dry_run' => ['type' => 'boolean']];
        Registry::add(
            'get-byline-choices',
            __('Discover authors, categories and boxes for a post byline', 'publishpress-authors'),
            __('Read paginated existing author term IDs and enabled author categories plus native Author Box choices for one editable post. Uses byline permissions rather than granting profile management. Never creates profiles for unassigned WordPress users. Follow each list total_pages separately.', 'publishpress-authors'),
            Registry::object(array_merge($post, Registry::page(), ['search' => ['type' => 'string', 'maxLength' => 100]]), ['post_id']),
            ['authors' => ['type' => 'array', 'items' => ['type' => 'object']], 'categories' => ['type' => 'array', 'items' => ['type' => 'object']],
                'author_boxes' => ['type' => 'object'], 'page' => ['type' => 'integer'], 'authors_total_pages' => ['type' => 'integer'], 'categories_total_pages' => ['type' => 'integer']],
            [__CLASS__, 'choices'],
            [__CLASS__, 'canEdit']
        );
        Registry::add(
            'get-post-byline',
            __('Read post authors and Author Box', 'publishpress-authors'),
            __('Read exact ordered author term IDs, author-category assignments, fallback WordPress author and per-post Author Box with a revision hash. Requires native byline and edit_post permissions; never guesses author IDs or writes during reads.', 'publishpress-authors'),
            Registry::object($post, ['post_id']),
            $output,
            [__CLASS__, 'get'],
            [__CLASS__, 'canEdit']
        );
        Registry::add(
            'update-post-byline',
            __('Update post authors and Author Box', 'publishpress-authors'),
            __('Replace a post byline with ordered author_ids, or patch category assignments and its Author Box. Omitted fields are preserved. Requires the latest hash and an enabled post type. dry_run previews without saving. Discover authors, categories and native layout choices first.', 'publishpress-authors'),
            $change,
            $output,
            [__CLASS__, 'update'],
            [__CLASS__, 'canEdit'],
            true
        );
        $batchChange = $change;
        unset($batchChange['properties']['dry_run']);
        Registry::add(
            'bulk-update-post-bylines',
            __('Update bylines on multiple posts', 'publishpress-authors'),
            __('Apply explicit per-post byline changes after validating every object, revision hash and author/category identifier. Each change has its own post_id and expected_hash. dry_run previews all targets without saving. On failure, prior writes are compensated with their original bylines.', 'publishpress-authors'),
            Registry::object(['changes' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 100, 'items' => $batchChange], 'dry_run' => Registry::preview()], ['changes']),
            ['results' => ['type' => 'array', 'items' => ['type' => 'object']], 'dry_run' => ['type' => 'boolean']],
            [__CLASS__, 'bulk'],
            [__CLASS__, 'canBulk'],
            true
        );
    }

    public static function choices(array $input)
    {
        $current = self::get($input);
        if (is_wp_error($current)) {
            return $current;
        }
        $page = $input['page'] ?? 1;
        $limit = $input['per_page'] ?? 20;
        $args = ['taxonomy' => 'author', 'hide_empty' => false, 'search' => $input['search'] ?? '', 'number' => $limit, 'offset' => ($page - 1) * $limit];
        $terms = get_terms($args);
        if (is_wp_error($terms)) {
            return $terms;
        }
        $authors = [];
        foreach ($terms as $term) {
            $authors[] = ['author_id' => (int) $term->term_id, 'display_name' => $term->name, 'slug' => $term->slug];
        }
        unset($args['number'], $args['offset']);
        $total = wp_count_terms($args);
        $categoryArgs = ['category_status' => 1, 'paged' => $page, 'limit' => $limit, 'post_type_and_empty' => get_post_type($input['post_id'])];
        $categories = [];
        foreach (get_ppma_author_categories($categoryArgs) as $category) {
            $categories[] = ['id' => (int) $category['id'], 'category_name' => $category['category_name'], 'plural_name' => $category['plural_name']];
        }
        $categoryTotal = get_ppma_author_categories(array_merge($categoryArgs, ['count_only' => true]));
        $options = Factory::getLegacyPlugin()->modules->multiple_authors->options;
        return ['authors' => $authors, 'categories' => $categories,
            'author_boxes' => (object) (($options->show_editor_author_box_selection ?? 'no') === 'yes' ? apply_filters('pp_multiple_authors_author_layouts', []) : []),
            'page' => $page, 'authors_total_pages' => is_wp_error($total) ? 0 : (int) ceil((int) $total / $limit), 'categories_total_pages' => (int) ceil((int) $categoryTotal / $limit)];
    }

    private static function normalizedCategories($map): array
    {
        $normalized = [];
        foreach ((array) $map as $author => $ids) {
            if (!$ids) {
                continue;
            }
            $ids = array_map('intval', $ids);
            sort($ids);
            $normalized[(int) $author] = $ids;
        }
        ksort($normalized);
        return $normalized;
    }

    public static function canEdit(array $input = []): bool
    {
        $postId = $input['post_id'] ?? 0;
        $taxonomy = get_taxonomy('author');
        return $postId && $taxonomy && Utils::is_post_type_enabled(get_post_type($postId)) && Capability::currentUserCanEditPostAuthors()
            && current_user_can('edit_post', $postId) && current_user_can($taxonomy->cap->assign_terms);
    }

    public static function canBulk(array $input = []): bool
    {
        foreach ($input['changes'] ?? [] as $change) {
            if (!self::canEdit($change)) {
                return false;
            }
        }
        return !empty($input['changes']);
    }

    public static function get(array $input)
    {
        $post = get_post($input['post_id']);
        if (!$post || !Utils::is_post_type_enabled($post->post_type)) {
            return Registry::error(__('Choose a post type enabled for Authors.', 'publishpress-authors'));
        }
        $authors = Registry::postAuthors($post->ID);
        if (is_wp_error($authors)) {
            return $authors;
        }
        $categories = [];
        foreach (get_ppma_author_relations(['post_id' => $post->ID]) as $relation) {
            $categories[$relation['author_term_id']][] = (int) $relation['category_id'];
        }
        $result = ['post_id' => $post->ID, 'author_ids' => array_map('intval', wp_list_pluck($authors, 'term_id')),
            'categories' => (object) $categories, 'fallback_user_id' => (int) $post->post_author,
            'author_box' => (string) get_post_meta($post->ID, 'ppma_selected_author_box', true),
            'disable_author_box' => (bool) get_post_meta($post->ID, 'ppma_disable_author_box', true)];
        $result['hash'] = Registry::fingerprint($result);
        $result['dry_run'] = false;
        return $result;
    }

    private static function plan(array $input)
    {
        $current = self::get($input);
        if (is_wp_error($current)) {
            return $current;
        }
        if ($input['expected_hash'] !== $current['hash']) {
            return Registry::error(__('The post byline changed. Read it again before saving.', 'publishpress-authors'), 409);
        }
        $next = array_merge($current, array_intersect_key($input, $current));
        foreach ($next['author_ids'] as $id) {
            if (!Registry::author($id)) {
                return Registry::error(__('Choose existing author term IDs.', 'publishpress-authors'));
            }
        }
        $options = Factory::getLegacyPlugin()->modules->multiple_authors->options;
        if (!$next['author_ids'] && ($options->force_empty_author ?? 'no') !== 'yes') {
            return Registry::error(__('Empty bylines must first be enabled in Authors settings.', 'publishpress-authors'));
        }
        foreach ((array) $next['categories'] as $authorId => $categoryIds) {
            if (!in_array((int) $authorId, $next['author_ids'], true)) {
                // Retain only relations belonging to authors that remain on the post.
                if (isset($input['categories'])) {
                    return Registry::error(__('Category assignments must refer to the selected authors.', 'publishpress-authors'));
                }
                $categories = (array) $next['categories'];
                unset($categories[$authorId]);
                $next['categories'] = (object) $categories;
                continue;
            }
            if (count($categoryIds) > 1 && !Utils::isAuthorMultipleCategoriesEnabled()) {
                return Registry::error(__('Multiple author categories are disabled.', 'publishpress-authors'));
            }
            foreach ($categoryIds as $categoryId) {
                $category = get_ppma_author_categories(['id' => $categoryId, 'category_status' => 1]);
                $targets = $category ? maybe_unserialize($category['post_types'] ?? []) : [];
                if (
                    !$category || (int) $category['category_status'] !== 1
                    || (Utils::isAuthorsProActive() && $targets && (!is_array($targets) || !in_array(get_post_type($input['post_id']), $targets, true)))
                ) {
                    return Registry::error(__('Choose an enabled author category available for this post type.', 'publishpress-authors'));
                }
            }
        }
        $next['categories'] = (object) $next['categories'];
        if (!$next['author_ids'] && $next['fallback_user_id'] !== $current['fallback_user_id']) {
            return Registry::error(__('An empty byline preserves its existing fallback WordPress author.', 'publishpress-authors'));
        }
        if (!get_userdata($next['fallback_user_id'])) {
            return Registry::error(__('Choose an existing fallback WordPress user.', 'publishpress-authors'));
        }
        if (isset($input['author_box'])) {
            $layouts = apply_filters('pp_multiple_authors_author_layouts', []);
            if (($options->show_editor_author_box_selection ?? 'no') !== 'yes' || ($input['author_box'] !== '' && !isset($layouts[$input['author_box']]))) {
                return Registry::error(__('Choose an available Author Box and enable per-post box selection.', 'publishpress-authors'));
            }
        }
        foreach ($next['author_ids'] as $id) {
            $author = Registry::author($id);
            if (!$author->is_guest()) {
                $next['fallback_user_id'] = (int) $author->user_id;
                break;
            }
        }
        return ['before' => $current, 'after' => $next];
    }

    public static function update(array $input)
    {
        $plan = self::plan($input);
        if (is_wp_error($plan)) {
            return $plan;
        }
        if (!empty($input['dry_run'])) {
            return array_merge($plan['after'], ['dry_run' => true]);
        }
        $result = self::apply($plan['after']);
        if (is_wp_error($result) && is_wp_error(self::apply($plan['before']))) {
            return Registry::error(__('The byline save failed and its original state could not be restored. Read the post before retrying.', 'publishpress-authors'), 500);
        }
        return $result;
    }

    private static function apply(array $state)
    {
        $authors = array_map([Registry::class, 'author'], $state['author_ids']);
        Utils::set_post_authors($state['post_id'], $authors, !empty($authors), $state['fallback_user_id'], (array) $state['categories'], true);
        update_post_meta($state['post_id'], 'ppma_selected_author_box', $state['author_box']);
        update_post_meta($state['post_id'], 'ppma_disable_author_box', $state['disable_author_box'] ? '1' : '');
        \MultipleAuthors\Classes\Post_Editor::flush_post_cache($state['post_id']);
        do_action('publishpress_authors_post_authors_metabox_action_saved', $state['post_id']);
        do_action('publishpress_authors_flush_cache_for_post', $state['post_id']);
        $result = self::get($state);
        if (is_wp_error($result) || $result['fallback_user_id'] !== $state['fallback_user_id'] || $result['author_ids'] !== $state['author_ids'] || $result['author_box'] !== $state['author_box'] || $result['disable_author_box'] !== $state['disable_author_box'] || self::normalizedCategories($result['categories']) !== self::normalizedCategories($state['categories'])) {
            return Registry::error(__('Could not save the complete post byline.', 'publishpress-authors'), 500);
        }
        return $result;
    }

    public static function bulk(array $input)
    {
        $plans = [];
        foreach ($input['changes'] as $change) {
            if (isset($plans[$change['post_id']])) {
                return Registry::error(__('Include each post only once in a batch.', 'publishpress-authors'));
            }
            $plan = self::plan($change);
            if (is_wp_error($plan)) {
                return $plan;
            }
            $plans[$change['post_id']] = $plan;
        }
        $results = [];
        $applied = [];
        foreach ($plans as $plan) {
            $applied[] = $plan;
            $result = !empty($input['dry_run']) ? array_merge($plan['after'], ['dry_run' => true]) : self::apply($plan['after']);
            if (is_wp_error($result)) {
                $rollbackFailed = false;
                foreach (array_reverse($applied) as $restore) {
                    if (is_wp_error(self::apply($restore['before']))) {
                        $rollbackFailed = true;
                    }
                }
                return $rollbackFailed ? Registry::error(__('The batch failed and some original bylines could not be restored. Read all target posts before retrying.', 'publishpress-authors'), 500) : $result;
            }
            $results[] = $result;
        }
        return ['results' => $results, 'dry_run' => !empty($input['dry_run'])];
    }
}
