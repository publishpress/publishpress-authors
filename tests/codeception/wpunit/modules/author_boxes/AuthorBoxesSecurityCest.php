<?php

namespace modules\author_boxes;

use MultipleAuthorBoxes\AuthorBoxesDefault;
use ReflectionClass;
use WpunitTester;

class AuthorBoxesSecurityCest
{
    private $originalPostData;

    public function _before(WpunitTester $I)
    {
        $this->originalPostData = $_POST;
        get_role('administrator')->add_cap('ppma_manage_layouts');
    }

    public function _after(WpunitTester $I)
    {
        $_POST = $this->originalPostData;
        wp_set_current_user(0);
    }

    public function authorBoxesPostType_requiresManageLayoutsCapability(WpunitTester $I)
    {
        $postType = get_post_type_object(\MA_Author_Boxes::POST_TYPE_BOXES);
        $authorId = $I->factory('an author')->user->create(['role' => 'author']);
        $adminId = $I->factory('an administrator')->user->create(['role' => 'administrator']);

        wp_set_current_user($authorId);
        $I->assertFalse(current_user_can($postType->cap->create_posts));
        $I->assertFalse(current_user_can($postType->cap->publish_posts));

        wp_set_current_user($adminId);
        $I->assertTrue(current_user_can($postType->cap->create_posts));
        $I->assertTrue(current_user_can($postType->cap->publish_posts));
    }

    public function profileValuePrefixes_areSanitizedAsUrlsOnSave(WpunitTester $I)
    {
        $adminId = $I->factory('an administrator')->user->create(['role' => 'administrator']);
        wp_set_current_user($adminId);

        $postId = wp_insert_post(
            [
                'post_type'   => \MA_Author_Boxes::POST_TYPE_BOXES,
                'post_title'  => 'Security test box',
                'post_status' => 'publish',
            ]
        );
        $fields = apply_filters(
            'multiple_authors_author_boxes_fields',
            \MA_Author_Boxes::get_fields(get_post($postId)),
            get_post($postId)
        );
        $prefixFieldKeys = $this->getProfileValuePrefixFieldKeys($fields);

        $I->assertNotEmpty($prefixFieldKeys);

        $_POST = [
            'author-boxes-editor-nonce' => wp_create_nonce('author-boxes-editor'),
        ];

        foreach ($prefixFieldKeys as $key) {
            $I->assertSame('esc_url_raw', $fields[$key]['sanitize']);
            $_POST[$key] = 'javascript:alert(document.domain)//';
        }
        $I->assertSame('esc_url_raw', $fields['meta_custom_link']['sanitize']);
        $_POST['meta_custom_link'] = 'javascript:alert(document.domain)//';

        $moduleReflection = new ReflectionClass(\MA_Author_Boxes::class);
        $module = $moduleReflection->newInstanceWithoutConstructor();
        $module->saveAuthorBoxesData($postId);

        $savedData = get_post_meta(
            $postId,
            \MA_Author_Boxes::META_PREFIX . 'layout_meta_value',
            true
        );

        foreach ($prefixFieldKeys as $key) {
            $I->assertSame('', $savedData[$key]);
        }
        $I->assertSame('', $savedData['meta_custom_link']);
    }

    public function profileLinks_rejectUnsafeSchemesForEveryProfileField(WpunitTester $I)
    {
        $authors = $I->haveAuthorsMappedToUsers(1);
        $author = $authors[0];
        $editorData = AuthorBoxesDefault::getAuthorBoxesDefaultData('author_boxes_boxed');
        $fields = apply_filters(
            'multiple_authors_author_boxes_fields',
            \MA_Author_Boxes::get_fields(false),
            false
        );
        $prefixFieldKeys = $this->getProfileValuePrefixFieldKeys($fields);

        foreach ($prefixFieldKeys as $prefixFieldKey) {
            $fieldKey = substr(
                $prefixFieldKey,
                strlen('profile_fields_'),
                -strlen('_value_prefix')
            );
            $editorData['profile_fields_' . $fieldKey . '_html_tag'] = 'a';
            $editorData[$prefixFieldKey] = 'javascript:alert(document.domain)//';
            $editorData['profile_fields_hide_' . $fieldKey] = 0;
        }

        $args = [];
        foreach ($fields as $key => $fieldArgs) {
            $fieldArgs['key'] = $key;
            $fieldArgs['value'] = isset($editorData[$key]) ? $editorData[$key] : '';
            $args[$key] = $fieldArgs;
        }
        $args['authors'] = [$author];
        $args['post_id'] = 0;
        $args['short_code_args'] = [];

        $html = \MA_Author_Boxes::get_rendered_author_boxes_editor_preview($args);

        $I->assertStringNotContainsString('javascript:', $html);
    }

    private function getProfileValuePrefixFieldKeys(array $fields)
    {
        return array_keys(
            array_filter(
                $fields,
                static function ($args, $key) {
                    return strpos($key, 'profile_fields_') === 0
                        && substr($key, -strlen('_value_prefix')) === '_value_prefix';
                },
                ARRAY_FILTER_USE_BOTH
            )
        );
    }
}
