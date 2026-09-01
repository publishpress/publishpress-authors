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

    public function actionEditedAuthor_forMappedAuthorEmailChangeUnlinksAuthorAndDoesNotDeleteUserEmail(WpunitTester $I)
    {
        $userID = $I->factory('a new user')->user->create(
            [
                'role'          => 'author',
                'display_name'  => 'Mapped Author',
                'first_name'    => 'Mapped',
                'last_name'     => 'Author',
                'user_email'    => 'mapped-author@example.com',
                'user_login'    => 'mapped-author',
                'user_nicename' => 'mapped-author',
                'user_url'      => 'https://example.com/mapped-author',
                'description'   => 'Mapped author bio.',
            ]
        );
        $author = Author::create_from_user($userID);

        $adminUserID = $I->factory('a new administrator user')->user->create(
            [
                'role' => 'administrator',
            ]
        );
        wp_set_current_user($adminUserID);

        $_POST['author-edit-nonce']  = wp_create_nonce('author-edit');
        $_POST['authors-user_id']    = $userID;
        $_POST['authors-first_name'] = 'Mapped';
        $_POST['authors-last_name']  = 'Author';
        $_POST['authors-user_email'] = '';
        $_POST['authors-user_url']   = 'https://example.com/mapped-author';
        $_POST['authors-description'] = 'Mapped author bio.';

        Author_Editor::action_edited_author($author->term_id);

        $user = get_user_by('id', $userID);

        $I->assertEquals('', get_term_meta($author->term_id, 'user_email', true));
        $I->assertEquals('mapped-author@example.com', $user->user_email);
        $I->assertEquals('', get_term_meta($author->term_id, 'user_id', true));
        $I->assertEquals('', get_term_meta($author->term_id, 'user_id_' . $userID, true));
        $I->assertFalse(Author::get_by_user_id($userID));

        $_POST = [];
    }

    public function actionEditedAuthor_forMappedAuthorRejectsEmailUsedByAnotherUser(WpunitTester $I)
    {
        $userID = $I->factory('a new user')->user->create(
            [
                'role'          => 'author',
                'display_name'  => 'Mapped Author',
                'user_email'    => 'mapped-author-conflict@example.com',
                'user_login'    => 'mapped-author-conflict',
                'user_nicename' => 'mapped-author-conflict',
            ]
        );
        $author = Author::create_from_user($userID);

        $otherUserID = $I->factory('another user')->user->create(
            [
                'role'       => 'author',
                'user_email' => 'other-author@example.com',
            ]
        );

        $adminUserID = $I->factory('a new administrator user')->user->create(
            [
                'role' => 'administrator',
            ]
        );
        wp_set_current_user($adminUserID);

        $_POST['author-edit-nonce']  = wp_create_nonce('author-edit');
        $_POST['authors-user_id']    = $userID;
        $_POST['authors-first_name'] = '';
        $_POST['authors-last_name']  = '';
        $_POST['authors-user_email'] = 'other-author@example.com';
        $_POST['authors-user_url']   = '';
        $_POST['authors-description'] = '';

        Author_Editor::action_edited_author($author->term_id);

        $user = get_user_by('id', $userID);
        ob_start();
        Author_Editor::admin_notices();
        $notice = ob_get_clean();

        $I->assertEquals('mapped-author-conflict@example.com', get_term_meta($author->term_id, 'user_email', true));
        $I->assertEquals('mapped-author-conflict@example.com', $user->user_email);
        $I->assertEquals($userID, (int)get_term_meta($author->term_id, 'user_id', true));
        $I->assertEquals('user_id', get_term_meta($author->term_id, 'user_id_' . $userID, true));
        $I->assertNotEquals($otherUserID, (int)get_term_meta($author->term_id, 'user_id', true));
        $I->assertStringContainsString(
            'This email address is already linked to another WordPress user.',
            $notice
        );

        $_POST = [];
    }

    public function filterPreInsertTerm_forNewUserAuthorRejectsEmailUsedByAnotherUser(WpunitTester $I)
    {
        $I->factory('another user')->user->create(
            [
                'role'       => 'author',
                'user_email' => 'existing-user@example.com',
            ]
        );

        $_POST['action']               = 'add-tag';
        $_POST['authors-author_type']  = 'new_user';
        $_POST['authors-author_email'] = 'existing-user@example.com';
        $_POST['authors-new']          = '';
        $_POST['tag-name']             = 'Duplicate Email Author';

        $result = Author_Editor::filter_pre_insert_term('Duplicate Email Author', 'author');

        $I->assertTrue(is_wp_error($result));
        $I->assertEquals('publishpress_authors_email_exists', $result->get_error_code());

        $_POST = [];
    }
}
