---
title: "Import WordPress Users to Author Profiles"
order: 58
doc_category: publishpress-authors
---

# Import WordPress Users to Author Profiles

Table of Contents

- [Automatic Migration on Install](#h-automatic-migration-on-install)
- [Manual Migration](#h-manually-run-a-migration-to-publishpress-authors)

This article will help if you are moving from the core WordPress user system to [PublishPress Authors](https://publishpress.com/links/authors).

[PublishPress Authors](https://publishpress.com/links/authors) does use a different system than the WordPress core, so there may be some work involved with moving author data.

## Automatic Migration on Install [#](#h-automatic-migration-on-install)

When you first install PublishPress Authors, our plugin will try to import all your existing post authors. After installing PublishPress Authors, go to the “Authors” screen to see if your authors have imported correctly.

However, you may need to follow this guide if you have a large site or have been using a different plugin.

## Manual Migration [#](#h-manually-run-a-migration-to-publishpress-authors)

PublishPress Authors has helpful tools if the initial import of users did not work completely.

- Go to Authors > Settings in the WordPress admin menu.
- Go to the “Maintenance” area.
- Look for the “Create missing PublishPress Authors Profile” button.

This action is very helpful if you’re installing PublishPress Authors on an existing WordPress site. This action analyzes all the posts on your site. If the action finds a WordPress user is set as an author, it will automatically create an author profile for them. This process runs on install, but this button allows you to run it again. You can safely click this button as many times as you wish.

If you have a very large site with 1,000s or authors or posts, we recommend you use the [WP CLI command line system](https://publishpress.com/knowledge-base/wordpress-cli-authors/). [Please click here for more on sites with large numbers of authors](https://publishpress.com/blog/large-sites/).

![Create PublishPress Authors Profiles for all post authors](https://docs-media.metaslider.com/publishpress-authors/authors-settings-8cfff608b1.webp)
