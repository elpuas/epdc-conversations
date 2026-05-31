<?php
/**
 * Plugin settings access.
 *
 * @package EPDC\Conversations\Infrastructure
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Infrastructure;

final class Settings {
	public const OPTION_GROUP = 'epdc_conversations_settings';
	public const OPTION_NAME  = 'epdc_conversations_options';

	/**
	 * Get default settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_defaults(): array {
		return [
			'phone_number'           => '',
			'default_message'        => '',
			'enable_floating_button' => true,
			'show_on_mobile'         => true,
			'show_on_desktop'        => true,
			'button_position'        => 'bottom-right',
			'button_label'           => __( 'Chat on WhatsApp', 'epdc-conversations' ),
			'open_in_new_tab'        => true,
		];
	}

	/**
	 * Get merged settings values.
	 *
	 * @return array<string, mixed>
	 */
	public function get_all(): array {
		$saved_settings = get_option( self::OPTION_NAME, [] );

		if ( ! is_array( $saved_settings ) ) {
			$saved_settings = [];
		}

		return wp_parse_args( $saved_settings, $this->get_defaults() );
	}

	/**
	 * Get one setting by key.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public function get( string $key ): mixed {
		$settings = $this->get_all();

		return $settings[ $key ] ?? null;
	}
}
