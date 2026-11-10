---
title: "Add Author Boxes Snippets to Your Theme"
order: 57
doc_category: publishpress-authors
---

# Add Author Boxes Snippets to Your Theme

Table of Contents

- [The Default Authors Code](#h-the-basic-authors-code)
- [Expanding the Code: Title](#h-expanding-the-code-title)
- [Expanding the Code: Layout](#h-expanding-the-code-layout)
- [Expanding the Code: Author Pages](#h-expanding-the-code-author-pages)
- [Expanding the Code: Archive Pages](#h-expanding-the-code-archive-pages)

The [PublishPress Authors](https://publishpress.com/authors/) plugin allows you to show author profiles using a snippet of PHP code.

Don’t worry if you’re not technical. There are [many ways to show your authors that require no code](https://publishpress.com/knowledge-base/display-multiple-authors/).

We recommend that you add these code snippets to a child theme, as that is best practice for WordPress theme development. We do have [child themes available for some popular themes](https://publishpress.com/knowledge-base/publishpress-authors-child-themes/).

One disadvantage to the approach in this guide is that you will be able easily edit the layout via the “[Author Boxes](https://publishpress.com/links/author-box)” screen. There is an alternative approach available when you [place the whole code Author Box inside your theme](https://publishpress.com/knowledge-base/author-boxes-theme-templates/).

## The Default Authors Code [#](#h-the-basic-authors-code)

This is the most basic PHP code to load the [Author Box](https://publishpress.com/links/author-box) for a post. This will show the default “Boxed” layout.

```
<?php do_action( 'pp_multiple_authors_show_author_box' ); ?>
```

## Expanding the Code: Title [#](#h-expanding-the-code-title)

You can modify this basic code in several ways. The first argument you can can add to the code will control the title.

Here is one example with the first argument set to `false`. In this code, `false` will hide the title of the authors area. If you change this to `true`, the title for the area will appear.

```
<?php do_action('pp_multiple_authors_show_author_box', false); ?>
```

## Expanding the Code: Layout [#](#h-expanding-the-code-layout)

The second argument you can add to this code will control the layout. Here is an example using the layout with an ID of “ppma_boxes_117”:

```
<?php do_action('pp_multiple_authors_show_author_box', false, 'ppma_boxes_117'); ?>
```

## Expanding the Code: Author Pages [#](#h-expanding-the-code-author-pages)

The third argument allows you to use this code on the author pages of your site. These are the individual author pages you see at URLs like this:

- example.com/authors/john-doe
- example.com/authors/jane-doe

The `true` in the third argument of this code will force it to detect the author from the current URL, instead of from the current post. This means it can be used to display the author’s information on the page header, for the current author.

```
<?php do_action('pp_multiple_authors_show_author_box', false, 'ppma_boxes_117', true); ?>
```

For the posts loop in the author page, you have to keep the false in the third argument and follow the instructions below (for archive pages) so it will display the authors of the current post in the loop.

## Expanding the Code: Archive Pages [#](#h-expanding-the-code-archive-pages)

By default, the basic code shown at the top of this guide will only display the [author box](https://publishpress.com/links/author-box) for a single post page.

The fourth argument allows you to use this code in the posts loop on archive/author pages or on the homepage of your site. The `true` in the fourth argument of this code will force it to run whenever used, not just inside a single post loop. It is important to also keep the third argument as false in this case.

```
<?php do_action('pp_multiple_authors_show_author_box', false, 'ppma_boxes_117', false, true); ?>
```
