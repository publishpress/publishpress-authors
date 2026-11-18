<?php
/** Standalone installer regression: php tests/author-categories-install.php [plugin-root]. */
namespace MultipleAuthors\Classes\Legacy {
    class Module {}
}
namespace MultipleAuthorCategories {
    class AuthorCategoriesSchema {
        public static $tables = [];
        public static function tableName() { return 'test_ppma_author_categories'; }
        public static function createTableIfNotExists() { self::$tables['categories'] = true; }
        public static function createMetaTableIfNotExists() { self::$tables['meta'] = true; }
        public static function createRelationTableIfNotExists() { self::$tables['relationships'] = true; }
    }
}
namespace {
    class WP_Role {
        public $caps = [];
        public function add_cap($cap) { $this->caps[$cap] = true; }
        public function has_cap($cap) { return !empty($this->caps[$cap]); }
        public function remove_cap($cap) { unset($this->caps[$cap]); }
    }
    class CategoryTestDatabase {
        public $rows = [];
        public $insert_id = 0;
        public function prepare($sql, $slug) { return $slug; }
        public function get_var($slug) {
            foreach ($this->rows as $row) {
                if ($row['slug'] === $slug) { return $row['id']; }
            }
            return null;
        }
        public function insert($table, $row) {
            $row['id'] = ++$this->insert_id;
            $this->rows[$row['id']] = $row;
            return 1;
        }
    }
    function current_user_can($cap) { return $GLOBALS['can_manage_categories']; }
    function apply_filters($name, $value) { return $value; }
    function esc_html__($text, $domain) { return $text; }
    function sanitize_title($text) { return strtolower($text); }
    function current_time($format, $gmt) { return '2026-11-18 00:00:00'; }
    function do_action($name, ...$args) {}
    function get_role($name) { return $GLOBALS['roles'][$name] ?? null; }
    function get_option($name) { return $GLOBALS['options'][$name] ?? false; }
    function update_option($name, $value) { $GLOBALS['options'][$name] = $value; }
    function get_ppma_author_categories($args = []) {
        if (!empty($args['count_only'])) { return count($GLOBALS['wpdb']->rows); }
        return $GLOBALS['wpdb']->rows[$args['id']] ?? [];
    }
    function check($condition, $message) {
        if (!$condition) { throw new \RuntimeException($message); }
        echo "PASS: $message\n";
    }
    function reset_installation() {
        $GLOBALS['wpdb'] = new CategoryTestDatabase();
        $GLOBALS['options'] = [];
        $GLOBALS['roles'] = ['administrator' => new WP_Role()];
        $GLOBALS['can_manage_categories'] = false;
        \MultipleAuthorCategories\AuthorCategoriesSchema::$tables = [];
    }
    $root = $argv[1] ?? dirname(__DIR__);
    require $root . '/src/modules/author-categories/author-categories.php';
    $module = (new \ReflectionClass('MA_Author_Categories'))->newInstanceWithoutConstructor();
    reset_installation();
    $module->runInstallTasks('4.20.1');
    check(count($wpdb->rows) === 4, 'Fresh installation seeds four defaults without a current-user capability');
    check(count(\MultipleAuthorCategories\AuthorCategoriesSchema::$tables) === 3, 'Category, metadata and relationship tables are initialized');
    check(array_column($wpdb->rows, 'slug') === ['author', 'coauthor', 'reviewer', 'editor'], 'Expected default categories are present');
    check(array_column($wpdb->rows, 'category_status') === [1, 0, 0, 0], 'Only the Author category is enabled by default');
    check(get_option('ppma_author_categories_installed') === 1 && get_role('administrator')->has_cap('ppma_manage_author_categories'), 'Installation records completion and grants administrator access');
    check($module->addAuthorCategory(['slug' => 'unauthorized']) === false && count($wpdb->rows) === 4, 'Public category creation still rejects unauthorized callers');
    $wpdb->rows[1]['category_name'] = 'Custom Author';
    $module->runInstallTasks('4.20.1');
    check(count($wpdb->rows) === 4 && $wpdb->rows[1]['category_name'] === 'Custom Author', 'Repeated installation preserves customized defaults without duplicates');
    $GLOBALS['can_manage_categories'] = true;
    check(!empty($module->addAuthorCategory(['slug' => 'translator', 'category_name' => 'Translator'])), 'Authorized callers can still create categories');
    reset_installation();
    update_option('ppma_author_categories_installed', 1);
    update_option('ppma_author_categories_meta_installed', 1);
    $module->runUpgradeTasks('4.20.0');
    check(count($wpdb->rows) === 4, 'Upgrade repairs an empty installation previously marked complete');
    $wpdb->rows = [1 => ['id' => 1, 'slug' => 'custom', 'category_name' => 'Custom']];
    $module->runUpgradeTasks('4.20.0');
    check(count($wpdb->rows) === 1 && $wpdb->rows[1]['slug'] === 'custom', 'Upgrade preserves existing custom categories and deleted defaults');
    $wpdb->rows = [];
    $module->runUpgradeTasks('4.20.1');
    check(count($wpdb->rows) === 0, 'Later upgrades do not recreate intentionally removed categories');
}
