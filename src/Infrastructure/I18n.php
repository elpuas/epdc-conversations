<?php
/**
 * Translation loader.
 *
 * @package EPDC\Conversations\Infrastructure
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Infrastructure;

final class I18n implements ServiceInterface {
	public function __construct( string $plugin_file ) {
		unset( $plugin_file );
	}

	public function register(): void {
		// WordPress loads the plugin text domain automatically for modern plugins.
	}
}
