---
title: "How to Change Author Pages URLs"
order: 48
doc_category: publishpress-authors
---

# How to Change Author Pages URLs

Table of Contents

- [How to Change Individual Author URLs](#0-toc-title)
- [How to Change the Base URL for all Authors](#1-toc-title)
- [Troubleshooting URLs After a Change](#h-troubleshooting-urls-after-a-change)

WordPress automatically creates URLs for every author. These URLs normally contain all the posts written by that author. For example, an author called “Steve” may have all his posts listed at example.com/author/steve/.

With the [PublishPress Authors](https://publishpress.com/links/authors) plugin we try to keep close to the WordPress core features, so we also create these author URLs.

It is not possible to change these author URLs in default WordPress or in the [PublishPress Authors](https://publishpress.com/links/authors) plugin. However, you can change the URLs using the [Edit Author Slug](http://wordpress.org/plugins/edit-author-slug/) plugin.

## How to Change Individual Author URLs [#](#0-toc-title)

In the first part of this guide, we’ll show you how to change the author URLs for individual authors.

- Install and activate the Edit Author Slug plugin.
- Go to “Users” in your WordPress admin menu.
- Edit the user whose URL you want to change.
- Scroll down and you’ll be able to change the author’s URL in this area. In the screenshot below, the “Author Slug” is “emperor” and this can be edited.

![Editor Author Slug plugin](https://docs-media.metaslider.com/publishpress-authors/choose-author-url-f205011652.webp)

You will also need to update the URL inside PublishPress Authors.

- Go to “Authors” in your WordPress admin menu.
- Notice that the “Author URL” is in the right column:

- Click “Edit Author Profile” to edit the author whose URL you want to change.

![Author URLs in PublishPress Authors](https://docs-media.metaslider.com/publishpress-authors/author-url-7c38ef3fe4.webp)

Look at the “Author URL” box to see if it’s correct. If it’s not correct, try these steps:

- Choose the user again under “Mapped User”.
- Click “Update”.
- Check the “Author URL” box again and the URL should be updated.

![Updated author URLs](https://docs-media.metaslider.com/publishpress-authors/author-url-box-9ff7a4f029.webp)

## How to Change the Base URL for all Authors [#](#1-toc-title)

At the beginning of this tutorial, we explained that an author called “Steve” may have all his posts listed at example.com/author/steve/. It also possible to change the base “author” portion of this URL. As before, we’ll use the [Edit Author Slug](http://wordpress.org/plugins/edit-author-slug/) plugin.

- Go to “Settings” then “Edit Author Slug”.
- You can change the ‘Author Base” option to meet your needs. You could also chose to have a different base URL for different user roles.

![Edit Author Slug for base URLs](https://docs-media.metaslider.com/publishpress-authors/author-url-base-bc363887f8.webp)

## Troubleshooting URLs After a Change [#](#h-troubleshooting-urls-after-a-change)

If your author URLs aren’t working after the changes in this tutorial, go to “Settings”, then “Permalinks” and click “Save Changes”. This will refresh the links on your site.

![](https://docs-media.metaslider.com/publishpress-authors/permalinks-b2aee71521.webp)
