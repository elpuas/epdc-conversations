<?php
/**
 * Analytics data access for admin screens.
 *
 * @package EPDC\Conversations\Admin
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Admin;

final class AnalyticsRepository {
	private const CACHE_GROUP = 'epdc_conversations_analytics';
	private const CACHE_KEY_VERSION = 'epdc_conversations_analytics_cache_version';

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

		$cache_key = 'metrics:' . $this->get_cache_version() . ':' . md5( implode( '|', [ $today_start, $last_seven_days, $last_thirty ] ) );
		$result    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false === $result ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom analytics table query for plugin-owned data.
			$result = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT
						COUNT(*) AS total_clicks,
						SUM(CASE WHEN created_at >= %s THEN 1 ELSE 0 END) AS clicks_today,
						SUM(CASE WHEN created_at >= %s THEN 1 ELSE 0 END) AS clicks_last_7_days,
						SUM(CASE WHEN created_at >= %s THEN 1 ELSE 0 END) AS clicks_last_30_days
					FROM %i
					WHERE event_type = %s',
					$today_start,
					$last_seven_days,
					$last_thirty,
					$table_name,
					'whatsapp_click'
				),
				ARRAY_A
			);

			wp_cache_set( $cache_key, $result, self::CACHE_GROUP, MINUTE_IN_SECONDS );
		}

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

		$cache_key = 'top-pages:' . $this->get_cache_version() . ':' . md5( wp_json_encode( [ $event_type, $start_date, $limit ] ) );
		$results   = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $results && is_array( $results ) ) {
			return $results;
		}

		if ( '' !== $start_date ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom analytics table query for plugin-owned data.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT
						page_url,
						COUNT(*) AS total_clicks,
						MAX(created_at) AS last_interaction
					FROM %i
					WHERE event_type = %s
						AND page_url <> \'\'
						AND created_at >= %s
					GROUP BY page_url
					ORDER BY total_clicks DESC, last_interaction DESC
					LIMIT %d',
					$table_name,
					$event_type,
					$start_date,
					$limit
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom analytics table query for plugin-owned data.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT
						page_url,
						COUNT(*) AS total_clicks,
						MAX(created_at) AS last_interaction
					FROM %i
					WHERE event_type = %s
						AND page_url <> \'\'
					GROUP BY page_url
					ORDER BY total_clicks DESC, last_interaction DESC
					LIMIT %d',
					$table_name,
					$event_type,
					$limit
				),
				ARRAY_A
			);
		}

		if ( is_array( $results ) ) {
			wp_cache_set( $cache_key, $results, self::CACHE_GROUP, MINUTE_IN_SECONDS );
		}

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

		$cache_key = 'recent-events:' . $this->get_cache_version() . ':' . md5( wp_json_encode( [ $event_type, $start_date, $limit ] ) );
		$results   = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $results && is_array( $results ) ) {
			return $results;
		}

		if ( '' !== $start_date ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom analytics table query for plugin-owned data.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT
						created_at,
						event_type,
						page_url,
						device_type,
						utm_source,
						session_id
					FROM %i
					WHERE event_type = %s
						AND created_at >= %s
					ORDER BY created_at DESC, id DESC
					LIMIT %d',
					$table_name,
					$event_type,
					$start_date,
					$limit
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom analytics table query for plugin-owned data.
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT
						created_at,
						event_type,
						page_url,
						device_type,
						utm_source,
						session_id
					FROM %i
					WHERE event_type = %s
					ORDER BY created_at DESC, id DESC
					LIMIT %d',
					$table_name,
					$event_type,
					$limit
				),
				ARRAY_A
			);
		}

		if ( is_array( $results ) ) {
			wp_cache_set( $cache_key, $results, self::CACHE_GROUP, MINUTE_IN_SECONDS );
		}

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Flush analytics query caches after new tracking data is stored.
	 */
	public static function flush_cache(): void {
		update_option( self::CACHE_KEY_VERSION, (string) microtime( true ), false );
	}

	/**
	 * Get the active analytics cache version.
	 */
	private function get_cache_version(): string {
		$cache_version = get_option( self::CACHE_KEY_VERSION, '1' );

		return is_string( $cache_version ) && '' !== $cache_version ? $cache_version : '1';
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
