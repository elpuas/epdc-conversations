# EPDC Conversations - Plugin Checker Cleanup

Date: 2026-05-31
Branch: codex/epdc-conversations-floating-button

## Summary
Applied targeted fixes based on the local Plugin Check report, limited to plugin-level errors and warnings.

## Files Modified
- `epdc-conversations.php`
- `readme.txt`
- `src/Infrastructure/I18n.php`
- `src/Messaging/MessageParser.php`
- `templates/frontend-button.php`

## Files Added
- `tests/index.php`
- `vendor/index.php`
- `.context/2026-05-31-plugin-checker-cleanup.md`

## Files Removed
- `tests/.gitkeep`
- `vendor/.gitkeep`

## Fixes Applied
- Added a GPL-compatible license and license URI to the plugin header.
- Updated readme metadata to `Tested up to: 7.0` and aligned the declared license.
- Prefixed bootstrap and template-scope variables with `epdc_conversations_`.
- Removed the explicit `load_plugin_textdomain()` call to avoid the discouraged-function warning.
- Sanitized `$_SERVER['REQUEST_URI']` before using it to build `{current_url}`.
- Replaced hidden placeholder files with `index.php` stubs to avoid hidden-file errors.

## Intentionally Not Addressed
- `phpcs.xml.dist` warning, per request.
- `AGENTS.md` warning, treated as local repository metadata rather than plugin runtime code.
