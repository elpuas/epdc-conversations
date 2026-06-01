<?php
/**
 * Analytics data access for admin screens.
 *
 * @package EPDC\Conversations\Admin
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Admin;

final class AnalyticsRepository {
	/**
	 * Fetch dashboard metrics.
	 *
	 * @return array<string, int>
	 */
	public function get_metrics(): array {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'epdc_conversation_events';
		$current_time    = time();
		$today_start     = gmdate( 'Y-m-d 00:00:00', $current_time );
		$last_seven_days = gmdate( 'Y-m-d H:i:s', $current_time - ( 7 * DAY_IN_SECONDS ) );
		$last_thirty     = gmdate( 'Y-m-d H:i:s', $current_time - ( 30 * DAY_IN_SECONDS ) );

		$sql = $wpdb->prepare(
			"SELECT
				COUNT(*) AS total_clicks,
				SUM(CASE WHEN created_at >= %s THEN 1 ELSE 0 END) AS clicks_today,
				SUM(CASE WHEN created_at >= %s THEN 1 ELSE 0 END) AS clicks_last_7_days,
				SUM(CASE WHEN created_at >= %s THEN 1 ELSE 0 END) AS clicks_last_30_days
			FROM {$table_name}
			WHERE event_type = %s",
			$today_start,
			$last_seven_days,
			$last_thirty,
			'whatsapp_click'
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query uses $wpdb->prepare() above.
		$result = $wpdb->get_row( $sql, ARRAY_A );

		$metrics = [
			'total_clicks'        => is_array( $result ) && isset( $result['total_clicks'] ) ? (int) $result['total_clicks'] : 0,
			'clicks_today'        => is_array( $result ) && isset( $result['clicks_today'] ) ? (int) $result['clicks_today'] : 0,
			'clicks_last_7_days'  => is_array( $result ) && isset( $result['clicks_last_7_days'] ) ? (int) $result['clicks_last_7_days'] : 0,
			'clicks_last_30_days' => is_array( $result ) && isset( $result['clicks_last_30_days'] ) ? (int) $result['clicks_last_30_days'] : 0,
		];

		/**
		 * Filter dashboard metrics before rendering.
		 *
		 * @param array<string, int> $metrics Metrics keyed by period.
		 */
		$metrics = apply_filters( 'epdc_conversations_analytics_metrics', $metrics );

		return is_array( $metrics ) ? $metrics : [
			'total_clicks'        => 0,
			'clicks_today'        => 0,
			'clicks_last_7_days'  => 0,
			'clicks_last_30_days' => 0,
		];
	}

	/**
	 * Fetch top converting pages for a time range.
	 *
	 * @param string $range Range key.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_top_pages( string $range ): array {
		global $wpdb;

		$table_name = $wpdb->prefix . 'epdc_conversation_events';
		$args       = [
			'event_type' => 'whatsapp_click',
			'range'      => $range,
			'start_date' => $this->get_start_date_for_range( $range ),
			'limit'      => 10,
		];

		/**
		 * Filter top pages query arguments.
		 *
		 * @param array<string, mixed> $args Query arguments.
		 */
		$args = apply_filters( 'epdc_conversations_top_pages_query_args', $args );

		if ( ! is_array( $args ) ) {
			return [];
		}

		$limit      = isset( $args['limit'] ) ? max( 1, (int) $args['limit'] ) : 10;
		$event_type = isset( $args['event_type'] ) ? sanitize_key( (string) $args['event_type'] ) : 'whatsapp_click';
		$start_date = isset( $args['start_date'] ) ? (string) $args['start_date'] : '';

		if ( '' !== $start_date ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is derived from $wpdb->prefix.
			$sql = $wpdb->prepare(
				"SELECT
					page_url,
					COUNT(*) AS total_clicks,
					MAX(created_at) AS last_interaction
				FROM {$table_name}
				WHERE event_type = %s
					AND page_url <> ''
					AND created_at >= %s
				GROUP BY page_url
				ORDER BY total_clicks DESC, last_interaction DESC
				LIMIT %d",
				$event_type,
				$start_date,
				$limit
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is derived from $wpdb->prefix.
			$sql = $wpdb->prepare(
				"SELECT
					page_url,
					COUNT(*) AS total_clicks,
					MAX(created_at) AS last_interaction
				FROM {$table_name}
				WHERE event_type = %s
					AND page_url <> ''
				GROUP BY page_url
				ORDER BY total_clicks DESC, last_interaction DESC
				LIMIT %d",
				$event_type,
				$limit
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query uses $wpdb->prepare() above.
		$results = $wpdb->get_results( $sql, ARRAY_A );

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Fetch recent tracking events for a time range.
	 *
	 * @param string $range Range key.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_recent_events( string $range ): array {
		global $wpdb;

		$table_name = $wpdb->prefix . 'epdc_conversation_events';
		$args       = [
			'event_type' => 'whatsapp_click',
			'range'      => $range,
			'start_date' => $this->get_start_date_for_range( $range ),
			'limit'      => 25,
		];

		/**
		 * Filter recent events query arguments.
		 *
		 * @param array<string, mixed> $args Query arguments.
		 */
		$args = apply_filters( 'epdc_conversations_recent_events_query_args', $args );

		if ( ! is_array( $args ) ) {
			return [];
		}

		$limit      = isset( $args['limit'] ) ? max( 1, (int) $args['limit'] ) : 25;
		$event_type = isset( $args['event_type'] ) ? sanitize_key( (string) $args['event_type'] ) : 'whatsapp_click';
		$start_date = isset( $args['start_date'] ) ? (string) $args['start_date'] : '';

		if ( '' !== $start_date ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is derived from $wpdb->prefix.
			$sql = $wpdb->prepare(
				"SELECT
					created_at,
					event_type,
					page_url,
					device_type,
					utm_source,
					session_id
				FROM {$table_name}
				WHERE event_type = %s
					AND created_at >= %s
				ORDER BY created_at DESC, id DESC
				LIMIT %d",
				$event_type,
				$start_date,
				$limit
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is derived from $wpdb->prefix.
			$sql = $wpdb->prepare(
				"SELECT
					created_at,
					event_type,
					page_url,
					device_type,
					utm_source,
					session_id
				FROM {$table_name}
				WHERE event_type = %s
				ORDER BY created_at DESC, id DESC
				LIMIT %d",
				$event_type,
				$limit
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query uses $wpdb->prepare() above.
		$results = $wpdb->get_results( $sql, ARRAY_A );

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Resolve a UTC start date for a supported range.
	 */
	private function get_start_date_for_range( string $range ): string {
		$current_time = time();

		return match ( $range ) {
			'today' => gmdate( 'Y-m-d 00:00:00', $current_time ),
			'7days' => gmdate( 'Y-m-d H:i:s', $current_time - ( 7 * DAY_IN_SECONDS ) ),
			'30days' => gmdate( 'Y-m-d H:i:s', $current_time - ( 30 * DAY_IN_SECONDS ) ),
			default => '',
		};
	}
}
