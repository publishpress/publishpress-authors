---
title: "Troubleshooting PublishPress Authors"
order: 74
doc_category: publishpress-authors
---

# Troubleshooting PublishPress Authors

Table of Contents

- [More than One Author Box is Visible](#h-more-than-one-author-area-is-visible)
- [The Author Post Count is Wrong](#1-toc-title)
- [Updates to Author Profiles are Not Showing](#2-toc-title)
- [The Authors Dropdown is Not Showing](#h-the-wordpress-interface-has-changes)
- [Guest Authors are Missing from Yoast SEO Sitemaps](#4-toc-title)

This guide has solutions to some common issues with [PublishPress Authors](https://publishpress.com/links/authors).

## More than One Author Box is Visible [#](#h-more-than-one-author-area-is-visible)

Many themes will add their own [author boxes](https://publishpress.com/links/author-box) under posts. If you are seeing more than one [author box](https://publishpress.com/links/author-box), you will need to find the setting inside your theme and disable that extra box.

## The Author Post Count is Wrong [#](#1-toc-title)

If you install [PublishPress Authors](https://publishpress.com/links/authors) on a site with many posts, the number of posts for each author may be incorrect. This may impact the display on the “Posts” screen and elsewhere. To solve this, go to “Authors”, then “Settings”, then “Maintenance”. Click the “Sync author and user fields” button. [Click here for more details on this feature](https://publishpress.com/knowledge-base/authors-maintenance/#h-synchronize-the-author-and-user-profile-fields).

![Author post count is wrong](https://docs-media.metaslider.com/publishpress-authors/suync-fields-323f366c6a.webp)

Note: This process default to 10 posts per request so this may take a long time to process on a site with 10,000’s of posts. You can speed up the process by adding this to your wp-config.php file:

```
define('PUBLISHPRESS_AUTHORS_SYNC_POST_AUTHOR_CHUNK_SIZE', 1000);
```

## Updates to Author Profiles are Not Showing [#](#2-toc-title)

Caches are a common source of issues. If you are having problems with Author profile information not updating on the front of your site, test your site with all caches disabled.

## The Authors Dropdown is Not Showing [#](#h-the-wordpress-interface-has-changes)

The PublishPress Authors plugin does make some small changes to the WordPress admin interface. This is done to make the plugin easier to use and to avoid confusion. One key change is that PublishPress Authors removes the default “Author” dropdown from inside the WordPress editing screen. This dropdown is shown in the screenshot below, but you won’t see it with PublishPress Authors installed. The only places to add and remove authors is this the PublishPress Authors metabox, or the “Quick Edit” option.

![Authors dropdown in WordPress](https://docs-media.metaslider.com/publishpress-authors/author-dropdown-c00ff8f314.webp)

## Guest Authors are Missing from Yoast SEO Sitemaps [#](#4-toc-title)

The Yoast SEO plugin does offer sitemaps for individual authors. Guest Authors in the PublishPress Authors plugin may be missing from these sitemaps because Yoast SEO has these requirements to include authors:

- Must have “Edit Post” capability.
- Must have a user lever of greater than 0.
