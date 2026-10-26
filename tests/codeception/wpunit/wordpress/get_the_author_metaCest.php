<?php

namespace wordpress;

use Codeception\Example;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use WpunitTester;

class get_the_author_metaCest
{
    /**
     * @example {"metaKey": "aim"}
     * @example {"metaKey": "description"}
     * @example {"metaKey": "jabber"}
     * @example {"metaKey": "nickname"}
     * @example {"metaKey": "yim"}
     * @example {"metaKey": "facebook"}
     * @example {"metaKey": "twitter"}
     * @example {"metaKey": "instagram"}
     */
    public function getMetadataWhenAuthorIsMappedToUserUsingPassedAuthorID(
        WpunitTester $I,
        Example $example
    ) {
        $expectedMetaValue = sprintf('meta_%s', $example['metaKey']);

        $userID = $I->factory('a new user')->user->create(['role' => 'author']);
        $author = Author::create_from_user($userID);

        update_user_meta($userID, $example['metaKey'], $expectedMetaValue);

        $metaValue = get_the_author_meta($example['metaKey'], $author->ID);

        $I->assertEquals(
            $expectedMetaValue,
            $metaValue,
            'The returned meta value should match the user meta'
        );
    }

    /**
     * @example {"metaKey": "aim"}
     * @example {"metaKey": "description"}
     * @example {"metaKey": "jabber"}
     * @example {"metaKey": "nickname"}
     * @example {"metaKey": "yim"}
     * @example {"metaKey": "facebook"}
     * @example {"metaKey": "twitter"}
     * @example {"metaKey": "instagram"}
     */
    public function getMetadataWhenAuthorIsMappedToUserUsingGlobalAuthordata(
        WpunitTester $I,
        Example $example
    ) {
        $postId          = $I->factory('a new post')->post->create();
        $post            = get_post($postId);
        $GLOBALS['post'] = $post;

        $userId1 = $I->factory('author 1 user')->user->create(['role' => 'author']);
        $author1 = Author::create_from_user($userId1);

        $userId2 = $I->factory('author 2 user')->user->create(['role' => 'author']);
        $author2 = Author::create_from_user($userId2);

        Utils::set_post_authors($postId, [$author1, $author2]);

        $GLOBALS['authordata'] = get_user_by('ID', $userId1);

        $expectedMetaValue = sprintf('meta_%s', $example['metaKey']);

        update_user_meta($userId1, $example['metaKey'], $expectedMetaValue);

        $metaValue = get_the_author_meta($example['metaKey']);

        $I->assertEquals(
            $expectedMetaValue,
            $metaValue,
            'The returned meta value should match the user meta'
        );
    }

    /**
     * @example {"metaKey": "aim"}
     * @example {"metaKey": "description"}
     * @example {"metaKey": "jabber"}
     * @example {"metaKey": "nickname"}
     * @example {"metaKey": "yim"}
     * @example {"metaKey": "facebook"}
     * @example {"metaKey": "twitter"}
     * @example {"metaKey": "instagram"}
     */
    public function getMetadataWhenAuthorIsGuestUsingPassedAuthorId(WpunitTester $I, Example $example)
    {
        $authorSlug        = sprintf('guest_author_%s', rand(1, PHP_INT_MAX));
        $expectedMetaValue = sprintf('meta_%s', $example['metaKey']);

        $author = Author::create(
            [
                'slug'         => $authorSlug,
                'display_name' => strtoupper($authorSlug),
            ]
        );

        update_term_meta($author->term_id, $example['metaKey'], $expectedMetaValue);

        $metaValue = get_the_author_meta($example['metaKey'], $author->ID);

        $I->assertEquals(
            $expectedMetaValue,
            $metaValue,
            'The returned meta value doesnt match the term meta'
        );
    }

    /**
     * @example {"metaKey": "aim"}
     * @example {"metaKey": "description"}
     * @example {"metaKey": "jabber"}
     * @example {"metaKey": "nickname"}
     * @example {"metaKey": "yim"}
     * @example {"metaKey": "facebook"}
     * @example {"metaKey": "twitter"}
     * @example {"metaKey": "instagram"}
     */
    public function tryWhenAuthorIsGuestUsingGlobalAuthordata(WpunitTester $I, Example $example)
    {
        $postId          = $I->factory('a new post')->post->create();
        $post            = get_post($postId);
        $GLOBALS['post'] = $post;

        $authorSlug        = sprintf('guest_author_%s', rand(1, PHP_INT_MAX));
        $expectedMetaValue = sprintf('meta_%s', $example['metaKey']);

        $author = Author::create(
            [
                'slug'         => $authorSlug,
                'display_name' => strtoupper($authorSlug),
            ]
        );

        Utils::set_post_authors($postId, [$author]);

        $GLOBALS['authordata'] = $author;

        update_term_meta($author->term_id, $example['metaKey'], $expectedMetaValue);

        $metaValue = get_the_author_meta($example['metaKey']);

        $I->assertEquals(
            $expectedMetaValue,
            $metaValue,
            'The returned meta value should match the user meta'
        );
    }

    /**
     * @example {"metaKey": "url", "expectedValue": "http://test.example.com"}
     * @example {"metaKey": "user_url", "expectedValue": "http://test.example.com"}
     * @example {"metaKey": "email", "expectedValue": "##slug##@example.com"}
     * @example {"metaKey": "user_email", "expectedValue": "##slug##@example.com"}
     * @example {"metaKey": "nicename", "expectedValue": "##slug##"}
     * @example {"metaKey": "user_nicename", "expectedValue": "##slug##"}
     * @example {"metaKey": "first_name", "expectedValue": "MyFirstName"}
     * @example {"metaKey": "last_name", "expectedValue": "MyLastName"}
     * @example {"metaKey": "display_name", "expectedValue": "MyDisplayName"}
     */
    public function tryToGetUserDataWhenAuthorIsMappedToUserUsingPassedAuthorId(WpunitTester $I, Example $example)
    {
        $authorSlug               = sprintf('author_%s', rand(1, PHP_INT_MAX));
        $example['expectedValue'] = str_replace('##slug##', $authorSlug, $example['expectedValue']);

        $userProperty = $example['metaKey'];
        if (in_array($userProperty, ['url', 'email', 'nicename'])) {
            $userProperty = sprintf('user_%s', $userProperty);
        }

        $userID = $I->factory('a new user')->user->create(
            [
                'user_nicename' => $authorSlug,
                'role'          => 'author',
                $userProperty   => $example['expectedValue']
            ]
        );
        $author = Author::create_from_user($userID);

        $metaValue = get_the_author_meta($example['metaKey'], $author->ID);

        $I->assertEquals(
            $example['expectedValue'],
            $metaValue
        );
    }

    /**
     * @example {"metaKey": "url", "expectedValue": "http://test.example.com"}
     * @example {"metaKey": "user_url", "expectedValue": "http://test.example.com"}
     * @example {"metaKey": "email", "expectedValue": "##slug##@example.com"}
     * @example {"metaKey": "user_email", "expectedValue": "##slug##@example.com"}
     * @example {"metaKey": "nicename", "expectedValue": "##slug##"}
     * @example {"metaKey": "user_nicename", "expectedValue": "##slug##"}
     * @example {"metaKey": "first_name", "expectedValue": "MyFirstName"}
     * @example {"metaKey": "last_name", "expectedValue": "MyLastName"}
     * @example {"metaKey": "display_name", "expectedValue": "MyDisplayName"}
     */
    public function tryToGetUserDataWhenAuthorIsMappedToUserUsingGlobalAuthordata(WpunitTester $I, Example $example)
    {
        $authorSlug = sprintf('guest_author_%s', rand(1, PHP_INT_MAX));

        $example['expectedValue'] = str_replace('##slug##', $authorSlug, $example['expectedValue']);

        $userProperty = $example['metaKey'];
        if (in_array($userProperty, ['url', 'email', 'nicename'])) {
            $userProperty = sprintf('user_%s', $userProperty);
        }

        $userID = $I->factory('a new user')->user->create(
            [
                'user_nicename' => $authorSlug,
                'role'          => 'author',
                $userProperty   => $example['expectedValue']
            ]
        );
        $author = Author::create_from_user($userID);

        $GLOBALS['authordata'] = get_user_by('ID', $userID);

        $postId          = $I->factory('a new post')->post->create();
        $post            = get_post($postId);
        $GLOBALS['post'] = $post;

        Utils::set_post_authors($postId, [$author]);

        $metaValue = get_the_author_meta($example['metaKey']);

        $I->assertEquals(
            $example['expectedValue'],
            $metaValue
        );
    }

    /**
     * @example {"metaKey": "url", "expectedValue": "http://test.example.com"}
     * @example {"metaKey": "user_url", "expectedValue": "http://test.example.com"}
     * @example {"metaKey": "email", "expectedValue": "##slug##@example.com"}
     * @example {"metaKey": "user_email", "expectedValue": "##slug##@example.com"}
     * @example {"metaKey": "nicename", "expectedValue": "##slug##"}
     * @example {"metaKey": "user_nicename", "expectedValue": "##slug##"}
     * @example {"metaKey": "first_name", "expectedValue": "MyFirstName"}
     * @example {"metaKey": "last_name", "expectedValue": "MyLastName"}
     * @example {"metaKey": "display_name", "expectedValue": "##display_name##"}
     */
    public function tryToGetMetaWhenAuthorIsGuestUsingPassedAuthorId(WpunitTester $I, Example $example)
    {
        $authorSlug = sprintf('guest_author_%s', rand(1, PHP_INT_MAX));
        $authorName = strtoupper($authorSlug);

        $example['expectedValue'] = str_replace('##slug##', $authorSlug, $example['expectedValue']);
        $example['expectedValue'] = str_replace('##display_name##', $authorName, $example['expectedValue']);

        $author = Author::create(
            [
                'slug'         => $authorSlug,
                'display_name' => $authorName,
            ]
        );

        if ('nicename' !== $example['metaKey']) {
            $metaKey = $example['metaKey'];
            if (in_array($metaKey, ['url', 'email'])) {
                $metaKey = sprintf('user_%s', $metaKey);
            }

            update_term_meta(
                $author->term_id,
                $metaKey,
                $example['expectedValue']
            );
        }

        $metaValue = get_the_author_meta($example['metaKey'], $author->ID);

        $I->assertEquals(
            $example['expectedValue'],
            $metaValue
        );
    }

    /**
     * @example {"metaKey": "url", "expectedValue": "http://test.example.com"}
     * @example {"metaKey": "user_url", "expectedValue": "http://test.example.com"}
     * @example {"metaKey": "email", "expectedValue": "##slug##@example.com"}
     * @example {"metaKey": "user_email", "expectedValue": "##slug##@example.com"}
     * @example {"metaKey": "nicename", "expectedValue": "##slug##"}
     * @example {"metaKey": "user_nicename", "expectedValue": "##slug##"}
     * @example {"metaKey": "first_name", "expectedValue": "MyFirstName"}
     * @example {"metaKey": "last_name", "expectedValue": "MyLastName"}
     * @example {"metaKey": "display_name", "expectedValue": "##display_name##"}
     */
    public function tryToGetMetaWhenAuthorIsGuestUsingGlobalAuthordata(WpunitTester $I, Example $example)
    {
        $authorSlug = sprintf('guest_author_%s', rand(1, PHP_INT_MAX));
        $authorName = strtoupper($authorSlug);

        $example['expectedValue'] = str_replace('##slug##', $authorSlug, $example['expectedValue']);
        $example['expectedValue'] = str_replace('##display_name##', $authorName, $example['expectedValue']);

        $author = Author::create(
            [
                'slug'         => $authorSlug,
                'display_name' => $authorName,
            ]
        );

        if ('nicename' !== $example['metaKey']) {
            $metaKey = $example['metaKey'];
            if (in_array($metaKey, ['url', 'email'])) {
                $metaKey = sprintf('user_%s', $metaKey);
            }

            update_term_meta(
                $author->term_id,
                $metaKey,
                $example['expectedValue']
            );
        }

        $GLOBALS['authordata'] = $author;

        $postId          = $I->factory('a new post')->post->create();
        $post            = get_post($postId);
        $GLOBALS['post'] = $post;

        Utils::set_post_authors($postId, [$author]);

        $metaValue = get_the_author_meta($example['metaKey']);

        $I->assertEquals(
            $example['expectedValue'],
            $metaValue
        );
    }


    public function tryToGetIdWhenAuthorIsMappedToUserUsingPassedAuthorId(WpunitTester $I)
    {
        $userID = $I->factory('a new user')->user->create(['role' => 'author']);
        $author = Author::create_from_user($userID);

        $metaValue = get_the_author_meta('ID', $author->ID);

        $I->assertEquals(
            $userID,
            $metaValue,
            'The ID should match the user ID'
        );
    }

    public function tryToGetIdWhenAuthorIsMappedToUserUsingGlobalAuthordata(WpunitTester $I)
    {
        $userID = $I->factory('a new user')->user->create(['role' => 'author']);
        $author = Author::create_from_user($userID);

        $GLOBALS['authordata'] = get_user_by('ID', $userID);

        $postId          = $I->factory('a new post')->post->create();
        $post            = get_post($postId);
        $GLOBALS['post'] = $post;

        Utils::set_post_authors($postId, [$author]);

        $metaValue = get_the_author_meta('ID');

        $I->assertEquals(
            $userID,
            $metaValue,
            'The ID should match the user ID'
        );
    }

    public function tryToGetIdForWhenAuthorIsGuestUsingPassedAuthorId(WpunitTester $I)
    {
        $authorSlug = sprintf('guest_author_%s', rand(1, PHP_INT_MAX));

        $author = Author::create(
            [
                'slug'         => $authorSlug,
                'display_name' => strtoupper($authorSlug),
            ]
        );

        $metaValue = get_the_author_meta('ID', $author->ID);

        $I->assertEquals(
            $author->ID,
            $metaValue,
            'The ID should match the term_id as negative integer'
        );
    }

    public function tryToGetIdForWhenAuthorIsGuestUsingGlobalAuthordata(WpunitTester $I)
    {
        $authorSlug = sprintf('guest_author_%s', rand(1, PHP_INT_MAX));

        $author = Author::create(
            [
                'slug'         => $authorSlug,
                'display_name' => strtoupper($authorSlug),
            ]
        );

        $GLOBALS['authordata'] = $author;

        $postId          = $I->factory('a new post')->post->create();
        $post            = get_post($postId);
        $GLOBALS['post'] = $post;

        Utils::set_post_authors($postId, [$author]);

        $metaValue = get_the_author_meta('ID');

        $I->assertEquals(
            $author->ID,
            $metaValue,
            'The ID should match the term_id as negative integer'
        );
    }

    public function tryToGetGuestAuthorDisplayNameWhenThemePassesPostAuthorId(WpunitTester $I)
    {
        $fallbackUserID = $I->factory('a fallback post author')->user->create(['role' => 'administrator']);
        $postId         = $I->factory('a new post')->post->create(['post_author' => $fallbackUserID]);
        $post           = get_post($postId);

        $GLOBALS['post'] = $post;

        $authorSlug = sprintf('guest_author_%s', rand(1, PHP_INT_MAX));
        $authorName = strtoupper($authorSlug);

        $author = Author::create(
            [
                'slug'         => $authorSlug,
                'display_name' => $authorName,
            ]
        );

        Utils::set_post_authors($postId, [$author]);

        $metaValue = get_the_author_meta('display_name', $fallbackUserID);

        $I->assertEquals(
            $authorName,
            $metaValue,
            'Themes passing the WordPress post_author ID should receive the selected PublishPress author name'
        );
    }
}
