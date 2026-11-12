---
title: "Update the Authors Byline in Your Theme"
order: 17
doc_category: publishpress-authors
---

# Update the Authors Byline in Your Theme

Table of Contents

- [How to Modify the Author Byline](#h-how-to-modify-the-author-byline)
- [How to Modify the Byline in a Commercial Theme](#h-how-to-modify-the-byline-in-a-commercial-theme)
- [Diving Deeper on Author Bylines](#h-diving-deeper-on-author-bylines)
- [Technical Details for the WordPress Author Byline](#h-technical-details-for-the-wordpress-author-byline)

[PublishPress Authors](https://publishpress.com/links/authors) can extend WordPress to support multiple authors. [In this guide, we show ways to display these authors](https://publishpress.com/knowledge-base/display-multiple-authors/).

However, [PublishPress Authors](https://publishpress.com/links/authors) can not automatically modify the default WordPress byline on posts. This image below shows a typical byline. Modifying this byline can’t be done automatically because many themes have different approaches, but most of them use the normal WordPress approach that doesn’t support multiple authors.

This tutorial will show you how to customize the author byline if you are using a commercial theme or a theme from WordPress.org.

![WordPress Author Byline](https://docs-media.metaslider.com/publishpress-authors/byline-example-e7c4618479.webp)

*Author Byline*

## How to Modify the Author Byline [#](#h-how-to-modify-the-author-byline)

If you are using your own custom WordPress theme, then you should include this code to show the new PublishPress Authors boxes.

```
<?php do_action( 'pp_multiple_authors_show_author_box' ); ?>
```

If you want to closely mimic the default WordPress byline, we recommend two extra filters. [This article has the full details on the available filters](https://publishpress.com/knowledge-base/modify-theme-authors/).

- The first filter is “true” or false” and will hide the title of the authors area.
- The second filter allows you to choose a specific Authors Box. The plugin provides [sample boxes called “Inline” and “Inline with Avatars”](https://publishpress.com/knowledge-base/layout/) that look like the default bylines.

```
<?php do_action('pp_multiple_authors_show_author_box', false, 'ppma_boxes_116'); ?>
```

## How to Modify the Byline in a Commercial Theme [#](#h-how-to-modify-the-byline-in-a-commercial-theme)

If you are using a theme developed by a commercial developer, the best way to add PublishPress Authors support to your theme is using a child theme. [This post has details on creating a child theme](https://developer.wordpress.org/themes/advanced-topics/child-themes/). We don’t advise you customize the main theme, because the changes would be overwritten on every update. We do provide [some child themes for some commercial themes](https://publishpress.com/knowledge-base/publishpress-authors-theme-compatibility/).

The short version of this tutorial is that we need to replace the normal authors byline in our theme with this code. You can drop that code almost anywhere in your WordPress theme files and it will work.

```
<?php do_action( 'pp_multiple_authors_show_author_box' ); ?>
```

## Diving Deeper on Author Bylines [#](#h-diving-deeper-on-author-bylines)

The longer version of this tutorial is that creating a 100% replacement for the default byline will require some detective work. Ever theme is different and we need to find out which template file we need to modify or override.

In many cases, you can add this PHP snippet directly into the main template file for posts. This file often has a name such as `single.php` or `singular.php`.

If the location of the author byline isn’t obvious, you can use the browser to inspect the page, looking for an element with name like this:

- `byline`
- `post-author`

If you are using Chrome, you can right click on the author byline and check the markup. Here is an example from the Twenty Twenty theme:

![Twenty Twenty](https://docs-media.metaslider.com/publishpress-authors/twenty-twenty-16ba62791f.webp)

*Twenty Twenty*

For the Twenty Nineteen theme, this image shows the markup:

![Twenty Nineteen](https://docs-media.metaslider.com/publishpress-authors/twenty-nineteen-d7e2287396.webp)

*Twenty Nineteen*

Alternatively, you can use your code editor to search for “author” or “byline”. I’m using PHPStorm in the image below, and it quickly points me to the correct file:

![](https://docs-media.metaslider.com/publishpress-authors/php-storm-d3d4936bd2.webp)

We found out that the template file we need to modify is “wp-content/themes/twentynineteen/inc/template-tags.php”.

If you want to, you can modify this file to include the PublishPress Authors code. This update would work:

```
if ( ! function_exists( 'twentynineteen_posted_by' ) ) :
	/**
	 * Prints HTML with meta information about theme author.
	 */
	function twentynineteen_posted_by() {
	do_action( 'pp_multiple_authors_show_author_box' );
	}
endif;
```

However, we would recommend that you leave this file alone and override this using the functions.php file in your child theme. In this longer code snippet below, we are overriding three lines of code, starting at `<span class="byline">` and ending with `<span class="author vcard">`. In this code, we have also added two filters to closely mimic the default byline:

```
function twentynineteen_posted_by()
{
    /**
     * This first block is specific to each theme.
     */
    echo '<span class="byline">';
    echo twentynineteen_get_icon_svg('person', 16);
    echo '<span class="screen-reader-text">' . __('Posted by', 'twentynineteen') . '</span><span class="author vcard">';

    /**
     * This is the action that echoes the author box in the screen.
     * The action pp_multiple_authors_show_author_box accepts 4 parameters, but for this example we will be using only 2:
     *
     * $showTitle = false
     * $layout = 'inline'
     */
    do_action('pp_multiple_authors_show_author_box', false, 'inline');

    /**
     * This block is specific to each theme.
     */
    echo '</span></span>';
}
```

Now you can try refreshing the page and checking the result.

![](https://docs-media.metaslider.com/publishpress-authors/bylines-updates-ab42f9ff3d.webp)

In the Twenty Nineteen theme there is conditional code checking if the function “twentynineteen_posted_by” exists. If the function doesn’t exist, it defines it. That means we can define it on our “functions.php” file. That way the theme will use our function instead of the one defined in the theme. If your theme doesn’t have a conditional like that, you can override the entire template file and modifying the required functions. More information [in this WordPress.org guide](https://developer.wordpress.org/themes/advanced-topics/child-themes/#adding-template-files).

## Technical Details for the WordPress Author Byline [#](#h-technical-details-for-the-wordpress-author-byline)

WordPress themes usually rely on functions that are designed to return data from only one author. These key functions include such as `the_author()`, `get_the_author()`, and `get_the_author_meta()`.

PublishPress Authors can modify these functions and include the names of multiple authors. Unfortunately, this doesn’t work for links to the authors’ pages because HTML won’t allow 2 links inside the same “<a>” tag.

This is the technical explanation for why we need to replace the default code. For this reason, when you first install PublishPress Authors, the default WordPress byline will continue to show one author.
