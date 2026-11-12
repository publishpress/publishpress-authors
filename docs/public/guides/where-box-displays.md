---
title: "Change Where the Author Box Displays"
order: 75
doc_category: publishpress-authors
---

# Change Where the Author Box Displays

Table of Contents

- [Show Authors on Custom Post Types](#h-show-authors-on-custom-post-types)
- [Change When the Post Type is Visible](#h-change-when-the-post-type-is-visible)

The [PublishPress Authors](https://publishpress.com/authors) plugin allows you to show author details in your theme.

You can use the action “pp_multiple_authors_show_author_box” to display the [author box](https://publishpress.com/links/author-box) programmatically:

```
<?php do_action( 'pp_multiple_authors_show_author_box' ); ?>
```

## Show Authors on Custom Post Types [#](#h-show-authors-on-custom-post-types)

By default, the [author box](https://publishpress.com/links/author-box) under content is only displayed in the Post or Page post types. There are [other ways to display authors](https://publishpress.com/knowledge-base/display-multiple-authors/), but if you want author box under content on more post types, you can use this filter in your theme’s functions.php file.

```
<?php
add_filter( 'pp_multiple_authors_post_types', 'my_custom_post_type_filter' );

function my_custom_post_type_filter( $post_types ) {
    $post_types['my_custom_postype'] = esc_html__( 'MY Custom Post Type' );

    return $post_types;
}
?>
```

## Change When the Post Type is Visible [#](#h-change-when-the-post-type-is-visible)

By default, the author box under content only displays on the following conditions:

- Is not the homepage
- Is not a category page
- Is a single post or page
- Is a valid post type

You can change the result of the conditions test using the following filter in your theme’s functions.php file.

```
<?php
add_filter( 'pp_multiple_authors_filter_should_display_author_box', 'my_filter_should_display' );

function my_filter_should_display( $display ) {
    if ( $my_conditions... ) {
        $display = false;
    }

    return $display;
}
?>
```
