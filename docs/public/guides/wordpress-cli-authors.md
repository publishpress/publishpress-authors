---
title: "WordPress CLI and PublishPress Authors"
order: 76
doc_category: publishpress-authors
---

# WordPress CLI and PublishPress Authors

Table of Contents

- [Using PublishPress Authors with the WordPress CLI](#0-toc-title)
- [CLI Commands and Plesk](#h-cli-commands-and-plesk)

The best way to manage large WordPress sites (over 1,000 authors or 100,000 posts) is with the WordPress Command Line Interface (CLI).

## Using PublishPress Authors with the WordPress CLI [#](#0-toc-title)

If you install [PublishPress Authors](https://publishpress.com/links/authors) on a large site with many existing authors, we recommend you run this command below. This command will update your posts and attach the correct author data into [PublishPress Authors](https://publishpress.com/links/authors).

```
$ wp publishpress-authors create-terms-for-posts
```

You can also limit this command to a certain number of posts at a time (default to 300):

```
$ wp publishpress-authors create-terms-for-posts --posts_per_page=500
```

Or you can also limit it to a specific post type (default to “post”):

```
$ wp publishpress-authors create-terms-for-posts --post_type=my_post_type
```

## CLI Commands and Plesk [#](#h-cli-commands-and-plesk)

If you are having issues running CLI commands on Plesk, please try the following command format:

```
$ plesk ext wp-toolkit --wp-cli -instance-id 2 --publishpress-authors create-terms-for-posts --skip-plugins=false
```

Just make sure to replace “create-terms-for-posts” with the subcommands and parameters you want to run.
