---
title: "Add Author Boxes Code to Your Theme"
order: 9
doc_category: publishpress-authors
---

# Add Author Boxes Code to Your Theme

Table of Contents

- [Create the Template Code](#h-create-the-template-code)
- [Add the Template Code to Your Theme](#h-add-the-template-code-to-your-theme)

[PublishPress Authors](https://publishpress.com/links/authors) allows you to generate PHP versions of your [Author Boxes](https://publishpress.com/links/author-box) layouts.

This approach is best suited for theme developers or people who want to reuse and distribute their layouts. There are a couple of key advantages to this approach:

- You can place these layouts inside your theme where you can add further customizations.
- You can distribute these layouts. For example, some theme designers will include their own layouts for the [Author Boxes](https://publishpress.com/links/author-box) inside their themes.

One disadvantage to this approach is that you will not be able easily edit the layout via the “Author Boxes” screen. If you want to maintain that easy of customization, you can use [a single line of PHP code instead](https://publishpress.com/knowledge-base/modify-theme-authors/).

## Create the Template Code [#](#h-create-the-template-code)

- Go to “Authors” then “Authors Boxes” in your WordPress admin menu.
- Edit an existing layout, or create a new layout.
- Go to the “Generate Theme Template” tab.
- Click the “Generate Template” button under the text area.

![Author Box editor theme template](https://docs-media.metaslider.com/publishpress-authors/generate-theme-template-78cad40ffa.webp)

## Add the Template Code to Your Theme [#](#h-add-the-template-code-to-your-theme)

- Create a folder inside your theme. The folder should be here: `/wp-content/themes/your-theme-name/publishpress-authors/author-boxes/`
- Create an empty PHP template file with your desired file slug in the `/author-boxes/` folder of your theme. For example, the file can be located here: `/wp-content/themes/your-theme-name/publishpress-authors/author-boxes/my-custom-author-box.php` .
- Copy the generated code from the [Author Box](https://publishpress.com/links/author-box) Editor and paste it inside the newly created file.
- This image below shows a finished file inside the TwentyTwentyTwo theme folder.

![Author Box editor theme template file](https://docs-media.metaslider.com/publishpress-authors/author-box-theme-089195ecd2.webp)

- Congratulations. Your can now choose your template inside the [PublishPress Authors](https://publishpress.com/links/authors) Settings:

![PublishPress Authors Settings for a custom layout](https://docs-media.metaslider.com/publishpress-authors/author-box-custom-2b10c5c8e3.webp)

- You can also insert it using a shortcode by using the file name. Here is an example of the shortcode:

```

```
