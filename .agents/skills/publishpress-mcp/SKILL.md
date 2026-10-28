---
name: publishpress-mcp
description: Implement or extend PublishPress Authors actions through the WordPress Abilities API. Use for profile, byline, settings, box, list, category and Free/Pro field operations, schemas, permissions and integration tests.
---

# PublishPress Authors MCP integration

Hub provides an optional connection through the separately installed WordPress MCP Adapter; it does not bundle the adapter or implement MCP transport. Users connect through a compatible external MCP client. The integration does not require a particular AI provider or integrate with WordPress.com's hosted MCP service.

## Plugin ownership

Register independently of Hub on `wp_abilities_api_init`, outside admin-only bootstrap paths. Guard missing API functions. Register the shared `publishpress` category only if `wp_has_ability_category` is false. Keep `publishpress-authors/{action}` abilities private with `meta.public`, `meta.show_in_rest` and `meta.mcp.public` all false. Never filter registration by the current actor; authentication may happen later.

Keep shared operations in `src/core/Mcp/` and the existing read actions in `src/abilities.php`. Follow Authors' class and Factory/module services; do not introduce Hub's container structure. Preserve PHP 7.2.5 compatibility. Pro consumes this code through packaging; do not edit its vendored Free dependency.

## Implementation map

- `Registry` owns explicit registration and bounded schemas. Empty object schemas omit `properties`, avoiding WordPress validation errors on malformed input when an empty stdClass is used as a property map.
- `Fields` projects native editor definitions and reuses trusted sanitizers. Omit action/promotion controls, expose active-edition choices, reject unknown fields and privileged user metadata. Never accept a sanitizer or callable from tool input.
- `Profiles` reuses native author API callbacks with filtered management, term and linked-user checks. Keep native administrator profile locks and mapping restrictions. Apply per-term checks to legacy reads and filter denied terms from lists. Public profile reads exclude email, hidden REST fields and arbitrary metadata. Account creation and role-changing guest conversions remain outside these actions.
- `Bylines` uses `Utils::set_post_authors` and native category relationships. Read stored terms without legacy fallback profile creation; flush native post caches after writes. Distinguish ordered term IDs from WordPress user IDs and per-post Author Box choices. Validate category enabled state and active Pro post-type scope; native ID lookups do not enforce those list filters. Validate every batch target before saving and compensate applied targets on failure.
- `Settings` uses a non-secret typed whitelist, native module validation and `update_all_module_options`. Patch only supplied fields after whole-form validation. Keep license inputs out of validation to avoid licensing requests during ordinary settings changes. Optional module switching honors autoloaded/core module locks.
- `Objects` uses native box fields and custom-field types with per-object create/edit/publish/delete capabilities. Custom field reads require the filtered dedicated `ppma_manage_custom_fields` capability, not the settings capability. Definition writes additionally require actual Pro activation. Read definitions separately from author values; retain native menu_order. Preserve unrelated fields and metadata. Box shortcodes use bounded parallel shortcode/position arrays and the native sanitization chain; keep CSS, profile-field order and list order separate.
- `Lists` uses native field definitions, shortcode parsing and module storage. Regenerate shortcode arguments from validated fields; do not accept arbitrary executable content as a configuration shortcut.
- `Categories` reuses the native category persistence and deletion services. Normalize serialized targeting data. Keep category management permissions distinct from post-byline assignment, and gate post-type targeting on Pro.
- Pro registers its companion through `publishpress_authors_mcp_register`. Detect the actual edition through `Utils::isAuthorsProActive`, not a fake marker. Native Pro fields and grouping choices come from the loaded module filters.

## Shared implementation standard

- Follow the plugin's native class structure, bootstrap, minimum PHP version and Free/Pro packaging. Keep Hub responsible for connection and exposure; keep plugin operations and tests in the plugin repository.
- Audit every Free/Pro admin screen and relevant editor feature. Maintain a coverage map with discovery, reads, supported writes, permission checks, edition gates and intentional exclusions. Do not declare completeness from an action count alone.
- Expose native screen labels, exact identifiers, field types, choices, defaults and edition requirements through registered action descriptions, schemas and discovery results. Include common user wording and distinguish similarly named settings and scopes.
- Pair native reads with typed create/update/delete operations where supported. Preserve omitted fields and unrelated objects. Reject unknown identifiers, validate all targets before bulk writes, and use optimistic concurrency for existing content edits.
- Reuse native services, sanitizers and object-specific capability checks. Enforce feature and edition gates at execution; an Administrator role or Hub exposure setting never bypasses a denied capability. Keep secrets outside tool inputs and outputs.
- For multi-step writes, test failure between steps and avoid reporting an unchanged failure after partially saving. Provide write previews and bulk operations where the native workflow needs them; document unsupported previews and multi-object operations explicitly.
- Verify translated action metadata and domain errors, native round trips, malformed inputs, stale content, unrelated-data preservation and capability-restricted Administrators. Cover Free/Pro boundaries and distinguish shared-contract fixtures from full Pro distribution tests.
- Verify missing-API compatibility, duplicate registration, private discovery and execution with a released adapter. Keep shared transport tests in Hub and domain tests in each plugin.
- Maintain request examples mapped to expected discovery, ambiguity handling, writes and read-back verification. Documentation describes current behavior and limitations, without development history or unreleased change logs.

## Verification

`tests/Mcp/registration.php` verifies the shared category, private flags and translated labels/descriptions. `tests/Mcp/compatibility.php` checks missing-API loading. `tests/Mcp/smoke.php` requires an isolated `mcp_smoke_` database and a real Free or Pro plugin bootstrap selected by `AUTHORS_MCP_MODE`. It restores module options and deletes its synthetic users, posts, terms and categories. The test loader and runtime packaging are test-environment setup, not plugin code.

Run native round trips, previews, stale revisions, unknown fields/objects, mixed-validity batches, denied capabilities including Administrators, native edition gates, hidden fields and released-adapter projection/execution. Keep request examples in `docs/mcp-prompt-examples.md` and coverage in `docs/mcp-feature-coverage.md`. Do not run mutating tests on a live site or commit credentials.

Skills here guide developers and their coding agents. They are separate from optional end-user instructions for AI clients consuming tools.
