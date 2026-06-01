<?php
/**
 * Asset registration and enqueue service.
 *
 * @package EPDC\Conversations\Infrastructure
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Infrastructure;

use EPDC\Conversations\Tracking\TrackingService;

final class Assets implements ServiceInterface {
	public const STYLE_HANDLE  = 'epdc-conversations-frontend';
	public const SCRIPT_HANDLE = 'epdc-conversations-frontend';

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	private string $plugin_file;

	private Settings $settings;
	private TrackingService $tracking_service;

	public function __construct( string $plugin_file, Settings $settings, TrackingService $tracking_service ) {
		$this->plugin_file      = $plugin_file;
		$this->settings         = $settings;
		$this->tracking_service = $tracking_service;
	}

	public function register(): void {
		add_action( 'init', [ $this, 'register_assets' ] );
	}

	/**
	 * Register shared frontend and editor assets.
	 */
	public function register_assets(): void {
		$base_url = plugin_dir_url( $this->plugin_file ) . 'assets/';
		$base_dir = plugin_dir_path( $this->plugin_file ) . 'assets/';

		wp_register_style(
			self::STYLE_HANDLE,
			$base_url . 'css/frontend.css',
			[],
			$this->get_asset_version( $base_dir . 'css/frontend.css' )
		);

		wp_register_script(
			self::SCRIPT_HANDLE,
			$base_url . 'js/frontend.js',
			[],
			$this->get_asset_version( $base_dir . 'js/frontend.js' ),
			true
		);

		$frontend_config = $this->tracking_service->get_frontend_config( ! empty( $this->settings->get( 'enable_ga_tracking' ) ) );

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'epdcConversationsTracking',
			$frontend_config
		);
	}

	/**
	 * Resolve a stable asset version string.
	 */
	private function get_asset_version( string $asset_path ): string {
		if ( file_exists( $asset_path ) ) {
			return (string) filemtime( $asset_path );
		}

		return defined( 'EPDC_CONVERSATIONS_VERSION' ) ? EPDC_CONVERSATIONS_VERSION : '0.1.0';
	}
}
