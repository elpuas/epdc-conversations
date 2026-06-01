<?php
/**
 * Frontend renderer.
 *
 * @package EPDC\Conversations\Frontend
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Frontend;

use EPDC\Conversations\Infrastructure\Assets;
use EPDC\Conversations\Infrastructure\ServiceInterface;
use EPDC\Conversations\Infrastructure\Settings;
use EPDC\Conversations\Messaging\MessageParser;
use EPDC\Conversations\Tracking\TrackingService;

final class Renderer implements ServiceInterface {
	private Settings $settings;
	private MessageParser $message_parser;
	private TrackingService $tracking_service;

	public function __construct( Settings $settings, MessageParser $message_parser, TrackingService $tracking_service ) {
		$this->settings         = $settings;
		$this->message_parser   = $message_parser;
		$this->tracking_service = $tracking_service;
	}

	public function register(): void {
		add_shortcode( 'epdc_conversations', [ $this, 'render_shortcode' ] );
		add_action( 'wp_footer', [ $this, 'render_floating_button' ] );
	}

	/**
	 * Render the site-wide floating button.
	 */
	public function render_floating_button(): void {
		if ( is_admin() ) {
			return;
		}

		$settings = $this->settings->get_all();

		if ( empty( $settings['enable_floating_button'] ) ) {
			return;
		}

		if ( ! $this->should_render_floating_button() ) {
			return;
		}

		echo $this->render_button( [], true, 'floating' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Shortcode renderer.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 */
	public function render_shortcode( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'message' => '',
			],
			$atts,
			'epdc_conversations'
		);

		return $this->render_button( $atts, false, 'shortcode' );
	}

	/**
	 * Block renderer.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function render_block( array $attributes = [] ): string {
		return $this->render_button( $attributes, false, 'block' );
	}

	/**
	 * Render a button instance.
	 *
	 * @param array<string, mixed> $overrides Button overrides.
	 * @param bool                 $is_floating Whether this instance is floating.
	 */
	private function render_button( array $overrides, bool $is_floating, string $source ): string {
		$args = $this->build_button_args( $overrides, $is_floating, $source );

		if ( ! $this->should_render( $args ) ) {
			return '';
		}

		wp_enqueue_style( Assets::STYLE_HANDLE );
		wp_enqueue_script( Assets::SCRIPT_HANDLE );

		$this->tracking_service->register_placeholder_event( $is_floating ? 'floating_button_rendered' : $source . '_button_rendered' );

		ob_start();
		require dirname( __DIR__, 2 ) . '/templates/frontend-button.php';
		return (string) ob_get_clean();
	}

	/**
	 * Build button arguments for rendering.
	 *
	 * @param array<string, mixed> $overrides Button overrides.
	 * @param bool                 $is_floating Whether the button is floating.
	 * @return array<string, mixed>
	 */
	private function build_button_args( array $overrides, bool $is_floating, string $source ): array {
		$settings = $this->settings->get_all();
		$context  = [
			'post_id'     => get_the_ID(),
			'is_floating' => $is_floating,
			'overrides'   => $overrides,
			'source'      => $source,
		];

		$raw_message = isset( $overrides['message'] ) && '' !== trim( (string) $overrides['message'] )
			? (string) $overrides['message']
			: (string) $settings['default_message'];

		$message = $this->message_parser->parse( $raw_message, $context );

		/**
		 * Filter the final WhatsApp message.
		 *
		 * @param string               $message Final parsed message.
		 * @param array<string, mixed> $context Render context.
		 */
		$message = (string) apply_filters( 'epdc_conversations_message', $message, $context );

		$position = 'bottom-left' === $settings['button_position'] ? 'bottom-left' : 'bottom-right';
		$variant  = isset( $overrides['variant'] ) ? (string) $overrides['variant'] : 'default';
		$variant  = in_array( $variant, [ 'default', 'inline', 'compact' ], true ) ? $variant : 'default';

		$label = isset( $overrides['label'] ) && '' !== trim( (string) $overrides['label'] )
			? (string) $overrides['label']
			: (string) $settings['button_label'];

		$phone_override = isset( $overrides['phoneNumber'] ) && '' !== trim( (string) $overrides['phoneNumber'] )
			? (string) $overrides['phoneNumber']
			: (string) $settings['phone_number'];
		$phone          = preg_replace( '/\D+/', '', $phone_override ) ?? '';

		$new_tab = array_key_exists( 'newTab', $overrides )
			? ! empty( $overrides['newTab'] )
			: ! empty( $settings['open_in_new_tab'] );

		$show_icon = ! ( array_key_exists( 'showIcon', $overrides ) && empty( $overrides['showIcon'] ) );

		$classes = [
			'epdc-conversations',
			$is_floating ? 'epdc-conversations--floating' : 'epdc-conversations--inline',
			'epdc-conversations--' . $position,
		];

		if ( ! $is_floating ) {
			$classes[] = 'epdc-conversations--variant-' . $variant;
		}

		if ( empty( $settings['show_on_mobile'] ) ) {
			$classes[] = 'epdc-conversations--hide-mobile';
		}

		if ( empty( $settings['show_on_desktop'] ) ) {
			$classes[] = 'epdc-conversations--hide-desktop';
		}

		/**
		 * Filter button classes before rendering.
		 *
		 * @param string[]             $classes CSS classes.
		 * @param array<string, mixed> $context Render context.
		 */
		$classes = apply_filters( 'epdc_conversations_button_classes', $classes, $context );

		$args = [
			'aria_label' => sprintf(
				/* translators: %s: button label. */
				__( 'Open WhatsApp conversation: %s', 'epdc-conversations' ),
				$label
			),
			'classes'    => array_values( array_filter( array_map( 'sanitize_html_class', (array) $classes ) ) ),
			'source'     => $source,
			'variant'    => $variant,
			'is_floating' => $is_floating,
			'label'      => $label,
			'message'    => $message,
			'new_tab'    => $new_tab,
			'phone'      => $phone,
			'position'   => $position,
			'show_icon'  => $show_icon,
			'url'        => $this->build_whatsapp_url( $phone, $message ),
		];

		/**
		 * Filter button arguments before template rendering.
		 *
		 * @param array<string, mixed> $args Button arguments.
		 * @param array<string, mixed> $context Render context.
		 */
		$args = apply_filters( 'epdc_conversations_button_args', $args, $context );

		if ( ! is_array( $args ) ) {
			return [];
		}

		$args['url'] = (string) apply_filters( 'epdc_conversations_url', (string) ( $args['url'] ?? '' ), $args );

		return $args;
	}

	/**
	 * Determine whether a button should render.
	 *
	 * @param array<string, mixed> $args Button arguments.
	 */
	private function should_render( array $args ): bool {
		if ( empty( $args['phone'] ) || empty( $args['url'] ) ) {
			return false;
		}

		if ( empty( $args['label'] ) ) {
			return false;
		}

		if ( ! empty( $args['is_floating'] ) && in_array( 'epdc-conversations--hide-mobile', (array) $args['classes'], true ) && in_array( 'epdc-conversations--hide-desktop', (array) $args['classes'], true ) ) {
			return false;
		}

		return ! empty( $args['classes'] );
	}

	/**
	 * Determine whether the global floating button should render.
	 */
	private function should_render_floating_button(): bool {
		$should_render = true;

		if ( is_singular() ) {
			$post_id = get_queried_object_id();

			if ( $post_id > 0 ) {
				$post_content = (string) get_post_field( 'post_content', $post_id );

				if ( '' !== $post_content && has_block( 'epdc/conversations', $post_content ) ) {
					$should_render = false;
				}
			}
		}

		/**
		 * Filter whether the global floating button should render.
		 *
		 * @param bool $should_render Whether the floating button should render.
		 */
		return (bool) apply_filters( 'epdc_conversations_should_render_floating_button', $should_render );
	}

	/**
	 * Build the WhatsApp URL.
	 */
	private function build_whatsapp_url( string $phone, string $message ): string {
		if ( '' === $phone ) {
			return '';
		}

		$url = 'https://wa.me/' . rawurlencode( $phone );

		if ( '' !== $message ) {
			$url .= '?text=' . rawurlencode( $message );
		}

		return esc_url_raw( $url );
	}
}
