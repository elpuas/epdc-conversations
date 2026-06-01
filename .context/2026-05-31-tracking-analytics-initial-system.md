# EPDC Conversations Tracking + Analytics (Initial System)

## Database Schema
- Added activation routine in `Lifecycle::activate()` to create table `{$wpdb->prefix}epdc_conversation_events` via `dbDelta()`.
- Charset/collation uses `$wpdb->get_charset_collate()` (utf8mb4-compatible), with fallback to `utf8mb4_unicode_ci`.
- Columns:
  - `id` bigint unsigned primary key
  - `created_at` datetime
  - `session_id` varchar(36)
  - `event_type` varchar(50)
  - `page_url` text
  - `referrer_url` text
  - `utm_source` varchar(100)
  - `utm_medium` varchar(100)
  - `utm_campaign` varchar(100)
  - `device_type` varchar(20)
  - `user_agent` varchar(255)
  - `ip_hash` varchar(64)
- Added indexes on `event_type`, `created_at`, `session_id`, and `ip_hash`.
- Raw IPs are never stored. `ip_hash` is stored as `hash( 'sha256', $ip )`.

## REST Endpoints
- Added endpoint: `POST /wp-json/epdc-conversations/v1/track`
- Namespace: `epdc-conversations/v1`
- Route: `/track`
- Validation/sanitization:
  - `event_type` is required and checked against allowed events.
  - URL fields use `esc_url_raw`.
  - Device type normalized to: `mobile`, `desktop`, `tablet`, `unknown`.
  - UTM fields sanitized via `sanitize_text_field`.
- Error responses:
  - `400` for invalid event type/payload
  - `500` for insertion failures
- Success response:
  - `201` with `{ success: true, event_id: <id> }`

## Tracking Architecture
- Added `TrackingService` as registered plugin service.
- Session tracking:
  - Cookie name: `epdc_conversations_session`
  - TTL: 30 days
  - ID format: UUID-like using `wp_generate_uuid4()`
  - Cookie flags: `secure` (when SSL), `httponly`, `samesite=Lax`
- Unified click tracking:
  - Floating button, shortcode render, and Gutenberg block all render the same CTA template.
  - CTA links now include `data-epdc-conversations-event="whatsapp_click"`.
  - Frontend script listens to click events and sends payload asynchronously to REST endpoint using `fetch(..., { keepalive: true })`.

## GA4 Integration Behavior
- Added setting: `enable_ga_tracking` (admin settings page + sanitization + defaults).
- Frontend receives localized tracking config from PHP (`restUrl`, nonce, GA flags/data).
- GA forwarding only runs when:
  - setting is enabled, and
  - `window.gtag` exists.
- Default event:
  - `gtag('event', 'epdc_whatsapp_click', { event_category: 'EPDC Conversations', event_label: window.location.pathname })`
- Fails gracefully if GA is absent.

## Hooks Added
- `apply_filters( 'epdc_conversations_tracking_payload', $payload )`
- `apply_filters( 'epdc_conversations_tracking_event_types', $event_types )`
- `apply_filters( 'epdc_conversations_event_insertion_data', $data, $payload )`
- `do_action( 'epdc_conversations_event_tracked', $event_id, $payload )`
- `apply_filters( 'epdc_conversations_ga_event_payload', $ga_event_payload )`
- `apply_filters( 'epdc_conversations_ga_event_name', 'epdc_whatsapp_click' )`

## Security Considerations
- No raw IP persistence.
- Session identifiers are anonymous and random.
- Inputs are validated/sanitized server-side before database write.
- Payload scope intentionally minimal for performance/privacy balance.
- Non-blocking frontend delivery avoids UX regressions on navigation.

## Remaining Limitations
- Endpoint currently allows public posting (intended for frontend events); no anti-abuse rate-limiting yet.
- Device detection is user-agent heuristic based.
- No analytics dashboard/UI yet for stored events.
- No retention policy cleanup job yet.

## Next Recommended Steps
1. Add basic anti-abuse controls (light throttling per session/IP hash).
2. Add event retention/cleanup policy with scheduled task.
3. Add admin analytics summary (daily clicks, top pages, UTM breakdown).
4. Add unit/integration tests for REST validation and insertion.
