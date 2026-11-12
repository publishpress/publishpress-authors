---
title: "PublishPress Authors and Polylang"
order: 30
doc_category: publishpress-authors
---

# PublishPress Authors and Polylang

The Pro version of [PublishPress Authors](https://publishpress.com/links/authors) plugin does provide an integration with [the Polylang plugin](https://wordpress.org/plugins/polylang/) which allows you to create a multilingual site.

One common issue between the Authors and Polylang plugin is the translation of author profiles. The Authors plugin stores the author profiles as taxonomy terms. The Polylang plugin will normally attempt to duplicate taxonomy terms which can be a problem as it leads to duplicate author profiles.

If you want to disable this behavior, follow these steps:

- Go to the “Settings” screen in [PublishPress Authors Pro](https://publishpress.com/links/authors) and click the “Integration” tab.
- Disable the “Enable translation of author profiles in Polylang” setting.

![Authors and Polylang](https://docs-media.metaslider.com/publishpress-authors/polylang-box-8dfd89f67a.webp)

Next, you can confirm that the Polylang translation is disabled.

- Go to “Languages” then “Settings” in your WordPress admin menu.
- Look for the “Settings” link under “Custom post types and Taxonomies”. If you have a small site with few plugin, this link may not be available, in which case you do not need to do anything more. If the “Settings” link is available, click the link and check that the “Authors” box is not available.

![Custom post types and taxonomies](https://docs-media.metaslider.com/publishpress-authors/polylang-settings-c1f6c0d52e.webp)
