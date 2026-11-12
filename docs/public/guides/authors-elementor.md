---
title: "PublishPress Authors and Elementor"
order: 21
doc_category: publishpress-authors
---

# PublishPress Authors and Elementor

[PublishPress Authors](https://publishpress.com/links/authors) works well with [the Elementor plugin](https://wordpress.org/plugins/elementor/).

There are [several options for showing Author profiles on your site](https://publishpress.com/knowledge-base/display-multiple-authors/). All of these will work with Elementor. For example, Option #1 will automatically show authors under your posts.

You can also add [PublishPress Authors](https://publishpress.com/links/authors) listings from inside the main Elementor layout. Perhaps the easiest option for the Elementor layout builder is [the shortcode option](https://publishpress.com/knowledge-base/shortcodes/). You can enter any shortcode from PublishPress Authors into the “Shortcode” widget in Elementor.

![](https://docs-media.metaslider.com/publishpress-authors/shortcode-elementor-16a17b10b8.webp)

Some sites have issue with the page title on author pages when using Elementor Pro. Instead of displaying the name of current author in the title, it displays the name from the author of the first post listed on the page.

If you see this issue you can try [the Authors Pages feature](https://publishpress.com/knowledge-base/author-pages/) inside PublishPress.

You can also try disabling a filter for “the_author” in PublishPress Authors by defining a constant in your wp-config.php file.

```
define('PUBLISHPRESS_AUTHORS_DISABLE_FILTER_THE_AUTHOR', true);
```
