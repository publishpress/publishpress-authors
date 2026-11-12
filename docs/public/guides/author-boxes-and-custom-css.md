---
title: "Author Boxes and Custom CSS"
order: 8
doc_category: publishpress-authors
---

# Author Boxes and Custom CSS

Table of Contents

- [#1. Wider Space for Author Fields](#h-1-wider-space-for-author-fields)
- [#2. Fix Stretched Author Avatars](#h-2-fix-stretched-author-avatars)
- [#3. Author Boxes Side-by-Side/2 Columns](#h-3-author-boxes-side-by-side-2-columns)

The [Author Boxes](https://publishpress.com/links/author-box) features in [PublishPress Authors](https://publishpress.com/links/authors) allows you to use custom CSS to make modifications to your [author boxes](https://publishpress.com/links/author-box). Here are a few real use case scenarios that might be useful for your needs. This can be done from the “Custom CSS” tab inside each [Author Box](https://publishpress.com/links/author-box):

![Custom CSS box in PublishPress Authors](https://docs-media.metaslider.com/publishpress-authors/custom-css-tab-9276e624b3.webp)

## #1. Wider Space for Author Fields [#](#h-1-wider-space-for-author-fields)

By default, Author fields will take up the space that is needed (inline). You could change it to occupy the entire space by changing the display to block. Here is the custom CSS to do that.

```
a.ppma-author-field-meta {display: block !important};
```

![PublishPress Author profile](https://docs-media.metaslider.com/publishpress-authors/author-fields-stacked-no-hyperlink-d65fc18194.webp)

## #2. Fix Stretched Author Avatars [#](#h-2-fix-stretched-author-avatars)

If you upload an image that is not proportional, it may appear stretched and distorted. To fix this, you can use custom CSS.:

```
.pp-author-boxes-avatar img {
    object-fit: contain;
}
```

![PublishPress Author authors profiles](https://docs-media.metaslider.com/publishpress-authors/image-stretched-a63d5d99e2.webp)

## #3. Author Boxes Side-by-Side/2 Columns [#](#h-3-author-boxes-side-by-side-2-columns)

Normally, our plugin displays author boxes in a vertical layout, one stacked on top of the other. However, if you prefer a 2 column layout, you can adjust the width to take up half of the space. To achieve this, you can use the following CSS code as an example::

```
.pp-multiple-authors-boxes-ul {
display: flex;
flex-wrap: wrap;
justify-content: space-between;
list-style-type: none;
padding: 0;
}

.pp-multiple-authors-boxes-li {
width: calc(50% - 10px);
margin-bottom: 20px;
}
```

![PublishPress Author profile side-by-side](https://docs-media.metaslider.com/publishpress-authors/author-boxed-2-columns-8518b57c3d.webp)
