<?php
/** Standalone registration, localization and private-metadata contract. */
define('ABSPATH', __DIR__);
$abilities = [];
$categories = [];
function __($value) { return 'translated:' . $value; }
function add_action() {}
function do_action() {}
function wp_has_ability_category($name) { global $categories; return isset($categories[$name]); }
function wp_register_ability_category($name, $args) { global $categories; $categories[$name] = $args; }
function wp_register_ability($name, $args) { global $abilities; $abilities[$name] = $args; }
foreach (glob(__DIR__ . '/../../src/core/Mcp/*.php') as $file) {
    require_once $file;
}
require __DIR__ . '/../../src/abilities.php';
PP_Authors_Abilities::register_category();
PP_Authors_Abilities::register_category();
PP_Authors_Abilities::register_abilities();
if (count($categories) !== 1 || count($abilities) !== 35) {
    throw new RuntimeException('Expected one shared category and 35 Free actions.');
}
foreach ($abilities as $ability) {
    if ($ability['category'] !== 'publishpress' || $ability['meta']['public'] !== false || $ability['meta']['show_in_rest'] !== false || $ability['meta']['mcp']['public'] !== false) {
        throw new RuntimeException('Actions must be private in the shared category.');
    }
    foreach (['label', 'description'] as $key) {
        if (strpos($ability[$key], 'translated:') !== 0) {
            throw new RuntimeException('Metadata must be translated.');
        }
    }
}
echo "Registration, localization and private metadata passed.\n";
