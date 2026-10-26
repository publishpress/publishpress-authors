<?php

namespace modules\multiple_authors;

use WpunitTester;

class AuthorsDataSecurityCest
{
    public function shortcodeSeparatorsAreEscapedAfterAttributeDecoding(WpunitTester $I)
    {
        $authors = $I->haveAuthorsMappedToUsers(2);
        $postId = $I->haveAPostWithAuthors($authors);
        $names = array_map(static function ($author) {
            return $author->display_name;
        }, get_post_authors($postId));
        $I->assertCount(2, $names);

        foreach (['separator', 'seperator'] as $attribute) {
            foreach ([
                '\x3cimg src=x onerror=document.body.dataset.ppmaxss=1\x3e',
                '\x3csvg onload=document.body.dataset.ppmaxss=1\x3e',
                '\x3cscript\x3edocument.body.dataset.ppmaxss=1\x3c/script\x3e',
            ] as $payload) {
                $shortcode = sprintf(
                    '[publishpress_authors_data post_id="%d" %s="%s"]',
                    $postId,
                    $attribute,
                    $payload
                );
                $savedContent = wp_kses_post($shortcode);
                $I->assertSame($shortcode, $savedContent);
                $I->assertSame(
                    implode(esc_html(stripcslashes($payload)), $names),
                    do_shortcode($savedContent)
                );
            }

            foreach (['', ', ', '\x2c ', ' &amp; '] as $separator) {
                $I->assertSame(
                    implode(esc_html(stripcslashes($separator)), $names),
                    do_shortcode(sprintf(
                        '[publishpress_authors_data post_id="%d" %s="%s"]',
                        $postId,
                        $attribute,
                        $separator
                    ))
                );
            }
        }
    }
}
