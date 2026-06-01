<?php
/**
 * Admin settings page.
 *
 * @package EPDC\Conversations\Admin
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Admin;

use EPDC\Conversations\Infrastructure\ServiceInterface;
use EPDC\Conversations\Infrastructure\Settings;

final class SettingsPage implements ServiceInterface {
	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_menu(): void {
		add_menu_page(
			esc_html__( 'EPDC Conversations', 'epdc-conversations' ),
			esc_html__( 'EPDC Conversations', 'epdc-conversations' ),
			'manage_options',
			'epdc-conversations',
			[ $this, 'render_page' ],
			'dashicons-format-chat'
		);

		add_submenu_page(
			'epdc-conversations',
			esc_html__( 'Settings', 'epdc-conversations' ),
			esc_html__( 'Settings', 'epdc-conversations' ),
			'manage_options',
			'epdc-conversations',
			[ $this, 'render_page' ]
		);
	}

	public function register_settings(): void {
		register_setting(
			Settings::OPTION_GROUP,
			Settings::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_options' ],
				'default'           => $this->settings->get_defaults(),
			]
		);

		add_settings_section(
			'epdc_conversations_general',
			esc_html__( 'Floating WhatsApp Button', 'epdc-conversations' ),
			[ $this, 'render_general_section' ],
			'epdc-conversations'
		);

		$this->register_field(
			'phone_number',
			esc_html__( 'WhatsApp phone number', 'epdc-conversations' ),
			'render_text_field',
			[
				'description' => esc_html__( 'Use international format without spaces. Only digits will be stored.', 'epdc-conversations' ),
				'inputmode'   => 'tel',
				'required'    => true,
			]
		);

		$this->register_field(
			'default_message',
			esc_html__( 'Default CTA message', 'epdc-conversations' ),
			'render_textarea_field',
			[
				'description' => esc_html__( 'Supports variables like {site_name}, {post_title}, {post_url}, and {current_url}.', 'epdc-conversations' ),
			]
		);

		$this->register_field(
			'enable_floating_button',
			esc_html__( 'Enable floating button', 'epdc-conversations' ),
			'render_checkbox_field',
			[
				'label' => esc_html__( 'Display the floating WhatsApp button site-wide.', 'epdc-conversations' ),
			]
		);

		$this->register_field(
			'show_on_mobile',
			esc_html__( 'Show on mobile', 'epdc-conversations' ),
			'render_checkbox_field',
			[
				'label' => esc_html__( 'Show the floating button on mobile screens.', 'epdc-conversations' ),
			]
		);

		$this->register_field(
			'show_on_desktop',
			esc_html__( 'Show on desktop', 'epdc-conversations' ),
			'render_checkbox_field',
			[
				'label' => esc_html__( 'Show the floating button on desktop screens.', 'epdc-conversations' ),
			]
		);

		$this->register_field(
			'button_position',
			esc_html__( 'Button position', 'epdc-conversations' ),
			'render_select_field',
			[
				'options' => [
					'bottom-right' => esc_html__( 'Bottom right', 'epdc-conversations' ),
					'bottom-left'  => esc_html__( 'Bottom left', 'epdc-conversations' ),
				],
			]
		);

		$this->register_field(
			'button_label',
			esc_html__( 'Button label', 'epdc-conversations' ),
			'render_text_field',
			[
				'description' => esc_html__( 'Visible text shown inside the WhatsApp button.', 'epdc-conversations' ),
			]
		);

		$this->register_field(
			'open_in_new_tab',
			esc_html__( 'Open in new tab', 'epdc-conversations' ),
			'render_checkbox_field',
			[
				'label' => esc_html__( 'Open WhatsApp in a new browser tab.', 'epdc-conversations' ),
			]
		);

		$this->register_field(
			'enable_ga_tracking',
			esc_html__( 'Enable Google Analytics tracking', 'epdc-conversations' ),
			'render_checkbox_field',
			[
				'label' => esc_html__( 'Forward WhatsApp click events to Google Analytics 4 when gtag is available.', 'epdc-conversations' ),
			]
		);
	}

	public function render_general_section(): void {
		echo '<p>' . esc_html__( 'Configure the default floating WhatsApp button behavior.', 'epdc-conversations' ) . '</p>';
	}

	/**
	 * Sanitize settings values.
	 *
	 * @param mixed $input Raw option input.
	 * @return array<string, mixed>
	 */
	public function sanitize_options( mixed $input ): array {
		$defaults = $this->settings->get_defaults();

		if ( ! is_array( $input ) ) {
			return $defaults;
		}

		$position = isset( $input['button_position'] ) ? sanitize_key( (string) $input['button_position'] ) : 'bottom-right';

		if ( ! in_array( $position, [ 'bottom-right', 'bottom-left' ], true ) ) {
			$position = 'bottom-right';
		}

		$button_label = sanitize_text_field( (string) ( $input['button_label'] ?? '' ) );

		if ( '' === $button_label ) {
			$button_label = (string) $defaults['button_label'];
		}

		return [
			'phone_number'           => preg_replace( '/\D+/', '', (string) ( $input['phone_number'] ?? '' ) ) ?? '',
			'default_message'        => sanitize_textarea_field( (string) ( $input['default_message'] ?? '' ) ),
			'enable_floating_button' => ! empty( $input['enable_floating_button'] ),
			'show_on_mobile'         => ! empty( $input['show_on_mobile'] ),
			'show_on_desktop'        => ! empty( $input['show_on_desktop'] ),
			'button_position'        => $position,
			'button_label'           => $button_label,
			'open_in_new_tab'        => ! empty( $input['open_in_new_tab'] ),
			'enable_ga_tracking'     => ! empty( $input['enable_ga_tracking'] ),
		];
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'EPDC Conversations', 'epdc-conversations' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( Settings::OPTION_GROUP );
				do_settings_sections( 'epdc-conversations' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register one settings field.
	 *
	 * @param string               $key Field key.
	 * @param string               $title Field title.
	 * @param string               $callback Callback method name.
	 * @param array<string, mixed> $args Field arguments.
	 */
	private function register_field( string $key, string $title, string $callback, array $args = [] ): void {
		add_settings_field(
			$key,
			$title,
			[ $this, $callback ],
			'epdc-conversations',
			'epdc_conversations_general',
			[
				'key' => $key,
			] + $args
		);
	}

	/**
	 * Render a text field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function render_text_field( array $args ): void {
		$key         = (string) $args['key'];
		$field_id    = $this->get_field_id( $key );
		$value       = (string) $this->settings->get( $key );
		$description = isset( $args['description'] ) ? (string) $args['description'] : '';
		$inputmode   = isset( $args['inputmode'] ) ? (string) $args['inputmode'] : 'text';
		$required    = ! empty( $args['required'] );
		$help_id     = '' !== $description ? $field_id . '-description' : '';
		?>
		<input
			id="<?php echo esc_attr( $field_id ); ?>"
			type="text"
			class="regular-text"
			name="<?php echo esc_attr( Settings::OPTION_NAME . '[' . $key . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			inputmode="<?php echo esc_attr( $inputmode ); ?>"
			<?php if ( '' !== $help_id ) : ?>
				aria-describedby="<?php echo esc_attr( $help_id ); ?>"
			<?php endif; ?>
			<?php if ( $required ) : ?>
				required
			<?php endif; ?>
		/>
		<?php if ( '' !== $description ) : ?>
			<p id="<?php echo esc_attr( $help_id ); ?>" class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render a textarea field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function render_textarea_field( array $args ): void {
		$key         = (string) $args['key'];
		$field_id    = $this->get_field_id( $key );
		$value       = (string) $this->settings->get( $key );
		$description = isset( $args['description'] ) ? (string) $args['description'] : '';
		$help_id     = '' !== $description ? $field_id . '-description' : '';
		?>
		<textarea
			id="<?php echo esc_attr( $field_id ); ?>"
			class="large-text"
			name="<?php echo esc_attr( Settings::OPTION_NAME . '[' . $key . ']' ); ?>"
			rows="4"
			<?php if ( '' !== $help_id ) : ?>
				aria-describedby="<?php echo esc_attr( $help_id ); ?>"
			<?php endif; ?>
		><?php echo esc_textarea( $value ); ?></textarea>
		<?php if ( '' !== $description ) : ?>
			<p id="<?php echo esc_attr( $help_id ); ?>" class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function render_checkbox_field( array $args ): void {
		$key   = (string) $args['key'];
		$field_id = $this->get_field_id( $key );
		$value = (bool) $this->settings->get( $key );
		$label = isset( $args['label'] ) ? (string) $args['label'] : '';
		?>
		<label>
			<input
				id="<?php echo esc_attr( $field_id ); ?>"
				type="checkbox"
				name="<?php echo esc_attr( Settings::OPTION_NAME . '[' . $key . ']' ); ?>"
				value="1"
				<?php checked( $value ); ?>
			/>
			<?php echo esc_html( $label ); ?>
		</label>
		<?php
	}

	/**
	 * Render a select field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function render_select_field( array $args ): void {
		$key     = (string) $args['key'];
		$field_id = $this->get_field_id( $key );
		$value   = (string) $this->settings->get( $key );
		$options = isset( $args['options'] ) && is_array( $args['options'] ) ? $args['options'] : [];
		?>
		<select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( Settings::OPTION_NAME . '[' . $key . ']' ); ?>">
			<?php foreach ( $options as $option_value => $option_label ) : ?>
				<option value="<?php echo esc_attr( (string) $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
					<?php echo esc_html( (string) $option_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Build a stable field ID for admin settings controls.
	 */
	private function get_field_id( string $key ): string {
		return 'epdc-conversations-' . sanitize_html_class( $key );
	}
}
