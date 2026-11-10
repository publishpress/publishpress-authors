---
title: "Retrieving Authors Data via the REST API"
order: 32
doc_category: publishpress-authors
---

# Retrieving Authors Data via the REST API

You can get [PublishPress Authors](https://publishpress.com/links/authors) information via the WordPress API. This is a useful feature that allows you to integrate [PublishPress Authors](https://publishpress.com/links/authors) with other plugins.

You can see the API integration at a link [such as this one](https://publishpress.com/wp-json/wp/v2/posts). The content of the API looks like this:

```
“authors:[{“term_id”:2866,“user_id”:2,“is_guest”:0,“slug”:“steve”,“display_name”:“Steve Burge”,“avatar_url”:“https://example.com/avatar.png”}]
```

Here’s that same information in a more readable format:

```
“authors”: [
  {
    “term_id”: 2866,
    “user_id”: 2,
    “is_guest”: 0,
    “slug”: “steve”,
    “display_name”: “Steve Burge”,
    “avatar_url”: “https://example.com/avatar.png”
  },
]
```

[PublishPress Authors stores all authors as taxonomy terms](https://publishpress.com/knowledge-base/publishpress-authors-database-settings/), so Guest Authors will not have a Term ID but not a User ID. You can query posts from guest authors via the Rest API by adding the ppma_author parameter to your request.

For example: /wp-json/wp/v2/posts?ppma_author=118 will query post by any author, including guest authors, with the Term ID of 118.
