---
title: "Using the WordPress Core Authors Page"
order: 2
doc_category: publishpress-authors
---

# Using the WordPress Core Authors Page

Table of Contents

- [Option #1. Use a Theme With PublishPress Authors Support](#h-option-1-use-a-theme-with-publishpress-authors-support)
- [Option #2. Use a shortcode](#h-option-2-use-a-shortcode)
- [Option #3. Add PHP to your theme](#h-option-3-add-php-to-your-theme)
- [](#3-toc-title)[More Advanced Theme Options](#4-toc-title)

In WordPress, and the [PublishPress Authors](https://publishpress.com/links/authors) plugin, every author has their own URL. This page lists all the content they have written.

By default, these author pages do not contain much information. For example, the TwentyTwenty theme, will only show the author’s name and list of their posts.

The best way to customize this page is to use [the Author Pages feature](https://publishpress.com/knowledge-base/author-pages/) in [PublishPress Authors](https://publishpress.com/links/authors).

It is possible to use the normal authors pages in WordPress with PublishPress Authors. However, this route is likely to be more complicated. We’ve included some suggestions below.

## Option #1. Use a Theme With PublishPress Authors Support [#](#h-option-1-use-a-theme-with-publishpress-authors-support)

Some themes do automatically support PublishPress Authors. For example, the popular [Astra](https://wpastra.com/) theme will automatically find and display your author’s name, description and avatar. [We also have child themes available](https://publishpress.com/knowledge-base/publishpress-authors-child-themes/) that will help with many themes.

![Publishpress Authors Astra](https://docs-media.metaslider.com/publishpress-authors/publishpress-authors-astra-f2b1c89fae.webp)

*Publishpress Authors Astra*

## Option #2. Use a shortcode [#](#h-option-2-use-a-shortcode)

This option can be used if you have a theme or pagebuilder that allows you to customize the Authors pages.

For example, the [Elementor Pro](https://elementor.com) plugin has a “[Single & Archive Builder](https://elementor.com/blog/single-archive-builder/)” that allows you to control the author pages. Another example, is the Beaver Builder that allows you to modify author pages with the Beaver Themer add-on.

If you have this option on your site, you can use this shortcode to show the author profile. [This guide explains how to customize the shortcode](https://publishpress.com/knowledge-base/shortcodes/).

```

                
                    

                    
                    
                                                
                        
                                                                                                                
                        
                                            
                    
                    
                
                            
        
```

If your pagebuilder or theme doesn’t have a way to display shortcodes, they probably will accept the “[Widget Shortcode](https://wordpress.org/plugins/widget-shortcode/)” plugin.

## Option #3. Add PHP to your theme [#](#h-option-3-add-php-to-your-theme)

One option is to place this code directly into the template file for your WordPress theme. As with all WordPress development, we do recommend using a child theme if you want to modify the code.

This code uses PHP to display a shortcode and [this guide explains how to customize the shortcode](https://publishpress.com/knowledge-base/shortcodes/).

```
<?php echo do_shortcode( '
                
                    

                    
                    
                                                
                        
                                                                                                                
                        
                                            
                    
                    
                
                            
        ' ); ?>
```

This code should be placed in the Archives section of the themes layout. For example, in the TwentyTwenty theme, you can place it directly under this code at line 65 of the index.php file:

```
<?php if ( $archive_title ) { ?>
<h1 class="archive-title"><?php echo wp_kses_post( $archive_title ); ?></h1>	
<?php } ?>
```

## More Advanced Theme Options [#](#3-toc-title)

If you want more control you can use the function *get_multiple_authors* to get an array of authors for the current post or post specified by the post ID.

Check the [API provided by the Authors plugin](https://publishpress.com/knowledge-base/the-authors-api/) for more details.

Here is an example of how you can iterate over the authors list passing the post ID:

```
<?php

$authors = get_multiple_authors($post_id);
$first_author = true;

echo '<ul>';

foreach ($authors as $author) {
    echo '<li>';
    
    if (!$first_author) {
        echo ', ';
    }

    echo sprintf(
        '<a href="%s">%s %s</a>',
        $author->link,
        $author->get_avatar(),
        $author->display_name
    ); 

    echo '</li>';

    $first_author = false;
}

echo '</ul>';
```

The *get_multiple_authors* function can be used to return the current user on the author’s page setting the third param as true and the first as 0:

```
<?php

$authors = get_multiple_authors(0, true, true);

echo $authors[0]->display_name;
```
