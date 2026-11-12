<?php

namespace modules\multiple_authors;

use ReflectionClass;
use WpunitTester;

class GenesisShortcodeSecurityCest
{
    public function authorShortcodeWrappersPreserveBasicHtmlWithoutActiveContent(WpunitTester $I)
    {
        if (!class_exists('MA_Genesis_Integration')) {
            require_once PP_AUTHORS_BASE_PATH . 'src/modules/genesis-integration/genesis-integration.php';
        }

        $authors = $I->haveAuthorsMappedToUsers(2);
        $postId = $I->haveAPostWithAuthors($authors);
        $previousPost = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = get_post($postId);
        $reflection = new ReflectionClass(\MA_Genesis_Integration::class);
        $integration = $reflection->newInstanceWithoutConstructor();
        $attributes = shortcode_parse_atts(
            'before="\x3cspan class=\x22credits\x22\x3e\x3cimg src=x onerror=document.body.dataset.ppmaxss=1\x3e" '
            . 'after="\x3c/span\x3e\x3cscript\x3edocument.body.dataset.ppmaxss=1\x3c/script\x3e"'
        );

        try {
            $output = $integration->filter_genesis_post_author_posts_link_shortcode('', $attributes);
            $I->assertStringContainsString('<span class="credits">', $output);
            $I->assertStringNotContainsString('onerror=', $output);
            $I->assertStringNotContainsString('<script', $output);
            foreach ($authors as $author) {
                $I->assertStringContainsString($author->display_name, $output);
            }

            if (function_exists('genesis_attr')) {
                foreach (['filter_genesis_post_author_shortcode', 'filter_genesis_post_author_link_shortcode'] as $method) {
                    $output = $integration->$method('', $attributes);
                    $I->assertStringContainsString('<span class="credits">', $output);
                    $I->assertStringNotContainsString('onerror=', $output);
                    $I->assertStringNotContainsString('<script', $output);
                }
            }
        } finally {
            $GLOBALS['post'] = $previousPost;
        }
    }
}
