<?php
/**
 * Message parser.
 *
 * @package EPDC\Conversations\Messaging
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Messaging;

use WP_Post;

final class MessageParser {
	/**
	 * Parse dynamic variables in a message template.
	 *
	 * @param string               $template Message template.
	 * @param array<string, mixed> $context Rendering context.
	 */
	public function parse( string $template, array $context = [] ): string {
		$variables = $this->get_variables( $context );
		$parsed    = strtr( $template, $variables );

		return wp_strip_all_tags( $parsed );
	}

	/**
	 * Build supported variables for the current render context.
	 *
	 * @param array<string, mixed> $context Rendering context.
	 * @return array<string, string>
	 */
	private function get_variables( array $context ): array {
		$post = $this->resolve_post( $context );

		$variables = [
			'{site_name}'   => (string) get_bloginfo( 'name' ),
			'{post_title}'  => $post instanceof WP_Post ? get_the_title( $post ) : '',
			'{post_url}'    => $post instanceof WP_Post ? (string) get_permalink( $post ) : '',
			'{current_url}' => $this->get_current_url(),
		];

		/**
		 * Filter supported dynamic variables.
		 *
		 * @param array<string, string> $variables Supported variables.
		 * @param array<string, mixed>  $context Render context.
		 */
		$variables = apply_filters( 'epdc_conversations_variables', $variables, $context );

		if ( ! is_array( $variables ) ) {
			return [];
		}

		$normalized = [];

		foreach ( $variables as $key => $value ) {
			$normalized[ (string) $key ] = is_scalar( $value ) ? (string) $value : '';
		}

		return $normalized;
	}

	/**
	 * Resolve a post object from render context.
	 *
	 * @param array<string, mixed> $context Rendering context.
	 */
	private function resolve_post( array $context ): ?WP_Post {
		if ( isset( $context['post'] ) && $context['post'] instanceof WP_Post ) {
			return $context['post'];
		}

		if ( isset( $context['post_id'] ) ) {
			$post = get_post( (int) $context['post_id'] );

			if ( $post instanceof WP_Post ) {
				return $post;
			}
		}

		$queried_post = get_queried_object();

		return $queried_post instanceof WP_Post ? $queried_post : null;
	}

	/**
	 * Get the current request URL.
	 */
	private function get_current_url(): string {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '/';

		return esc_url_raw( home_url( $request_uri ) );
	}
}
