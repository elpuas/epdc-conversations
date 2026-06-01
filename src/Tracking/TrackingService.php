<?php
/**
 * Tracking service.
 *
 * @package EPDC\Conversations\Tracking
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Tracking;

use EPDC\Conversations\Infrastructure\ServiceInterface;

final class TrackingService implements ServiceInterface {
	private const COOKIE_NAME       = 'epdc_conversations_session';
	private const COOKIE_EXPIRATION = 2592000;
	private const MAX_URL_LENGTH    = 2048;
	private const MAX_UTM_LENGTH    = 100;
	private const REST_NAMESPACE    = 'epdc-conversations/v1';
	private const REST_ROUTE        = '/track';

	public function register(): void {
		add_action( 'init', [ $this, 'ensure_session_cookie' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Ensure the anonymous session cookie exists.
	 */
	public function ensure_session_cookie(): void {
		if ( is_admin() || wp_doing_cron() || wp_doing_ajax() || wp_is_json_request() ) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		$session_id = $this->get_session_id();

		if ( null !== $session_id ) {
			return;
		}

		$session_id = $this->generate_session_id();

		setcookie(
			self::COOKIE_NAME,
			$session_id,
			[
				'expires'  => time() + self::COOKIE_EXPIRATION,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);

		$_COOKIE[ self::COOKIE_NAME ] = $session_id;
	}

	/**
	 * Register tracking REST routes.
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_track_request' ],
				'permission_callback' => [ $this, 'validate_track_request_permissions' ],
				'args'                => [
					'event_type'   => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => [ $this, 'validate_event_type' ],
					],
					'page_url'     => [
						'type'              => 'string',
						'sanitize_callback' => [ $this, 'sanitize_url' ],
						'validate_callback' => [ $this, 'validate_url' ],
					],
					'referrer_url' => [
						'type'              => 'string',
						'sanitize_callback' => [ $this, 'sanitize_url' ],
						'validate_callback' => [ $this, 'validate_url' ],
					],
					'device_type'  => [
						'type'              => 'string',
						'sanitize_callback' => [ $this, 'sanitize_device_type' ],
						'validate_callback' => [ $this, 'validate_device_type' ],
					],
					'utm_source'   => [
						'type'              => 'string',
						'sanitize_callback' => [ $this, 'sanitize_utm_value' ],
					],
					'utm_medium'   => [
						'type'              => 'string',
						'sanitize_callback' => [ $this, 'sanitize_utm_value' ],
					],
					'utm_campaign' => [
						'type'              => 'string',
						'sanitize_callback' => [ $this, 'sanitize_utm_value' ],
					],
				],
			]
		);
	}

	/**
	 * Handle tracking requests.
	 */
	public function handle_track_request( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$payload = $this->build_tracking_payload( $request );

		/**
		 * Filter the sanitized tracking payload before insertion.
		 *
		 * @param array<string, string> $payload Tracking payload.
		 */
		$payload = apply_filters( 'epdc_conversations_tracking_payload', $payload );

		if ( ! is_array( $payload ) ) {
			return new \WP_Error(
				'epdc_conversations_invalid_payload',
				esc_html__( 'Tracking payload is invalid.', 'epdc-conversations' ),
				[ 'status' => 400 ]
			);
		}

		$payload = $this->normalize_tracking_payload( $payload );

		if ( ! $this->validate_event_type( $payload['event_type'] ) ) {
			return new \WP_Error(
				'epdc_conversations_invalid_event_type',
				esc_html__( 'Event type is not allowed.', 'epdc-conversations' ),
				[ 'status' => 400 ]
			);
		}

		$event_id = $this->insert_event( $payload );

		if ( $event_id <= 0 ) {
			return new \WP_Error(
				'epdc_conversations_track_failed',
				esc_html__( 'Could not store tracking event.', 'epdc-conversations' ),
				[ 'status' => 500 ]
			);
		}

		/**
		 * Fires after a tracking event is stored.
		 *
		 * @param int                  $event_id Tracked event ID.
		 * @param array<string, mixed> $payload  Tracking payload.
		 */
		do_action( 'epdc_conversations_event_tracked', $event_id, $payload );

		return new \WP_REST_Response(
			[
				'success'  => true,
				'event_id' => $event_id,
			],
			201
		);
	}

	/**
	 * Validate permissions for public tracking requests.
	 */
	public function validate_track_request_permissions( \WP_REST_Request $request ): bool|\WP_Error {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( ! is_string( $nonce ) || '' === $nonce ) {
			$nonce = (string) $request->get_param( '_wpnonce' );
		}

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error(
				'epdc_conversations_invalid_nonce',
				esc_html__( 'The tracking request could not be verified.', 'epdc-conversations' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Insert event in local database.
	 *
	 * @param array<string, mixed> $payload Tracking payload.
	 */
	public function insert_event( array $payload ): int {
		global $wpdb;

		$table_name = $wpdb->prefix . 'epdc_conversation_events';
		$ip_address = $this->get_client_ip();
		$session_id = $this->get_session_id();

		$data = [
			'created_at'   => current_time( 'mysql', true ),
			'session_id'   => $session_id ?? '',
			'event_type'   => sanitize_key( (string) ( $payload['event_type'] ?? '' ) ),
			'page_url'     => $this->sanitize_url( (string) ( $payload['page_url'] ?? '' ) ),
			'referrer_url' => $this->sanitize_url( (string) ( $payload['referrer_url'] ?? '' ) ),
			'utm_source'   => $this->sanitize_utm_value( $payload['utm_source'] ?? '' ),
			'utm_medium'   => $this->sanitize_utm_value( $payload['utm_medium'] ?? '' ),
			'utm_campaign' => $this->sanitize_utm_value( $payload['utm_campaign'] ?? '' ),
			'device_type'  => $this->sanitize_device_type( (string) ( $payload['device_type'] ?? '' ) ),
			'user_agent'   => substr( sanitize_text_field( wp_unslash( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) ), 0, 255 ),
			'ip_hash'      => '' !== $ip_address ? hash( 'sha256', $ip_address ) : '',
		];

		/**
		 * Filter event insertion data before writing to the database.
		 *
		 * @param array<string, string> $data    Event insertion data.
		 * @param array<string, mixed>  $payload Tracking payload.
		 */
		$data = apply_filters( 'epdc_conversations_event_insertion_data', $data, $payload );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom analytics table insert for plugin-owned data.
		$inserted = $wpdb->insert(
			$table_name,
			$data,
			[
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			]
		);

		if ( false === $inserted ) {
			return 0;
		}

		if ( class_exists( '\EPDC\Conversations\Admin\AnalyticsRepository' ) && method_exists( '\EPDC\Conversations\Admin\AnalyticsRepository', 'flush_cache' ) ) {
			\EPDC\Conversations\Admin\AnalyticsRepository::flush_cache();
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Build frontend tracking configuration.
	 *
	 * @param bool $ga_enabled Whether GA forwarding is enabled.
	 * @return array<string, mixed>
	 */
	public function get_frontend_config( bool $ga_enabled ): array {
		$ga_event_payload = [
			'event_category' => 'EPDC Conversations',
			'event_label'    => '{{pathname}}',
		];

		/**
		 * Filter GA event payload defaults passed to frontend script.
		 *
		 * @param array<string, string> $ga_event_payload GA event payload.
		 */
		$ga_event_payload = apply_filters( 'epdc_conversations_ga_event_payload', $ga_event_payload );

		return [
			'restUrl'      => esc_url_raw( rest_url( self::REST_NAMESPACE . self::REST_ROUTE ) ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'gaEnabled'    => $ga_enabled,
			'gaEventName'  => (string) apply_filters( 'epdc_conversations_ga_event_name', 'epdc_whatsapp_click' ),
			'gaEventData'  => is_array( $ga_event_payload ) ? $ga_event_payload : [],
			'cookieName'   => self::COOKIE_NAME,
			'eventTypes'   => $this->get_allowed_event_types(),
		];
	}

	/**
	 * Get allowed tracking event types.
	 *
	 * @return string[]
	 */
	private function get_allowed_event_types(): array {
		$event_types = [ 'whatsapp_click' ];

		/**
		 * Filter supported tracking event types.
		 *
		 * @param string[] $event_types Allowed tracking event types.
		 */
		$event_types = apply_filters( 'epdc_conversations_tracking_event_types', $event_types );

		if ( ! is_array( $event_types ) || [] === $event_types ) {
			return [ 'whatsapp_click' ];
		}

		$sanitized_event_types = [];

		foreach ( $event_types as $event_type ) {
			$sanitized_event_type = sanitize_key( (string) $event_type );

			if ( '' !== $sanitized_event_type ) {
				$sanitized_event_types[] = $sanitized_event_type;
			}
		}

		return array_values( array_unique( $sanitized_event_types ) );
	}

	/**
	 * Sanitize URL values.
	 */
	public function sanitize_url( mixed $url ): string {
		$url = is_string( $url ) ? $url : '';

		if ( '' === $url ) {
			return '';
		}

		$url = substr( $url, 0, self::MAX_URL_LENGTH );

		return esc_url_raw( $url, [ 'http', 'https' ] );
	}

	/**
	 * Sanitize tracked device type.
	 */
	public function sanitize_device_type( mixed $device_type ): string {
		$device_type = sanitize_key( is_string( $device_type ) ? $device_type : '' );

		if ( in_array( $device_type, [ 'mobile', 'desktop', 'tablet', 'unknown' ], true ) ) {
			return $device_type;
		}

		return 'unknown';
	}

	/**
	 * Sanitize a UTM value before storage.
	 */
	public function sanitize_utm_value( mixed $value ): string {
		$value = sanitize_text_field( is_string( $value ) ? $value : '' );

		if ( '' === $value ) {
			return '';
		}

		return substr( $value, 0, self::MAX_UTM_LENGTH );
	}

	/**
	 * Validate an allowed event type.
	 */
	public function validate_event_type( mixed $value ): bool {
		$event_type = sanitize_key( is_string( $value ) ? $value : '' );

		return in_array( $event_type, $this->get_allowed_event_types(), true );
	}

	/**
	 * Validate a trackable URL value.
	 */
	public function validate_url( mixed $value ): bool {
		$url = is_string( $value ) ? $value : '';

		if ( '' === $url ) {
			return true;
		}

		return '' !== $this->sanitize_url( $url );
	}

	/**
	 * Validate a supported device type.
	 */
	public function validate_device_type( mixed $value ): bool {
		$device_type = sanitize_key( is_string( $value ) ? $value : '' );

		if ( '' === $device_type ) {
			return true;
		}

		return in_array( $device_type, [ 'mobile', 'desktop', 'tablet', 'unknown' ], true );
	}

	/**
	 * Get existing anonymous session ID.
	 */
	private function get_session_id(): ?string {
		$session_id = isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( (string) $_COOKIE[ self::COOKIE_NAME ] ) ) : '';

		if ( preg_match( '/^[a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12}$/', $session_id ) ) {
			return $session_id;
		}

		return null;
	}

	/**
	 * Generate UUID-like anonymous session ID.
	 */
	private function generate_session_id(): string {
		return wp_generate_uuid4();
	}

	/**
	 * Build a sanitized request payload.
	 *
	 * @return array<string, string>
	 */
	private function build_tracking_payload( \WP_REST_Request $request ): array {
		return [
			'event_type'   => sanitize_key( (string) $request->get_param( 'event_type' ) ),
			'page_url'     => $this->sanitize_url( (string) $request->get_param( 'page_url' ) ),
			'referrer_url' => $this->sanitize_url( (string) $request->get_param( 'referrer_url' ) ),
			'device_type'  => $this->sanitize_device_type( (string) $request->get_param( 'device_type' ) ),
			'utm_source'   => $this->sanitize_utm_value( $request->get_param( 'utm_source' ) ),
			'utm_medium'   => $this->sanitize_utm_value( $request->get_param( 'utm_medium' ) ),
			'utm_campaign' => $this->sanitize_utm_value( $request->get_param( 'utm_campaign' ) ),
		];
	}

	/**
	 * Normalize a filtered payload before database insertion.
	 *
	 * @param array<string, mixed> $payload Filtered payload.
	 * @return array<string, string>
	 */
	private function normalize_tracking_payload( array $payload ): array {
		return [
			'event_type'   => sanitize_key( (string) ( $payload['event_type'] ?? '' ) ),
			'page_url'     => $this->sanitize_url( $payload['page_url'] ?? '' ),
			'referrer_url' => $this->sanitize_url( $payload['referrer_url'] ?? '' ),
			'device_type'  => $this->sanitize_device_type( $payload['device_type'] ?? '' ),
			'utm_source'   => $this->sanitize_utm_value( $payload['utm_source'] ?? '' ),
			'utm_medium'   => $this->sanitize_utm_value( $payload['utm_medium'] ?? '' ),
			'utm_campaign' => $this->sanitize_utm_value( $payload['utm_campaign'] ?? '' ),
		];
	}

	/**
	 * Resolve the client IP.
	 */
	private function get_client_ip(): string {
		$ip = '';

		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
		}

		if ( '' === $ip ) {
			return '';
		}

		if ( false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return '';
		}

		return $ip;
	}
}
