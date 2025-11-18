<?php
/**
 * Plugin Name: Monday's Work AI Core
 * Plugin URI: https://github.com/yourusername/mondays-work-ai-core
 * Description: Advanced AI integration core plugin with security features, encryption, and rate limiting for WordPress.
 * Version: 1.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Your Name
 * Author URI: https://layers.tv
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mondays-work-ai-core
 * Domain Path: /languages
 *
 * @package MondaysWork\AI\Core
 */

namespace MondaysWork\AI;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants.
 */
define( 'MONDAYS_WORK_AI_VERSION', '1.0.1' );
define( 'MONDAYS_WORK_AI_PLUGIN_FILE', __FILE__ );
define( 'MONDAYS_WORK_AI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MONDAYS_WORK_AI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MONDAYS_WORK_AI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * PSR-4 Autoloader.
 *
 * @param string $class Class name to load.
 * @return void
 */
spl_autoload_register(
	function ( $class ) {
		$prefix   = 'MondaysWork\\AI\\';
		$base_dir = MONDAYS_WORK_AI_PLUGIN_DIR . 'includes/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * Main Plugin Class
 */
class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * AJAX Handler instance.
	 *
	 * @var Ajax\AjaxHandler
	 */
	private $ajax_handler;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		register_activation_hook( MONDAYS_WORK_AI_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( MONDAYS_WORK_AI_PLUGIN_FILE, array( $this, 'deactivate' ) );
		
		add_action( 'plugins_loaded', array( $this, 'load_plugin' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load plugin functionality.
	 *
	 * @return void
	 */
	public function load_plugin() {
		// Check requirements.
		if ( ! $this->check_requirements() ) {
			return;
		}

		// Initialize AJAX handler.
		$this->ajax_handler = new Ajax\AjaxHandler();

		// Hook for other components to initialize.
		do_action( 'mondays_work_ai_loaded' );
	}

	/**
	 * Load plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'mondays-work-ai-core',
			false,
			dirname( MONDAYS_WORK_AI_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Check plugin requirements.
	 *
	 * @return bool
	 */
	private function check_requirements() {
		$requirements_met = true;

		// Check PHP version.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			$requirements_met = false;
		}

		// Check OpenSSL extension.
		if ( ! extension_loaded( 'openssl' ) ) {
			add_action( 'admin_notices', array( $this, 'openssl_notice' ) );
			$requirements_met = false;
		}

		return $requirements_met;
	}

	/**
	 * Display PHP version notice.
	 *
	 * @return void
	 */
	public function php_version_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: Required PHP version, 2: Current PHP version */
						__( 'Monday\'s Work AI Core requires PHP version %1$s or higher. You are running version %2$s.', 'mondays-work-ai-core' ),
						'7.4',
						PHP_VERSION
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Display OpenSSL extension notice.
	 *
	 * @return void
	 */
	public function openssl_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				echo esc_html__( 'Monday\'s Work AI Core requires the OpenSSL PHP extension to be installed and enabled.', 'mondays-work-ai-core' );
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Plugin activation.
	 *
	 * @return void
	 */
	public function activate() {
		// Check requirements on activation.
		if ( ! $this->check_requirements() ) {
			deactivate_plugins( MONDAYS_WORK_AI_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'Monday\'s Work AI Core cannot be activated due to unmet requirements.', 'mondays-work-ai-core' ),
				esc_html__( 'Plugin Activation Error', 'mondays-work-ai-core' ),
				array( 'back_link' => true )
			);
		}

		// Set default options.
		$this->set_default_options();

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Set activation flag.
		set_transient( 'mondays_work_ai_activated', true, 30 );
	}

	/**
	 * Set default plugin options.
	 *
	 * @return void
	 */
	private function set_default_options() {
		$defaults = array(
			'mondays_work_ai_version'        => MONDAYS_WORK_AI_VERSION,
			'mondays_work_ai_installed_date' => current_time( 'mysql' ),
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}

	/**
	 * Plugin deactivation.
	 *
	 * @return void
	 */
	public function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();

		// Clean up transients.
		delete_transient( 'mondays_work_ai_activated' );

		// Hook for cleanup actions.
		do_action( 'mondays_work_ai_deactivated' );
	}

	/**
	 * Get AJAX handler instance.
	 *
	 * @return Ajax\AjaxHandler|null
	 */
	public function get_ajax_handler() {
		return $this->ajax_handler;
	}
}

/**
 * Initialize the plugin.
 *
 * @return Plugin
 */
function mondays_work_ai() {
	return Plugin::get_instance();
}

// Start the plugin.
mondays_work_ai();
