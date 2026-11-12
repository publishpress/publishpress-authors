---
title: "PublishPress Authors and Kadence Themes"
order: 63
doc_category: publishpress-authors
---

# PublishPress Authors and Kadence Themes

If you are using Kadence themes, you may have noticed that only the first author is shown in the byline authors. This is a common issue that occurs in some themes. In general, you can resolve this by following [the guide provided](https://publishpress.com/knowledge-base/authors-byline-theme/).

However, the Kadence team has provided a filter that you can use to address this issue. You only need to add this code to your functions.php file or use a plugin like Code Snippets. You can choose whichever method you prefer.

```
add_filter( 'kadence_author_meta_output', 'custom_author_by_line' );
function custom_author_by_line( $output ) {
ob_start(); ?>
<span class="posted-by">
<span class="meta-label">By</span>
<?php do_action('pp_multiple_authors_show_author_box', false, 'ppma_boxes_9', false, true); ?>
</span>
<?php
return ob_get_clean();
}
```

Make sure to change **ppma_boxes_9** with your [author box](https://publishpress.com/links/author-box) ID. Each site will have different [author box](https://publishpress.com/links/author-box) ID. You can find it here:

![inline shortcode](https://docs-media.metaslider.com/publishpress-authors/inline-shortcode-d3c5083728.webp)
