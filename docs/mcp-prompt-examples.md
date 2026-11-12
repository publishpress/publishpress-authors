# Authors MCP request examples

Discover identifiers and field types, clarify ambiguous scope, and read back saved changes. Author term IDs, user IDs and layout/preset IDs are not interchangeable.

| Request | Expected behavior |
| --- | --- |
| What can I manage in Authors? | Call `list-features`, then the relevant field discovery. Distinguish profiles, bylines, boxes, lists, categories and settings. |
| Add a guest author called Jane Doe. | Discover profile fields and read guest settings. Create a guest profile only if allowed; clarify whether the user means a profile or a WordPress account. |
| Create an author profile for the existing user Jane. | Resolve the exact WordPress user through authorized Hub lookup, then use `save-author` with that `user_id`. Do not create a second WordPress account or guess an ID. |
| Change Jane's biography and avatar alternative text. | Resolve the exact profile, read its hash and native fields, preview and patch only biography/alternative text, then read back. Respect linked-user and administrator profile locks. |
| Link this guest profile to Jane's account. | Resolve the existing account and call `link-author-user` with the profile's latest hash. Do not change account roles. |
| Automatically match this guest to a user in Pro. | Use `map-guest-author-to-user`; inspect its default preview, then save the verified match explicitly. A missing match does not justify creating an account. |
| Put Jane first and Sam second on the Hello World post. | Resolve the actual post through Hub. Use `get-byline-choices` to resolve existing profile term IDs, read the byline hash and save the ordered pair. Preserve the box and categories. |
| Mark Sam as Reviewer and Jane as Writer on this post. | Discover enabled Author Categories for the post, use their category IDs, then patch the category map. Do not change WordPress post categories. |
| Apply the same author order to these three posts. | Resolve all targets, read each hash, build explicit per-post changes, preview the batch and save. An invalid final target must leave earlier posts unchanged. |
| Use the Centered Author Box on this post. | Discover the post's native box choices and patch its `author_box`, preserving byline order and category assignments. Do not change the global default box. |
| Change the default box for all posts. | Discover native settings/layout choices and patch `layout`. Existing per-post overrides remain intact. |
| Create a centered box with a pink background. | Discover box fields and templates, create a box with the native centered template and the correct background field. Assign it to content only if requested. |
| Make an Author List showing biographies and websites. | Discover list layouts/display fields, create a list preset and return its generated shortcode. Do not silently insert it into unrelated posts. |
| Add search to this list in Pro. | Read the list and its hash, discover active Pro controls and patch the search fields. Free must reject the unsupported control. |
| Add a required Expertise field. | Clarify definition versus one person's value. Creating a definition requires Pro; discover allowed types and save a field. Changing a person's value uses `save-author`. |
| Hide this custom field from REST. | Read its definition and hash, then update its REST visibility. Do not print the hidden value in the confirmation. |
| Turn off the footer Author Box. | Read settings and patch only `append_to_content: false`; do not disable Author Boxes or remove per-post authors. |
| Enable author pages with a grid layout. | Discover page settings, preview and patch the relevant enable/layout fields. Preserve other display settings and use the native rewrite refresh. |
| Disable an integration. | Discover the optional module ID and use `set-feature-enabled`. Core/autoloaded modules cannot be switched off. |

Missing client tools require checking Hub exposure and refreshing the connection. A permission denial must be respected. Automated integration checks verify schemas, permissions and saved values; they do not guarantee that every AI model chooses the same action for every wording.
