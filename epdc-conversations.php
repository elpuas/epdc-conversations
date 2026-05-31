<?php
/**
 * Plugin Name: EPDC Conversations
 * Description: Lightweight WhatsApp conversion-focused plugin scaffold.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.3
 * Author: EPDC
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: epdc-conversations
 * Domain Path: /languages
 *
 * @package EPDC\Conversations
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epdc_conversations_autoload = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $epdc_conversations_autoload ) ) {
	require_once $epdc_conversations_autoload;
} else {
	require_once __DIR__ . '/includes/autoload.php';
}

if ( ! class_exists( \EPDC\Conversations\Infrastructure\Plugin::class ) ) {
	return;
}

register_activation_hook( __FILE__, [ \EPDC\Conversations\Infrastructure\Lifecycle::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \EPDC\Conversations\Infrastructure\Lifecycle::class, 'deactivate' ] );

$epdc_conversations_plugin = new \EPDC\Conversations\Infrastructure\Plugin( __FILE__ );
$epdc_conversations_plugin->register();
