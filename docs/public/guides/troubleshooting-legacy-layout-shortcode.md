---
title: "Troubleshooting Legacy Layout Shortcodes"
order: 73
doc_category: publishpress-authors
---

# Troubleshooting Legacy Layout Shortcodes

In version 4.3.1 of [PublishPress Authors](https://publishpress.com/links/authors), we made the decision to remove support for legacy layout shortcodes.

These layout shortcodes specify the layout style for [author boxes](https://publishpress.com/links/author-box), such as “boxed,” “centered,” or “inline.”. For a practical example, check this screenshot, with the legacy version of the layout shortcode. In these examples, the name of the layout is specified.

![Legacy Author Shortcode](https://docs-media.metaslider.com/publishpress-authors/legacy-layout-09b01c8e4d.webp)

This next screenshot shows the new version of the shortcode. In the new version, the ID of the [Author Box](https://publishpress.com/links/author-box) is used.

![New Author shortcode](https://docs-media.metaslider.com/publishpress-authors/new-version-author-boxes-532d2734e8.webp)

We moved away from labeling layouts with terms like “boxed,” “centered,” or “inline.” Instead, we now identify layouts by their unique [author box](https://publishpress.com/links/author-box) IDs. Continuing to use these legacy layout names was causing performance issues within our plugin. So this change was aimed at enhancing the overall performance of our plugin.

However, recognizing that many users had already integrated the legacy layout shortcode extensively across their websites, we do allow user to restore this feature. Users can now find this option in the “Settings” area, under the “Advanced” tab.

![Advanced tab layout settings](https://docs-media.metaslider.com/publishpress-authors/legacy-layout-option-d2fc92680e.webp)

This optional feature allows users to select their preferred layout style (e.g., “boxed”) and associate it with the appropriate author box by choosing the corresponding author box ID. This enables users can maintain their preferred layout while still benefiting from the performance improvements implemented in the plugin.

![Layout options for legacy shortcodes](https://docs-media.metaslider.com/publishpress-authors/boxed-legacy-layout-481d0b34d7.webp)
