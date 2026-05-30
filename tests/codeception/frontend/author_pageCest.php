<?php

use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;

class _author_pageCest
{
    public function _before(FrontendTester $I)
    {
        $I->setPermalinkStructure('/%postname%/');
        $I->switchTheme('twentytwenty');

        $legacyPlugin = Factory::getLegacyPlugin();
        $legacyPlugin->update_module_option('multiple_authors', 'enable_plugin_author_pages', 'no');
        $legacyPlugin->update_module_option('multiple_authors', 'author_pages_layout', 'list');
        $legacyPlugin->update_module_option('multiple_authors', 'hide_author_pages_empty_posts_message', 'no');
    }

    public function pageTitleShowsAuthorForAuthorMappedToUser(FrontendTester $I)
    {
        $postId = $I->factory('a new post')->post->create();
        $userId = $I->factory('a new user')->user->create(['role' => 'author']);

        $post = get_post($postId);

        $author = Author::create_from_user($userId);

        Utils::set_post_authors($postId, [$author]);

        $I->amOnPage($I->getRelativeAuthorLink($author));
        $I->see($author->display_name, 'h1.archive-title .vcard');
    }

    public function pageTitleShowsAuthorForGuestAuthor(FrontendTester $I)
    {
        $postId = $I->factory('a new post')->post->create();

        $author = Author::create(
            [
                'display_name' => 'FFAP Author 1',
                'slug'         => 'ffap_author_1',
            ]
        );

        Utils::set_post_authors($postId, [$author]);

        $I->amOnPage($I->getRelativeAuthorLink($author));
        $I->see($author->display_name, 'h1.archive-title .vcard');
    }

    public function articleBylineShowsAuthorForAuthorMappedToUser(FrontendTester $I)
    {
        $postId = $I->factory('a new post')->post->create();
        $userId = $I->factory('a new user')->user->create(['role' => 'author']);

        $post = get_post($postId);

        $author = Author::create_from_user($userId);

        Utils::set_post_authors($postId, [$author]);

        $I->amOnPage($I->getRelativeAuthorLink($author));
        $I->see("By $author->display_name", "#post-$postId .post-author .meta-text");
    }

    public function articleBylineShowsAuthorForGuestAuthor(FrontendTester $I)
    {
        $postId = $I->factory('a new post')->post->create();

        $author = Author::create(
            [
                'display_name' => 'FFAP Author 2',
                'slug'         => 'ffap_author_2',
            ]
        );

        Utils::set_post_authors($postId, [$author]);

        $I->amOnPage($I->getRelativeAuthorLink($author));
        $I->see("By $author->display_name", "#post-$postId .post-author .meta-text");
    }

    public function authorPagesEmptyPostsMessageShowsByDefault(FrontendTester $I)
    {
        $legacyPlugin = Factory::getLegacyPlugin();
        $legacyPlugin->update_module_option('multiple_authors', 'enable_plugin_author_pages', 'yes');

        $author = Author::create(
            [
                'display_name' => 'FFAP Empty Author 1',
                'slug'         => 'ffap_empty_author_1',
            ]
        );

        $I->amOnPage($I->getRelativeAuthorLink($author));
        $I->see('Post not found for the author', '.ppma-page-content h2');
    }

    public function authorPagesEmptyPostsMessageCanBeHidden(FrontendTester $I)
    {
        $legacyPlugin = Factory::getLegacyPlugin();
        $legacyPlugin->update_module_option('multiple_authors', 'enable_plugin_author_pages', 'yes');
        $legacyPlugin->update_module_option('multiple_authors', 'author_pages_layout', 'grid');
        $legacyPlugin->update_module_option('multiple_authors', 'hide_author_pages_empty_posts_message', 'yes');

        $author = Author::create(
            [
                'display_name' => 'FFAP Empty Author 2',
                'slug'         => 'ffap_empty_author_2',
            ]
        );

        $I->amOnPage($I->getRelativeAuthorLink($author));
        $I->dontSee('Post not found for the author', '.ppma-page-content');
    }
}
