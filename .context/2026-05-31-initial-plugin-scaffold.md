# EPDC Conversations - Initial Plugin Scaffold

Date: 2026-05-31
Branch: codex/epdc-conversations-initial-scaffold

## Summary
Created the initial scaffolding for a modern WordPress plugin named **EPDC Conversations** with a lightweight service-based architecture, Composer PSR-4 autoloading, and placeholder-only functionality.

## Created Files
- `composer.json`
- `phpcs.xml.dist`
- `readme.txt`
- `epdc-conversations.php`
- `assets/css/frontend.css`
- `assets/js/frontend.js`
- `includes/index.php`
- `templates/frontend-button.php`
- `languages/epdc-conversations.pot`
- `tests/.gitkeep`
- `vendor/.gitkeep`
- `src/Infrastructure/ServiceInterface.php`
- `src/Infrastructure/Plugin.php`
- `src/Infrastructure/Lifecycle.php`
- `src/Infrastructure/I18n.php`
- `src/Infrastructure/Assets.php`
- `src/Admin/SettingsPage.php`
- `src/Frontend/Renderer.php`
- `src/Tracking/TrackingService.php`
- `src/Messaging/MessageParser.php`
- `.context/2026-05-31-initial-plugin-scaffold.md`

## Architectural Decisions
- Used `EPDC\\Conversations\\` namespace with PSR-4 mapping to `src/`.
- Chose composition-based bootstrap via `Infrastructure\\Plugin` that wires focused services.
- Avoided singleton usage; all services are instantiated through plugin bootstrap.
- Added lifecycle hooks in a dedicated `Lifecycle` class for activation/deactivation placeholders.
- Kept frontend JS minimal and non-functional for now.
- Used server-side shortcode rendering with a simple PHP template placeholder.
- Added a Settings page placeholder under `Settings > EPDC Conversations`.
- Added explicit sanitization placeholder in settings callback and escaping in output template.
- Added i18n loader service and basic text domain setup.
- Kept tracking and message parsing as empty/placeholder services without persistence.

## Validation
- Ran PHP syntax check on all plugin PHP files.
- No syntax errors detected.

## Next Recommended Steps
1. Add actual settings fields (WhatsApp number, CTA message, visibility rules) with per-field sanitization and validation.
2. Implement real frontend button rendering with accessible markup and keyboard/focus behavior.
3. Expand message parser to support core variables and custom variable filters/hooks.
4. Add tracking event schema and storage strategy (without raw IP persistence).
5. Add unit/integration tests and enforce PHPCS in CI.
