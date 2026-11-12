---
title: "How to Add Author Boxes to Your Site"
order: 46
doc_category: publishpress-authors
---

# How to Add Author Boxes to Your Site

Table of Contents

- [Important: Default WordPress displays do not support multiple authors](#h-important-default-wordpress-displays-do-not-support-multiple-authors)
- [Automatically add Author Boxes to Posts](#h-author-boxes-below-the-content)
- [Author Boxes in a Gutenberg Block](#2-toc-title)
- [Author Boxes in a Shortcode](#h-author-boxes-in-a-shortcode)
- [Author Boxes in a Widget](#h-author-boxes-in-a-widget)
- [Author Boxes in Code](#h-author-boxes-in-code)

[PublishPress Authors](https://publishpress.com/links/authors) has a feature called “Author Boxes” that controls how you display authors on your site.

You have several different options for displaying Author Boxes. This guide describes four different ways to show your authors.

## Important: Default WordPress displays do not support multiple authors [#](#h-important-default-wordpress-displays-do-not-support-multiple-authors)

Most WordPress themes will have a default author byline and sometimes also an author profile box.

**The default WordPress displays will not support multiple authors**. The [PublishPress Authors](https://publishpress.com/links/authors) plugin is here to fill a gap in the WordPress core functionality.

We recommend that you use one of the four PublishPress Authors options in this guide. We also recommend that you disable the default WordPress author displays.

## Automatically add Author Boxes to Posts [#](#h-author-boxes-below-the-content)

This feature will display the Author Box above or below your Posts.

- Go to Authors > Settings and click the “Author Boxes” tab.
- Check the box saying, “This will display the authors box at the top of the content” or “This will display the authors box at the end of the content”.
- In the “Default Layout” box, you can [choose from several layouts](https://publishpress.com/knowledge-base/layout/).

![Automatically add Author Boxes to Posts](https://docs-media.metaslider.com/publishpress-authors/author-boxes-tab-b5bde28fae.webp)

You will now see the PublishPress Authors box appear below the posts on your site. This image shows where the box will if you choose to display it at the end of the content:

![Authors Box Under Post](https://docs-media.metaslider.com/publishpress-authors/authors-box-under-post-6d06798113.webp)

## Author Boxes in a Gutenberg Block [#](#2-toc-title)

There is a Gutenberg block available for you to display the author box in any part of your content. Search for “Author Box” when inserting a new block.

![Author Box Gutenberg block](https://docs-media.metaslider.com/publishpress-authors/author-gutenberg-block-49a5a94c05.webp)

## Author Boxes in a Shortcode [#](#h-author-boxes-in-a-shortcode)

With PublishPress Authors shortcodes, you can display the author box in any part of the content.

[Click here to find out more about shortcode options](https://publishpress.com/knowledge-base/shortcodes/).

## Author Boxes in a Widget [#](#h-author-boxes-in-a-widget)

PublishPress Authors provides a widget that you can use to show the authors. [Click here to see more about the Post Authors widget](https://publishpress.com/knowledge-base/post-author-widget/).

- Go to Appearance > Widgets in your WordPress admin menu.
- You can position your PublishPress Authors widget in any sidebar or widget area.
- The image below is a preview of how the widget may appear:

![Authors Widget](https://docs-media.metaslider.com/publishpress-authors/authors-widget-dc5c51d3e3.webp)

## Author Boxes in Code [#](#h-author-boxes-in-code)

PublishPress Authors allows you to show author details in your plugin or theme.

You can use the action “pp_multiple_authors_show_author_box” to display the author box programmatically:

```
<?php do_action( 'pp_multiple_authors_show_author_box' ); ?>
```

If you want more details on this option, [click here for more on expanding on this code snippet](https://publishpress.com/knowledge-base/modify-theme-output-authors/).

It’s also possible to use the PublishPress Authors shortcodes inside a PHP snippet, as in this example:

```
<?php echo do_shortcode( '
                
                    

                    
                    
                                                
                        
                                                                                                                                    
                                                                                                                        
                                                                                                                                                                                                                                                                                                                                                            
                                                                                                                    
                                                                                                                                                                                    
                                                                    
                                                                                                                                                                                                                                                                                                                                                                                                                            
                                                                                                                                    
                                                            
                                                            
                                                                Steve Burge                                                                                                                                                                                                    
                                                                                                                                            
                                                                                                                                                    Steve is the founder of PublishPress. He's been working with open source software for over 20 years. Originally from the UK, he now lives in Sarasota in the USA. This profile is generated by the PublishPress Authors plugin.
                                                                                                                                                
                                                                                                                                                                                                    
                                                                                                                                
                                                                                                                            
                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                                                        
                                                                            
                                                                                                                        
                        
                                            
                    
                    
                
                            
        ' ); ?>
```
