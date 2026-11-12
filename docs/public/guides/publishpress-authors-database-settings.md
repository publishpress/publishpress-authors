---
title: "Where are PublishPress Authors Settings Stored in the Database?"
order: 66
doc_category: publishpress-authors
---

# Where are PublishPress Authors Settings Stored in the Database?

Table of Contents

- [Data for PublishPress Authors Settings](#h-data-for-publishpress-authors-settings)
- [Data for Migrations](#h-data-for-migrations)
- [Data for the PublishPress Authors Widgets](#h-data-for-the-publishpress-authors-widgets)
- [Date for Integration with Other Plugins](#h-date-for-integration-with-other-plugins)
- [Data for the Pro version of PublishPress Authors](#h-data-for-the-pro-version-of-publishpress-authors)
- [Data for Plugin Versions](#h-data-for-plugin-versions)

This guide will introduce to the data is created and stored when you’re using the [PublishPress Authors](https://publishpress.com/links/authors) plugin.

[PublishPress Authors](https://publishpress.com/links/authors) stores the settings as options in the `wp_options` table.

![Publishpress Authors Database](https://docs-media.metaslider.com/publishpress-authors/publishpress-authors-database-dfb54d17e7.webp)

*Publishpress Authors Database*

## Data for PublishPress Authors Settings [#](#h-data-for-publishpress-authors-settings)

These options store data from key settings in the Authors plugin:

- **multiple_authors_modules_settings_options**: Some internal data for the plugin’s modules.
- **multiple_authors_settings_options**: Some internal data for the settings module.
- **multiple_authors_multiple_authors_options**: Some internal data for the multiple authors module.
- **multiple_authors_default_layouts_options**: Some internal data for the module Default Layouts.

## Data for Migrations [#](#h-data-for-migrations)

These settings contain data if you have emigrated to the Authors plugin from another plugin:

- **publishpress_multiple_authors_settings_migrated_3_0_0**: This helps with backward compatibility. This option records if data was migrated to the 3.0.0 release of the plugin.
- **multiple_authors_byline_migration_options**: Data for [migration from the Byline plugin](https://publishpress.com/knowledge-base/how-to-import-from-byline-to-multiple-authors/).
- **multiple_authors_bylines_migration_options**: Data for [migration from the Bylines plugin](https://publishpress.com/knowledge-base/migrate-bylines/).

## Data for the PublishPress Authors Widgets [#](#h-data-for-the-publishpress-authors-widgets)

These settings are for the two widgets that install with the plugin:

- **widget_multiple_authors_widget**: Settings for [the Authors widget](https://publishpress.com/knowledge-base/post-author-widget/).
- **widget_multiple_authors_list_widget**: Settings for [the Authors List widget](https://publishpress.com/knowledge-base/authors-list-widget/).

## Date for Integration with Other Plugins [#](#h-date-for-integration-with-other-plugins)

These settings relate to integrations with other plugins and page-builders:

- **multiple_authors_yoast_seo_integration_options**: Data for integration with Yoast SEO.
- **multiple_authors_genesis_integration_options**: Data for integration with the Genesis Framework.
- **multiple_authors_elementor_integration_options**: Data for [integration with Elementor](https://publishpress.com/knowledge-base/authors-elementor/).
- **multiple_authors_divi_integration_options**: Data for [integration with Divi](https://publishpress.com/knowledge-base/authors-divi/).

## Data for the Pro version of PublishPress Authors [#](#h-data-for-the-pro-version-of-publishpress-authors)

These options will show if you’re using the Pro version:

- **multiple_authors_author_custom_fields_options**: Data for [the Custom Fields feature](https://publishpress.com/knowledge-base/custom-fields/).
- **multiple_authors_author_custom_layouts_options**: Data for [the Custom Layouts feature](https://publishpress.com/knowledge-base/layout/).
- **multiple_authors_prosettings_options**: Settings for the Pro plugin.

## Data for Plugin Versions [#](#h-data-for-plugin-versions)

For backwards compatibility, we do store the version twice:

- **multiple_authors_version**: the current plugin version.
- **pp_authors_version**: the current plugin version.
