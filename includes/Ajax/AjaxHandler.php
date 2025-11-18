<?php
/**
 * AJAX Handler Class
 *
 * Handles AJAX requests with nonce validation and rate limiting.
 *
 * @package MondaysWork\AI\Core
 * @subpackage Ajax
 * @since 1.0.0
 */

namespace MondaysWork\AI\Ajax;

use MondaysWork\AI\Core\Security\RateLimiter;

/**
 * Class AjaxHandler
 *
 * Manages AJAX requests with built-in security features including
 * nonce validation, capability checks, and rate limiting.
 *
 * @since 1.0.0
 */
class AjaxHandler {

	/**
	 * Rate limiter instance.
	 *
	 * @since 1.0.0
	 * @var RateLimiter
	 */
	private $rate_limiter;

	/**
	 * Nonce action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const NONCE_ACTION = 'mondays_work_ai_ajax_nonce';

	/**
	 * Nonce name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const NONCE_NAME = 'nonce';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->rate_limiter = new RateLimiter();
		$this->register_hooks();
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_hooks() {
		// Register AJAX actions.
		add_action( 'wp_ajax_mondays_work_ai_process', array( $this, 'handle_process_request' ) );
		add_action( 'wp_ajax_mondays_work_ai_get_status', array( $this, 'handle_get_status' ) );
		add_action( 'wp_ajax_mondays_work_ai_test_connection', array( $this, 'handle_test_connection' ) );

		// Enqueue scripts with nonce.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_ajax_scripts' ) );
	}

	/**
	 * Enqueues AJAX scripts with localized nonce.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_ajax_scripts( $hook_suffix ) {
		// Only load on plugin pages.
		if ( strpos( $hook_suffix, 'mondays-work-ai' ) === false ) {
			return;
		}

		wp_localize_script(
			'mondays-work-ai-admin',
			'mondaysWorkAI',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
			)
		);
	}

	/**
	 * Validates AJAX request security.
	 *
	 * @since 1.0.0
	 * @param string $capability Required user capability.
	 * @param bool   $check_rate_limit Whether to check rate limits.
	 * @return bool True if validation passes, sends error and dies otherwise.
	 */
	private function validate_request( $capability = 'manage_options', $check_rate_limit = true ) {
		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || 
			 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			$this->send_error( 'Invalid security token.', 'invalid_nonce', 403 );
		}

		// Check user capability.
		if ( ! current_user_can( $capability ) ) {
			$this->send_error( 'Insufficient permissions.', 'insufficient_permissions', 403 );
		}

		// Check rate limit.
		if ( $check_rate_limit ) {
			$identifier = RateLimiter::get_request_identifier();
			$action     = $this->get_current_action();

			if ( ! $this->rate_limiter->is_allowed( $identifier, $action, 30, 60 ) ) {
				$time_until_reset = $this->rate_limiter->get_time_until_reset( $identifier, $action );
				$this->send_error(
					sprintf( 'Rate limit exceeded. Please try again in %d seconds.', $time_until_reset ),
					'rate_limit_exceeded',
					429
				);
			}
		}

		return true;
	}

	/**
	 * Gets the current AJAX action name.
	 *
	 * @since 1.0.0
	 * @return string Current action name.
	 */
	private function get_current_action() {
		return isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : 'unknown';
	}

	/**
	 * Handles process request.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_process_request() {
		$this->validate_request();

		try {
			// Get and validate input.
			$input = $this->get_post_parameter( 'input', '' );
			
			if ( empty( $input ) ) {
				$this->send_error( 'Input is required.', 'missing_input', 400 );
			}

			// Process the request.
			$result = $this->process_ai_request( $input );

			$this->send_success(
				array(
					'result'  => $result,
					'message' => 'Request processed successfully.',
				)
			);

		} catch ( \Exception $e ) {
			$this->send_error( $e->getMessage(), 'processing_error', 500 );
		}
	}

	/**
	 * Handles get status request.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_get_status() {
		$this->validate_request( 'manage_options', false );

		try {
			$status = array(
				'connected'    => true,
				'api_key'      => get_option( 'mondays_work_ai_api_key' ) ? true : false,
				'last_request' => get_option( 'mondays_work_ai_last_request' ),
			);

			$this->send_success( $status );

		} catch ( \Exception $e ) {
			$this->send_error( $e->getMessage(), 'status_error', 500 );
		}
	}

	/**
	 * Handles test connection request.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_test_connection() {
		$this->validate_request();

		try {
			// Test API connection.
			$connection_status = $this->test_api_connection();

			if ( $connection_status ) {
				$this->send_success(
					array(
						'message' => 'Connection successful.',
						'status'  => $connection_status,
					)
				);
			} else {
				$this->send_error( 'Connection failed.', 'connection_failed', 500 );
			}

		} catch ( \Exception $e ) {
			$this->send_error( $e->getMessage(), 'connection_error', 500 );
		}
	}

	/**
	 * Processes AI request (placeholder - implement your logic).
	 *
	 * @since 1.0.0
	 * @param string $input User input.
	 * @return array Processing result.
	 */
	private function process_ai_request( $input ) {
		// Implement your AI processing logic here.
		return array(
			'output'    => 'Processed: ' . $input,
			'timestamp' => current_time( 'mysql' ),
		);
	}

	/**
	 * Tests API connection (placeholder - implement your logic).
	 *
	 * @since 1.0.0
	 * @return array|false Connection status or false on failure.
	 */
	private function test_api_connection() {
		// Implement your API connection test logic here.
		return array(
			'status'  => 'connected',
			'version' => '1.0.0',
		);
	}

	/**
	 * Gets a POST parameter with sanitization.
	 *
	 * @since 1.0.0
	 * @param string $key Parameter key.
	 * @param mixed  $default Default value if parameter not found.
	 * @return mixed Sanitized parameter value.
	 */
	private function get_post_parameter( $key, $default = null ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			return $default;
		}

		// Handle arrays.
		if ( is_array( $_POST[ $key ] ) ) {
			return array_map( 'sanitize_text_field', wp_unslash( $_POST[ $key ] ) );
		}

		return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
	}

	/**
	 * Sends success response and terminates.
	 *
	 * @since 1.0.0
	 * @param mixed $data Response data.
	 * @return void
	 */
	private function send_success( $data = null ) {
		wp_send_json_success( $data );
	}

	/**
	 * Sends error response and terminates.
	 *
	 * @since 1.0.0
	 * @param string $message Error message.
	 * @param string $code Error code.
	 * @param int    $status_code HTTP status code.
	 * @return void
	 */
	private function send_error( $message, $code = 'error', $status_code = 400 ) {
		status_header( $status_code );
		wp_send_json_error(
			array(
				'message' => $message,
				'code'    => $code,
			)
		);
	}

	/**
	 * Creates a nonce for AJAX requests.
	 *
	 * @since 1.0.0
	 * @return string Nonce value.
	 */
	public static function create_nonce() {
		return wp_create_nonce( self::NONCE_ACTION );
	}

	/**
	 * Verifies a nonce for AJAX requests.
	 *
	 * @since 1.0.0
	 * @param string $nonce Nonce to verify.
	 * @return bool True if nonce is valid, false otherwise.
	 */
	public static function verify_nonce( $nonce ) {
		return wp_verify_nonce( $nonce, self::NONCE_ACTION ) !== false;
	}
}
