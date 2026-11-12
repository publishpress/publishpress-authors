---
title: "Default Author For New Posts"
order: 44
doc_category: publishpress-authors
---

# Default Author For New Posts

With the [PublishPress Authors](https://publishpress.com/links/authors) plugin, you can choose which Author is the default for new all content.

- Go to “Authors” in your WordPress admin menu.
- Click “Settings” then “Advanced”.
- You can choose the Author from the “Default author for new posts” option:

![Default author](https://docs-media.metaslider.com/publishpress-authors/default-author-1af851fc80.webp)

*Default Authors*

Now when you create content, the Author you chose will be the default:

![Default Author Choice](https://docs-media.metaslider.com/publishpress-authors/default-author-choice-0ec0d2ac1d.webp)

*Default Author Choice*

Please note there is an exception to this default author feature. This is done to avoid permissions issues when the post doesn’t belong to the current user and they can’t edit the post.

- If the user creating the post has the “[edit_others_posts](https://publishpress.com/links/edit_others_posts)” capability, the default author is always applied to the post.
- If the user creating the new post doesn’t have the “[edit_others_posts](https://publishpress.com/links/edit_others_posts)” capability, the default author will not be applied, and the current user will be set as the post author.
