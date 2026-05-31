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
		// Reserved for future installation routines.
	}

	/**
	 * Plugin deactivation callback.
	 */
	public static function deactivate(): void {
		// Reserved for future cleanup routines.
	}
}
