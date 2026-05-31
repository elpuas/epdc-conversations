<?php
/**
 * Frontend button template.
 *
 * @package EPDC\Conversations
 *
 * @var array<string, mixed> $args
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$class_name = isset( $args['classes'] ) && is_array( $args['classes'] )
	? implode( ' ', array_map( 'sanitize_html_class', $args['classes'] ) )
	: 'epdc-conversations';

$target = ! empty( $args['new_tab'] ) ? '_blank' : '';
$rel    = ! empty( $args['new_tab'] ) ? 'noopener noreferrer' : '';
?>
<div class="<?php echo esc_attr( $class_name ); ?>" data-epdc-conversations>
	<a
		class="epdc-conversations__link"
		href="<?php echo esc_url( (string) $args['url'] ); ?>"
		aria-label="<?php echo esc_attr( (string) $args['aria_label'] ); ?>"
		<?php if ( '' !== $target ) : ?>
			target="<?php echo esc_attr( $target ); ?>"
			rel="<?php echo esc_attr( $rel ); ?>"
		<?php endif; ?>
	>
		<span class="epdc-conversations__icon" aria-hidden="true">
			<svg viewBox="0 0 32 32" focusable="false" role="img" aria-hidden="true">
				<path fill="currentColor" d="M19.11 17.38c-.29-.15-1.73-.85-2-.95-.27-.1-.47-.15-.67.15-.2.29-.77.95-.94 1.15-.17.19-.35.22-.64.07-.29-.15-1.24-.45-2.36-1.43-.88-.79-1.47-1.76-1.64-2.06-.17-.29-.02-.45.13-.6.13-.13.29-.35.44-.52.15-.17.2-.29.3-.49.1-.19.05-.37-.02-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.19 0-.52.07-.79.37-.27.29-1.04 1.02-1.04 2.5s1.07 2.9 1.22 3.1c.15.19 2.1 3.21 5.08 4.5.71.31 1.27.49 1.7.63.71.23 1.35.2 1.86.12.57-.08 1.73-.71 1.98-1.4.24-.69.24-1.28.17-1.4-.07-.12-.27-.2-.57-.35Z"/>
				<path fill="currentColor" d="M16.01 3.2c-7.07 0-12.8 5.73-12.8 12.8 0 2.25.59 4.46 1.7 6.4L3.2 28.8l6.56-1.68a12.76 12.76 0 0 0 6.25 1.62c7.06 0 12.79-5.73 12.79-12.79S23.07 3.2 16.01 3.2Zm0 23.36c-1.95 0-3.87-.52-5.54-1.5l-.4-.24-3.89.99 1.03-3.79-.26-.39a10.55 10.55 0 0 1-1.63-5.68c0-5.86 4.78-10.63 10.69-10.63 2.84 0 5.5 1.11 7.51 3.12a10.55 10.55 0 0 1 3.12 7.51c0 5.88-4.78 10.61-10.63 10.61Z"/>
			</svg>
		</span>
		<span class="epdc-conversations__label"><?php echo esc_html( (string) $args['label'] ); ?></span>
	</a>
</div>
