# First Gutenberg Conversations Block

Date: 2026-05-31
Branch: codex/feature-gutenberg-conversations-block

## Summary
Implemented the first dynamic Gutenberg block `epdc/conversations` with server-side rendering and minimal editor-side JavaScript.

## Files Created
- `src/Blocks/ConversationsBlock.php`
- `blocks/conversations/block.json`
- `blocks/conversations/index.js`
- `.context/2026-05-31-first-gutenberg-conversations-block.md`

## Files Modified
- `src/Infrastructure/Plugin.php`
- `src/Frontend/Renderer.php`
- `templates/frontend-button.php`
- `assets/css/frontend.css`

## Architectural Decisions
- Added a dedicated block registration service (`ConversationsBlock`) and registered it through the existing plugin service container/bootstrap flow.
- Kept server-side rendering in PHP with a `render_callback` that delegates directly to `Renderer::render_block()`.
- Reused the existing renderer as the single source of truth for message parsing, WhatsApp URL generation, accessibility label generation, class assembly, and tracking placeholder hook calls.
- Extended renderer override support to include block attributes (`message`, `label`, `phoneNumber`, `variant`, `showIcon`, `newTab`) without duplicating logic.
- Kept editor experience lightweight: one inspector panel with focused controls and an SSR preview via `wp.serverSideRender`.

## Shared Renderer Usage
- Block render callback sanitizes incoming attributes and passes them to `Renderer::render_block()`.
- `Renderer::render_block()` forwards to the existing internal render pipeline (`render_button` / `build_button_args` / template) used by shortcode/floating instances.
- Template remains shared and now supports optional icon rendering and tracking-ready data attributes (`data-epdc-conversations-source`, `data-epdc-conversations-variant`).

## Remaining Limitations
- Block editor script is plain unbundled JS and currently not passed through a build step.
- Translations for editor strings are in place but no script translation JSON pipeline is configured yet.
- Variant styles are intentionally minimal and leverage the existing base CSS.

## Next Recommended Steps
1. Add automated integration tests for block render output (attribute defaults, sanitization, and variant class behavior).
2. Add block documentation snippets to `readme.txt` including usage and attributes.
3. Add future tracking payload mappings using the new source/variant data attributes.
