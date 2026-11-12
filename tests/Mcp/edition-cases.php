<?php
/** Included by the smoke suite; tests the real active edition. */
$pro = \MultipleAuthors\Classes\Utils::isAuthorsProActive();
if (!$pro) {
    $check(!isset(wp_get_abilities()['publishpress-authors/map-guest-author-to-user']), 'Free does not register Pro mapping');
    $check(!isset(wp_get_abilities()['publishpress-authors/save-custom-field']) && !isset(wp_get_abilities()['publishpress-authors/delete-custom-field']), 'Free omits Pro-only definition writes');
    $check(is_wp_error($call('update-plugin-settings', ['settings' => ['display_branding' => false]])), 'Free rejects Pro branding changes');
    $check(is_wp_error($call('save-author-category', ['id' => $categoryId, 'expected_hash' => $category['hash'], 'post_types' => ['post']])), 'Free rejects category post-type targeting');
    $check(!isset($listFields['schema']['properties']['search_box']), 'Free discovery excludes Pro search controls');
    return;
}
$check(isset($listFields['schema']['properties']['search_box']), 'Actual Pro helper contributes search fields');
$field = $success($call('save-custom-field', ['title' => 'Fixture Expertise', 'slug' => 'mcp-expertise-' . strtolower(wp_generate_password(6, false)),
    'status' => 'publish', 'fields' => ['type' => 'text', 'field_status' => 'on', 'show_in_rest' => 'on']]), 'Pro creates native custom field');
$posts[] = $field['id'];
$check(get_post_meta($field['id'], 'ppmacf_type', true) === 'text', 'Native custom field storage is correct');
$updatedCategory = $success($call('save-author-category', ['id' => $categoryId, 'expected_hash' => $category['hash'], 'post_types' => ['post']]), 'Pro saves category post-type targeting');
$check(!empty($updatedCategory['category']->post_types), 'Native category targeting round-trips');
$success($call('update-plugin-settings', ['settings' => ['display_branding' => false]]), 'Pro branding setting uses native storage');
$slugUser = get_userdata($users['author']);
$mappingProfile = $success($call('save-author', ['display_name' => 'Mapping fixture', 'slug' => 'mcp-map-' . strtolower(wp_generate_password(8, false))]), 'Create mapping fixture');
$terms[] = $mappingProfile['author']->term_id;
$success($call('update-plugin-settings', ['settings' => ['remove_single_user_map_restriction' => true]]), 'Enable native duplicate-user mapping for the synthetic preview');
$mapping = $success($call('link-author-user', ['author_id' => $mappingProfile['author']->term_id, 'user_id' => $users['author'], 'expected_hash' => $mappingProfile['hash'], 'dry_run' => true]), 'Preview native account linking');
$check($mapping['dry_run'] && $mapping['author']->user_id === $users['author'], 'Link preview identifies the account without saving');
$check($call('get-author-profile', ['author_id' => $mappingProfile['author']->term_id])['author']->is_guest, 'Link preview preserves guest status');

$legacy->update_module_option('automap_author', 'enabled', 'on');
$matchingUser = get_userdata($users['subscriber']);
$matched = $success($call('save-author', ['display_name' => 'Pro exact-match fixture', 'slug' => $matchingUser->user_nicename]), 'Create a Pro Auto Map match');
$terms[] = $matched['author']->term_id;
$mapInput = ['author_id' => $matched['author']->term_id, 'expected_hash' => $matched['hash']];
$mappedPreview = $success($call('map-guest-author-to-user', $mapInput), 'Pro Auto Map previews by default');
$check($mappedPreview['dry_run'] && $call('get-author-profile', ['author_id' => $matched['author']->term_id])['author']->is_guest, 'Default mapping preview preserves guest storage');
$mapped = $success($call('map-guest-author-to-user', $mapInput + ['dry_run' => false]), 'Pro Auto Map links the exact native user');
$check($mapped['author']->user_id === $users['subscriber'] && !$mapped['author']->is_guest, 'Pro mapping round-trips the matching account');
$legacy->update_module_option('automap_author', 'enabled', 'off');
$check(!wp_get_ability('publishpress-authors/map-guest-author-to-user')->check_permissions($mapInput), 'Disabled Pro Auto Map denies execution');

$fieldOrdered = $success($call('save-custom-field', ['id' => $field['id'], 'expected_hash' => $field['hash'], 'menu_order' => 12]), 'Save native Pro field ordering');
$check($fieldOrdered['menu_order'] === 12 && get_post($field['id'])->menu_order === 12, 'Custom field order uses native menu_order');
$definitions = $call('get-author-fields');
$check(in_array($field['slug'], array_column($definitions['fields'], 'name'), true), 'Legacy field discovery includes native Pro custom fields');

$proTemplates = \MA_Author_Boxes_Pro::getAuthorBoxesDefaultList();
$proTemplate = array_keys($proTemplates)[0];
$proBox = $success($call('save-author-box', ['title' => 'Pro grouping fixture', 'template' => $proTemplate]), 'Create a box from an actual Pro category template');
$posts[] = $proBox['id'];
$check($proBox['parent_layout'] === $proTemplate && count((array) $proBox['fields']) > 5, 'Pro template retains its native parent and layout fields');
$freshGuest = $call('get-author-profile', ['author_id' => $authorId]);
$customValue = $success($call('save-author', ['author_id' => $authorId, 'expected_hash' => $freshGuest['hash'], 'fields' => [$field['slug'] => 'Synthetic expertise']]), 'Save an actual Pro custom profile field');
$check($customValue['fields']->{$field['slug']} === 'Synthetic expertise', 'Pro custom field values round-trip through native profile storage');

$pageCategory = $success($call('save-author-category', ['category_name' => 'Page-only fixture ' . wp_generate_password(5, false), 'plural_name' => 'Page-only fixtures', 'post_types' => ['page'], 'enabled' => true]), 'Create a Pro post-type scoped category');
$categories[] = $pageCategory['category']->id;
$freshByline = $call('get-post-byline', ['post_id' => $postId]);
$check(is_wp_error($call('update-post-byline', ['post_id' => $postId, 'expected_hash' => $freshByline['hash'], 'categories' => [(string) $authorId => [$pageCategory['category']->id]], 'dry_run' => true])), 'Post bylines reject a category scoped to a different post type');
