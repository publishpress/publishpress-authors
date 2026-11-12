---
title: "Import from Co-Authors Plus to PublishPress Authors"
order: 37
doc_category: publishpress-authors
---

# Import from Co-Authors Plus to PublishPress Authors

Table of Contents

- [How to Migrate from Co-Authors Plus](#0-toc-title)
- [Errors after Activating PublishPress Authors?](#h-errors-after-activating-publishpress-authors)

[PublishPress Authors](https://publishpress.com/links/authors) has a [Co-Authors Plus](https://wordpress.org/plugins/co-authors-plus/) import feature.

Co-Authors Plus is a popular but older alternative to [PublishPress Authors](https://publishpress.com/links/authors).

This import will bring across all your author profiles. It will also keep track of which posts the authors are associated with.

If you want to migrate your site from the Co-Authors Plus plugin, please try this on a test site before attempting it on a live site.

## How to Migrate from Co-Authors Plus [#](#0-toc-title)

- Start with the Co-Authors Plus plugin installed.

- Go to the “Plugins” screen and install the PublishPress Authors plugin.

- You will see a message saying that you have “It looks like you have Co-Authors Plus installed”. That link will send you to this documentation you’re reading now.

![Authors Import Warning](https://docs-media.metaslider.com/publishpress-authors/authors-import-warning-80ea5088cd.webp)

*Authors Import Warning*

- Go to the “Authors” link in your WordPress admin menu, and then click “Settings”.
- Click the “Maintenance” tab.
- Click the button “Copy Co-Authors Plus data” button.

![Importing from Co-Authors Plus to PublishPress Authors](https://docs-media.metaslider.com/publishpress-authors/co-authors-plus-import-0c9c43dcae.webp)

- You will now see a progress bar as your import progresses.
- When the import is finished, click “Deactivate Co-Authors Plus”.

![Import finished from Co-Authors Plus  to PublishPress Authors](https://docs-media.metaslider.com/publishpress-authors/co-authors-import-63d87ecf9c.webp)

- Go to the “Authors” link in your WordPress admin menu and check to see if the import has worked successfully.
- On the “Plugins” screen, delete the Co-Authors Plus plugin.

## Errors after Activating PublishPress Authors? [#](#h-errors-after-activating-publishpress-authors)

Please note that you may see an error message if you activate Co-Authors Plus on a site that already has PublishPress Authors installed. The error may look like this screenshot below.

![Error with Co-Authors Plus and PublishPress Authors installed](https://docs-media.metaslider.com/publishpress-authors/coauthors-plus-error-22a7270112.webp)

```
Fatal error: Cannot redeclare cap_get_coauthor_terms_for_post()
```

Unfortunately, we can’t fix this on our side, unless we disable the backward compatibility functions for Co-Authors.

It is safe to temporarily have both plugins active on your site. However, both plugins perform the same function so you need to choose either PublishPress Authors *or* Co-Authors Plus.
