<?php
/**
 * Translation loader.
 *
 * @package EPDC\Conversations\Infrastructure
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Infrastructure;

final class I18n implements ServiceInterface {
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
		add_action( 'init', [ $this, 'load_textdomain' ] );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'epdc-conversations',
			false,
			dirname( plugin_basename( $this->plugin_file ) ) . '/languages'
		);
	}
}
