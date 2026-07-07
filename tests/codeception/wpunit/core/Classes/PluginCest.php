<?php

namespace core\Classes;

use Codeception\Example;
use MultipleAuthors\Classes\Author_Editor;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Factory;
use WpunitTester;

class PluginCest
{
    private function countAuthors()
    {
        $terms = get_terms(
            [
                'taxonomy' => 'author',
                'hide_empty' => false
            ]
        );

        return count($terms);
    }

    /**
     * @example ["author"]
     * @example ["subscriber"]
     * @example ["administrator"]
     */
    public function actionUserRegister_forNoUserRoleSelected_doNotCreateAuthor(WpunitTester $I, Example $example)
    {
        $legacyPlugin = Factory::getLegacyPlugin();

        $countAuthorsBefore = $this->countAuthors();

        add_action(
            'user_register',
            ['MultipleAuthors\\Classes\\Author_Editor', 'action_user_register'],
            20
        );

        $legacyPlugin->modules->multiple_authors->options->author_for_new_users = [];

        $userID = $I->factory("a new {$example[0]} user")->user->create(['role' => $example[0]]);

        $author = Author::get_by_user_id($userID);

        $countAuthorsAfter = $this->countAuthors();

        $I->assertFalse($author);
        $I->assertEquals($countAuthorsBefore, $countAuthorsAfter);
    }

    /**
     * @example ["author"]
     * @example ["subscriber"]
     * @example ["administrator"]
     */
    public function actionUserRegister_forUserRoleNotSelected_doNotCreateAuthor(WpunitTester $I, Example $example)
    {
        $legacyPlugin = Factory::getLegacyPlugin();

        $countAuthorsBefore = $this->countAuthors();

        add_action(
            'user_register',
            ['MultipleAuthors\\Classes\\Author_Editor', 'action_user_register'],
            20
        );

        $legacyPlugin->modules->multiple_authors->options->author_for_new_users = ['contributor'];

        $userID = $I->factory("a new {$example[0]} user")->user->create(['role' => $example[0]]);

        $author = Author::get_by_user_id($userID);

        $countAuthorsAfter = $this->countAuthors();

        $I->assertFalse($author);
        $I->assertEquals($countAuthorsBefore, $countAuthorsAfter);
    }

    /**
     * @example ["author"]
     * @example ["subscriber"]
     * @example ["administrator"]
     */
    public function actionUserRegister_forUserRoleSelected_doNotCreateAuthor(WpunitTester $I, Example $example)
    {
        $legacyPlugin = Factory::getLegacyPlugin();

        add_action(
            'user_register',
            ['MultipleAuthors\\Classes\\Author_Editor', 'action_user_register'],
            20
        );

        $legacyPlugin->modules->multiple_authors->options->author_for_new_users = ['author', 'subscriber', 'administrator'];

        $userID = $I->factory('a new user')->user->create(['role' => 'author']);

        $author = Author::get_by_user_id($userID);

        $I->assertInstanceOf(Author::class, $author);
    }

    public function actionEditedAuthor_forMappedAuthor_preservesParagraphTagsInDescription(WpunitTester $I)
    {
        $userID = $I->factory('a new user')->user->create(
            [
                'role'          => 'author',
                'display_name'  => 'Original Author',
                'first_name'    => 'Original',
                'last_name'     => 'Author',
                'user_email'    => 'original-author@example.com',
                'user_login'    => 'original-author',
                'user_nicename' => 'original-author',
                'user_url'      => 'https://example.com/original-author',
                'description'   => 'Original author bio.',
            ]
        );

        $author = Author::create_from_user($userID);

        wp_set_current_user($userID);

        $_POST = [
            'author-edit-nonce' => wp_create_nonce('author-edit'),
            'authors-user_id' => $userID,
            'authors-description' => '<p>First paragraph.</p><p>Second paragraph.</p>',
            'authors-first_name' => 'Updated',
        ];

        Author_Editor::action_edited_author($author->term_id);

        $I->assertEquals('<p>First paragraph.</p><p>Second paragraph.</p>', get_user_meta($userID, 'description', true));
        $I->assertEquals('<p>First paragraph.</p><p>Second paragraph.</p>', get_term_meta($author->term_id, 'description', true));
    }

    public function profileUpdate_forMappedAuthor_syncsAuthorProfileFields(WpunitTester $I)
    {
        $userID = $I->factory('a new user')->user->create(
            [
                'role'          => 'author',
                'display_name'  => 'Original Author',
                'first_name'    => 'Original',
                'last_name'     => 'Author',
                'user_email'    => 'original-author@example.com',
                'user_login'    => 'original-author',
                'user_nicename' => 'original-author',
                'user_url'      => 'https://example.com/original-author',
                'description'   => 'Original author bio.',
            ]
        );

        $author = Author::create_from_user($userID);

        wp_update_user(
            [
                'ID'            => $userID,
                'display_name'  => 'Updated Author',
                'first_name'    => 'Updated',
                'last_name'     => 'Author',
                'user_email'    => 'updated-author@example.com',
                'user_nicename' => 'updated-author',
                'user_url'      => 'https://example.com/updated-author',
                'description'   => 'Updated author bio.',
            ]
        );

        $term = get_term($author->term_id, 'author');

        $I->assertEquals('Updated Author', $term->name);
        $I->assertEquals('updated-author', $term->slug);
        $I->assertEquals('Updated', get_term_meta($author->term_id, 'first_name', true));
        $I->assertEquals('Author', get_term_meta($author->term_id, 'last_name', true));
        $I->assertEquals('updated-author@example.com', get_term_meta($author->term_id, 'user_email', true));
        $I->assertEquals('original-author', get_term_meta($author->term_id, 'user_login', true));
        $I->assertEquals('https://example.com/updated-author', get_term_meta($author->term_id, 'user_url', true));
        $I->assertEquals('Updated author bio.', get_term_meta($author->term_id, 'description', true));
    }
}
