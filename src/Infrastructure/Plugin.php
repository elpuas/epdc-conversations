<?php
/**
 * Main plugin bootstrap service.
 *
 * @package EPDC\Conversations\Infrastructure
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Infrastructure;

use EPDC\Conversations\Admin\AnalyticsPage;
use EPDC\Conversations\Admin\AnalyticsRepository;
use EPDC\Conversations\Admin\SettingsPage;
use EPDC\Conversations\Blocks\ConversationsBlock;
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
		$settings             = new Settings();
		$message_parser       = new MessageParser();
		$tracking_service     = new TrackingService();
		$analytics_repository = new AnalyticsRepository();
		$renderer             = new Renderer( $settings, $message_parser );

		$this->plugin_file = $plugin_file;
		$this->services    = [
			new Assets( $this->plugin_file, $settings, $tracking_service ),
			new SettingsPage( $settings ),
			new AnalyticsPage( $analytics_repository ),
			$tracking_service,
			$renderer,
			new ConversationsBlock( $this->plugin_file, $renderer ),
		];
	}

	public function register(): void {
		foreach ( $this->services as $service ) {
			$service->register();
		}
	}
}
