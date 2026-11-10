---
title: "Schema Data for Authors"
order: 68
doc_category: publishpress-authors
---

# Schema Data for Authors

Table of Contents

- [SEO Plugins and PublishPress Authors](#h-seo-plugins-and-publishpress-authors)
- [How to Test the Author Schema Data](#h-how-to-test-the-author-schema-data)
- [Social Media Profiles and the SameAs Schema](#h-social-media-profiles-and-the-sameas-schema)

[PublishPress Authors](https://publishpress.com/links/authors) can provide schema data to help search engines find your author information. This schema data will not be visible in normal search results, but search engines will find this information and use it to identify the authors.

## SEO Plugins and PublishPress Authors [#](#h-seo-plugins-and-publishpress-authors)

PublushPress Authors integrates with the [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/) or [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/) plugins to show schema data for your authors.

If you have the [PublishPress Authors](https://publishpress.com/links/authors) plugin installed, plus either Yoast or Rank Math, the schema data will be available automatically. No configuration is needed.

## How to Test the Author Schema Data [#](#h-how-to-test-the-author-schema-data)

The author schema data will appear in two situations:

- On individual posts.
- On author pages.

You can test the schema data for your authors using a tool such as [https://validator.schema.org](https://validator.schema.org). If you have multiple authors assigned to an individual post, this data will appear multiple times: once for each author.

- The schema data uses the “Person” type as in the example result shown below.
- The “Name” data is taken from the “Display Name” field inside PublishPress Authors.
- The “Image” data is take from the “Avatar” field inside PublishPress Authors.

![PublishPress Authors schema data](https://docs-media.metaslider.com/publishpress-authors/author-schema-data-8dd1c8a96b.webp)

If users want to update the name used in their schema data, they should update the “Display name publicly as” field inside the “Author Profile” area:

![Display name publicly as](https://docs-media.metaslider.com/publishpress-authors/public-display-7d137a3274.webp)

## Social Media Profiles and the SameAs Schema [#](#h-social-media-profiles-and-the-sameas-schema)

If you’re using the Pro version of PublishPress Authors, you will be able to add the “SameAs” schema option for fields that use the “Link” format.

Choose “Yes, this is a Social Profile”. This feature will add the SameAs property to this link so that search engines realize that the social profile is connected to this author.

![PublishPress Authors and SameAs schema data](https://docs-media.metaslider.com/publishpress-authors/sameas-8702df4c54.webp)
