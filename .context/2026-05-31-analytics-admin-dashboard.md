# EPDC Conversations Analytics Admin Dashboard

## Dashboard Architecture
- Added a new admin analytics screen at `EPDC Conversations > Analytics`.
- Kept the implementation lightweight with two focused classes:
  - `AnalyticsPage` for menu registration, filter handling, and native WordPress admin rendering.
  - `AnalyticsRepository` for direct `$wpdb` reads against the existing tracking table.
- Reused the existing `epdc_conversation_events` table and current tracking flow. No tracking schema changes or frontend analytics changes were introduced.
- Updated the existing settings page registration so `EPDC Conversations` is now a top-level admin menu, with `Settings` and `Analytics` as subpages.

## Queries Added
- Metrics query:
  - Single aggregate query for `whatsapp_click` events.
  - Returns total clicks, clicks today, clicks in the last 7 days, and clicks in the last 30 days.
- Top pages query:
  - Groups by `page_url`.
  - Returns top 10 converting pages ordered by click count, then latest interaction date.
  - Applies the selected lightweight range filter.
- Recent events query:
  - Returns the latest 25 `whatsapp_click` rows.
  - Selects only the fields needed by the admin UI: `created_at`, `event_type`, `page_url`, `device_type`, `utm_source`, `session_id`.
  - Applies the selected lightweight range filter.

## Performance Considerations
- All analytics reads use direct `$wpdb` queries with prepared statements.
- Queries only select the columns required by each section.
- Metrics use one aggregate query instead of multiple separate count queries.
- Range filters constrain table queries to reduce row scans where possible.
- Existing indexes on `event_type` and `created_at` help the filtered analytics reads.
- No charting libraries, React bundles, or extra admin assets were added.

## Hooks Added
- `apply_filters( 'epdc_conversations_analytics_metrics', $metrics )`
- `apply_filters( 'epdc_conversations_top_pages_query_args', $args )`
- `apply_filters( 'epdc_conversations_recent_events_query_args', $args )`
- `do_action( 'epdc_conversations_analytics_page_sections', $selected_range, $metrics, $top_pages, $recent_events )`

## Privacy Notes
- The analytics UI does not expose raw IP data.
- Session identifiers are truncated before display.
- All admin output is escaped at render time.

## Remaining Limitations
- No charts or visual trend reporting yet.
- No advanced filtering by event type, UTM campaign, page pattern, or device beyond the initial date ranges.
- Top pages currently groups by the raw stored `page_url`, so equivalent URLs with different query strings will appear separately.
- No pagination yet on recent events because the initial scope is fixed to the latest 25 rows.

## Next Recommended Steps
1. Add URL normalization rules for analytics grouping where query-string fragmentation becomes a problem.
2. Add optional pagination for recent events once event volume grows.
3. Add lightweight UTM and device summary sections using the same repository pattern.
4. Introduce automated tests around repository query arguments and admin range handling.
