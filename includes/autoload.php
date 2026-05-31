<?php
/**
 * Minimal PSR-4 autoloader fallback for local plugin classes.
 *
 * @package EPDC\Conversations
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'EPDC\\Conversations\\';

		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( $prefix ) );

		if ( false === $relative_class ) {
			return;
		}

		$relative_path = str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class );
		$file          = dirname( __DIR__ ) . '/src/' . $relative_path . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);
