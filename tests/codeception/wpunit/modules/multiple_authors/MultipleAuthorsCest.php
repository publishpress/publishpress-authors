<?php namespace modules\multiple_authors;

use MultipleAuthors\Factory;
use WpunitTester;

class MultipleAuthorsCest
{
    private function getMultipleAuthorsModule()
    {
        $container = Factory::get_container();

        return $container['module'];
    }

    public function testActionSetAuthors_withMultipleAuthorTerms_shouldAddRelationshipBetweenThePostAndAuthors(
        WpunitTester $I
    ) {
        $authors = $I->haveAuthorsMappedToUsers(3);
        $postId  = $I->haveAPost();

        do_action(
            'publishpress_authors_set_post_authors',
            $postId,
            [$authors[0], $authors[1], $authors[2]]
        );

        $postAuthors = $I->getPostAuthors($postId);

        $I->assertIsArray($postAuthors);
        $I->assertCount(3, $postAuthors);
        $I->assertEquals($authors[0]->term_id, $postAuthors[0]->term_id);
        $I->assertEquals($authors[1]->term_id, $postAuthors[1]->term_id);
        $I->assertEquals($authors[2]->term_id, $postAuthors[2]->term_id);
    }

    public function testFilterWorkflowReceiverPostAuthors_withPostWithAuthorsMappedToUser_shouldAddUserIdsToTheReceiversList(
        WpunitTester $I
    ) {
        $authors = $I->haveAuthorsMappedToUsers(3);
        $postId  = $I->haveAPostWithAuthors($authors);

        $args = [
            'params' => [
                'post_id' => $postId
            ]
        ];

        $receivers = $this->getMultipleAuthorsModule()->filter_workflow_receiver_post_authors([99], 0, $args);

        $I->assertCount(4, $receivers);
        $I->assertEquals(
            [
                99,
                $authors[0]->user_id,
                $authors[1]->user_id,
                $authors[2]->user_id],
            $receivers
        );
    }

    public function testFilterWorkflowReceiverPostAuthors_withPostWithGuestAuthorsWithEmail_shouldAddAuthorEmailsToReceiversList(
        WpunitTester $I
    ) {
        $authorsMappedToUser = $I->haveAuthorsMappedToUsers(1);
        $guestAuthors        = $I->haveGuestAuthors(2, ['user_email' => 'guest_author%d@example.com']);

        $postId = $I->haveAPostWithAuthors(array_merge($authorsMappedToUser, $guestAuthors));

        $args      = [
            'params' => [
                'post_id' => $postId
            ]
        ];
        $receivers = $this->getMultipleAuthorsModule()->filter_workflow_receiver_post_authors([99], 0, $args);

        $I->assertCount(4, $receivers);
        $I->assertEquals(
            [
                99,
                $authorsMappedToUser[0]->user_id,
                'guest_author0@example.com',
                'guest_author1@example.com'
            ],
            $receivers
        );
    }

    public function testFilterWorkflowReceiverPostAuthors_withReceiversListAsEmailString_shouldAddAuthorEmailsToReceiversList(
        WpunitTester $I
    ) {
        $authorsMappedToUser = $I->haveAuthorsMappedToUsers(1);
        $guestAuthors        = $I->haveGuestAuthors(2, ['user_email' => 'guest_author%d@example.com']);

        $postId = $I->haveAPostWithAuthors(array_merge($authorsMappedToUser, $guestAuthors));

        $args      = [
            'params' => [
                'post_id' => $postId
            ]
        ];
        $receivers = $this->getMultipleAuthorsModule()->filter_workflow_receiver_post_authors(
            'current_receiver@example.com',
            0,
            $args
        );

        $I->assertCount(4, $receivers);
        $I->assertEquals(
            [
                'current_receiver@example.com',
                $authorsMappedToUser[0]->user_id,
                'guest_author0@example.com',
                'guest_author1@example.com'
            ],
            $receivers
        );
    }

    public function testFilterWorkflowReceiverPostAuthors_withReceiversListAsUserId_shouldAddAuthorEmailsToReceiversList(
        WpunitTester $I
    ) {
        $authorsMappedToUser = $I->haveAuthorsMappedToUsers(1);
        $guestAuthors        = $I->haveGuestAuthors(2, ['user_email' => 'guest_author%d@example.com']);

        $postId = $I->haveAPostWithAuthors(array_merge($authorsMappedToUser, $guestAuthors));

        $args      = [
            'params' => [
                'post_id' => $postId
            ]
        ];
        $receivers = $this->getMultipleAuthorsModule()->filter_workflow_receiver_post_authors(
            99,
            0,
            $args
        );

        $I->assertCount(4, $receivers);
        $I->assertEquals(
            [
                99,
                $authorsMappedToUser[0]->user_id,
                'guest_author0@example.com',
                'guest_author1@example.com'
            ],
            $receivers
        );
    }

    public function testFilterWorkflowReceiverPostAuthors_withMissingPostAuthor_shouldSkipInvalidAuthor(
        WpunitTester $I
    ) {
        global $wpdb;

        $missingUserId = 999999999;
        $I->setPluginSettingsPostTypes(['post']);
        $postId = $I->haveAPost();
        wp_delete_object_term_relationships($postId, 'author');
        $wpdb->update(
            $wpdb->posts,
            ['post_author' => $missingUserId],
            ['ID' => $postId],
            ['%d'],
            ['%d']
        );
        clean_post_cache($postId);
        wp_cache_delete('authors_' . $postId, 'get_post_authors:authors');

        $args = [
            'params' => [
                'post_id' => $postId
            ]
        ];

        $receivers = $this->getMultipleAuthorsModule()->filter_workflow_receiver_post_authors([99], 0, $args);

        $I->assertSame([99], $receivers);
    }

    public function testGetSyncPostAuthorDataWithNoPostTypesClearsStalePostIds(WpunitTester $I)
    {
        $module = $this->getMultipleAuthorsModule();
        $originalPostTypes = $module->module->options->post_types;
        $originalUserId = get_current_user_id();
        $dieHandler = static function () {
            return static function () {
                throw new \RuntimeException('wp_send_json completed');
            };
        };

        $adminUserId = $I->factory('an admin user')->user->create(['role' => 'administrator']);
        wp_set_current_user($adminUserId);
        $module->module->options->post_types = [];
        set_transient('publishpress_authors_sync_post_author_ids', [123, 456], HOUR_IN_SECONDS);
        $_GET['nonce'] = wp_create_nonce('sync_post_author');

        add_filter('wp_die_handler', $dieHandler, PHP_INT_MAX);
        add_filter('wp_die_ajax_handler', $dieHandler, PHP_INT_MAX);
        ob_start();

        try {
            $module->getSyncPostAuthorData();
            $I->fail('Expected wp_send_json() to terminate the request.');
        } catch (\RuntimeException $exception) {
            $I->assertSame('wp_send_json completed', $exception->getMessage());
        } finally {
            $response = ob_get_clean();
            remove_filter('wp_die_handler', $dieHandler, PHP_INT_MAX);
            remove_filter('wp_die_ajax_handler', $dieHandler, PHP_INT_MAX);
            unset($_GET['nonce']);
            $module->module->options->post_types = $originalPostTypes;
            wp_set_current_user($originalUserId);
        }

        $I->assertSame(['total' => 0], json_decode($response, true));
        $I->assertFalse(get_transient('publishpress_authors_sync_post_author_ids'));
    }
}
