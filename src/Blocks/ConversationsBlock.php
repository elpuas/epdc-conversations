<?php
/**
 * Conversations block registration service.
 *
 * @package EPDC\Conversations\Blocks
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Blocks;

use EPDC\Conversations\Frontend\Renderer;
use EPDC\Conversations\Infrastructure\Assets;
use EPDC\Conversations\Infrastructure\ServiceInterface;

final class ConversationsBlock implements ServiceInterface {
	private const EDITOR_SCRIPT_HANDLE = 'epdc-conversations-block-editor';

	private string $plugin_file;
	private Renderer $renderer;

	public function __construct( string $plugin_file, Renderer $renderer ) {
		$this->plugin_file = $plugin_file;
		$this->renderer    = $renderer;
	}

	public function register(): void {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Register block type and editor assets.
	 */
	public function register_block(): void {
		$base_url  = plugin_dir_url( $this->plugin_file ) . 'blocks/conversations/';
		$base_path = plugin_dir_path( $this->plugin_file ) . 'blocks/conversations/';

		wp_register_script(
			self::EDITOR_SCRIPT_HANDLE,
			$base_url . 'index.js',
			[ 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render' ],
			file_exists( $base_path . 'index.js' ) ? (string) filemtime( $base_path . 'index.js' ) : '0.1.0',
			true
		);

		register_block_type(
			$base_path,
			[
				'editor_style'    => Assets::STYLE_HANDLE,
				'render_callback' => [ $this, 'render_callback' ],
				'style'           => Assets::STYLE_HANDLE,
			]
		);
	}

	/**
	 * Render callback.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function render_callback( array $attributes = [] ): string {
		$normalized_attributes = [
			'message'     => isset( $attributes['message'] ) ? sanitize_textarea_field( (string) $attributes['message'] ) : '',
			'label'       => isset( $attributes['label'] ) ? sanitize_text_field( (string) $attributes['label'] ) : '',
			'phoneNumber' => isset( $attributes['phoneNumber'] ) ? preg_replace( '/\D+/', '', (string) $attributes['phoneNumber'] ) : '',
			'variant'     => isset( $attributes['variant'] ) ? sanitize_key( (string) $attributes['variant'] ) : 'default',
			'size'        => isset( $attributes['size'] ) ? sanitize_key( (string) $attributes['size'] ) : 'medium',
			'showIcon'    => ! isset( $attributes['showIcon'] ) || (bool) $attributes['showIcon'],
			'newTab'      => isset( $attributes['newTab'] ) && (bool) $attributes['newTab'],
		];

		if ( 'inline' === $normalized_attributes['variant'] ) {
			$normalized_attributes['variant'] = 'compact';
		}

		if ( ! in_array( $normalized_attributes['variant'], [ 'default', 'compact', 'icon-only' ], true ) ) {
			$normalized_attributes['variant'] = 'default';
		}

		if ( ! in_array( $normalized_attributes['size'], [ 'small', 'medium', 'large' ], true ) ) {
			$normalized_attributes['size'] = 'medium';
		}

		if ( 'icon-only' === $normalized_attributes['variant'] ) {
			$normalized_attributes['showIcon'] = true;
		}

		$button_markup = $this->renderer->render_block( $normalized_attributes );

		if ( '' === $button_markup ) {
			return '';
		}

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes( [ 'class' => 'epdc-conversations-block' ] ),
			$button_markup
		);
	}
}
