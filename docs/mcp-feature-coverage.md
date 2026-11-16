# Authors MCP feature coverage

| Native feature | Discovery and reads | Changes | Permission and edition boundaries |
| --- | --- | --- | --- |
| Authors and guest profiles | `list-authors`, `get-author`, `get-author-profile`, `get-profile-fields`, `get-author-fields` | `save-author`, `link-author-user`, `delete-author` | Filtered author management, term capabilities, linked-account checks and administrator profile policy. No account creation/deletion or role changes. |
| Post author panel and ordered bylines | `get-post-byline`, `get-byline-choices`, `get-post-authors` | `update-post-byline`, `bulk-update-post-bylines` | Byline capability, assignment permission, per-post editing and enabled post types. |
| Per-post Author Box | Current box and allowed choices through byline reads | Patch box selection or disabled state through byline actions | Native post checks and per-post box-selection option. |
| General, Display, Guest Authors and Advanced settings | `get-plugin-settings` includes native choices and a typed whitelist | `update-plugin-settings` | Filtered settings capability, role/post-type validation and site-code locks. |
| Author Pages and legacy box mappings | Native layout, post display and mapping choices in settings | Patch supplied settings; rewrite refresh where needed | Native settings permission; layouts restricted to active-edition choices. |
| Author Boxes and templates | `list-author-boxes`, `get-author-box`, `get-author-box-fields` | `save-author-box`, `delete-author-box` | Layout management plus object creation/edit/publish/delete checks. Native templates, shortcode positions, CSS, profile-field order, list order and sanitizers. |
| Pro box category grouping | Active Pro box fields and templates | Box field patches and creation from native Pro templates | Actual Pro module definitions; Free rejects undiscovered Pro fields/templates. |
| Profile field values | Typed active profile fields and public values | `save-author` | Native sanitizers; hidden fields stay out of reads; authentication metadata is blocked. |
| Custom-field definitions | `list-custom-fields`, `get-custom-field`, `get-custom-field-fields` | `save-custom-field`, `delete-custom-field` | Reads require the dedicated filtered Author Fields capability and per-object editing. Writes additionally require Pro; native creation, publishing and deletion checks apply. List order uses menu_order. |
| Author Lists | `list-author-lists`, `get-author-list`, `get-author-list-fields` | `save-author-list`, `delete-author-list`, restore via status | Settings capability; generated native shortcode; Pro controls come from its real field filter. |
| Author Categories | `list-author-categories`, `get-author-category`, `get-author-category-fields` | `save-author-category`, `delete-author-category` | Separate category-management capability; native storage; referenced categories cannot be deleted. |
| Pro category targeting | Active post-type choices and saved targeting | Category patch | Actual Pro activation; Free rejects targeting inputs. |
| Pro Auto Map | Profile slug and verified existing-user match | `map-guest-author-to-user` | Enabled Pro module, term/account permissions, mapping restrictions and profile lock; preview by default. |
| Pro branding | Typed branding setting | `update-plugin-settings` | Pro activation; license fields remain excluded. |
| Optional integrations | `list-features` returns modules available in the current runtime | `set-feature-enabled` for optional modules | Native settings capability; core/autoloaded module locks. Dependency-specific data stays with that integration's native UI. |

Author Box ordering/appearance and Author List grouping controls use their discovered native field definitions. Publishing, trashing and profile deletion are separate from presentation changes. Existing content and references are preserved unless a supported action explicitly targets them.

Browser previews, widgets/block placement in content, generated PHP templates, licensing, destructive legacy import/migration/repair jobs, user-role conversion and external integration credentials retain their existing interfaces. Hub owns shared transport and general WordPress content/account actions; Authors owns the domain workflows above.

## Native permission map

Capabilities below use their native filters; the default name is not hard-coded as a replacement for the filter.

| Workflow | Default capability and object checks |
| --- | --- |
| Profile discovery and reads | `ppma_manage_authors`; taxonomy edit permission, with `edit_term` checked for individual profiles and denied terms omitted from lists. |
| Profile writes | Same management/term checks; linked accounts additionally require `edit_user`, native mapping restrictions and administrator-profile policy. Deletion also requires `delete_term` and an unassigned profile. |
| Post bylines | `ppma_edit_post_authors`, `edit_post`, taxonomy `assign_terms`, enabled post type; every batch target is checked. Categories must be enabled and match active Pro post-type targeting. |
| Settings, Author Pages and Author Lists | Filtered `manage_options`; native settings validators, editable-role choices and code-enforced post-type locks. |
| Author Boxes | `ppma_manage_layouts`, native `edit_ppma_box` and object editing; creation/publishing/deletion use the registered post-type capabilities. |
| Author Fields | `ppma_manage_custom_fields`, object editing and native post-type creation/publishing/deletion capabilities. General settings access does not grant this feature's access. |
| Author Categories | `ppma_manage_author_categories`; Pro for targeting; references must be reassigned before deletion. |
| Pro Auto Map | Enabled Auto Map module and all profile, term, linked-account and administrator-profile checks. |

## Additional native interfaces

| Interface | MCP behavior |
| --- | --- |
| Legacy Display | Singular/plural box titles, color scheme and email/website link switches are typed settings. This changes display configuration; profile reads still exclude email values. |
| Box Shortcodes and Custom CSS | Native shortcode/position pairs and CSS are editable through box fields with native sanitizers. The action saves configuration; it does not execute a shortcode while saving. |
| Box export/import | Read a box's editable configuration and create or patch typed settings. Native export/import files and generated PHP templates retain their existing UI. |
| Widgets, Gutenberg blocks and Pro Authors List shortcode | Saved list shortcodes and layout IDs are available for placement. Widget/block placement and direct shortcode rendering retain their editor interfaces; general content tools belong to Hub/Blocks. |
| Theme and SEO integrations | Available module switches are discoverable. Native hooks provide rendering and metadata; MCP does not simulate a theme request or configure third-party credentials. |
| BuddyPress, Ultimate Member, Polylang and Edit Flow | Runtime-available modules retain native dependency gates and can use the optional-module switch. External profile/account administration remains with those integrations. |
| Migration, maintenance and debug | Destructive bulk conversion, repair/reset jobs, browser diagnostics and logging retain their native interfaces. These are not generic MCP execution actions. |
| Reviews, support and licensing | Existing UI handles review prompts, support links and license administration; no license keys or credentials are exposed. |

Bulk byline changes are supported. Profile, box, list, category, definition and Pro mapping writes target one object per call; their native bulk UI operations remain available. Trashing a box or field does not rewrite references, and renaming a field does not migrate profile values.
