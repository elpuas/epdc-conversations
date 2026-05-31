<?php
/**
 * Plugin Name: EPDC Conversations
 * Description: Lightweight WhatsApp conversion-focused plugin scaffold.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.3
 * Author: EPDC
 * Text Domain: epdc-conversations
 * Domain Path: /languages
 *
 * @package EPDC\Conversations
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$autoload = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

if ( ! class_exists( \EPDC\Conversations\Infrastructure\Plugin::class ) ) {
	return;
}

register_activation_hook( __FILE__, [ \EPDC\Conversations\Infrastructure\Lifecycle::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \EPDC\Conversations\Infrastructure\Lifecycle::class, 'deactivate' ] );

$plugin = new \EPDC\Conversations\Infrastructure\Plugin( __FILE__ );
$plugin->register();
