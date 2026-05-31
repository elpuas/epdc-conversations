<?php
/**
 * Main plugin bootstrap service.
 *
 * @package EPDC\Conversations\Infrastructure
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Infrastructure;

use EPDC\Conversations\Admin\SettingsPage;
use EPDC\Conversations\Frontend\Renderer;
use EPDC\Conversations\Messaging\MessageParser;
use EPDC\Conversations\Tracking\TrackingService;

final class Plugin {
	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * Services.
	 *
	 * @var ServiceInterface[]
	 */
	private array $services;

	public function __construct( string $plugin_file ) {
		$settings         = new Settings();
		$message_parser   = new MessageParser();
		$tracking_service = new TrackingService();

		$this->plugin_file = $plugin_file;
		$this->services    = [
			new I18n( $this->plugin_file ),
			new Assets( $this->plugin_file ),
			new SettingsPage( $settings ),
			new Renderer( $settings, $message_parser, $tracking_service ),
		];
	}

	public function register(): void {
		foreach ( $this->services as $service ) {
			$service->register();
		}
	}
}
