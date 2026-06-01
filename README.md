# EPDC Conversations

EPDC Conversations is an internal WordPress plugin focused on conversion-oriented WhatsApp calls to action. It provides a shared floating CTA, reusable inline/button renderers, anonymous click tracking, and a small analytics surface for EPDC-managed sites.

## Overview

### Purpose

- Increase lead generation through WhatsApp CTAs.
- Keep rendering server-side first and JavaScript minimal.
- Preserve privacy by avoiding direct personal data storage in the tracking layer.

### Features

- Global floating WhatsApp button.
- Inline rendering through shortcode and Gutenberg block.
- Dynamic message variables such as `{site_name}` and `{current_url}`.
- Anonymous tracking for WhatsApp clicks.
- Optional GA4 event forwarding when `gtag()` is present.
- Lightweight admin analytics page.
- Public hooks for rendering, messaging, analytics, and tracking extensions.

### Architecture

- `epdc-conversations.php`: plugin bootstrap and lifecycle hooks.
- `src/Infrastructure`: plugin wiring, settings access, lifecycle, asset registration.
- `src/Frontend`: shared server-side renderer for shortcode, block, and floating button output.
- `src/Messaging`: message variable parsing.
- `src/Tracking`: REST endpoint, session cookie handling, tracking persistence.
- `src/Admin`: settings page and analytics queries/screens.
- `blocks/conversations`: Gutenberg block registration script and metadata.
- `templates/frontend-button.php`: shared CTA template.
- `assets/css/frontend.css` and `assets/js/frontend.js`: minimal frontend runtime.

### Requirements

- WordPress 6.6+
- PHP 8.2+
- Composer for local development

## Installation

### Composer install

```bash
composer install
```

### WordPress installation

1. Copy the plugin into `wp-content/plugins/epdc-conversations`.
2. Install dependencies with Composer if the plugin was checked out from source.

### Activation

1. Activate **EPDC Conversations** from the WordPress admin.
2. Activation creates the `{$wpdb->prefix}epdc_conversation_events` table used by tracking and analytics.

## Settings

All settings are stored in the `epdc_conversations_options` option.

### Supported settings

- `phone_number`: Default WhatsApp number. Digits only are persisted.
- `default_message`: Base CTA message template.
- `enable_floating_button`: Enables or disables the global floating CTA.
- `show_on_mobile`: Controls mobile visibility for the floating CTA.
- `show_on_desktop`: Controls desktop visibility for the floating CTA.
- `button_position`: `bottom-right` or `bottom-left`.
- `button_label`: Visible button text.
- `open_in_new_tab`: Opens the WhatsApp URL in a new tab when enabled.
- `enable_ga_tracking`: Forwards CTA clicks to GA4 when `window.gtag` exists.

### Notes

- Settings are sanitized through the Settings API.
- Button output still passes through the shared renderer, so shortcode and block overrides inherit the same escaping and URL generation rules.

## Shortcode Usage

### Basic examples

```text
[epdc_conversations]
[epdc_conversations message="Hello"]
[epdc_conversations label="Chat with sales" variant="compact"]
[epdc_conversations phone_number="50612345678" new_tab="yes" show_icon="no"]
```

### Supported shortcode attributes

- `message`: Message template override.
- `label`: Visible text override.
- `phone_number`: WhatsApp number override. Non-digits are stripped before rendering.
- `variant`: `default`, `inline`, or `compact`.
- `show_icon`: Truthy/falsey string flag. `no`, `false`, `off`, and `0` disable the icon.
- `new_tab`: Truthy/falsey string flag. `yes`, `true`, `on`, and `1` enable a new tab.

## Gutenberg Block

### Block name

- `epdc/conversations`

### Supported attributes

- `message`
- `label`
- `phoneNumber`
- `variant`
- `showIcon`
- `newTab`

### Usage examples

- Insert the **EPDC Conversations** block and use plugin defaults.
- Override the label or message for a single page.
- Use the `compact` variant inside landing page content.

### Dynamic rendering behavior

- The block is server-side rendered.
- Block attributes are sanitized in PHP before the shared renderer is called.
- Output is identical to the shortcode path because both use `EPDC\Conversations\Frontend\Renderer`.

## Dynamic Variables

### Built-in variables

- `{site_name}`
- `{post_title}`
- `{post_url}`
- `{current_url}`

### Custom variable extension

Use the `epdc_conversations_variables` filter to register additional variables.

```php
add_filter(
	'epdc_conversations_variables',
	function ( array $variables, array $context ): array {
		$variables['{campaign_name}'] = 'Summer Promo';
		return $variables;
	},
	10,
	2
);
```

## Tracking System

### Architecture

- Frontend clicks are captured by `assets/js/frontend.js`.
- The script sends a POST request to `epdc-conversations/v1/track`.
- `EPDC\Conversations\Tracking\TrackingService` validates, sanitizes, and stores the event.
- Analytics pages read from the custom events table through `AnalyticsRepository`.

### REST endpoint

- Namespace: `epdc-conversations/v1`
- Route: `/track`
- Method: `POST`

### Accepted payload

- `event_type`
- `page_url`
- `referrer_url`
- `device_type`
- `utm_source`
- `utm_medium`
- `utm_campaign`

### Database schema

Table: `{$wpdb->prefix}epdc_conversation_events`

- `id`
- `created_at`
- `session_id`
- `event_type`
- `page_url`
- `referrer_url`
- `utm_source`
- `utm_medium`
- `utm_campaign`
- `device_type`
- `user_agent`
- `ip_hash`

### Session tracking

- Anonymous visitors receive a UUIDv4 cookie named `epdc_conversations_session`.
- Cookie flags: `HttpOnly`, `SameSite=Lax`, and `Secure` on SSL requests.

### GA4 integration behavior

- GA forwarding is optional and settings-controlled.
- No GA request is attempted unless `window.gtag` exists.
- Default event name: `epdc_whatsapp_click`

## Security Review Notes

### Decisions

- Tracking requests require a WordPress REST nonce (`wp_rest`) even though the endpoint is public-facing.
- REST arguments are sanitized and validated before use.
- UTM values and URLs are length-limited before persistence.
- URLs are restricted to `http` and `https`.
- All analytics queries use `$wpdb->prepare()` or `$wpdb->insert()` format arrays.
- Output is escaped in templates and admin screens.
- The shared renderer normalizes filtered arguments after hooks run, preventing unsafe values from being echoed directly.
- Admin screens require `manage_options`.
- Raw IP addresses are never stored. Only `hash( 'sha256', $ip )` is persisted.
- The session identifier shown in analytics is truncated for privacy.

### Graceful failure behavior

- Tracking failures do not block link navigation.
- Invalid tracking requests return `WP_Error` responses with explicit HTTP status codes.
- If asset files are missing, asset registration falls back to the plugin version constant.

## Hooks Reference

### Filters

#### `epdc_conversations_variables`

- Type: filter
- Arguments: `array $variables`, `array $context`
- Purpose: register or override dynamic message variables.

#### `epdc_conversations_message`

- Type: filter
- Arguments: `string $message`, `array $context`
- Purpose: modify the final parsed WhatsApp message before URL generation.

#### `epdc_conversations_button_classes`

- Type: filter
- Arguments: `array $classes`, `array $context`
- Purpose: modify the renderer CSS classes.

#### `epdc_conversations_button_args`

- Type: filter
- Arguments: `array $args`, `array $context`
- Purpose: change renderer arguments before final normalization.

#### `epdc_conversations_url`

- Type: filter
- Arguments: `string $url`, `array $args`
- Purpose: override the final WhatsApp URL.

#### `epdc_conversations_should_render_floating_button`

- Type: filter
- Arguments: `bool $should_render`
- Purpose: disable or force the global floating CTA.

#### `epdc_conversations_tracking_payload`

- Type: filter
- Arguments: `array $payload`
- Purpose: modify the sanitized tracking payload before insertion.

#### `epdc_conversations_event_insertion_data`

- Type: filter
- Arguments: `array $data`, `array $payload`
- Purpose: adjust the final row written to the database.

#### `epdc_conversations_tracking_event_types`

- Type: filter
- Arguments: `array $event_types`
- Purpose: extend the allow-list of trackable event names.

#### `epdc_conversations_ga_event_payload`

- Type: filter
- Arguments: `array $ga_event_payload`
- Purpose: change the GA payload localized to the frontend script.

#### `epdc_conversations_ga_event_name`

- Type: filter
- Arguments: `string $event_name`
- Purpose: override the frontend GA event name.

#### `epdc_conversations_analytics_metrics`

- Type: filter
- Arguments: `array $metrics`
- Purpose: modify dashboard metric totals before rendering.

#### `epdc_conversations_top_pages_query_args`

- Type: filter
- Arguments: `array $args`
- Purpose: adjust the top-pages analytics query arguments.

#### `epdc_conversations_recent_events_query_args`

- Type: filter
- Arguments: `array $args`
- Purpose: adjust the recent-events analytics query arguments.

### Actions

#### `epdc_conversations_event_tracked`

- Type: action
- Arguments: `int $event_id`, `array $payload`
- Purpose: react after a tracking row is stored successfully.

#### `epdc_conversations_analytics_page_sections`

- Type: action
- Arguments: `string $selected_range`, `array $metrics`, `array $top_pages`, `array $recent_events`
- Purpose: append extra sections to the analytics screen.

### Hook examples

```php
add_filter(
	'epdc_conversations_message',
	function ( string $message, array $context ): string {
		if ( ! empty( $context['is_floating'] ) ) {
			return $message . ' - floating CTA';
		}

		return $message;
	},
	10,
	2
);

add_action(
	'epdc_conversations_event_tracked',
	function ( int $event_id, array $payload ): void {
		do_action( 'my_analytics_bridge_track_whatsapp_click', $event_id, $payload );
	},
	10,
	2
);
```

## Developer Notes

### Architectural decisions

- The plugin keeps a small service-based architecture without introducing container complexity.
- The shared renderer avoids divergent output paths between the floating button, shortcode, and block.

### Shared renderer approach

- `Renderer::render_button()` is the single HTML generation path.
- Block and shortcode inputs are normalized before they reach the renderer.
- Filtered renderer args are normalized again before template output as a final safety pass.

### SSR behavior

- The floating CTA, shortcode, and block all render on the server.
- JavaScript is only responsible for click tracking and optional GA forwarding.

### Accessibility philosophy

- CTA markup includes explicit `aria-label` text.
- Focus-visible styles are present in the frontend stylesheet.
- Motion effects are reduced when `prefers-reduced-motion` is enabled.
- Admin settings controls now include stable IDs and description associations.

### Performance philosophy

- Rendering stays server-side.
- Frontend JavaScript is small and event-driven.
- Shared assets are only enqueued when a button instance is actually rendered.
- Asset versions use file modification times for cache busting.

## Validation

### PHPCS

Run:

```bash
composer phpcs
```

### PHP syntax

Run:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

### Plugin Check

Recommended:

1. Install the WordPress Plugin Check plugin in a local development site.
2. Run the EPDC Conversations plugin through the standard Plugin Check scan.

### Remaining warnings and intentional deviations

- The Composer PHPCS script excludes `WordPress.Files.FileName` because the plugin intentionally uses PSR-4 class filenames instead of WordPress `class-*.php` naming.
- The Composer PHPCS script excludes `Universal.Arrays.DisallowShortArraySyntax` because short array syntax is a project convention.
- The Composer PHPCS script excludes `WordPress.DB.PreparedSQL` because the custom table name derived from `$wpdb->prefix` produces false positives in otherwise prepared queries.
- Plugin Check is an external WordPress runtime review and may not be available from this repository alone.
- The analytics admin page still uses inline CSS for a very small, screen-specific layout. This is intentional to avoid introducing another admin-only asset for a narrow internal screen.
