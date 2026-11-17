<?php

/**
 * Site Health Class
 *
 * Adds Carbonfooter checks to Tools → Site Health and a Carbonfooter
 * section to the Site Health "Info" tab (useful in support requests).
 *
 * Tests:
 * - Data collection enabled (direct)
 * - Background measurements not stuck in WP-Cron (direct)
 * - Carbonfooter API reachable (async, via REST)
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SiteHealth
 */
class SiteHealth {

	/**
	 * Overdue threshold for scheduled measurements (seconds).
	 */
	private const CRON_OVERDUE_AFTER = HOUR_IN_SECONDS;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'site_status_tests', array( $this, 'register_tests' ) );
		add_filter( 'debug_information', array( $this, 'add_debug_information' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register Site Health tests.
	 *
	 * @param array $tests Existing tests.
	 * @return array
	 */
	public function register_tests( array $tests ): array {
		$tests['direct']['carbonfooter_data_collection'] = array(
			'label' => __( 'Carbonfooter data collection', 'carbonfooter' ),
			'test'  => array( $this, 'test_data_collection' ),
		);

		$tests['direct']['carbonfooter_cron'] = array(
			'label' => __( 'Carbonfooter background measurements', 'carbonfooter' ),
			'test'  => array( $this, 'test_cron' ),
		);

		$tests['async']['carbonfooter_api'] = array(
			'label'             => __( 'Carbonfooter API connection', 'carbonfooter' ),
			'test'              => rest_url( Constants::API_NAMESPACE . '/site-health/api' ),
			'has_rest'          => true,
			'async_direct_test' => array( $this, 'test_api' ),
		);

		return $tests;
	}

	/**
	 * REST route backing the async API test.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			Constants::API_NAMESPACE,
			'/site-health/api',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'test_api' ),
				'permission_callback' => static function () {
					return current_user_can( 'view_site_health_checks' );
				},
			)
		);
	}

	/**
	 * Base result shape shared by all tests.
	 *
	 * @param string $test        Test identifier.
	 * @param string $status      good|recommended|critical.
	 * @param string $label       Result headline.
	 * @param string $description HTML description.
	 * @param string $actions     Optional HTML actions.
	 * @return array
	 */
	private function result( string $test, string $status, string $label, string $description, string $actions = '' ): array {
		return array(
			'label'       => $label,
			'status'      => $status,
			'badge'       => array(
				'label' => __( 'Carbonfooter', 'carbonfooter' ),
				'color' => 'good' === $status ? 'green' : 'orange',
			),
			'description' => $description,
			'actions'     => $actions,
			'test'        => $test,
		);
	}

	/**
	 * Settings page link for test actions.
	 *
	 * @return string
	 */
	private function settings_link(): string {
		return sprintf(
			'<p><a href="%s">%s</a></p>',
			esc_url( admin_url( 'admin.php?page=carbonfooter-settings' ) ),
			esc_html__( 'Open Carbonfooter settings', 'carbonfooter' )
		);
	}

	/**
	 * Test: data collection is enabled.
	 *
	 * @return array
	 */
	public function test_data_collection(): array {
		$enabled = (bool) get_option( Constants::OPTION_DATA_COLLECTION_ENABLED, Constants::DEFAULT_DATA_COLLECTION_ENABLED );

		if ( $enabled ) {
			return $this->result(
				'carbonfooter_data_collection',
				'good',
				__( 'Carbonfooter is measuring your pages', 'carbonfooter' ),
				'<p>' . esc_html__( 'Page emissions are measured in the background and refreshed automatically.', 'carbonfooter' ) . '</p>'
			);
		}

		return $this->result(
			'carbonfooter_data_collection',
			'recommended',
			__( 'Carbonfooter data collection is turned off', 'carbonfooter' ),
			'<p>' . esc_html__( 'No new measurements are made, so the emissions shown on your site may be outdated or based on the site average. Turn data collection back on if you want up-to-date numbers.', 'carbonfooter' ) . '</p>',
			$this->settings_link()
		);
	}

	/**
	 * Count overdue Carbonfooter cron events.
	 *
	 * @return int
	 */
	private function count_overdue_events(): int {
		if ( ! function_exists( '_get_cron_array' ) ) {
			return 0;
		}

		$crons   = _get_cron_array();
		$cutoff  = time() - self::CRON_OVERDUE_AFTER;
		$overdue = 0;

		foreach ( (array) $crons as $timestamp => $hooks ) {
			if ( $timestamp > $cutoff ) {
				break; // The cron array is sorted by timestamp.
			}
			if ( isset( $hooks['carbonfooter_process_emissions'] ) ) {
				$overdue += count( $hooks['carbonfooter_process_emissions'] );
			}
		}

		return $overdue;
	}

	/**
	 * Test: scheduled measurements are not piling up.
	 *
	 * @return array
	 */
	public function test_cron(): array {
		$overdue       = $this->count_overdue_events();
		$cron_disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;

		if ( 0 === $overdue ) {
			return $this->result(
				'carbonfooter_cron',
				'good',
				__( 'Carbonfooter background measurements are running', 'carbonfooter' ),
				'<p>' . esc_html__( 'No measurements are waiting in the WP-Cron queue.', 'carbonfooter' ) . '</p>'
			);
		}

		$description = '<p>' . esc_html(
			sprintf(
				/* translators: %d: number of overdue scheduled measurements */
				_n(
					'%d page measurement has been waiting in WP-Cron for over an hour.',
					'%d page measurements have been waiting in WP-Cron for over an hour.',
					$overdue,
					'carbonfooter'
				),
				$overdue
			)
		) . '</p>';

		if ( $cron_disabled ) {
			$description .= '<p>' . esc_html__( 'DISABLE_WP_CRON is set. Make sure a real cron job calls wp-cron.php regularly, otherwise emissions are only refreshed when an editor visits a page.', 'carbonfooter' ) . '</p>';
		}

		return $this->result(
			'carbonfooter_cron',
			'recommended',
			__( 'Carbonfooter measurements are delayed', 'carbonfooter' ),
			$description
		);
	}

	/**
	 * Test: the Carbonfooter API is reachable.
	 *
	 * Skips the network request when data collection is disabled, so no
	 * request is made to carbonfooter.nl without consent.
	 *
	 * @return array
	 */
	public function test_api(): array {
		if ( ! get_option( Constants::OPTION_DATA_COLLECTION_ENABLED, Constants::DEFAULT_DATA_COLLECTION_ENABLED ) ) {
			return $this->result(
				'carbonfooter_api',
				'good',
				__( 'Carbonfooter API check skipped', 'carbonfooter' ),
				'<p>' . esc_html__( 'Data collection is turned off, so the connection to carbonfooter.nl was not tested.', 'carbonfooter' ) . '</p>'
			);
		}

		// Probe the API host root: the bare /api endpoint errors without a target URL.
		$parts = wp_parse_url( Constants::API_BASE_URL );
		$probe = ( $parts['scheme'] ?? 'https' ) . '://' . ( $parts['host'] ?? 'carbonfooter.nl' ) . '/';

		$response = wp_remote_head(
			$probe,
			array(
				'timeout'    => 10,
				'user-agent' => 'WordPress/CarbonFooter-Plugin/' . CARBONFOOTER_VERSION,
			)
		);

		$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

		if ( $code > 0 && $code < 500 ) {
			return $this->result(
				'carbonfooter_api',
				'good',
				__( 'Your site can reach the Carbonfooter API', 'carbonfooter' ),
				'<p>' . esc_html__( 'Emissions can be measured through carbonfooter.nl.', 'carbonfooter' ) . '</p>'
			);
		}

		$reason = is_wp_error( $response )
			? $response->get_error_message()
			/* translators: %d: HTTP status code */
			: sprintf( __( 'HTTP status %d', 'carbonfooter' ), $code );

		return $this->result(
			'carbonfooter_api',
			'recommended',
			__( 'Your site cannot reach the Carbonfooter API', 'carbonfooter' ),
			'<p>' . esc_html__( 'New measurements will fail until the connection works. A firewall or outgoing-request block on your host is the most common cause.', 'carbonfooter' ) . '</p><p><code>' . esc_html( $reason ) . '</code></p>'
		);
	}

	/**
	 * Add a Carbonfooter section to Site Health → Info.
	 *
	 * @param array $info Debug information.
	 * @return array
	 */
	public function add_debug_information( array $info ): array {
		$emissions      = new Emissions();
		$last_processed = (int) get_option( 'carbonfooter_last_processed', 0 );
		$yes            = __( 'Yes', 'carbonfooter' );
		$no             = __( 'No', 'carbonfooter' );

		$info['carbonfooter'] = array(
			'label'  => __( 'Carbonfooter', 'carbonfooter' ),
			'fields' => array(
				'version'          => array(
					'label' => __( 'Version', 'carbonfooter' ),
					'value' => CARBONFOOTER_VERSION,
				),
				'data_collection'  => array(
					'label' => __( 'Data collection enabled', 'carbonfooter' ),
					'value' => get_option( Constants::OPTION_DATA_COLLECTION_ENABLED, Constants::DEFAULT_DATA_COLLECTION_ENABLED ) ? $yes : $no,
					'debug' => (bool) get_option( Constants::OPTION_DATA_COLLECTION_ENABLED, Constants::DEFAULT_DATA_COLLECTION_ENABLED ),
				),
				'display_setting'  => array(
					'label' => __( 'Display', 'carbonfooter' ),
					'value' => (string) get_option( Constants::OPTION_DISPLAY_SETTING, Constants::DEFAULT_DISPLAY_SETTING ),
				),
				'widget_style'     => array(
					'label' => __( 'Widget style', 'carbonfooter' ),
					'value' => (string) get_option( Constants::OPTION_WIDGET_STYLE, Constants::DEFAULT_WIDGET_STYLE ),
				),
				'measured_pages'   => array(
					'label' => __( 'Measured pages', 'carbonfooter' ),
					'value' => (int) $emissions->get_total_measured_posts(),
				),
				'average'          => array(
					'label' => __( 'Average emissions per visit', 'carbonfooter' ),
					'value' => number_format( (float) $emissions->get_average_emissions(), 2 ) . ' g CO2',
				),
				'green_host'       => array(
					'label' => __( 'Green hosting', 'carbonfooter' ),
					'value' => get_option( Constants::OPTION_GREEN_HOST, false ) ? $yes : $no,
				),
				'last_processed'   => array(
					'label' => __( 'Last background measurement', 'carbonfooter' ),
					'value' => $last_processed ? wp_date( 'Y-m-d H:i', $last_processed ) : __( 'Never', 'carbonfooter' ),
				),
				'wp_cron_disabled' => array(
					'label' => __( 'WP-Cron disabled', 'carbonfooter' ),
					'value' => ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ? $yes : $no,
				),
			),
		);

		return $info;
	}
}
