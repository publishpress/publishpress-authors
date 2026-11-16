<?php

namespace core\Classes;

use MA_Multiple_Authors;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Plugin;
use ReflectionClass;
use WpunitTester;

class AuthorSearchCest
{
    public function searchTreatsQuotesAndWildcardsAsText(WpunitTester $I)
    {
        global $wpdb, $pagenow, $current_screen;

        $previousPage = $pagenow;
        $previousScreen = $current_screen;
        $termIds = [];

        try {
            $pagenow = 'edit-tags.php';
            set_current_screen('edit-author');
            $suffix = uniqid();
            foreach (['Alice', 'Bob'] as $name) {
                $term = wp_insert_term($name . ' ' . $suffix, 'author');
                $I->assertFalse(is_wp_error($term));
                $termIds[] = (int)$term['term_id'];
            }
            update_term_meta($termIds[0], 'first_name', "O'Reilly");
            update_term_meta($termIds[0], 'last_name', '100%Real');
            update_term_meta($termIds[0], 'user_email', 'literal_under@example.test');
            update_term_meta($termIds[1], 'last_name', '100XReal');
            update_term_meta($termIds[1], 'user_email', 'literalXunder@example.test');

            $plugin = (new ReflectionClass(Plugin::class))->newInstanceWithoutConstructor();
            $cases = [
                ["zzz'OR(1=1)OR'", []],
                ["zzz'OR(1=0)OR'", []],
                ["O'Re", [$termIds[0]]],
                ['100%', [$termIds[0]]],
                ['literal_', [$termIds[0]]],
                ['Alice Bob', $termIds],
            ];
            foreach ($cases as $case) {
                $clauses = $plugin->include_term_name_in_search(
                    [
                        'where' => sprintf(' WHERE t.term_id IN (%d, %d)', $termIds[0], $termIds[1]),
                        'fields' => 't.term_id',
                        'orderby' => '',
                    ],
                    ['author'],
                    ['taxonomy' => ['author'], 'custom_author_search' => $case[0]]
                );
                $found = $wpdb->get_col(
                    "SELECT t.term_id FROM {$wpdb->terms} t"
                    . " INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id"
                    . $clauses['where'] . ' ORDER BY t.term_id'
                );
                $I->assertSame('', $wpdb->last_error);
                $I->assertSame($case[1], array_map('intval', $found), $case[0]);
            }
        } finally {
            foreach ($termIds as $termId) {
                wp_delete_term($termId, 'author');
            }
            $pagenow = $previousPage;
            $current_screen = $previousScreen;
        }
    }

    public function profileSaveExceptionCannotOpenTheAuthorList(WpunitTester $I)
    {
        global $pagenow;

        $previous = [$_POST, $_REQUEST, $_SERVER['REQUEST_METHOD'] ?? null, $pagenow, get_current_user_id()];
        $userId = $I->factory('a contributor')->user->create(['role' => 'contributor']);
        $author = Author::create_from_user($userId);
        $I->assertInstanceOf(Author::class, $author);
        $module = (new ReflectionClass(MA_Multiple_Authors::class))->newInstanceWithoutConstructor();

        try {
            wp_set_current_user($userId);
            $pagenow = 'edit-tags.php';
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $valid = [
                'action' => 'editedtag',
                'tag_ID' => (string)$author->term_id,
                'taxonomy' => 'author',
                '_wpnonce' => wp_create_nonce('update-tag_' . $author->term_id),
            ];
            $cases = [
                [$valid, ['ppma_edit_own_profile']],
                [$valid + ['filter_action' => 'filter'], ['ppma_manage_authors']],
                [array_replace($valid, ['_wpnonce' => 'invalid']), ['ppma_manage_authors']],
                [array_diff_key($valid, ['_wpnonce' => true]), ['ppma_manage_authors']],
                [array_replace($valid, ['taxonomy' => 'category']), ['ppma_manage_authors']],
            ];
            foreach ($cases as $case) {
                $_POST = $_REQUEST = $case[0];
                $I->assertSame(
                    $case[1],
                    $module->filter_term_map_meta_cap(['ppma_manage_authors'], 'ppma_manage_authors', $userId, [])
                );
            }
            $_POST = $_REQUEST = $valid;
            $_REQUEST['action'] = 'edit';
            $I->assertSame(
                ['ppma_manage_authors'],
                $module->filter_term_map_meta_cap(['ppma_manage_authors'], 'ppma_manage_authors', $userId, [])
            );
            $_POST = $_REQUEST = [];
            $I->assertSame(
                ['ppma_edit_own_profile'],
                $module->filter_term_map_meta_cap(['ppma_manage_authors'], 'edit_term', $userId, [$author->term_id])
            );
        } finally {
            list($_POST, $_REQUEST, $method, $pagenow, $previousUser) = $previous;
            if ($method === null) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $method;
            }
            wp_set_current_user($previousUser);
        }
    }
}
