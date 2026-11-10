---
title: "PublishPress Authors and Full-Site Editing"
order: 24
doc_category: publishpress-authors
---

# PublishPress Authors and Full-Site Editing

It is possible to use [PublishPress Authors](https://publishpress.com/links/authors) together with the Full-Site Editing feature in WordPress.

The Full-Site Editing feature often relies on the Query Loop block to display content. There is [a long-standing issue](https://github.com/WordPress/gutenberg/issues/43053) with the Query Loop block that prevents shortcodes from working correctly. This impacts plugins including [PublishPress Authors](https://publishpress.com/links/authors), so a workaround is needed.

![Query Loop block](https://docs-media.metaslider.com/publishpress-authors/query-loop-block-d0846e1994.webp)

This workround requires the plugin called “[Meta Field Block](https://wordpress.org/plugins/display-a-meta-field-as-block/)“. After installing this plugin, there will be a block available called “Meta Field Block”, as show in this next screenshot.

![Meta Field Block](https://docs-media.metaslider.com/publishpress-authors/meta-field-block-e14d9501d4.webp)

- Find the Query Loop block layout inside your theme or page template.
- Add the Meta Field Block inside the Query Loop Block, as in this screenshot below:

![Meta Field Block in Query Loop](https://docs-media.metaslider.com/publishpress-authors/meta-field-insert-0be7066cae.webp)

- In the sidebar, choose “Dynamic Field” for the “Field Type”.
- Enter the PublishPress Authors shortcode into the “Field Name”.

![Dynamic field and PublishPress Authors shortcode](https://docs-media.metaslider.com/publishpress-authors/dynamic-meta-field-c7e39254f5.webp)

This process will allow PublishPress Authors profiles to show inside the Query Loop. In the screenshot below, the PublishPress Authors plugin has successfully added multiple author profiles to each post.

![PublishPress Authors profiles inside the Query Loop](https://docs-media.metaslider.com/publishpress-authors/authors-query-loop-7b3e91d628.webp)
