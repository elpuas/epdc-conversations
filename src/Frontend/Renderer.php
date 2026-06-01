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

final class Renderer implements ServiceInterface {
	private Settings $settings;
	private MessageParser $message_parser;

	public function __construct( Settings $settings, MessageParser $message_parser ) {
		$this->settings         = $settings;
		$this->message_parser   = $message_parser;
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
				'message'      => '',
				'label'        => '',
				'phone_number' => '',
				'variant'      => 'default',
				'size'         => 'medium',
				'show_icon'    => 'yes',
				'new_tab'      => '',
			],
			$atts,
			'epdc_conversations'
		);

		$normalized_atts = [
			'message'     => sanitize_textarea_field( (string) $atts['message'] ),
			'label'       => sanitize_text_field( (string) $atts['label'] ),
			'phoneNumber' => preg_replace( '/\D+/', '', (string) $atts['phone_number'] ) ?? '',
			'variant'     => sanitize_key( (string) $atts['variant'] ),
			'size'        => sanitize_key( (string) $atts['size'] ),
			'showIcon'    => ! in_array( strtolower( (string) $atts['show_icon'] ), [ '0', 'false', 'no', 'off' ], true ),
			'newTab'      => in_array( strtolower( (string) $atts['new_tab'] ), [ '1', 'true', 'yes', 'on' ], true ),
		];

		return $this->render_button( $normalized_atts, false, 'shortcode' );
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
		$variant  = $this->normalize_variant( isset( $overrides['variant'] ) ? (string) $overrides['variant'] : (string) $settings['button_variant'] );
		$size     = $this->normalize_size( isset( $overrides['size'] ) ? (string) $overrides['size'] : (string) $settings['button_size'] );

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

		$show_icon = array_key_exists( 'showIcon', $overrides )
			? ! empty( $overrides['showIcon'] )
			: ! empty( $settings['show_button_icon'] );

		if ( 'icon-only' === $variant ) {
			$show_icon = true;
		}

		$classes = [
			'epdc-conversations',
			$is_floating ? 'epdc-conversations--floating' : 'epdc-conversations--inline',
			'epdc-conversations--' . $position,
			'epdc-conversations--variant-' . $variant,
			'epdc-conversations--size-' . $size,
		];

		if ( ! $show_icon ) {
			$classes[] = 'epdc-conversations--icon-hidden';
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
			'aria_label'  => sprintf(
				/* translators: %s: button label. */
				__( 'Open WhatsApp conversation: %s', 'epdc-conversations' ),
				$label
			),
			'classes'     => array_values( array_filter( array_map( 'sanitize_html_class', (array) $classes ) ) ),
			'source'      => $source,
			'variant'     => $variant,
			'is_floating' => $is_floating,
			'label'       => $label,
			'message'     => $message,
			'new_tab'     => $new_tab,
			'phone'       => $phone,
			'position'    => $position,
			'size'        => $size,
			'show_icon'   => $show_icon,
			'url'         => $this->build_whatsapp_url( $phone, $message ),
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

		return $this->normalize_button_args( $args );
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

	/**
	 * Normalize filtered button arguments before template rendering.
	 *
	 * @param array<string, mixed> $args Raw button arguments.
	 * @return array<string, mixed>
	 */
	private function normalize_button_args( array $args ): array {
		$variant = $this->normalize_variant( isset( $args['variant'] ) ? (string) $args['variant'] : 'default' );
		$size    = $this->normalize_size( isset( $args['size'] ) ? (string) $args['size'] : 'medium' );

		$position = isset( $args['position'] ) && 'bottom-left' === $args['position'] ? 'bottom-left' : 'bottom-right';
		$phone    = isset( $args['phone'] ) ? preg_replace( '/\D+/', '', (string) $args['phone'] ) : '';
		$message  = isset( $args['message'] ) ? wp_strip_all_tags( (string) $args['message'] ) : '';
		$label    = isset( $args['label'] ) ? sanitize_text_field( (string) $args['label'] ) : '';
		$url      = isset( $args['url'] ) ? esc_url_raw( (string) $args['url'], [ 'https' ] ) : '';
		$show_icon = ! isset( $args['show_icon'] ) || ! empty( $args['show_icon'] );

		if ( 'icon-only' === $variant ) {
			$show_icon = true;
		}

		return [
			'aria_label'  => isset( $args['aria_label'] ) ? sanitize_text_field( (string) $args['aria_label'] ) : '',
			'classes'     => isset( $args['classes'] ) && is_array( $args['classes'] ) ? array_values( array_filter( array_map( 'sanitize_html_class', $args['classes'] ) ) ) : [],
			'is_floating' => ! empty( $args['is_floating'] ),
			'label'       => $label,
			'message'     => $message,
			'new_tab'     => ! empty( $args['new_tab'] ),
			'phone'       => $phone ?? '',
			'position'    => $position,
			'show_icon'   => $show_icon,
			'size'        => $size,
			'source'      => isset( $args['source'] ) ? sanitize_key( (string) $args['source'] ) : 'unknown',
			'url'         => $url,
			'variant'     => $variant,
		];
	}

	/**
	 * Normalize button variant values, preserving legacy aliases.
	 */
	private function normalize_variant( string $variant ): string {
		$normalized_variant = sanitize_key( $variant );

		if ( 'inline' === $normalized_variant ) {
			return 'compact';
		}

		if ( in_array( $normalized_variant, [ 'default', 'compact', 'icon-only' ], true ) ) {
			return $normalized_variant;
		}

		return 'default';
	}

	/**
	 * Normalize button size values.
	 */
	private function normalize_size( string $size ): string {
		$normalized_size = sanitize_key( $size );

		if ( in_array( $normalized_size, [ 'small', 'medium', 'large' ], true ) ) {
			return $normalized_size;
		}

		return 'medium';
	}
}
