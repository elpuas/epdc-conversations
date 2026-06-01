# Final Stabilization Pass

Date: 2026-05-31

## Scope

- Cleanup and consistency pass across PHP, JS, templates, metadata, and admin settings UI.
- Lightweight security review of the tracking endpoint, renderer, and admin surfaces.
- Developer-facing documentation pass with a full `README.md` and hook reference.

## Cleanup work completed

- Removed the no-op `I18n` service and class.
- Aligned plugin metadata and tooling with PHP 8.2+.
- Added a stable plugin version constant for asset fallback versioning.
- Consolidated asset version resolution into `Assets::get_asset_version()`.
- Expanded shortcode normalization so inline rendering supports the same practical overrides as the block path.
- Added a final renderer normalization pass after public filters run.
- Improved admin settings field accessibility with IDs and `aria-describedby`.
- Hardened frontend tracking JS to fail quietly if `fetch()` is unavailable or URL parsing fails.
- Updated the Composer PHPCS script to match the plugin's PSR-4 and short-array conventions.

## Security review decisions

- Tracking REST requests now require a valid `wp_rest` nonce.
- REST arguments now use explicit validation callbacks for event types, device type, and URLs.
- Tracking payload normalization now runs both before and after the public payload filter.
- URLs are constrained to `http` and `https` and truncated to a bounded length.
- UTM values are sanitized and length-limited before persistence.
- Block and shortcode phone values are normalized to digits before URL generation.
- Filtered renderer arguments are sanitized again before template output.
- Raw IP addresses continue to be excluded from storage; only SHA-256 hashes are persisted.
- Existing admin capabilities remain `manage_options`.

## README additions

- Added plugin overview, architecture, installation, settings, shortcode, block, tracking, security, hooks, and validation sections.
- Documented all public filters and actions currently exposed by the plugin.
- Documented the rationale behind SSR, shared rendering, privacy handling, and performance decisions.

## Remaining technical debt

- Analytics admin styles remain inline to avoid adding a dedicated admin stylesheet for a small internal screen.
- There are still no automated tests in the repository for renderer behavior or tracking normalization.
- Plugin Check execution depends on a local WordPress runtime and is not guaranteed from the repository alone.

## Future roadmap ideas

- Add unit tests for message parsing, renderer normalization, and tracking payload normalization.
- Add page-level visibility overrides and CTA variants for campaign use cases.
- Add WooCommerce-specific dynamic variables and attribution context in a future integration phase.
