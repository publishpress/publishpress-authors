---
title: "PublishPress Authors Maintenance"
order: 29
doc_category: publishpress-authors
---

# PublishPress Authors Maintenance

Table of Contents

- [Create PublishPress Authors profiles for all authors](#h-create-publishpress-authors-profiles-for-all-authors)
- [Create PublishPress Authors profiles for all users in a role](#h-create-publishpress-authors-profiles-for-all-users-in-a-role)
- [Synchronize the author and user profile fields](#h-synchronize-the-author-and-user-profile-fields)
- [Synchronize the author and user URLs](#h-synchronize-the-author-and-user-urls)
- [Copy Co-Authors Plus Data](#h-copy-co-authors-plus-data)
- [Delete Mapped Authors / Delete Guest Authors](#h-delete-mapped-authors-delete-guest-authors)

[PublishPress Authors](https://publishpress.com/links/authors) offers several maintenance tools. You will find these by going to “Authors”, then “Settings, and clicking the “Maintenance” tab.

## Create PublishPress Authors profiles for all authors [#](#h-create-publishpress-authors-profiles-for-all-authors)

This button creates author profiles for users who are post authors but yet have profiles in [PublishPress Authors](https://publishpress.com/links/authors). This feature is designed to help when you install PublishPress Authors on an existing site. Here’s an example of when this tool will be useful.

- In this situation, I have an existing website. The post in the screenshot has an author named “Riza”.

![WordPress post with existing author](https://docs-media.metaslider.com/publishpress-authors/riza-author-attached-to-post-1-82989b9bd2.webp)

- I install the PublishPress Authors plugin, but find that I don’t have an author profiles for the “Riza” user.

![PublishPress Authors screen](https://docs-media.metaslider.com/publishpress-authors/no-riza-author-profile-1b3ef439af.webp)

- To create a PublishPress Authors profile for “Riza”, I can go to the “Maintenance” tab and click “Create missing PublishPress Authors profiles”.

![Create missing PublishPress Authors profiles button](https://docs-media.metaslider.com/publishpress-authors/create-author-profiles-for-all-authors-e37afcf057.webp)

- After running this maintenance tool, there will now be a PublishPress Authors profile for “Riza”.

![A new PublishPress Authors profile for a user](https://docs-media.metaslider.com/publishpress-authors/author-profile-riza-da9736f4b6.webp)

## Create PublishPress Authors profiles for all users in a role [#](#h-create-publishpress-authors-profiles-for-all-users-in-a-role)

This feature locates all users assigned to a user role and generates PublishPress Author profiles for them. This is helpful if you have a specific user role for writers/authors on your site. The user role for this feature can be selected by going to the “General” tab and the “Automatically create author profiles” setting.

!["Automatically create author profiles" setting](https://docs-media.metaslider.com/publishpress-authors/automatically-create-author-profile-029caa139b.webp)

For example, you can choose “Contributor” for the “Automatically create author profiles” setting. Then when you click the “Create PublishPress Authors profiles” button, this will generate PublishPress Authors profiles for all users with the Contributor role.

![“Create PublishPress Authors profiles” button](https://docs-media.metaslider.com/publishpress-authors/create-author-profiles-for-a-role-f1fea7f06b.webp)

## Synchronize the author and user profile fields [#](#h-synchronize-the-author-and-user-profile-fields)

This feature is helpful if you want users to update their details from the default “Edit Profile” area in WordPress. PublishPress Authors can automatically import the user data from WordPress and add that data to author profiles. For a clearer understanding, consider the example below:

- My user “Riza” has the biographical information shown in the screenshot below. This is the core WordPress “Edit Profile” screen.

![Completed Biographical Information in PublishPress Authors](https://docs-media.metaslider.com/publishpress-authors/biographical-info-riza-b3e8111aef.webp)

- However, in the PublishPress Authors profile for the “Riza” user, the “Biographical Info” field is empty:

![Empty Biographical Information in PublishPress Authors](https://docs-media.metaslider.com/publishpress-authors/no-bio-author-riza-dc99526f80.webp)

- To solve this problem, go to the “Maintenance” tab. Click the “Sync author and user field” button:

![Biographical Information in the WordPress core](https://docs-media.metaslider.com/publishpress-authors/sync-author-and-user-field-7e27c95e7d.webp)

- Now check the PublishPress Authors profile and you’ll find that the fields have been updated with details from the core WordPress user field.

![Biographical Information in PublishPress Authors](https://docs-media.metaslider.com/publishpress-authors/authors-bio-riza-0eb904abfa.webp)

## Synchronize the author and user URLs [#](#h-synchronize-the-author-and-user-urls)

This features is helpful if you’ve recently changed your author slugs. This may happen if you’ve changed your site’s permalinks or some usernames. After those changes, you can use this button to synchronize the changes to the PublishPress Authors profile.

![Synchronize the author and user URLs](https://docs-media.metaslider.com/publishpress-authors/sync-author-slug-30978cd3b9.webp)

## Copy Co-Authors Plus Data [#](#h-copy-co-authors-plus-data)

If you have Co-Authors Plus plugin, you will see additional maintenance button as below This import will bring across all your author profiles. It will also keep track of which posts the authors are associated with. You can read more about this here: [Import to PublishPress Authors from Co-Authors Plus](https://publishpress.com/knowledge-base/co-authors-plus/).

![Copy Co-Authors Plus Data](https://docs-media.metaslider.com/publishpress-authors/co-author-button-49c4fda2d1.webp)

## Delete Mapped Authors / Delete Guest Authors [#](#h-delete-mapped-authors-delete-guest-authors)

The last two features on the Maintenance tab should only be used if requested by the PublishPress support staff.

If you delete mapped authors, this will remove all corresponding author profiles that are linked to real users. Only the authors’ profiles in PublishPress Authors will be deleted, and the WordPress users will remain unaffected. However, any connections between posts and multiple authors will be lost.

If you delete guest authors, it will erase all author profiles designated as guest authors. This action won’t impact author profiles connected to real users.

![Delete Mapped Authors](https://docs-media.metaslider.com/publishpress-authors/delete-guest-author-and-mapped-user-1965b0c171.webp)
