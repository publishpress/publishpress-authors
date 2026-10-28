<?php

declare(strict_types=1);

namespace MultipleAuthors\Mcp;

use MultipleAuthors\Capability;
use MultipleAuthors\Classes\Author_Editor;
use MultipleAuthors\Classes\Author_Utils;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Factory;

\defined('ABSPATH') || exit;

/** Author profile operations reuse the native API's validation and synchronization. @since 4.18.0 */
class Profiles
{
    public static function register()
    {
        $id = ['author_id' => ['type' => 'integer', 'minimum' => 1]];
        Registry::add(
            'get-profile-fields',
            __('Discover author profile fields', 'publishpress-authors'),
            __('Read native field labels, types and choices for an author. Enabled custom fields are included. Email, account identity, passwords and privileged user fields are excluded from profile fields; account linking has a separate action.', 'publishpress-authors'),
            Registry::object($id),
            ['schema' => ['type' => 'object']],
            [__CLASS__, 'fields'],
            [__CLASS__, 'canManage']
        );
        Registry::add(
            'get-author-profile',
            __('Read author profile details', 'publishpress-authors'),
            __('Read an author profile, public native field values and a revision hash. Hidden REST fields, emails and arbitrary metadata are excluded. Use get-profile-fields before editing and the latest hash to update.', 'publishpress-authors'),
            Registry::object($id, ['author_id']),
            self::output(),
            [__CLASS__, 'get'],
            [__CLASS__, 'canManage']
        );
        Registry::add(
            'save-author',
            __('Create or update author profile', 'publishpress-authors'),
            __('Create a guest author, create a profile linked to an existing user_id, or patch an existing profile. Guest creation must be enabled. Discover native fields first; update requires expected_hash. Preserves omitted fields and respects linked-user permissions and administrator profile locks. dry_run validates without saving. Does not create WordPress accounts.', 'publishpress-authors'),
            Registry::object(array_merge($id, [
                'user_id' => ['type' => 'integer', 'minimum' => 1],
                'display_name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 200],
                'slug' => ['type' => 'string', 'pattern' => '^[a-z0-9][a-z0-9_-]*$', 'maxLength' => 200],
                'fields' => ['type' => 'object', 'maxProperties' => 100, 'additionalProperties' => ['type' => ['string', 'integer', 'boolean', 'array'], 'items' => ['type' => 'string']]],
                'expected_hash' => Registry::hash(), 'dry_run' => Registry::preview(),
            ])),
            self::output(),
            [__CLASS__, 'save'],
            [__CLASS__, 'canManage'],
            true,
            false
        );
        Registry::add(
            'link-author-user',
            __('Link or unlink an author user account', 'publishpress-authors'),
            __('Link an existing author to an existing WordPress user, or use user_id 0 to unlink without deleting or changing the account. Requires the latest profile hash, edit_user on linked accounts, native mapping restrictions and administrator profile policy. dry_run previews without saving.', 'publishpress-authors'),
            Registry::object(array_merge($id, ['user_id' => ['type' => 'integer', 'minimum' => 0], 'expected_hash' => Registry::hash(), 'dry_run' => Registry::preview()]), ['author_id', 'user_id', 'expected_hash']),
            self::output(),
            [__CLASS__, 'link'],
            [__CLASS__, 'canManage'],
            true
        );
        Registry::add(
            'delete-author',
            __('Delete an unassigned author profile', 'publishpress-authors'),
            __('Delete a verified, unassigned author profile using native taxonomy permissions. Reassign existing posts first. Never deletes its linked WordPress account. Requires the latest profile hash; dry_run checks without deleting.', 'publishpress-authors'),
            Registry::object(array_merge($id, ['expected_hash' => Registry::hash(), 'dry_run' => Registry::preview()]), ['author_id', 'expected_hash']),
            ['author_id' => ['type' => 'integer'], 'dry_run' => ['type' => 'boolean']],
            [__CLASS__, 'delete'],
            [__CLASS__, 'canManage'],
            true
        );
    }

    private static function output(): array
    {
        return ['author' => ['type' => 'object'], 'fields' => ['type' => 'object'], 'hash' => ['type' => 'string'], 'dry_run' => ['type' => 'boolean']];
    }

    public static function canManage(array $input = []): bool
    {
        $module = Factory::getLegacyPlugin()->modules->multiple_authors ?? null;
        $taxonomy = get_taxonomy('author');
        return $module && ($module->options->enabled ?? 'off') === 'on' && $taxonomy
            && Capability::currentUserCanManageAuthors()
            && (empty($input['author_id']) ? current_user_can($taxonomy->cap->edit_terms) : current_user_can('edit_term', $input['author_id']));
    }

    private static function definitions($author): array
    {
        $fields = apply_filters('multiple_authors_author_fields', Author_Editor::get_fields($author), $author);
        foreach ($fields as $name => $field) {
            if (in_array($name, ['user_id', 'user_email', 'user_login'], true) || Fields::sensitive($name)) {
                unset($fields[$name]);
            }
        }
        return $fields;
    }

    private static function visible(string $name, array $field): bool
    {
        $excluded = apply_filters('ppma_rest_api_authors_meta_excluded_fields', ['user_id', 'avatar']);
        if (in_array($name, $excluded, true)) {
            return false;
        }
        if (!empty($field['show_in_rest'])) {
            return $field['show_in_rest'] !== 'off';
        }
        return !in_array($name, apply_filters('ppma_rest_api_default_hidden_author_fields', ['user_email']), true);
    }

    public static function fields(array $input = []): array
    {
        $author = !empty($input['author_id']) ? Registry::author($input['author_id']) : false;
        $definitions = self::definitions($author);
        $schema = Fields::schema($definitions);
        foreach ((array) ($schema['properties'] ?? []) as $name => $field) {
            if (!self::visible($name, $definitions[$name])) {
                $schema['properties'][$name]['writeOnly'] = true;
            }
        }
        return ['schema' => $schema];
    }

    public static function get(array $input)
    {
        $author = Registry::author($input['author_id']);
        if (!$author) {
            return Registry::error(__('Author not found.', 'publishpress-authors'), 404);
        }
        $data = \PP_Authors_Abilities::get_author($input);
        if (is_wp_error($data)) {
            return $data;
        }
        $values = [];
        foreach (self::definitions($author) as $name => $field) {
            if (self::visible($name, $field) && isset(Fields::schema([$name => $field])['properties'][$name])) {
                $value = $author->get_meta($name);
                if (is_scalar($value) || is_array($value)) {
                    $values[$name] = $value;
                }
            }
        }
        return ['author' => (object) $data, 'fields' => (object) Fields::publicValues($values, self::definitions($author)),
            'hash' => Registry::fingerprint([$author->getTerm(), get_term_meta($author->term_id), self::userRevision((int) $author->user_id)]), 'dry_run' => false];
    }

    private static function userRevision(int $id): array
    {
        $user = $id ? get_userdata($id) : false;
        if (!$user) {
            return [];
        }
        $values = ['display_name' => $user->display_name, 'user_url' => $user->user_url];
        foreach (['first_name', 'last_name', 'nickname', 'description'] as $key) {
            $values[$key] = get_user_meta($id, $key, true);
        }
        return $values;
    }

    private static function restoreProfile(int $id, $beforeTerm, array $termValues, int $userId, array $userValues, array $userRevision): bool
    {
        $result = $beforeTerm
            ? wp_update_term($id, 'author', ['name' => $beforeTerm->name, 'slug' => $beforeTerm->slug])
            : wp_delete_term($id, 'author');
        if (is_wp_error($result) || !$result) {
            return false;
        }
        if ($userId) {
            $updated = wp_update_user(['ID' => $userId, 'display_name' => $userRevision['display_name'], 'user_url' => $userRevision['user_url']]);
            if (is_wp_error($updated)) {
                return false;
            }
        }
        if ($beforeTerm) {
            foreach (array_diff_key(get_term_meta($id), $termValues) as $name => $unused) {
                delete_term_meta($id, $name);
            }
        }
        foreach ($beforeTerm ? $termValues : [] as $name => $values) {
            if ($values) {
                update_term_meta($id, $name, $values[0]);
            } else {
                delete_term_meta($id, $name);
            }
            if (get_term_meta($id, $name) != $values) {
                return false;
            }
        }
        foreach ($userValues as $name => $values) {
            if ($values) {
                update_user_meta($userId, $name, $values[0]);
            } else {
                delete_user_meta($userId, $name);
            }
            if (get_user_meta($userId, $name) != $values) {
                return false;
            }
        }
        Author::clear_cache();
        return true;
    }

    private static function revision(array $input)
    {
        $current = self::get($input);
        if (is_wp_error($current)) {
            return $current;
        }
        if (($input['expected_hash'] ?? '') !== $current['hash']) {
            return Registry::error(__('The author changed. Read the profile again before saving.', 'publishpress-authors'), 409);
        }
        return $current;
    }

    private static function userAccess(int $userId)
    {
        if (!$userId) {
            return true;
        }
        $user = get_userdata($userId);
        if (!$user || !current_user_can('edit_user', $userId)) {
            return Registry::error(__('You cannot edit the linked user account.', 'publishpress-authors'), 403);
        }
        $options = Factory::getLegacyPlugin()->modules->multiple_authors->options;
        if (
            $userId !== get_current_user_id() && in_array('administrator', $user->roles, true)
            && (!current_user_can('manage_options') || ($options->allow_admin_edit_admin_profiles ?? 'no') !== 'yes')
        ) {
            return Registry::error(__('Editing other administrator profiles is disabled.', 'publishpress-authors'), 403);
        }
        return true;
    }

    public static function save(array $input)
    {
        $author = empty($input['author_id']) ? false : Registry::author($input['author_id']);
        $current = $author ? self::revision($input) : ['author' => (object) [], 'fields' => (object) [], 'hash' => '', 'dry_run' => false];
        if (!empty($input['author_id']) && !$author) {
            return Registry::error(__('Author not found.', 'publishpress-authors'), 404);
        }
        if (is_wp_error($current)) {
            return $current;
        }
        $mappedUser = !empty($input['user_id']) ? get_userdata($input['user_id']) : false;
        if (isset($input['user_id']) && ($author || !$mappedUser)) {
            return Registry::error(__('Choose an existing user for a new profile; use link-author-user to remap an existing author.', 'publishpress-authors'));
        }
        if ($mappedUser) {
            $existing = Author::get_by_user_id($mappedUser->ID);
            if ($existing && !publishpress_authors_remove_single_user_map_restriction()) {
                return Registry::error(__('This user already has an author profile.', 'publishpress-authors'), 409);
            }
            $input['display_name'] = $input['display_name'] ?? $mappedUser->display_name;
            $input['slug'] = $input['slug'] ?? $mappedUser->user_nicename;
        }
        $access = self::userAccess($author ? (int) $author->user_id : ($mappedUser ? $mappedUser->ID : 0));
        if (is_wp_error($access)) {
            return $access;
        }
        $values = Fields::prepare($input['fields'] ?? [], self::definitions($author));
        if (is_wp_error($values)) {
            return $values;
        }
        if (!empty($values['avatar']) && (get_post_type($values['avatar']) !== 'attachment' || !current_user_can('edit_post', (int) $values['avatar']))) {
            return Registry::error(__('Choose an editable media attachment for the avatar.', 'publishpress-authors'));
        }
        if (!$author && !$mappedUser && (empty($input['display_name']) || (Factory::getLegacyPlugin()->modules->multiple_authors->options->enable_guest_author_user ?? 'no') !== 'yes')) {
            return Registry::error(__('Guest author creation requires a name and must be enabled in Authors settings.', 'publishpress-authors'));
        }
        $linkedUser = $mappedUser ?: ($author && $author->user_id ? get_userdata($author->user_id) : false);
        if ($linkedUser && isset($input['slug']) && $input['slug'] !== $linkedUser->user_nicename) {
            return Registry::error(__('A linked author uses the WordPress account nicename as its slug.', 'publishpress-authors'));
        }
        $slug = $input['slug'] ?? ($author ? $author->slug : sanitize_title($input['display_name']));
        $duplicate = get_term_by('slug', $slug, 'author');
        if (!$slug || ($duplicate && (!$author || (int) $duplicate->term_id !== (int) $author->term_id))) {
            return Registry::error(__('Choose a unique, non-empty author slug.', 'publishpress-authors'), 409);
        }
        if (!empty($input['dry_run'])) {
            $current['fields'] = (object) array_merge((array) $current['fields'], array_diff_key($values, array_filter(self::definitions($author), static function ($field, $name) {
                return !self::visible($name, $field);
            }, ARRAY_FILTER_USE_BOTH)));
            if ($mappedUser) {
                $current['author']->user_id = $mappedUser->ID;
                $current['author']->is_guest = false;
            }
            foreach (['display_name', 'slug'] as $key) {
                if (isset($input[$key])) {
                    $current['author']->$key = sanitize_text_field($input[$key]);
                }
            }
            $current['dry_run'] = true;
            return $current;
        }
        $beforeTerm = $author ? $author->getTerm() : false;
        $beforeTermValues = $author ? get_term_meta($author->term_id) : [];
        $beforeUserValues = [];
        $userId = $author ? (int) $author->user_id : ($mappedUser ? (int) $mappedUser->ID : 0);
        $beforeUserRevision = self::userRevision($userId);
        foreach ($values as $name => $value) {
            if ($userId && Author_Editor::can_sync_author_field_to_user_meta($name)) {
                $beforeUserValues[$name] = get_user_meta($userId, $name);
            }
        }
        $request = new \WP_REST_Request($author ? 'PATCH' : 'POST');
        $request->set_param('author_fields', $values);
        if ($mappedUser) {
            $request->set_param('user_id', $mappedUser->ID);
        }
        foreach (['display_name', 'slug'] as $key) {
            if (isset($input[$key])) {
                $request->set_param($key, $input[$key]);
            }
        }
        if ($author) {
            $request->set_param('id', $input['author_id']);
        }
        $service = Factory::getLegacyPlugin()->rest_api;
        $response = $author ? $service->patchAuthorCallback($request) : $service->createAuthorCallback($request);
        if (is_wp_error($response)) {
            return $response;
        }
        $data = $response->get_data();
        $savedId = (int) ($data['term_id'] ?? $data['id'] ?? 0);
        foreach ($values as $name => $value) {
            if (get_term_meta($savedId, $name, true) != $value) {
                $restored = self::restoreProfile($savedId, $beforeTerm, $beforeTermValues, $userId, $beforeUserValues, $beforeUserRevision);
                return Registry::error($restored
                    ? __('Profile field save failed; the original state was restored.', 'publishpress-authors')
                    : __('Profile field save failed and restoration was incomplete. Read the profile before retrying.', 'publishpress-authors'), 500);
            }
        }
        Author::clear_cache();
        return self::get(['author_id' => (int) ($data['term_id'] ?? $data['id'] ?? 0)]);
    }

    public static function link(array $input)
    {
        $current = self::revision($input);
        if (is_wp_error($current)) {
            return $current;
        }
        foreach ([(int) $current['author']->user_id, $input['user_id']] as $userId) {
            $access = self::userAccess($userId);
            if (is_wp_error($access)) {
                return $access;
            }
        }
        if ($input['user_id']) {
            $existing = Author::get_by_user_id($input['user_id']);
            if ($existing && (int) $existing->term_id !== $input['author_id'] && !publishpress_authors_remove_single_user_map_restriction()) {
                return Registry::error(__('The user is already mapped to another author.', 'publishpress-authors'), 409);
            }
        }
        if (!empty($input['dry_run'])) {
            $current['author']->user_id = $input['user_id'];
            $current['author']->is_guest = !$input['user_id'];
            $current['dry_run'] = true;
            return $current;
        }
        if (!$input['user_id']) {
            Author_Utils::unlink_author_from_user($input['author_id']);
        } else {
            $request = new \WP_REST_Request('PATCH');
            $request->set_param('id', $input['author_id']);
            $request->set_param('user_id', $input['user_id']);
            $response = Factory::getLegacyPlugin()->rest_api->patchAuthorCallback($request);
            if (is_wp_error($response)) {
                return $response;
            }
        }
        Author::clear_cache();
        return self::get($input);
    }

    public static function delete(array $input)
    {
        $current = self::revision($input);
        if (is_wp_error($current)) {
            return $current;
        }
        if (!current_user_can('delete_term', $input['author_id'])) {
            return Registry::error(__('You cannot delete this author profile.', 'publishpress-authors'), 403);
        }
        $assigned = get_objects_in_term($input['author_id'], 'author');
        if (is_wp_error($assigned) || $assigned) {
            return Registry::error(__('Reassign posts before deleting this author profile.', 'publishpress-authors'));
        }
        if (empty($input['dry_run'])) {
            $result = wp_delete_term($input['author_id'], 'author');
            if (is_wp_error($result) || !$result) {
                return is_wp_error($result) ? $result : Registry::error(__('Could not delete the author profile.', 'publishpress-authors'), 500);
            }
            Author::clear_cache();
        }
        return ['author_id' => $input['author_id'], 'dry_run' => !empty($input['dry_run'])];
    }
}
