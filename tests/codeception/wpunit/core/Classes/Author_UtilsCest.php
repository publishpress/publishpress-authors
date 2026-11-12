<?php namespace core\Classes;

use MultipleAuthorCategories\AuthorCategoriesSchema;
use MultipleAuthors\Classes\Author_Utils;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use WpunitTester;
use function sq;

class Author_UtilsCest
{
    public function methodGet_author_term_id_by_emailShouldReturnTheAuthorWithTheGivenEmailWhenAuthorExists(
        WpunitTester $I
    ) {
        $userID = $I->factory('a new user')->user->create(['role' => 'author']);
        $author = Author::create_from_user($userID);

        $user = get_user_by('ID', $userID);

        $foundTermId = Author_Utils::get_author_term_id_by_email($user->user_email);

        $I->assertEquals($author->term_id, $foundTermId);
    }

    public function methodGet_author_term_id_by_emailShouldReturnTheGuestAuthorWithTheGivenEmailWhenAuthorExists(
        WpunitTester $I
    ) {
        $authorSlug = sprintf('guest_author_%s', rand(1, PHP_INT_MAX));
        $author     = Author::create(
            [
                'slug'         => $authorSlug,
                'display_name' => strtoupper($authorSlug),
            ]
        );

        $email = sprintf('%s@example.com', $authorSlug);

        update_term_meta($author->term_id, 'user_email', $email);

        $foundTermId = Author_Utils::get_author_term_id_by_email($author->user_email);

        $I->assertEquals($author->term_id, $foundTermId);
    }

    public function methodAuthor_has_custom_avatarShouldReturnFalseIfAuthorDontHaveAvatar(WpunitTester $I)
    {
        $userID = $I->factory('a new user')->user->create(['role' => 'author']);
        $author = Author::create_from_user($userID);

        $hasAvatar = Author_Utils::author_has_custom_avatar($author->term_id);

        $I->assertFalse($hasAvatar);
    }

    public function methodAuthor_has_custom_avatarShouldReturnTrueIfAuthorHasAnAvatar(WpunitTester $I)
    {
        $userID = $I->factory('a new user')->user->create(['role' => 'author']);
        $author = Author::create_from_user($userID);

        update_term_meta($author->term_id, 'avatar', '234');

        $hasAvatar = Author_Utils::author_has_custom_avatar($author->term_id);

        $I->assertTrue($hasAvatar);
    }

    public function methodGet_author_metaReturnsValidSingleMeta(WpunitTester $I)
    {
        $authorSlug = sprintf('guest_author_%s', rand(1, PHP_INT_MAX));
        $author     = Author::create(
            [
                'slug'         => $authorSlug,
                'display_name' => strtoupper($authorSlug),
            ]
        );

        $metaKey  = 'the_meta';
        $expected = 'Ha9d8sg76A%S%';

        update_term_meta($author->term_id, $metaKey, $expected);

        $meta = Author_Utils::get_author_meta($author->term_id, $metaKey, true);

        $I->assertEquals($expected, $meta);
    }

    public function methodGet_author_metaReturnsValidMultipleMeta(WpunitTester $I)
    {
        $authorSlug = sprintf('guest_author_%s', rand(1, PHP_INT_MAX));
        $author     = Author::create(
            [
                'slug'         => $authorSlug,
                'display_name' => strtoupper($authorSlug),
            ]
        );

        $metaKey  = 'the_meta';
        $expected = [
            'value1',
            'value2',
            'value3',
        ];

        add_term_meta($author->term_id, $metaKey, $expected[0]);
        add_term_meta($author->term_id, $metaKey, $expected[1]);
        add_term_meta($author->term_id, $metaKey, $expected[2]);

        $meta = Author_Utils::get_author_meta($author->term_id, $metaKey, false);

        $I->assertIsArray($meta);
        $I->assertEquals($expected, $meta);
    }

    public function methodAuthor_is_guestShouldReturnTrueForGuestAuthor(WpunitTester $I)
    {
        $authorSlug = sprintf('guest_author_%s', rand(1, PHP_INT_MAX));
        $author     = Author::create(
            [
                'slug'         => $authorSlug,
                'display_name' => strtoupper($authorSlug),
            ]
        );

        $isGuest = Author_Utils::author_is_guest($author->term_id);
        $I->assertTrue($isGuest);
    }

    public function methodAuthor_is_guestShouldReturnFalseForAuthorMappedToUser(WpunitTester $I)
    {
        $userID = $I->factory('a new user')->user->create(['role' => 'author']);
        $author = Author::create_from_user($userID);

        $isGuest = Author_Utils::author_is_guest($author->term_id);
        $I->assertFalse($isGuest);
    }

    public function methodGet_avatar_urlShouldReturnTheAvatarUrlForGuestAuthor(WpunitTester $I)
    {
        $authorSlug = sprintf('guest_author_%s', rand(1, PHP_INT_MAX));
        $author     = Author::create(
            [
                'slug'         => $authorSlug,
                'display_name' => strtoupper($authorSlug),
            ]
        );

        $email = 'imaguest3@example.com';
        update_term_meta($author->term_id, 'user_email', $email);

        $attachmentId = $I->factory('a new attachment for the avatar image')->attachment->create_upload_object(
            realpath(TESTS_ROOT_PATH . '/_data/avatar1.png')
        );
        update_term_meta($author->term_id, 'avatar', $attachmentId);
        $expectedAvatarUrl = wp_get_attachment_image_url($attachmentId, 96);

        $avatarUrl = Author_Utils::get_avatar_url($author->term_id);

        $I->assertEquals(
            $expectedAvatarUrl,
            $avatarUrl
        );
    }

    public function methodGet_avatar_urlShouldReturnTheCustomAvatarUrlAuthorMappedToUserWithCustomAvatar(
        WpunitTester $I
    ) {
        $userID = $I->factory('a new user')->user->create(['role' => 'author']);
        $author = Author::create_from_user($userID);

        $attachmentId = $I->factory('a new attachment for the avatar image')->attachment->create_upload_object(
            realpath(TESTS_ROOT_PATH . '/_data/avatar1.png')
        );
        update_term_meta($author->term_id, 'avatar', $attachmentId);
        $expectedAvatarUrl = wp_get_attachment_image_url($attachmentId, 96);

        $avatarUrl = Author_Utils::get_avatar_url($author->term_id);

        $I->assertEquals(
            $expectedAvatarUrl,
            $avatarUrl
        );
    }

    public function methodGet_avatar_urlShouldReturnFalseForAuthorMappedToUserWithNoCustomAvatar(WpunitTester $I)
    {
        $userID = $I->factory('a new user')->user->create(['role' => 'author']);
        $author = Author::create_from_user($userID);

        $avatarUrl = Author_Utils::get_avatar_url($author->term_id);

        $I->assertFalse($avatarUrl);
    }

    public function sync_post_author_columnShouldSetPost_authorAsCurrentUserWithArrayOfGuestAuthorsOnly(WpunitTester $I
    ) {
        $originalPostAuthorUserId = $I->factory('the original post author user')->user->create(['role' => 'author']);

        $postId = $I->factory('the post')->post->create(
            ['post_type' => 'post', 'post_author' => $originalPostAuthorUserId]
        );

        $selectedGuestAuthor1 = Author::create(
            [
                'slug'         => 'guest1',
                'display_name' => 'Guest 1',
            ]
        );

        $selectedGuestAuthor2 = Author::create(
            [
                'slug'         => 'guest2',
                'display_name' => 'Guest 3',
            ]
        );

        $selectedGuestAuthor3 = Author::create(
            [
                'slug'         => 'guest3',
                'display_name' => 'Guest 3',
            ]
        );

        $currentUserId = $I->factory('a new user for the current user')->user->create(['role' => 'author']);
        wp_set_current_user($currentUserId);

        $post = get_post($postId);
        $I->assertEquals($originalPostAuthorUserId, $post->post_author);

        Utils::sync_post_author_column($postId, [$selectedGuestAuthor1, $selectedGuestAuthor2, $selectedGuestAuthor3]);

        $postAuthor = $this->get_post_author($postId);

        $I->assertEquals(get_current_user_id(), $postAuthor);
    }

    public function sync_post_author_columnShouldSetPost_authorAsCurrentUserIfPostAuthorIsEmptyWithArrayOfGuestAuthorsOnly(WpunitTester $I
    ) {
        $postId = $I->factory('the post')->post->create(
            [
                'post_type' => 'post',
                'post_author' => 0
            ]
        );

        $selectedGuestAuthor1 = Author::create(
            [
                'slug'         => sq('guest0'),
                'display_name' => sq('Guest0'),
            ]
        );

        $selectedGuestAuthor2 = Author::create(
            [
                'slug'         => sq('guest1'),
                'display_name' => sq('Guest1'),
            ]
        );

        $selectedGuestAuthor3 = Author::create(
            [
                'slug'         => sq('guest2'),
                'display_name' => sq('Guest2'),
            ]
        );

        $currentUserId = $I->factory('a new user for the current user')->user->create(['role' => 'author']);
        wp_set_current_user($currentUserId);

        $post = get_post($postId);
        $I->assertEquals(0, $post->post_author);

        Utils::sync_post_author_column($postId, [$selectedGuestAuthor1, $selectedGuestAuthor2, $selectedGuestAuthor3]);

        $postAuthor = $this->get_post_author($postId);

        $I->assertEquals(get_current_user_id(), $postAuthor);
    }

    protected function get_post_author($post_id)
    {
        global $wpdb;

        return (int)$wpdb->get_var(
            $wpdb->prepare("SELECT post_author FROM {$wpdb->posts} WHERE ID = %d", $post_id)
        );
    }

    public function sync_post_author_columnShouldSetPost_authorUsingFirstMappedToUserAuthorButIgnoringGuestAuthorsWithArrayOfAuthorsInstances(
        WpunitTester $I
    ) {
        $originalPostAuthorUserId = $I->factory('the original post author user')->user->create(['role' => 'author']);

        $postId = $I->factory('the post')->post->create(
            ['post_type' => 'post', 'post_author' => $originalPostAuthorUserId]
        );

        $firstAuthorWhichIsGuest = Author::create(
            [
                'slug'         => sq('guest0'),
                'display_name' => sq('Guest0'),
            ]
        );

        $secondAuthorWhichIsGuest = Author::create(
            [
                'slug'         => sq('guest1'),
                'display_name' => sq('Guest1'),
            ]
        );

        $thirdAuthorUsersId     = $I->factory('a new user for the third selected author')->user->create(
            ['role' => 'author']
        );
        $thirdAuthorWhichIsUser = Author::create_from_user($thirdAuthorUsersId);

        $post = get_post($postId);
        $I->assertEquals($originalPostAuthorUserId, $post->post_author);

        Utils::sync_post_author_column(
            $postId,
            [$firstAuthorWhichIsGuest, $secondAuthorWhichIsGuest, $thirdAuthorWhichIsUser]
        );

        $postAuthor = $this->get_post_author($postId);

        $I->assertEquals($thirdAuthorUsersId, $postAuthor);
    }

    public function sync_post_author_columnShouldSetPost_authorUsingFirstAuthorWithArrayOfAuthorsInstances(
        WpunitTester $I
    ) {
        $originalPostAuthorUserId = $I->factory('the original post author user')->user->create(['role' => 'author']);

        $postId = $I->factory('the post')->post->create(
            ['post_type' => 'post', 'post_author' => $originalPostAuthorUserId]
        );

        $selectedAuthorUserId = $I->factory('a new user for the selected author')->user->create(['role' => 'author']);
        $selectedAuthor       = Author::create_from_user($selectedAuthorUserId);

        $post = get_post($postId);
        $I->assertEquals($originalPostAuthorUserId, $post->post_author);

        Utils::sync_post_author_column($postId, [$selectedAuthor]);

        $postAuthor = $this->get_post_author($postId);

        $I->assertEquals($selectedAuthorUserId, $postAuthor);
    }

    public function sync_post_author_columnShouldKeepPost_authorIntactIfAuthorListIsEmpty(WpunitTester $I)
    {
        $userId = $I->factory('a new user for the original post_author')->user->create(['role' => 'author']);
        $postId = $I->factory('a new post')->post->create(['post_type' => 'post', 'post_author' => $userId]);
        $post   = get_post($postId);

        $I->assertEquals($userId, $post->post_author);

        $currentUserId = $I->factory('a new user for being the current user')->user->create(['role' => 'author']);
        wp_set_current_user($currentUserId);

        Utils::sync_post_author_column($postId, []);

        unset($post);
        $post = get_post($postId);

        $postAuthor = $this->get_post_author($postId);

        $I->assertNotEquals($currentUserId, $postAuthor);
        $I->assertEquals($userId, $post->post_author);
    }

    public function sync_post_author_columnShouldSetPost_authorAfterAuthorsOrderChangesWithArrayOfAuthorsInstances(
        WpunitTester $I
    ) {
        $originalPostAuthorUserId = $I->factory('the original post author user')->user->create(['role' => 'author']);
        $postId                   = $I->factory('the post')->post->create(
            ['post_type' => 'post', 'post_author' => $originalPostAuthorUserId]
        );

        $authorAUserId = $I->factory('a new user for Author A')->user->create(['role' => 'author']);
        $authorA       = Author::create_from_user($authorAUserId);

        $authorBUserId = $I->factory('a new user for Author B')->user->create(['role' => 'author']);
        $authorB       = Author::create_from_user($authorBUserId);

        // Make sure the post_author is correct.
        $post = get_post($postId);
        $I->assertEquals($originalPostAuthorUserId, $post->post_author);

        // Set with Author A first.
        Utils::sync_post_author_column($postId, [$authorA, $authorB]);

        $postAuthor = $this->get_post_author($postId);

        $I->assertEquals($authorAUserId, $postAuthor, 'Author A should be in the post_author');

        // Set with Author B first.
        Utils::sync_post_author_column($postId, [$authorB, $authorA]);

        $postAuthor = $this->get_post_author($postId);

        $I->assertEquals($authorBUserId, $postAuthor, 'Author B should be in the post_author');
    }

    public function sync_post_author_columnShouldSetAuthorTermsAsCurrentPost_authorIfNoTermsAreFoundAndThereIsAUserAsAuthorInThePostWichHasAuthorTerm(
        WpunitTester $I
    ) {
        $originalPostAuthorUserId = $I->factory('the original post author user')->user->create(['role' => 'author']);

        $postId = $I->factory('the post')->post->create(
            ['post_type' => 'post', 'post_author' => $originalPostAuthorUserId]
        );

        // We create the author, but we won't set it to the post.
        $originalAuthor = Author::create_from_user($originalPostAuthorUserId);

        $post = get_post($postId);

        $emptyAuthorsList = [];

        Utils::sync_post_author_column($postId, $emptyAuthorsList);

        $postAuthorColumn = $this->get_post_author($postId);
        $postAuthors      = wp_get_post_terms($postId, 'author');

        $firstAuthorUserId = -1;
        if (!empty($postAuthors)) {
            $firstAuthorUserId = get_term_meta($postAuthors[0]->term_id, 'user_id', true);
        }

        $I->assertEquals(
            $originalPostAuthorUserId,
            $post->post_author,
            'Make sure the original was set as author for the post'
        );
        $I->assertEquals(
            $originalPostAuthorUserId,
            $postAuthorColumn,
            'The post_author should still match the user we set as author initially'
        );
        $I->assertEquals(1, count($postAuthors), 'There should have one author term set for the post');
        $I->assertEquals(
            $originalAuthor->term_id,
            $postAuthors[0]->term_id,
            'The first author term found for the post should be same one we created for the user'
        );
        $I->assertEquals(
            $postAuthorColumn,
            $firstAuthorUserId,
            'The user_id of the author term should match the post_author'
        );
    }

    public function sync_post_author_columnShouldSetAuthorTermsAsCurrentPost_authorIfNoTermsAreFoundAndThereIsAUserAsAuthorInThePostWichHasNotAnAuthorTerm(
        WpunitTester $I
    ) {
        $originalPostAuthorUserId = $I->factory('the original post author user')->user->create(['role' => 'author']);

        $postId = $I->factory('the post')->post->create(
            ['post_type' => 'post', 'post_author' => $originalPostAuthorUserId]
        );

        $post = get_post($postId);

        $emptyAuthorsList = [];

        Utils::sync_post_author_column($postId, $emptyAuthorsList);

        $postAuthorColumn = $this->get_post_author($postId);
        $postAuthors      = wp_get_post_terms($postId, 'author');

        $firstAuthorUserId = -1;
        if (!empty($postAuthors)) {
            $firstAuthorUserId = get_term_meta($postAuthors[0]->term_id, 'user_id', true);
        }

        $I->assertEquals(
            $originalPostAuthorUserId,
            $post->post_author,
            'Make sure the original was set as author for the post'
        );
        $I->assertEquals(
            $originalPostAuthorUserId,
            $postAuthorColumn,
            'The post_author should still match the user we set as author initially'
        );
        $I->assertEquals(1, count($postAuthors), 'There should have one author term set for the post');
        $I->assertEquals(
            $postAuthorColumn,
            $firstAuthorUserId,
            'The user_id of the author term should match the post_author'
        );
    }

    public function sync_post_author_columnShouldCreateAuthorTermForCurrentPost_authorIfNoTermsAreFoundAndThereIsAUserAsAuthorInThePostWichHasNotAnAuthorTerm(
        WpunitTester $I
    ) {
        $originalPostAuthorUserId = $I->factory('the original post author user')->user->create(['role' => 'author']);

        $postId = $I->factory('the post')->post->create(
            ['post_type' => 'post', 'post_author' => $originalPostAuthorUserId]
        );

        wp_delete_object_term_relationships($postId, 'author');

        $emptyAuthorsList = [];

        Utils::sync_post_author_column($postId, $emptyAuthorsList);

        $terms = wp_get_post_terms($postId, 'author');
        $I->assertCount(1, $terms, 'There should have one author term for the post');

        $term = $terms[0];

        $termUserId = get_term_meta($term->term_id, 'user_id', true);

        $user = get_user_by('ID', $originalPostAuthorUserId);

        $I->assertEquals($user->display_name, $term->name);
        $I->assertEquals($user->user_nicename, $term->slug);
        $I->assertEquals($originalPostAuthorUserId, $termUserId);
    }

    public function updatePostAuthorCategoryShouldRemoveRelationshipsForRemovedAuthorsWithoutCategoryPayload(
        WpunitTester $I
    ) {
        global $wpdb;

        AuthorCategoriesSchema::createTableIfNotExists();
        AuthorCategoriesSchema::createRelationTableIfNotExists();

        $currentUserId = $I->factory('a new admin user')->user->create(['role' => 'administrator']);
        wp_set_current_user($currentUserId);

        $authors = $I->haveAuthorsMappedToUsers(2);
        $postId  = $I->haveAPost();

        $categoriesTable   = AuthorCategoriesSchema::tableName();
        $relationshipTable = AuthorCategoriesSchema::relationTableName();
        $categorySlug      = 'reviewer-' . wp_rand(1, PHP_INT_MAX);

        $wpdb->insert(
            $categoriesTable,
            [
                'category_name'   => 'Reviewer',
                'plural_name'     => 'Reviewers',
                'slug'            => $categorySlug,
                'category_order'  => 1,
                'category_status' => 1,
                'created_at'      => current_time('mysql'),
                'meta_data'       => '',
            ],
            [
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%s',
                '%s',
            ]
        );

        $categoryId = (int) $wpdb->insert_id;

        wp_cache_flush();

        Utils::set_post_authors(
            $postId,
            $authors,
            false,
            null,
            [
                $authors[0]->term_id => [$categoryId],
                $authors[1]->term_id => [$categoryId],
            ]
        );

        Utils::set_post_authors($postId, [$authors[1]], false, null, []);

        $relationships = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT author_term_id FROM {$relationshipTable} WHERE post_id = %d ORDER BY id ASC",
                $postId
            ),
            ARRAY_A
        );

        $I->assertCount(1, $relationships);
        $I->assertEquals($authors[1]->term_id, (int) $relationships[0]['author_term_id']);
    }
}
