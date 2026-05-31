<?php
/**
 * Message parser placeholder.
 *
 * @package EPDC\Conversations\Messaging
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Messaging;

final class MessageParser {
	/**
	 * Parse dynamic variables in message templates.
	 */
	public function parse( string $template ): string {
		$site_name = get_bloginfo( 'name' );

		return str_replace(
			'{site_name}',
			is_string( $site_name ) ? $site_name : '',
			$template
		);
	}
}
