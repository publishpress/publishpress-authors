<?php

namespace core\Classes;

use MultipleAuthors\Classes\Installer;
use MultipleAuthors\Classes\Objects\Author;
use WpunitTester;

class InstallerCest
{
    public function _before(WpunitTester $I)
    {
        $I->resetTheDatabase();
    }

    public function createAuthorTermsForLegacyCoreAuthors__forAllAuthors(WpunitTester $I)
    {
        $postIds     = $I->havePostsWithDifferentAuthors(10);
        $postAuthors = $I->getCorePostAuthorFromPosts($postIds);

        Installer::createAuthorTermsForLegacyCoreAuthors();

        $I->assertUsersHaveAuthorTerm($postAuthors);
    }

    public function createAuthorTermsForLegacyCoreAuthors__withOnlySomeAuthorsNeeded(WpunitTester $I)
    {
        $postIds     = $I->havePostsWithDifferentAuthors(1);
        $postAuthors = $I->getCorePostAuthorFromPosts($postIds);

        $user = get_user_by('ID', $postAuthors[0]);

        $user->description = 'I create bonsais!';
        $user->first_name  = 'Miyagi';
        $user->last_name   = 'Morita';
        $user->user_url    = 'https://miyagibonsais.com';

        wp_update_user($user);

        Installer::createAuthorTermsForLegacyCoreAuthors();

        $author = Author::get_by_user_id($postAuthors[0]);

        $I->assertUsersHaveAuthorTerm($postAuthors);
        $I->assertEquals(
            $user->ID,
            $author->user_id,
            'The user ID should be the same in the user and author'
        );
        $I->assertEquals(
            $user->display_name,
            $author->display_name,
            'The display_name should be the same in the user and author'
        );
        $I->assertEquals(
            $user->user_nicename,
            $author->slug,
            'The user_nicename and author slug should be the same'
        );
        $I->assertEquals(
            $user->description,
            $author->description,
            'The description should be the same in the user and author'
        );
        $I->assertEquals(
            $user->first_name,
            $author->first_name,
            'The first_name should be the same in the user and author'
        );
        $I->assertEquals(
            $user->last_name,
            $author->last_name,
            'The last_name should be the same in the user and author'
        );
        $I->assertEquals(
            $user->user_email,
            $author->user_email,
            'The user_email should be the same in the user and author'
        );
        $I->assertEquals(
            $user->user_url,
            $author->user_url,
            'The user_url should be the same in the user and author'
        );
    }

    public function createAuthorTermsForPostsWithLegacyCoreAuthors__addAuthorTermsForAllPosts(WpunitTester $I)
    {
        $postIds = $I->havePostsWithDifferentAuthors(10);

        Installer::createAuthorTermsForPostsWithLegacyCoreAuthors();

        $I->assertPostsHaveAuthorTerms($postIds);
    }

    public function createAuthorTermsForPostsWithLegacyCoreAuthors__withPostTypeArgument__updateOnlyTheSelectedPostType(
        WpunitTester $I
    ) {
        $postIds = $I->havePostsWithDifferentAuthors(10);
        $pageIds = $I->havePostsWithDifferentAuthors(10, 'page');

        Installer::createAuthorTermsForPostsWithLegacyCoreAuthors(
            [
                'post_type' => 'page',
            ]
        );

        $I->assertPostsDontHaveAuthorTerms($postIds);
        $I->assertPostsHaveAuthorTerms($pageIds);
    }

    public function createAuthorTermsForPostsWithLegacyCoreAuthors__withPostsPerPageArgument__updateOnlyTheSelectedPostType(
        WpunitTester $I
    ) {
        $postIds = $I->havePostsWithDifferentAuthors(10);

        Installer::createAuthorTermsForPostsWithLegacyCoreAuthors(
            [
                'posts_per_page' => 2,
            ]
        );

        $I->assertPostsHaveAuthorTerms(array_slice($postIds, 0, 2));
        $I->assertPostsDontHaveAuthorTerms(array_slice($postIds, 2, 8));
    }

    public function createAuthorTermsForPostsWithLegacyCoreAuthors__withPagedArgument__updateOnlyTheSelectedPostType(
        WpunitTester $I
    ) {
        $postIds = $I->havePostsWithDifferentAuthors(10);

        Installer::createAuthorTermsForPostsWithLegacyCoreAuthors(
            [
                'posts_per_page' => 2,
                'paged' => 2,
            ]
        );

        $I->assertPostsDontHaveAuthorTerms(array_slice($postIds, 0, 2));
        $I->assertPostsHaveAuthorTerms(array_slice($postIds, 2, 2));
        $I->assertPostsDontHaveAuthorTerms(array_slice($postIds, 4, 6));
    }

    public function getPostsWithoutAuthorTerms__returnsPostsWithNoAuthorTerms(WpunitTester $I)
    {
        $postIds = $I->havePostsWithDifferentAuthors(10);
        $I->haveAuthorTermsForPosts(array_slice($postIds, 0, 4));

        $postsWithNoTerm = Installer::getPostsWithoutAuthorTerms();

        $I->assertCount(6, $postsWithNoTerm);
        $I->assertEquals($postIds[4], $postsWithNoTerm[0]->ID);
        $I->assertEquals($postIds[5], $postsWithNoTerm[1]->ID);
        $I->assertEquals($postIds[6], $postsWithNoTerm[2]->ID);
        $I->assertEquals($postIds[7], $postsWithNoTerm[3]->ID);
        $I->assertEquals($postIds[8], $postsWithNoTerm[4]->ID);
        $I->assertEquals($postIds[9], $postsWithNoTerm[5]->ID);
    }

    public function getUsersAuthorsWithNoAuthorTerm__withMultiplePostTypes_returnsListOfUsers(WpunitTester $I)
    {
        $postIds  = $I->havePostsWithDifferentAuthors(10);
        $pagesIds = $I->havePostsWithDifferentAuthors(10, 'page');

        $postIds = array_merge($postIds, $pagesIds);

        $I->haveAuthorTermsForPosts(array_slice($postIds, 0, 3));

        $usersWithNoTerms = Installer::getUsersAuthorsWithNoAuthorTerm();

        $I->assertCount(17, $usersWithNoTerms);

        $post = get_post($postIds[3]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[0]);

        $post = get_post($postIds[4]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[1]);

        $post = get_post($postIds[5]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[2]);

        $post = get_post($postIds[6]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[3]);

        $post = get_post($postIds[7]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[4]);

        $post = get_post($postIds[8]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[5]);

        $post = get_post($postIds[9]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[6]);

        $post = get_post($postIds[10]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[7]);

        $post = get_post($postIds[11]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[8]);
    }

    public function getUsersAuthorsWithNoAuthorTerm__withDefaultPostType__returnsListOfUsers(WpunitTester $I)
    {
        $postIds = $I->havePostsWithDifferentAuthors(10);
        $I->haveAuthorTermsForPosts(array_slice($postIds, 0, 3));

        $usersWithNoTerms = Installer::getUsersAuthorsWithNoAuthorTerm();

        $I->assertCount(7, $usersWithNoTerms);

        $post = get_post($postIds[3]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[0]);

        $post = get_post($postIds[4]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[1]);

        $post = get_post($postIds[5]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[2]);

        $post = get_post($postIds[6]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[3]);

        $post = get_post($postIds[7]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[4]);

        $post = get_post($postIds[8]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[5]);

        $post = get_post($postIds[9]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[6]);
    }

    public function getUsersAuthorsWithNoAuthorTerm__withPostTypeArgument__returnsListOfUsersFromSelectedPostType(
        WpunitTester $I
    ) {
        $I->havePostsWithDifferentAuthors(5);
        $postIds = $I->havePostsWithDifferentAuthors(10, 'page');
        $I->haveAuthorTermsForPosts(array_slice($postIds, 0, 3));

        $usersWithNoTerms = Installer::getUsersAuthorsWithNoAuthorTerm(
            [
                'post_type' => 'page'
            ]
        );

        $I->assertCount(7, $usersWithNoTerms);

        $post = get_post($postIds[3]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[0]);

        $post = get_post($postIds[4]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[1]);

        $post = get_post($postIds[5]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[2]);

        $post = get_post($postIds[6]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[3]);

        $post = get_post($postIds[7]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[4]);

        $post = get_post($postIds[8]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[5]);
    }

    public function getUsersAuthorsWithNoAuthorTerm__withPostsPerPageArgument__returnsListOfUsersLimitedToTheSpecifiedNumber(
        WpunitTester $I
    ) {
        $postIds = $I->havePostsWithDifferentAuthors(10);
        $I->haveAuthorTermsForPosts(array_slice($postIds, 0, 3));

        $usersWithNoTerms = Installer::getUsersAuthorsWithNoAuthorTerm(
            [
                'posts_per_page' => 4
            ]
        );

        $I->assertCount(4, $usersWithNoTerms);

        $post = get_post($postIds[3]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[0]);

        $post = get_post($postIds[4]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[1]);

        $post = get_post($postIds[5]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[2]);

        $post = get_post($postIds[6]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[3]);
    }

    public function getUsersAuthorsWithNoAuthorTerm__withZeroPostsPerPage__returnsNoResults(WpunitTester $I)
    {
        $I->havePostsWithDifferentAuthors(2);

        $usersWithNoTerms = Installer::getUsersAuthorsWithNoAuthorTerm(
            [
                'posts_per_page' => 0,
            ]
        );

        $I->assertSame([], $usersWithNoTerms);
    }

    public function getUsersAuthorsWithNoAuthorTerm__withPaged__returnsListOfUsersLimitedToTheSpecifiedNumber(
        WpunitTester $I
    ) {
        $postIds = $I->havePostsWithDifferentAuthors(10);
        $I->haveAuthorTermsForPosts(array_slice($postIds, 0, 3));

        $usersWithNoTerms = Installer::getUsersAuthorsWithNoAuthorTerm(
            [
                'posts_per_page' => 4,
                'paged'          => 2
            ]
        );

        $I->assertCount(3, $usersWithNoTerms);

        $post = get_post($postIds[7]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[0]);

        $post = get_post($postIds[8]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[1]);

        $post = get_post($postIds[9]);
        $I->assertEquals($post->post_author, $usersWithNoTerms[2]);
    }

    public function getPostsWithoutAuthorTerms__withPagePostType__returnsOnlyPages(WpunitTester $I)
    {
        $I->havePostsWithDifferentAuthors(5, 'post');
        $postIds = $I->havePostsWithDifferentAuthors(10, 'page');
        $I->haveAuthorTermsForPosts(array_slice($postIds, 0, 4));

        $postsWithNoTerm = Installer::getPostsWithoutAuthorTerms(
            [
                'post_type' => 'page',
            ]
        );

        $I->assertCount(6, $postsWithNoTerm);
        $I->assertEquals($postIds[4], $postsWithNoTerm[0]->ID);
        $I->assertEquals($postIds[5], $postsWithNoTerm[1]->ID);
        $I->assertEquals($postIds[6], $postsWithNoTerm[2]->ID);
        $I->assertEquals($postIds[7], $postsWithNoTerm[3]->ID);
        $I->assertEquals($postIds[8], $postsWithNoTerm[4]->ID);
        $I->assertEquals($postIds[9], $postsWithNoTerm[5]->ID);
    }

    public function getPostsWithoutAuthorTerms__withAscOrderArgument__returnsOnCorrectOrder(WpunitTester $I)
    {
        $postIds = $I->havePostsWithDifferentAuthors(10);
        $I->haveAuthorTermsForPosts(array_slice($postIds, 0, 6));

        $postsWithNoTerm = Installer::getPostsWithoutAuthorTerms(
            [
                'order' => 'asc',
            ]
        );

        $I->assertCount(4, $postsWithNoTerm);
        $I->assertEquals($postIds[6], $postsWithNoTerm[0]->ID);
        $I->assertEquals($postIds[7], $postsWithNoTerm[1]->ID);
        $I->assertEquals($postIds[8], $postsWithNoTerm[2]->ID);
        $I->assertEquals($postIds[9], $postsWithNoTerm[3]->ID);
    }

    public function getPostsWithoutAuthorTerms__withDescOrderArgument__returnsOnCorrectOrder(WpunitTester $I)
    {
        $postIds = $I->havePostsWithDifferentAuthors(10);
        $I->haveAuthorTermsForPosts(array_slice($postIds, 0, 6));

        $postsWithNoTerm = Installer::getPostsWithoutAuthorTerms(
            [
                'order' => 'desc',
            ]
        );

        $I->assertCount(4, $postsWithNoTerm);
        $I->assertEquals($postIds[6], $postsWithNoTerm[3]->ID);
        $I->assertEquals($postIds[7], $postsWithNoTerm[2]->ID);
        $I->assertEquals($postIds[8], $postsWithNoTerm[1]->ID);
        $I->assertEquals($postIds[9], $postsWithNoTerm[0]->ID);
    }

    public function getPostsWithoutAuthorTerms__withOrderByArgument__returnsOnCorrectOrder(WpunitTester $I)
    {
        $postIds = $I->havePostsWithDifferentAuthors(10);
        $I->haveAuthorTermsForPosts(array_slice($postIds, 0, 6));

        $postsWithNoTerm = Installer::getPostsWithoutAuthorTerms(
            [
                'orderby' => 'post_title',
                'order'   => 'desc',
            ]
        );

        $I->assertCount(4, $postsWithNoTerm);
        $I->assertEquals($postIds[6], $postsWithNoTerm[3]->ID);
        $I->assertEquals($postIds[7], $postsWithNoTerm[2]->ID);
        $I->assertEquals($postIds[8], $postsWithNoTerm[1]->ID);
        $I->assertEquals($postIds[9], $postsWithNoTerm[0]->ID);
    }

    public function getPostsWithoutAuthorTerms__withPreviouslySupportedOrderByColumn__returnsOnCorrectOrder(
        WpunitTester $I
    ) {
        $postIds = $I->havePostsWithDifferentAuthors(4);

        foreach ($postIds as $menuOrder => $postId) {
            wp_update_post(
                [
                    'ID'         => $postId,
                    'menu_order' => $menuOrder,
                ]
            );
        }

        $postsWithNoTerm = Installer::getPostsWithoutAuthorTerms(
            [
                'orderby' => 'menu_order',
                'order'   => 'desc',
            ]
        );

        $I->assertSame(
            array_reverse($postIds),
            array_map('intval', wp_list_pluck($postsWithNoTerm, 'ID'))
        );
    }

    public function getPostsWithoutAuthorTerms__withPostPorPageArgument__returnsOnlyTheSpecifiedNumberOfResults(
        WpunitTester $I
    ) {
        $postIds = $I->havePostsWithDifferentAuthors(10);
        $I->haveAuthorTermsForPosts(array_slice($postIds, 0, 6));

        $postsWithNoTerm = Installer::getPostsWithoutAuthorTerms(
            [
                'posts_per_page' => 2,
            ]
        );

        $I->assertCount(2, $postsWithNoTerm);
        $I->assertEquals($postIds[6], $postsWithNoTerm[0]->ID);
        $I->assertEquals($postIds[7], $postsWithNoTerm[1]->ID);
    }

    public function getPostsWithoutAuthorTerms__withZeroPostsPerPage__returnsNoResults(WpunitTester $I)
    {
        $I->havePostsWithDifferentAuthors(2);

        $postsWithNoTerm = Installer::getPostsWithoutAuthorTerms(
            [
                'posts_per_page' => 0,
            ]
        );

        $I->assertSame([], $postsWithNoTerm);
    }

    public function getPostsWithoutAuthorTerms__withPagedArgument__returnsOnlyTheSpecifiedNumberOfResults(
        WpunitTester $I
    ) {
        $postIds = $I->havePostsWithDifferentAuthors(10);
        $I->haveAuthorTermsForPosts(array_slice($postIds, 0, 6));

        $postsWithNoTerm = Installer::getPostsWithoutAuthorTerms(
            [
                'posts_per_page' => 2,
                'paged'          => 2,
            ]
        );

        $I->assertCount(2, $postsWithNoTerm);
        $I->assertEquals($postIds[8], $postsWithNoTerm[0]->ID);
        $I->assertEquals($postIds[9], $postsWithNoTerm[1]->ID);
    }
}
