---
title: "Author Pages Troubleshooting"
order: 15
doc_category: publishpress-authors
---

# Author Pages Troubleshooting

Table of Contents

- [Author Pages Show a Redirect Error](#0-toc-title)
- [Profile Pages Have the Wrong Authors](#h-profile-pages-have-the-wrong-authors)
- [Author Pages Don't Appear](#h-author-pages-don-t-appear)
- [Author Profile Page URLs are Incorrect](#h-author-profile-age-urls-are-incorrect)

By default, both the WordPress core and [PublishPress Authors](https://publishpress.com/links/authors) provide author profile pages. This is one URL where you can find all the posts written by an author. Some plugins can interfere with these author pages. The “Author Pages” feature in [PublishPress Authors](https://publishpress.com/links/authors) may not be compatible with all themes and sites.

This guide will help you troubleshoot if your pages are not working correctly. If none of these solutions work, your theme may be incompatible with the Author Pages feature.

## Author Pages Show a Redirect Error [#](#0-toc-title)

If you see an error similar to “Too many redirects”, try these steps:

- Go to “Settings” then “Permalinks” in your WordPress admin menu.
- Click the “Save Changes” button at the bottom of the screen. This will not change the URLs on your site, but it will clear the cache and may allow the “Author Pages” feature to work.

![Author Pages permalinks](https://docs-media.metaslider.com/publishpress-authors/save-changes-permalinks-b0046d246d.webp)

## Profile Pages Have the Wrong Authors [#](#h-profile-pages-have-the-wrong-authors)

If your profile pages aren’t working, trying changing your theme quickly to use the WordPress default theme.

If your theme is not working, but the default theme is working, you will need to make changes to your theme.

[This guide](https://publishpress.com/knowledge-base/modify-theme-output-authors/) will help you integrate PublishPress Authors with your theme. [We have some child themes available to help](https://publishpress.com/knowledge-base/publishpress-authors-child-themes/).

## Author Pages Don’t Appear [#](#h-author-pages-don-t-appear)

If you are using the Yoast SEO plugin, or similar SEO plugins, this may be disabling your author pages. This example below is for Yoast, but other plugins do have similar features.

- Go to SEO > Search Appearance in the WordPress admin menu.
- Click the “Archives” tab.
- Make sure that “Author archives” is set to “Enabled”.

![](https://docs-media.metaslider.com/publishpress-authors/yoast-ebbfb970ef.webp)

## Author Profile Page URLs are Incorrect [#](#h-author-profile-age-urls-are-incorrect)

If your profile URLs don’t match the format you want, [you can update the URLs using this technique](https://publishpress.com/knowledge-base/how-to-change-author-urls/).
