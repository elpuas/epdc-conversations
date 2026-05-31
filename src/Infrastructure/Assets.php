<?php
/**
 * Asset registration and enqueue service.
 *
 * @package EPDC\Conversations\Infrastructure
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Infrastructure;

final class Assets implements ServiceInterface {
	public const STYLE_HANDLE  = 'epdc-conversations-frontend';
	public const SCRIPT_HANDLE = 'epdc-conversations-frontend';

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	private string $plugin_file;

	public function __construct( string $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
	}

	public function enqueue_frontend_assets(): void {
		$base_url = plugin_dir_url( $this->plugin_file ) . 'assets/';

		wp_register_style(
			self::STYLE_HANDLE,
			$base_url . 'css/frontend.css',
			[],
			'0.1.0'
		);

		wp_register_script(
			self::SCRIPT_HANDLE,
			$base_url . 'js/frontend.js',
			[],
			'0.1.0',
			true
		);
	}
}
