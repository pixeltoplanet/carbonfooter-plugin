<?php

/**
 * Main Plugin Controller
 *
 * Central orchestration layer that wires together all subsystems
 * (admin, AJAX, REST, shortcodes, background tasks) and registers
 * plugin-wide hooks.
 *
 * Responsibilities:
 * - Instantiate core components and handlers
 * - Register WordPress hooks for lifecycle events
 * - Expose accessors to collaborating components
 *
 * Performance:
 * - Defers heavy work to dedicated handlers/services
 * - Keeps controller narrow and focused on wiring
 *
 * Security:
 * - Sensitive operations are authorized within their respective handlers
 * - Controller only delegates and does not output
 *
 * @package CarbonFooter
 */

namespace CarbonfooterPlugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin class - serves as the central controller
 */
class Plugin {


	/**
	 * Single instance of the plugin
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	public const VERSION = '0.19.0';

	/**
	 * Core components
	 */
	private Emissions $emissions_handler;
	private Cache $cache_manager;
	private Shortcodes $shortcode_manager;
	private Background_Processor $background_processor;
	private AdminHandler $admin_handler;
	private AjaxHandler $ajax_handler;
	private RestApiHandler $rest_api_handler;
	private HooksManager $hooks_manager;

	/**
	 * Retrieve the singleton instance.
	 *
	 * Why:
	 * - Avoids multiple controller instances and duplicated hooks.
	 *
	 * @return Plugin The single, lazily-initialized controller instance
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * Structure:
	 * - Initializes components
	 * - Registers plugin-level hooks
	 */
	private function __construct() {
		$this->initialize_components();
		$this->setup_hooks();
	}

	/**
	 * Initialize all plugin components.
	 *
	 * Why:
	 * - Builds all collaborating services and handlers in one place, enabling
	 *   dependency injection and easier testing.
	 *
	 * @return void
	 */
	private function initialize_components(): void {
		// Core components
		$this->emissions_handler    = new Emissions();
		$this->cache_manager        = new Cache();
		$this->shortcode_manager    = new Shortcodes();
		$this->background_processor = new Background_Processor();

		// Handler components
		$this->admin_handler    = new AdminHandler( $this->emissions_handler );
		$this->ajax_handler     = new AjaxHandler( $this->emissions_handler, $this->cache_manager );
		$this->rest_api_handler = new RestApiHandler();
		$this->hooks_manager    = new HooksManager( $this->get_all_handlers() );
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * Structure:
	 * - Delegates registration to `HooksManager`
	 * - Adds plugin-level admin initialization hooks
	 *
	 * @return void
	 */
	private function setup_hooks(): void {
		$this->hooks_manager->register_all_hooks();

		// Plugin-level hooks
		add_action( 'admin_init', array( $this, 'handle_database_setup' ) );
		add_action( 'admin_init', array( $this, 'handle_activation_redirect' ) );
	}

	/**
	 * Get all handler instances for dependency injection.
	 *
	 * @return array<string, object> Map of handler keys to instances
	 */
	private function get_all_handlers(): array {
		return array(
			'admin'    => $this->admin_handler,
			'ajax'     => $this->ajax_handler,
			'rest_api' => $this->rest_api_handler,
		);
	}

	/**
	 * Handle database setup and optimization.
	 *
	 * Why:
	 * - Ensures indices exist to keep data access fast on larger sites.
	 *
	 * Side effects:
	 * - May create database indices via `Database_Optimizer::add_performance_indices()`
	 *
	 * @return void
	 */
	public function handle_database_setup(): void {
		Database_Optimizer::add_performance_indices();
	}

	/**
	 * Handle activation redirect.
	 *
	 * Why:
	 * - Improves UX by guiding admins to the settings page post-activation.
	 *
	 * Structure:
	 * - Uses transient to perform a one-time redirect
	 * - Avoids redirect loops by inspecting current page
	 *
	 * @return void
	 */
	public function handle_activation_redirect(): void {
		$redirect_transient = get_transient( 'carbonfooter_activation_redirect' );

		if ( ! $redirect_transient ) {
			return;
		}

		Logger::log( 'Checking activation redirect transient: exists' );
		delete_transient( 'carbonfooter_activation_redirect' );
		Logger::log( 'Deleting activation redirect transient' );

		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		Logger::log( "Current page: $current_page" );

		if ( ! in_array( $current_page, array( 'carbonfooter', 'carbonfooter-settings' ) ) ) {
			Logger::log( 'Redirecting to settings page' );
			wp_safe_redirect( admin_url( 'admin.php?page=carbonfooter-settings' ) );
			exit;
		}

		Logger::log( 'Already on CarbonFooter page, not redirecting' );
	}

	/**
	 * Get emissions handler instance.
	 *
	 * @return Emissions Service for emissions measurement and stats
	 */
	public function get_emissions_handler(): Emissions {
		return $this->emissions_handler;
	}

	/**
	 * Get cache manager instance.
	 *
	 * @return Cache Light-weight cache wrapper service
	 */
	public function get_cache_manager(): Cache {
		return $this->cache_manager;
	}

	/**
	 * Get admin handler instance.
	 *
	 * @return AdminHandler Admin UI and menus controller
	 */
	public function get_admin_handler(): AdminHandler {
		return $this->admin_handler;
	}

	/**
	 * Get AJAX handler instance.
	 *
	 * @return AjaxHandler Secured admin-ajax endpoints controller
	 */
	public function get_ajax_handler(): AjaxHandler {
		return $this->ajax_handler;
	}

	/**
	 * Get REST API handler instance.
	 *
	 * @return RestApiHandler REST endpoints controller
	 */
	public function get_rest_api_handler(): RestApiHandler {
		return $this->rest_api_handler;
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization
	 */
	public function __wakeup() {}
}
