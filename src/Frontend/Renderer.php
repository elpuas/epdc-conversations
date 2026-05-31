<?php
/**
 * Frontend renderer placeholder.
 *
 * @package EPDC\Conversations\Frontend
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Frontend;

use EPDC\Conversations\Infrastructure\ServiceInterface;
use EPDC\Conversations\Messaging\MessageParser;
use EPDC\Conversations\Tracking\TrackingService;

final class Renderer implements ServiceInterface {
	private MessageParser $message_parser;
	private TrackingService $tracking_service;

	public function __construct( MessageParser $message_parser, TrackingService $tracking_service ) {
		$this->message_parser   = $message_parser;
		$this->tracking_service = $tracking_service;
	}

	public function register(): void {
		add_shortcode( 'epdc_conversations', [ $this, 'render_shortcode' ] );
	}

	/**
	 * Shortcode renderer placeholder.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 */
	public function render_shortcode( array $atts = [] ): string {
		unset( $atts );

		$message = $this->message_parser->parse( '{site_name}' );
		$this->tracking_service->register_placeholder_event( 'shortcode_render' );

		ob_start();
		require dirname( __DIR__, 2 ) . '/templates/frontend-button.php';
		$output = (string) ob_get_clean();

		return str_replace(
			esc_html__( 'EPDC Conversations placeholder output.', 'epdc-conversations' ),
			esc_html( $message ),
			$output
		);
	}
}
