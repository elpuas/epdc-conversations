<?php
/**
 * Admin settings page placeholder.
 *
 * @package EPDC\Conversations\Admin
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Admin;

use EPDC\Conversations\Infrastructure\ServiceInterface;

final class SettingsPage implements ServiceInterface {
	private const OPTION_GROUP = 'epdc_conversations_settings';
	private const OPTION_NAME  = 'epdc_conversations_options';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_menu(): void {
		add_options_page(
			esc_html__( 'EPDC Conversations', 'epdc-conversations' ),
			esc_html__( 'EPDC Conversations', 'epdc-conversations' ),
			'manage_options',
			'epdc-conversations',
			[ $this, 'render_page' ]
		);
	}

	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_options' ],
				'default'           => [],
			]
		);
	}

	/**
	 * Sanitize settings values.
	 *
	 * @param mixed $input Raw option input.
	 * @return array<string, mixed>
	 */
	public function sanitize_options( mixed $input ): array {
		if ( ! is_array( $input ) ) {
			return [];
		}

		// Placeholder sanitization flow for future settings fields.
		return array_map(
			static fn( $value ): string => sanitize_text_field( (string) $value ),
			$input
		);
	}

	public function render_page(): void {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'EPDC Conversations', 'epdc-conversations' ); ?></h1>
			<p><?php echo esc_html__( 'Settings page scaffold. Fields will be added in future iterations.', 'epdc-conversations' ); ?></p>
		</div>
		<?php
	}
}
