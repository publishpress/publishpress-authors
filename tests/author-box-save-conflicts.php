<?php
/** Standalone save regression: php tests/author-box-save-conflicts.php [plugin-root]. */
namespace MultipleAuthors\Classes\Legacy { class Module {} }
namespace {
    function wp_verify_nonce($nonce, $action) { return $nonce === 'valid'; }
    function sanitize_key($value) { return $value; }
    function get_post_type_object($type) { return (object) ['cap' => (object) ['edit_post' => 'edit_post']]; }
    function current_user_can($cap, ...$args) { return $GLOBALS['allowed']; }
    function get_post($id) { return (object) ['ID' => $id]; }
    function apply_filters($name, $value, ...$args) {
        return $name === 'author_boxes_editor_fields' ? $GLOBALS['fields'] : $value;
    }
    function wp_unslash($value) { return $value; }
    function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
    function map_deep($value, $callback) {
        return is_array($value) ? array_map(fn($item) => map_deep($item, $callback), $value) : $callback($value);
    }
    function get_post_meta($id, $key, $single) { return $GLOBALS['meta'][$key] ?? ''; }
    function update_post_meta($id, $key, $value) { $GLOBALS['meta'][$key] = $value; }
    require ($argv[1] ?? dirname(__DIR__)) . '/src/modules/author-boxes/author-boxes.php';
    $GLOBALS['fields'] = [
        'display_name_position' => ['type' => 'select', 'options' => ['after_avatar' => 'After Avatar', 'infront_of_avatar' => 'In front of Avatar']],
        'name_author_categories_position' => ['type' => 'select', 'options' => ['after' => 'After Name', 'before' => 'Before Name']],
        'name_show' => ['type' => 'checkbox', 'sanitize' => 'intval'],
        'name_size' => ['type' => 'number', 'sanitize' => 'intval'],
        'label' => ['type' => 'text'],
        'numeric_select' => ['type' => 'select', 'options' => [0 => 'Default', 1 => 'Alternative']],
        'alignment' => ['type' => 'select', 'options' => ['left' => 'Left', 'center' => 'Center', 'right' => 'Right']],
    ];
    function save_case($stored, $baseline, $submitted) {
        $GLOBALS['allowed'] = true;
        $GLOBALS['meta'] = ['ppma_boxes_layout_meta_value' => $stored];
        $_POST = ['author-boxes-editor-nonce' => 'valid',
            'author_boxes_editor_data' => json_encode($submitted),
            'author_boxes_editor_baseline' => json_encode($baseline)];
        $module = (new ReflectionClass('MA_Author_Boxes'))->newInstanceWithoutConstructor();
        $module->saveAuthorBoxesData(1);
        $property = new ReflectionProperty($module, 'saveConflicts');
        return [$GLOBALS['meta']['ppma_boxes_layout_meta_value'], $property->getValue($module)];
    }
    function check($condition, $message) {
        if (!$condition) { throw new RuntimeException($message); }
        echo "PASS: $message\n";
    }
    foreach ([[], ['display_name_position' => ''], ['display_name_position' => 'legacy-option']] as $stored) {
        [$saved, $conflicts] = save_case($stored, ['display_name_position' => 'after_avatar'], ['display_name_position' => 'infront_of_avatar']);
        check($saved['display_name_position'] === 'infront_of_avatar' && !$conflicts, 'Display-name position saves from an implicit browser default');
    }
    [$saved, $conflicts] = save_case([], ['name_author_categories_position' => 'after'], ['name_author_categories_position' => 'before']);
    check($saved['name_author_categories_position'] === 'before' && !$conflicts, 'Category position saves from an implicit browser default');
    [$saved, $conflicts] = save_case(['label' => 'Newer tab'], ['label' => 'Original'], ['label' => 'Stale tab']);
    check($saved['label'] === 'Newer tab' && $conflicts[1] === ['label'], 'Genuine same-field conflicts keep the newer value and warn');
    [$saved, $conflicts] = save_case(['alignment' => 'center'], ['alignment' => 'left'], ['alignment' => 'right']);
    check($saved['alignment'] === 'center' && $conflicts[1] === ['alignment'], 'Genuine dropdown conflicts still keep the newer selection and warn');
    [$saved, $conflicts] = save_case(['display_name_position' => 'infront_of_avatar'], ['display_name_position' => 'after_avatar'], ['display_name_position' => 'after_avatar']);
    check($saved['display_name_position'] === 'infront_of_avatar' && !$conflicts, 'An untouched stale dropdown preserves another tab\'s change');
    [$saved, $conflicts] = save_case(['label' => 'Newer tab'], ['display_name_position' => 'after_avatar', 'label' => 'Original'], ['display_name_position' => 'infront_of_avatar', 'label' => 'Original']);
    check($saved['label'] === 'Newer tab' && $saved['display_name_position'] === 'infront_of_avatar' && !$conflicts, 'Independent edits from two tabs are merged');
    [$saved, $conflicts] = save_case(['label' => 'Same edit'], ['label' => 'Original'], ['label' => 'Same edit']);
    check($saved['label'] === 'Same edit' && !$conflicts, 'Identical concurrent edits do not warn');
    [$saved, $conflicts] = save_case(['name_show' => 0], ['name_show' => ''], ['name_show' => '1']);
    check($saved['name_show'] === 1 && !$conflicts, 'Unchecked-checkbox normalization remains intact');
    [$saved, $conflicts] = save_case(['name_size' => 20], ['name_size' => '20'], ['name_size' => '24']);
    check($saved['name_size'] === 24 && !$conflicts, 'Numeric storage and form strings remain equivalent');
    [$saved, $conflicts] = save_case(['label' => 'Keep me'], [], ['display_name_position' => 'infront_of_avatar']);
    check($saved['label'] === 'Keep me', 'Fields absent from the consolidated payload are preserved');
    [$saved, $conflicts] = save_case([], ['numeric_select' => '0'], ['numeric_select' => '1']);
    check($saved['numeric_select'] === '1' && !$conflicts, 'Dropdowns with numeric option keys save correctly');
    $largeStored = [];
    for ($index = 0; $index < 1200; ++$index) {
        $GLOBALS['fields']['extra_' . $index] = ['type' => 'text'];
        $largeStored['extra_' . $index] = 'old';
    }
    $largeSubmitted = $largeStored;
    $largeSubmitted['extra_1199'] = 'changed';
    [$saved, $conflicts] = save_case($largeStored, $largeStored, $largeSubmitted);
    check(count($saved) === 1200 && $saved['extra_1199'] === 'changed' && !$conflicts, 'Consolidated JSON still saves more than 1000 fields');
    $module = (new ReflectionClass('MA_Author_Boxes'))->newInstanceWithoutConstructor();
    $_POST['author_boxes_editor_data'] = '{invalid';
    $before = $GLOBALS['meta'];
    $module->saveAuthorBoxesData(1);
    check($GLOBALS['meta'] === $before, 'Malformed JSON cannot overwrite saved settings');
    $_POST['author_boxes_editor_data'] = json_encode(['label' => 'Unauthorized']);
    $GLOBALS['allowed'] = false;
    $module->saveAuthorBoxesData(1);
    check($GLOBALS['meta'] === $before, 'Unauthorized saves remain rejected');
}
