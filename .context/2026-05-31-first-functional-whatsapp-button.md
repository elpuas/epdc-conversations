# EPDC Conversations - First Functional WhatsApp Button

Date: 2026-05-31
Branch: codex/epdc-conversations-floating-button

## Summary
Implemented the first working version of EPDC Conversations with a real floating WhatsApp button, shortcode rendering, dynamic message parsing, validated settings, and lightweight frontend assets.

## Files Modified
- `assets/css/frontend.css`
- `assets/js/frontend.js`
- `epdc-conversations.php`
- `src/Admin/SettingsPage.php`
- `src/Frontend/Renderer.php`
- `src/Infrastructure/Assets.php`
- `src/Infrastructure/Plugin.php`
- `src/Messaging/MessageParser.php`
- `templates/frontend-button.php`

## Files Added
- `includes/autoload.php`
- `src/Infrastructure/Settings.php`
- `.context/2026-05-31-first-functional-whatsapp-button.md`

## Architectural Decisions
- Added a minimal fallback PSR-4 autoloader so the plugin works even when `vendor/autoload.php` is not present yet.
- Introduced one lightweight `Settings` service to centralize defaults and option retrieval without adding extra abstraction layers.
- Kept rendering server-side through the existing `Renderer` service and a PHP template.
- Used the Settings API directly in `SettingsPage` with per-field sanitization instead of a custom settings framework.
- Kept the frontend JS intentionally minimal and non-blocking.

## New Hooks
- `epdc_conversations_variables`
- `epdc_conversations_message`
- `epdc_conversations_button_classes`
- `epdc_conversations_button_args`
- `epdc_conversations_url`

## Remaining Limitations
- Tracking remains a placeholder and does not persist click or conversion data yet.
- The shortcode currently supports the requested `message` override only.
- The plugin does not yet expose Gutenberg block support or page-level visibility overrides.
- No automated test suite or PHPCS run was executed in this pass.

## Next Recommended Steps
1. Implement click/conversion logging with anonymous session identifiers and hashed IP handling.
2. Add block support and page-level conditional visibility controls.
3. Expand shortcode overrides for label, position, and tab behavior if needed.
4. Add PHPUnit and PHPCS workflows so settings, parsing, and URL generation are covered by automated checks.
