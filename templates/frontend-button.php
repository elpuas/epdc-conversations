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

$epdc_conversations_class_name = isset( $args['classes'] ) && is_array( $args['classes'] )
	? implode( ' ', array_map( 'sanitize_html_class', $args['classes'] ) )
	: 'epdc-conversations';

$epdc_conversations_target = ! empty( $args['new_tab'] ) ? '_blank' : '';
$epdc_conversations_rel    = ! empty( $args['new_tab'] ) ? 'noopener noreferrer' : '';
$epdc_conversations_is_icon_only = isset( $args['variant'] ) && 'icon-only' === $args['variant'];
$epdc_conversations_label_class  = $epdc_conversations_is_icon_only
	? 'epdc-conversations__label epdc-conversations__label--screen-reader'
	: 'epdc-conversations__label';
?>
<div class="<?php echo esc_attr( $epdc_conversations_class_name ); ?>" data-epdc-conversations>
	<a
		class="epdc-conversations__link"
		href="<?php echo esc_url( (string) $args['url'] ); ?>"
		aria-label="<?php echo esc_attr( (string) $args['aria_label'] ); ?>"
		data-epdc-conversations-event="whatsapp_click"
		data-epdc-conversations-source="<?php echo esc_attr( (string) ( $args['source'] ?? 'unknown' ) ); ?>"
		data-epdc-conversations-variant="<?php echo esc_attr( (string) ( $args['variant'] ?? 'default' ) ); ?>"
		<?php if ( '' !== $epdc_conversations_target ) : ?>
			target="<?php echo esc_attr( $epdc_conversations_target ); ?>"
			rel="<?php echo esc_attr( $epdc_conversations_rel ); ?>"
		<?php endif; ?>
	>
		<?php if ( ! isset( $args['show_icon'] ) || ! empty( $args['show_icon'] ) ) : ?>
			<span class="epdc-conversations__icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
					<path fill="currentColor" d="M12 2.25a9.77 9.77 0 0 0-8.3 14.92L2.25 21.75l4.7-1.39A9.75 9.75 0 1 0 12 2.25Zm0 17.73a7.92 7.92 0 0 1-4.04-1.11l-.29-.17-2.79.82.91-2.72-.19-.3A7.92 7.92 0 1 1 12 19.98Zm4.34-5.96c-.24-.12-1.44-.71-1.66-.79-.22-.08-.38-.12-.54.12-.17.24-.63.79-.77.95-.14.16-.29.18-.53.06-.24-.12-1.01-.37-1.92-1.17-.71-.63-1.19-1.41-1.33-1.64-.14-.24-.01-.37.11-.49.11-.11.24-.29.36-.43.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.43-.06-.12-.54-1.3-.74-1.79-.2-.47-.4-.41-.54-.41l-.46-.01c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2 0 1.18.86 2.31.98 2.47.12.15 1.68 2.57 4.06 3.6.57.25 1.02.4 1.37.51.58.18 1.09.16 1.5.1.46-.07 1.44-.59 1.64-1.15.2-.57.2-1.06.14-1.16-.06-.1-.22-.16-.46-.28Z"/>
				</svg>
			</span>
		<?php endif; ?>
		<span class="<?php echo esc_attr( $epdc_conversations_label_class ); ?>"><?php echo esc_html( (string) $args['label'] ); ?></span>
	</a>
</div>
