<?php

namespace modules\multiple_authors;

use WpunitTester;

class AuthorsListSecurityCest
{
    public function shortcodeLayoutIsRestrictedToAClassNameInInlineStyles(WpunitTester $I)
    {
        add_filter('publishpress_authors_load_style_in_frontend', '__return_true');
        $handle = 'multiple-authors-widget-css-';
        $styles = wp_styles();
        $previousStyles = $styles->get_data($handle, 'after');

        try {
            foreach (['authors_grid', 'authors_grid\x3c/style\x3e\x3cimg src=x onerror=document.body.dataset.ppmaxss=1\x3e'] as $layout) {
                do_shortcode(sprintf(
                    '[publishpress_authors_list layout="%s" layout_columns="2"]',
                    $layout
                ));
                $inlineStyles = $styles->get_data($handle, 'after');
                $I->assertNotEmpty($inlineStyles);
                $css = implode("\n", $inlineStyles);
                $I->assertStringContainsString('.pp-multiple-authors-wrapper.authors_grid', $css);
                $I->assertStringNotContainsString('</style', $css);
                $I->assertStringNotContainsString('<img', $css);
            }
        } finally {
            remove_filter('publishpress_authors_load_style_in_frontend', '__return_true');
            if (isset($styles->registered[$handle])) {
                $styles->registered[$handle]->extra['after'] = $previousStyles ?: [];
            }
        }
    }
}
