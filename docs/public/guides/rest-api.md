---
title: "Adding Authors to Posts via the REST API"
order: 67
doc_category: publishpress-authors
---

# Adding Authors to Posts via the REST API

[PublishPress Authors](https://publishpress.com/links/authors) does integrate with WordPress’ REST API.

Adding posts via should be handled by the core WordPress REST API. In the [PublishPress Authors](https://publishpress.com/links/authors) plugin, authors are created as taxonomies. [This article explains the data structure in PublishPress Authors](https://publishpress.com/knowledge-base/publishpress-authors-database-settings/).

To assign authors when creating posts, you can assign taxonomy terms to your posts. In this example code, `ppma_author` is REST API slug. 87 is the taxonomy term ID for the author.

```
{
"title": "The story of Dr Foo",
"content": "This is the story content",
"ppma_author": [87],
"status":"publish",
"excerpt":"Dr Foo story"
}
```

Here is another approach to that code with 87 and 103 as the term IDs for authors.

```
[
'title' => 'The story of Dr Foo',
'content' => 'This is the story content',
'ppma_author' => [87, 103],
'status' => 'publish',
'excerpt' => 'Dr Foo story'
]
```

To extend this, it’s worth checking [our guide to the database structure for PublishPress Authors](https://publishpress.com/knowledge-base/authors-data-backup/#h-author-profile-information). We use core WordPress tables for all the author data. For example, custom fields and profile data are stored in the **_termmeta** table.
