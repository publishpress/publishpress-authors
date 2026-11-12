<?php

namespace wordpress;

use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use RestapiTester;

use function sq;

class postsCest
{
    public function _before(RestapiTester $I)
    {
    }

    public function tryToGrabAuthorIdFromPostWithAuthorMappedToUser(RestapiTester $I)
    {
        $userId1 = $I->factory('Create user for being the author')->user->create();
        $userId2 = $I->factory('Create user for being the author')->user->create();
        $author1 = Author::create_from_user($userId1);
        $author2 = Author::create_from_user($userId2);

        $postId = $I->factory('Create a new post')->post->create(
            [
                'post_author' => $userId1,
            ]
        );

        Utils::set_post_authors($postId, [$author2, $author1]);

        $I->haveHttpHeader('accept', 'application/json');
        $I->haveHttpHeader('content-type', 'application/json');
        $I->sendGet('?rest_route=/wp/v2/posts/' . $postId);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['author' => $author2->user_id]);
    }

    public function tryToGrabUserIdFromAuthorOnPostWithGuestAuthor(RestapiTester $I)
    {
        $userId = $I->factory('Create user for being the fallback author')->user->create();
        $guestAuthor = Author::create(
            [
                'display_name' => sq('Guest author 0'),
                'slug'         => sq('guest-author-0'),
            ]
        );

        $postId = $I->factory('Create a new post')->post->create(
            [
                'post_author' => $userId,
            ]
        );

        Utils::set_post_authors($postId, [$guestAuthor], true, $userId);

        $I->haveHttpHeader('accept', 'application/json');
        $I->haveHttpHeader('content-type', 'application/json');
        $I->sendGet('?rest_route=/wp/v2/posts/' . $postId);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['author' => $userId]);
    }

    public function tryToGrabMultipleAuthorIdsFromPostWithAuthorsMappedToUser(RestapiTester $I)
    {
        $userId1 = $I->factory('Create user for being the author')->user->create();
        $userId2 = $I->factory('Create user for being the author')->user->create();
        $author1 = Author::create_from_user($userId1);
        $author2 = Author::create_from_user($userId2);

        $postId = $I->factory('Create a new post')->post->create(
            [
                'post_author' => $userId1,
            ]
        );

        Utils::set_post_authors($postId, [$author2, $author1]);

        $I->haveHttpHeader('accept', 'application/json');
        $I->haveHttpHeader('content-type', 'application/json');
        $I->sendGet('?rest_route=/wp/v2/posts/' . $postId);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(
            [
                'authors' => [
                    [
                        "term_id"      => $author2->term_id,
                        "user_id"      => $author2->user_id,
                        "is_guest"     => 0,
                        "slug"         => $author2->user_nicename,
                        "display_name" => $author2->display_name,
                    ],
                    [
                        "term_id"      => $author1->term_id,
                        "user_id"      => $author1->user_id,
                        "is_guest"     => 0,
                        "slug"         => $author1->user_nicename,
                        "display_name" => $author1->display_name,
                    ]
                ]
            ]
        );
    }

    public function tryToGrabAuthorsFromMultiplePostsCollection(RestapiTester $I)
    {
        $userId1 = $I->factory('Create user for being the first author')->user->create();
        $userId2 = $I->factory('Create user for being the second author')->user->create();
        $author1 = Author::create_from_user($userId1);
        $author2 = Author::create_from_user($userId2);

        $postId1 = $I->factory('Create first post')->post->create(
            [
                'post_author' => $userId1,
            ]
        );
        $postId2 = $I->factory('Create second post')->post->create(
            [
                'post_author' => $userId2,
            ]
        );

        Utils::set_post_authors($postId1, [$author1]);
        Utils::set_post_authors($postId2, [$author2]);

        $I->haveHttpHeader('accept', 'application/json');
        $I->haveHttpHeader('content-type', 'application/json');
        $I->sendGet(
            '?rest_route=/wp/v2/posts&include[]=' . $postId1 . '&include[]=' . $postId2 . '&_fields=id,authors'
        );
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(
            [
                [
                    'id'      => $postId1,
                    'authors' => [
                        [
                            'term_id'      => $author1->term_id,
                            'user_id'      => $author1->user_id,
                            'display_name' => $author1->display_name,
                        ],
                    ],
                ],
                [
                    'id'      => $postId2,
                    'authors' => [
                        [
                            'term_id'      => $author2->term_id,
                            'user_id'      => $author2->user_id,
                            'display_name' => $author2->display_name,
                        ],
                    ],
                ],
            ]
        );
    }

    public function tryToGrabMultipleAuthorIdsFromPostWithGuestAuthors(RestapiTester $I)
    {
        $userId = $I->factory('Create user for being the fallback author')->user->create();
        $author1 = Author::create(
            [
                'display_name' => sq('Guest author 0'),
                'slug'         => sq('guest-author-0'),
            ]
        );
        $author2 = Author::create(
            [
                'display_name' => sq('Guest author 1'),
                'slug'         => sq('guest-author-1'),
            ]
        );

        $postId = $I->factory('Create a new post')->post->create(
            [
                'post_author' => $userId,
            ]
        );

        Utils::set_post_authors($postId, [$author2, $author1]);

        $I->haveHttpHeader('accept', 'application/json');
        $I->haveHttpHeader('content-type', 'application/json');
        $I->sendGet('?rest_route=/wp/v2/posts/' . $postId);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(
            [
                'authors' => [
                    [
                        "term_id"      => $author2->term_id,
                        "user_id"      => 0,
                        "is_guest"     => 1,
                        "slug"         => $author2->user_nicename,
                        "display_name" => $author2->display_name,
                    ],
                    [
                        "term_id"      => $author1->term_id,
                        "user_id"      => 0,
                        "is_guest"     => 1,
                        "slug"         => $author1->user_nicename,
                        "display_name" => $author1->display_name,
                    ]
                ]
            ]
        );
    }
}
