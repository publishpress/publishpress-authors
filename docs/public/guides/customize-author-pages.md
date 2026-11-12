---
title: "How to Customize Author Pages"
order: 43
doc_category: publishpress-authors
---

# How to Customize Author Pages

Table of Contents

- [Find the Default Author Pages Templates](#h-find-the-default-author-pages-templates)
- [Modify the Default Author Pages Templates](#1-toc-title)

It is possible to modify [the author pages provided by PublishPress Authors](https://publishpress.com/knowledge-base/author-pages/). This guide will show you how to find and then modify the Author Pages templates.

## Find the Default Author Pages Templates [#](#h-find-the-default-author-pages-templates)

For users of the Free version, templates you can modify are in this folder: `/wp-content/plugins/publishpress-authors/src/templates/parts/`. There are two default templates:

- `author-pages-grid.php`
- `author-pages-list.php`

Users of the Pro version will find the templates in this folder: `/wp-content/plugins/publishpress-authors-pro/lib/vendor/publishpress/publishpress-authors/src/templates/parts/`

## Modify the Default Author Pages Templates [#](#1-toc-title)

To modify the design of your author pages, you should copy the default files and place them inside a `/publishpress-authors/` folder in your theme’s folder.

Here’s an example, showing how to modify the “Grid” layout:

- Find and copy this file:

`/wp-content/plugins/publishpress-authors/src/templates/parts/author-pages-grid.php`

- Place your modified copy of this file in this location:

`/wp-content/themes/your-theme-name/publishpress-authors/templates/author-pages-grid.php`

Please note that this exact folder structure may not exist. You will probably need to create this folder inside your theme: `/publishpress-authors/templates/`
