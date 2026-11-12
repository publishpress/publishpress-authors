---
title: "Authors Data Shortcode"
order: 19
doc_category: publishpress-authors
---

# Authors Data Shortcode

Table of Contents

- [How to Use the Authors Data Shortcode](#h-how-to-use-the-authors-data-shortcode)
- [How to Customize the Authors Data Shortcode](#h-how-to-customize-the-authors-data-shortcode)

One of the shortcodes available in [PublishPress Authors](https://publishpress.com/links/authors) is the Authors Data shortcode.

The “Authors Data” shortcode is a more flexible alternative to [the Author Box shortcode](https://publishpress.com/knowledge-base/shortcodes/). With this shortcode you can show specific data from the authors attached to the post being viewed.

## How to Use the Authors Data Shortcode [#](#h-how-to-use-the-authors-data-shortcode)

This is the basic version of the shortcode:

```
Steve Burge
```

Here is how this shortcode appears when added to the Gutenberg editor in a block:

![Authors Data Gutenberg](https://docs-media.metaslider.com/publishpress-authors/authors-data-gutenberg-7205c1234b.webp)

*Authors Data Gutenberg*

The simplest version of the shortcode will only show the Authors names. The power of this shortcodes in the extra parameters.

You can find examples of the Authors Data shortcodes by going to “Authors”, then “Settings”, then “Shortcodes” in your WordPress site.

![Shortcodes Data](https://docs-media.metaslider.com/publishpress-authors/shortcodes-data-0433785ce3.webp)

*Shortcodes Data*

## How to Customize the Authors Data Shortcode [#](#h-how-to-customize-the-authors-data-shortcode)

Here are some examples of data elements that you can show:

- `display_name`
- `first_name`
- `last_name`
- `ID`
- `user_nicename`
- `user_email`
- `avatar`
- `description`

Let’s see some examples. This shortcode will show only the first name of each author attached to the post:

```
Steve
```

This shortcode will show only the first name of each author, but separated with an asterisk:

```
Burge
```

This shortcode will show only the WordPress nicename of each author, only from the post with an ID of 32.

```

```

This shortcode will show only the display name of author with term id 103. Where can you get the term id of an author? You could get it from Authors menu, and edit the author:

![](https://docs-media.metaslider.com/publishpress-authors/term-id-9ceb1cffcc.webp)

```

```

If you want to use user id instead of term id, you could do that as well using user_id parameter. You could get the user id from Users menu.

![](https://docs-media.metaslider.com/publishpress-authors/user-id-691b42fbaa.webp)

```
Steve Burge
```

This shortcode retrieve authors belonging to a specific [Author Category](https://publishpress.com/links/author-categories) by providing the category slug or slugs. Separate multiple slugs with a comma.

```

```

How far does this go? You can use almost any user field provided by WordPress. [This documentation has more details](https://developer.wordpress.org/reference/functions/get_user_by/). Some more examples include these:

- `user_login`
- `user_email`
- `user_url`
- `user_registered`
- `user_status`
- `user_description`

This shortcode is also useful for writing code to expand the [PublishPress Authors](https://publishpress.com/links/authors) plugin.

A good usage example is below. This will display all authors meta in the <head> code of your site:

```
<?php $authors_name = explode(",", do_shortcode('Steve Burge')); ?>

<?php foreach ($authors_name as $author_name) : ?>

<metaproperty="article:author" content="<?php esc_attr_e($author_name);"/>

<?php endforeach; ?>
```

There is one final option to mention. This is mostly useful if you’re using a theme or page builder to customize the Author profile pages you find at URLs such as /author/username/. You can use the following shortcode on the authors page to display the profile of the current author. You just need to add the parameter `archive="true"`:

```

```
