---
title: "Target the Author Boxes With CSS"
order: 72
doc_category: publishpress-authors
---

# Target the Author Boxes With CSS

The [Author Box](https://publishpress.com/links/author-box) displays in [PublishPress Authors](https://publishpress.com/links/authors) can be targeted with CSS to give each box a unique style.

For example, when each box loads on a page it is given a unique “instance”. So if you want to hide the first and third time that a box appears, you can use this CSS:

```
.pp-multiple-authors-boxes-wrapper.box-instance-id-1,
.pp-multiple-authors-boxes-wrapper.box-instance-id-3 {
  display: none !important;
}
```

Alternatively, you can use this CSS code to hide any [Author Boxes](https://publishpress.com/links/author-box) that are NOT inside the main post content.

```
.pp-multiple-authors-boxes-wrapper {
    display: none;
}

article .pp-multiple-authors-boxes-wrapper {
    display: block;
}
```

The opposite is possible too. This code below will hide only the [Author box](https://publishpress.com/links/author-box) that appears inside the main post content.

```
article .pp-multiple-authors-boxes-wrapper {
    display: none;
}
```
