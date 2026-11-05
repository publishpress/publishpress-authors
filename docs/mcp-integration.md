# Authors MCP integration

PublishPress Authors registers private actions through the WordPress Abilities API on WordPress 6.9 or newer. PublishPress Hub can expose selected actions through the separately installed official WordPress MCP Adapter. Older supported WordPress versions continue to run Authors without these actions.

Enable AI connections in Hub, choose the actions and write access you want to permit, save, and connect your AI service. Each action also checks the connected account's native Authors and WordPress capabilities. After changing exposed actions or enabling modules, refresh the AI connection to discover the current tools.

All action names below use the `publishpress-authors/` prefix. Start with `list-features` and the relevant field discovery. Profiles use author **term IDs**, which are different from WordPress user IDs. Author Boxes, custom-field definitions, saved Author Lists and Author Categories each have their own IDs.

## Profile and account operations

`list-authors`, `get-author` and `get-author-fields` provide profile summaries and field definitions. `get-author-profile` adds editable public field values and a revision hash; `get-profile-fields` provides exact native labels, types and choices, including enabled custom fields. Email, hidden REST fields, authentication data and arbitrary metadata are excluded from profile reads. Hidden writable fields are marked `writeOnly` in discovery.

`save-author` creates a guest profile when guest profiles without accounts are enabled, creates a profile linked to an existing `user_id`, or patches an existing `author_id`. Existing updates require `expected_hash`. It preserves omitted fields and uses native profile sanitization and linked-user synchronization. Creating or deleting WordPress accounts is outside these actions.

`link-author-user` links an existing profile to an existing user. A `user_id` of zero unlinks it without changing the account's role or deleting it. Native single-profile mapping restrictions and administrator profile policy apply. `delete-author` deletes an unassigned profile using native taxonomy permissions; reassign its posts first. Deleting a profile does not delete its account.

In Pro, `map-guest-author-to-user` follows Auto Map's exact slug/nicename matching. It previews by default; explicitly use `dry_run: false` to save a verified match. It requires an enabled Auto Map module and all shared linking permissions. It never creates an account.

## Post bylines and display

`get-post-byline` reads stored ordered author IDs, category assignments, the fallback WordPress author and per-post Author Box settings. Reads do not create fallback profiles or synchronize relationships.

`get-byline-choices` searches existing author profiles and returns enabled category and Author Box choices for an editable post. It uses byline permissions, so an Editor does not need profile-management access to choose an existing author. Author and category results have separate page totals; continue each list through its own total.

`update-post-byline` replaces `author_ids` in order, or patches `categories`, `author_box` and `disable_author_box`. Unmentioned settings remain unchanged. Category-map keys are author term IDs; values are arrays of category IDs. An explicit empty map clears assignments. Multiple categories for one author require the native multiple-category option. Categories must be enabled and, with Pro active, available for the target post type. The post type must be enabled for Authors, and per-post box selection must be enabled before changing its box.

`fallback_user_id` applies to guest-only bylines. Native synchronization uses the first linked user when the byline contains a user-backed profile. Stored relationships and the core fallback author are reported separately; normal frontend fallback behavior still follows Authors settings.

`bulk-update-post-bylines` accepts an explicit list of changes, each with its own `post_id` and `expected_hash`. Every target is validated before writing. Duplicate targets and invalid identifiers are rejected. Failed writes trigger restoration of applied targets; an incomplete restoration returns an explicit error requiring a fresh read.

## Settings and modules

`get-plugin-settings` discovers non-secret General, Display, Author Pages, Guest Authors, legacy layout mapping and Advanced settings. `update-plugin-settings` patches supplied settings and preserves unrelated configuration. Boolean settings use `true` and `false`, not HTML form strings. Native layout choices, role names, enabled post types and numeric ranges are validated. Site-code post-type locks and native editable-role filtering remain effective. Legacy Display settings include singular/plural box titles, color scheme and email/website link switches.

`list-features` lists native modules, their enabled state and the supported workflows. `set-feature-enabled` switches one discovered optional module; core/autoloaded modules cannot be disabled. This changes an entire module rather than an individual setting. The next WordPress request loads the updated module state.

Author Pages settings retain native layout choices, display controls, post-type selection and rewrite-rule refresh behavior. SEO, theme and other third-party integration modules are discoverable when their dependencies make them available. Module switches do not configure external accounts or credentials. Pro branding is supported; licensing remains in the existing licensing interface.

## Author Boxes and custom fields

`list-author-boxes`, `get-author-box` and `get-author-box-fields` discover native boxes and editable layout, title, avatar, biography, profile-field, recent-post and CSS controls. Active Pro category-grouping controls come from the Pro runtime. Box `shortcodes` use matching `shortcode` and `position` arrays; supported positions are `meta`, `name`, `bio` and `authors`. Native sanitizers apply.

`save-author-box` creates a draft or patches an existing ID. Use the optional `template` on creation to choose a native Free or Pro template from field discovery. Pro templates retain their native parent layout. Existing updates require the latest hash and preserve unmentioned fields. `delete-author-box` uses native Trash and object-deletion permissions. Existing references are not rewritten when a box is renamed or trashed.

`list-custom-fields`, `get-custom-field` and `get-custom-field-fields` read native field definitions. Pro enables `save-custom-field` and `delete-custom-field`. Supported definition settings include field type/status, required state, social/schema properties, link behavior, description and REST visibility. Field definitions differ from profile values: use `save-author` to change a person's value. Use `menu_order` to change native box or Pro field list ordering. Box `field_order` separately controls the profile fields within that layout. Renaming a field does not migrate values stored under its previous slug. Reserved authentication and account-control field names are rejected.

## Saved Author Lists and categories

`list-author-lists`, `get-author-list` and `get-author-list-fields` manage saved list presets rather than individual profiles. `save-author-list` validates native fields and generates a `publishpress_authors_list` shortcode and parsed arguments. Pro adds search, ordering and pagination controls. Omitted fields and other lists are preserved. Set status `active` to restore a trashed list; `delete-author-list` trashes by default and requires `permanent: true` for permanent removal. Editing a preset does not insert its shortcode into posts.

`list-author-categories`, `get-author-category` and `get-author-category-fields` discover singular/plural names, enabled state, order and schema configuration. `save-author-category` creates or patches one category; Pro adds post-type targeting. These describe roles such as Writer or Reviewer in a byline, not WordPress post categories. `delete-author-category` requires references in posts and default profile categories to be reassigned first.

## Permissions, previews and limits

- Profiles require the filtered Authors management capability and relevant term permissions. Linked account changes also require `edit_user` and the native administrator-profile setting.
- Bylines require the filtered byline-edit capability, `edit_post` and author-taxonomy assignment permission. Every bulk target is checked independently.
- Settings and lists use the filtered settings-management capability. Boxes use the filtered layout-management capability plus native object permissions. Custom-field reads require the filtered Author Fields capability (`ppma_manage_custom_fields` by default) and relevant object permission; definition writes additionally require Pro. Categories retain their separate filtered category-management capability.
- An Administrator role never overrides a denied capability. Application-password restrictions remain effective. Hub selection controls exposure rather than granting permission.
- Mutation actions support `dry_run: true` for validated previews. Previews do not persist configuration; they retain the current revision hash. Existing profile, byline, object, list and category updates/deletions require a fresh hash. Re-read if another user changes the target.
- Paged actions default to 20 items and allow at most 100 per page. Some pages may be empty after permission filtering; follow the returned totals.
- Only byline changes provide a multi-object action. Other writes, including Pro Auto Map, target one object per call; native bulk interfaces remain available.

License keys, passwords, arbitrary user/option metadata, browser account switching, role-changing guest conversions, destructive legacy migration/maintenance jobs and third-party account administration remain in their native interfaces. Core content/account operations belong to Hub's WordPress actions. These tools do not run PHP, SQL or supplied callbacks.

See [feature coverage](mcp-feature-coverage.md) and [request examples](mcp-prompt-examples.md) for supported workflows.
