---
title: "Where is Author Data Stored in the Database?"
order: 18
doc_category: publishpress-authors
---

# Where is Author Data Stored in the Database?

Table of Contents

- [Authors and Post Relationships](#h-authors-and-post-relationships)
- [Author Fields Information](#h-author-profile-information)
- [The Default post_author Column](#h-the-default-post-author-column)

This guide is an introduction to how authors data is stored in the [PublishPress Authors](https://publishpress.com/links/authors) plugin.

## Authors and Post Relationships [#](#h-authors-and-post-relationships)

The key thing to know about the data in the [PublishPress Authors](https://publishpress.com/links/authors) plugin is that “authors” are created using WordPress taxonomies. This means that “authors” are stored as taxonomy terms.

Here is an example of the **_terms** table. “Uncategorized” is not relevant for this guide because it is the default category for Posts. The other two terms are authors from the PublishPress Authors plugin.

![Wp Terms 1](https://docs-media.metaslider.com/publishpress-authors/wp-terms-1-5371cff65d.webp)

*Wp Terms 1*

The taxonomy we use to organize author profiles is “author”. This next image is from the **_term_taxonomy** table. Each author has one entry on this table to say the term “belongs” to the taxonomy “author”.

![Wp Term Taxonomy](https://docs-media.metaslider.com/publishpress-authors/wp-term-taxonomy-d0028facf2.webp)

*Wp Term Taxonomy*

The **_term_relationships** table stores the relationships between posts and terms. This table stores the list of posts for each author, and the list of authors for each post.

![Wp Term Relationships](https://docs-media.metaslider.com/publishpress-authors/wp-term-relationships-93c65de34d.webp)

*Wp Term Relationships*

**_postmeta**: We store one register called `ppma_authors_name` in this table for each post with the name of each author separated by “, “.

![Wp Postmeta](https://docs-media.metaslider.com/publishpress-authors/wp-postmeta-e8f959991d.webp)

*Wp Postmeta*

## Author Fields Information [#](#h-author-profile-information)

The**_termmeta** table stores the profile fields for each author. All the author’s information is metadata. This includes the author’s name, email, path to the custom avatar, custom fields etc. You will find multiple rows for each author.

When authors are mapped to a WordPress user, the user_id will also stored as metadata as well. The avatar image for the author will be stored in the “avatar” field in the **_usermeta** table.

![Wp Termmeta](https://docs-media.metaslider.com/publishpress-authors/wp-termmeta-6e11ceb586.webp)

*Wp Termmeta*

## The Default post_author Column [#](#h-the-default-post-author-column)

By default, WordPress records the author in the **post_author** column in the **_posts** table.

PublishPress Authors does not update that column.

Even if PublishPress Authors is enabled, WordPress will continue to record the user that created the post. So if the Authors plugin is disabled, that is the data WordPress will use to display the author.

We can’t use the **post_author** field because it doesn’t support multiple user IDs, and does not support guest authors. The PublishPress Authors plugin was created because of the limitations of this area in WordPress.
