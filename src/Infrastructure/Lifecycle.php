<?php
/**
 * Activation and deactivation lifecycle hooks.
 *
 * @package EPDC\Conversations\Infrastructure
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Infrastructure;

final class Lifecycle {
	/**
	 * Plugin activation callback.
	 */
	public static function activate(): void {
		self::create_events_table();
	}

	/**
	 * Plugin deactivation callback.
	 */
	public static function deactivate(): void {
		// Reserved for future cleanup routines.
	}

	/**
	 * Create events table.
	 */
	private static function create_events_table(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'epdc_conversation_events';
		$charset_collate = $wpdb->get_charset_collate();

		if ( '' === $charset_collate ) {
			$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
		}

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created_at datetime NOT NULL,
			session_id varchar(36) NOT NULL,
			event_type varchar(50) NOT NULL,
			page_url text NULL,
			referrer_url text NULL,
			utm_source varchar(100) NOT NULL DEFAULT '',
			utm_medium varchar(100) NOT NULL DEFAULT '',
			utm_campaign varchar(100) NOT NULL DEFAULT '',
			device_type varchar(20) NOT NULL DEFAULT 'unknown',
			user_agent varchar(255) NOT NULL DEFAULT '',
			ip_hash varchar(64) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY event_type (event_type),
			KEY created_at (created_at),
			KEY session_id (session_id),
			KEY ip_hash (ip_hash)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
