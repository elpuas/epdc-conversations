<?php
/**
 * Analytics admin page.
 *
 * @package EPDC\Conversations\Admin
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Admin;

use EPDC\Conversations\Infrastructure\ServiceInterface;

final class AnalyticsPage implements ServiceInterface {
	private const PAGE_SLUG = 'epdc-conversations-analytics';

	private AnalyticsRepository $repository;

	public function __construct( AnalyticsRepository $repository ) {
		$this->repository = $repository;
	}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			'epdc-conversations',
			esc_html__( 'Analytics', 'epdc-conversations' ),
			esc_html__( 'Analytics', 'epdc-conversations' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$selected_range = $this->get_selected_range();
		$metrics        = $this->repository->get_metrics();
		$top_pages      = $this->repository->get_top_pages( $selected_range );
		$recent_events  = $this->repository->get_recent_events( $selected_range );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Analytics', 'epdc-conversations' ); ?></h1>
			<p><?php echo esc_html__( 'WhatsApp conversion events captured by the tracking system.', 'epdc-conversations' ); ?></p>

			<?php $this->render_filter_navigation( $selected_range ); ?>

			<div class="epdc-conversations-analytics-metrics">
				<?php $this->render_metrics( $metrics ); ?>
			</div>

			<h2><?php echo esc_html__( 'Top Converting Pages', 'epdc-conversations' ); ?></h2>
			<?php $this->render_top_pages_table( $top_pages ); ?>

			<h2><?php echo esc_html__( 'Recent Events', 'epdc-conversations' ); ?></h2>
			<?php $this->render_recent_events_table( $recent_events ); ?>

			<?php
			/**
			 * Render extra analytics page sections.
			 *
			 * @param string                          $selected_range Active range key.
			 * @param array<string, int>              $metrics        Dashboard metrics.
			 * @param array<int, array<string, mixed>> $top_pages     Top pages rows.
			 * @param array<int, array<string, mixed>> $recent_events Recent events rows.
			 */
			do_action( 'epdc_conversations_analytics_page_sections', $selected_range, $metrics, $top_pages, $recent_events );
			?>
		</div>
		<style>
			.epdc-conversations-analytics-metrics {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
				gap: 12px;
				margin: 16px 0 24px;
			}

			.epdc-conversations-analytics-card {
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 4px;
				padding: 16px;
			}

			.epdc-conversations-analytics-card__label {
				color: #50575e;
				display: block;
				font-size: 13px;
				margin-bottom: 8px;
			}

			.epdc-conversations-analytics-card__value {
				font-size: 28px;
				font-weight: 600;
				line-height: 1.2;
				margin: 0;
			}

			@media (max-width: 782px) {
				.epdc-conversations-analytics-metrics {
					grid-template-columns: 1fr;
				}

				.epdc-conversations-analytics-table td,
				.epdc-conversations-analytics-table th {
					word-break: break-word;
				}
			}
		</style>
		<?php
	}

	/**
	 * Render metrics cards.
	 *
	 * @param array<string, int> $metrics Metrics keyed by period.
	 */
	private function render_metrics( array $metrics ): void {
		$items = [
			'total_clicks'        => esc_html__( 'Total WhatsApp clicks', 'epdc-conversations' ),
			'clicks_today'        => esc_html__( 'Clicks today', 'epdc-conversations' ),
			'clicks_last_7_days'  => esc_html__( 'Clicks last 7 days', 'epdc-conversations' ),
			'clicks_last_30_days' => esc_html__( 'Clicks last 30 days', 'epdc-conversations' ),
		];

		foreach ( $items as $key => $label ) :
			$value = isset( $metrics[ $key ] ) ? (int) $metrics[ $key ] : 0;
			?>
			<div class="epdc-conversations-analytics-card">
				<span class="epdc-conversations-analytics-card__label"><?php echo esc_html( $label ); ?></span>
				<p class="epdc-conversations-analytics-card__value"><?php echo esc_html( number_format_i18n( $value ) ); ?></p>
			</div>
			<?php
		endforeach;
	}

	/**
	 * Render range filter links.
	 */
	private function render_filter_navigation( string $selected_range ): void {
		$ranges = [
			'today'   => esc_html__( 'Today', 'epdc-conversations' ),
			'7days'   => esc_html__( 'Last 7 days', 'epdc-conversations' ),
			'30days'  => esc_html__( 'Last 30 days', 'epdc-conversations' ),
			'all'     => esc_html__( 'All time', 'epdc-conversations' ),
		];
		?>
		<ul class="subsubsub">
			<?php foreach ( $ranges as $range => $label ) : ?>
				<?php
				$url = add_query_arg(
					[
						'page'  => self::PAGE_SLUG,
						'range' => $range,
					],
					admin_url( 'admin.php' )
				);
				?>
				<li>
					<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $range === $selected_range ? 'current' : '' ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
					<?php if ( 'all' !== $range ) : ?>
						|
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<div class="clear"></div>
		<?php
	}

	/**
	 * Render top pages table.
	 *
	 * @param array<int, array<string, mixed>> $top_pages Rows.
	 */
	private function render_top_pages_table( array $top_pages ): void {
		?>
		<table class="widefat striped epdc-conversations-analytics-table">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Page URL', 'epdc-conversations' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Total clicks', 'epdc-conversations' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Last interaction date', 'epdc-conversations' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( [] === $top_pages ) : ?>
					<tr>
						<td colspan="3"><?php echo esc_html__( 'No tracked events found for this period.', 'epdc-conversations' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $top_pages as $row ) : ?>
						<tr>
							<td>
								<?php if ( '' !== (string) $row['page_url'] ) : ?>
									<a href="<?php echo esc_url( (string) $row['page_url'] ); ?>" target="_blank" rel="noreferrer noopener">
										<?php echo esc_html( (string) $row['page_url'] ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html__( 'Unknown', 'epdc-conversations' ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( number_format_i18n( (int) $row['total_clicks'] ) ); ?></td>
							<td><?php echo esc_html( $this->format_datetime( (string) $row['last_interaction'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render recent events table.
	 *
	 * @param array<int, array<string, mixed>> $recent_events Rows.
	 */
	private function render_recent_events_table( array $recent_events ): void {
		?>
		<table class="widefat striped epdc-conversations-analytics-table">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Timestamp', 'epdc-conversations' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Event type', 'epdc-conversations' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Page URL', 'epdc-conversations' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Device type', 'epdc-conversations' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'UTM source', 'epdc-conversations' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Session ID', 'epdc-conversations' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( [] === $recent_events ) : ?>
					<tr>
						<td colspan="6"><?php echo esc_html__( 'No tracked events found for this period.', 'epdc-conversations' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $recent_events as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $this->format_datetime( (string) $row['created_at'] ) ); ?></td>
							<td><?php echo esc_html( (string) $row['event_type'] ); ?></td>
							<td>
								<?php if ( '' !== (string) $row['page_url'] ) : ?>
									<a href="<?php echo esc_url( (string) $row['page_url'] ); ?>" target="_blank" rel="noreferrer noopener">
										<?php echo esc_html( (string) $row['page_url'] ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html__( 'Unknown', 'epdc-conversations' ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( (string) $row['device_type'] ); ?></td>
							<td><?php echo esc_html( (string) $row['utm_source'] ); ?></td>
							<td><?php echo esc_html( $this->truncate_session_id( (string) $row['session_id'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get the active range from the request.
	 */
	private function get_selected_range(): string {
		$range = isset( $_GET['range'] ) ? sanitize_key( wp_unslash( (string) $_GET['range'] ) ) : '7days';

		if ( in_array( $range, [ 'today', '7days', '30days', 'all' ], true ) ) {
			return $range;
		}

		return '7days';
	}

	/**
	 * Format a stored UTC datetime for admin display.
	 */
	private function format_datetime( string $datetime ): string {
		if ( '' === $datetime ) {
			return esc_html__( 'Unknown', 'epdc-conversations' );
		}

		$timestamp = mysql2date( 'U', $datetime, true );

		if ( false === $timestamp ) {
			return esc_html__( 'Unknown', 'epdc-conversations' );
		}

		return wp_date(
			sprintf(
				'%s %s',
				get_option( 'date_format' ),
				get_option( 'time_format' )
			),
			(int) $timestamp
		);
	}

	/**
	 * Truncate session identifiers for privacy-conscious display.
	 */
	private function truncate_session_id( string $session_id ): string {
		if ( '' === $session_id ) {
			return esc_html__( 'Unknown', 'epdc-conversations' );
		}

		if ( strlen( $session_id ) <= 16 ) {
			return $session_id;
		}

		return substr( $session_id, 0, 8 ) . '...' . substr( $session_id, -4 );
	}
}
