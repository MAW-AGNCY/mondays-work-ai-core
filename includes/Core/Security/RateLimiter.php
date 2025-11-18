<?php
/**
 * Rate Limiter Class
 *
 * Implements rate limiting functionality using WordPress transients.
 *
 * @package MondaysWork\AI\Core
 * @subpackage Security
 * @since 1.0.0
 */

namespace MondaysWork\AI\Core\Security;

/**
 * Class RateLimiter
 *
 * Provides rate limiting functionality to prevent abuse of API endpoints
 * and resource-intensive operations using WordPress transients for storage.
 *
 * @since 1.0.0
 */
class RateLimiter {

	/**
	 * Transient prefix.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const TRANSIENT_PREFIX = 'mondays_work_ai_rate_limit_';

	/**
	 * Default maximum attempts.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private const DEFAULT_MAX_ATTEMPTS = 60;

	/**
	 * Default time window in seconds (1 minute).
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private const DEFAULT_TIME_WINDOW = 60;

	/**
	 * Checks if a request is allowed based on rate limits.
	 *
	 * @since 1.0.0
	 * @param string $identifier Unique identifier for the rate limit (e.g., user ID, IP address).
	 * @param string $action Action being rate limited.
	 * @param int    $max_attempts Maximum number of attempts allowed.
	 * @param int    $time_window Time window in seconds.
	 * @return bool True if request is allowed, false if rate limit exceeded.
	 */
	public function is_allowed( $identifier, $action = 'default', $max_attempts = null, $time_window = null ) {
		if ( empty( $identifier ) || empty( $action ) ) {
			return false;
		}

		$max_attempts = $max_attempts ?? self::DEFAULT_MAX_ATTEMPTS;
		$time_window  = $time_window ?? self::DEFAULT_TIME_WINDOW;

		$transient_key = $this->get_transient_key( $identifier, $action );
		$attempts      = get_transient( $transient_key );

		// If no transient exists, this is the first attempt.
		if ( false === $attempts ) {
			$this->record_attempt( $transient_key, $time_window );
			return true;
		}

		// Check if limit exceeded.
		if ( $attempts >= $max_attempts ) {
			$this->log_rate_limit_exceeded( $identifier, $action, $attempts );
			return false;
		}

		// Increment attempt counter.
		$this->record_attempt( $transient_key, $time_window, $attempts );
		return true;
	}

	/**
	 * Records an attempt for rate limiting.
	 *
	 * @since 1.0.0
	 * @param string $transient_key Transient key.
	 * @param int    $time_window Time window in seconds.
	 * @param int    $current_attempts Current attempt count.
	 * @return bool True on success, false on failure.
	 */
	private function record_attempt( $transient_key, $time_window, $current_attempts = 0 ) {
		$new_attempts = $current_attempts + 1;
		return set_transient( $transient_key, $new_attempts, $time_window );
	}

	/**
	 * Gets the remaining attempts for an identifier.
	 *
	 * @since 1.0.0
	 * @param string $identifier Unique identifier for the rate limit.
	 * @param string $action Action being rate limited.
	 * @param int    $max_attempts Maximum number of attempts allowed.
	 * @return int Number of remaining attempts.
	 */
	public function get_remaining_attempts( $identifier, $action = 'default', $max_attempts = null ) {
		if ( empty( $identifier ) || empty( $action ) ) {
			return 0;
		}

		$max_attempts  = $max_attempts ?? self::DEFAULT_MAX_ATTEMPTS;
		$transient_key = $this->get_transient_key( $identifier, $action );
		$attempts      = get_transient( $transient_key );

		if ( false === $attempts ) {
			return $max_attempts;
		}

		$remaining = $max_attempts - (int) $attempts;
		return max( 0, $remaining );
	}

	/**
	 * Gets the time until rate limit reset.
	 *
	 * @since 1.0.0
	 * @param string $identifier Unique identifier for the rate limit.
	 * @param string $action Action being rate limited.
	 * @return int|false Time in seconds until reset, or false if no limit active.
	 */
	public function get_time_until_reset( $identifier, $action = 'default' ) {
		if ( empty( $identifier ) || empty( $action ) ) {
			return false;
		}

		$transient_key     = $this->get_transient_key( $identifier, $action );
		$transient_timeout = get_option( '_transient_timeout_' . $transient_key );

		if ( false === $transient_timeout ) {
			return false;
		}

		$time_until_reset = $transient_timeout - time();
		return max( 0, $time_until_reset );
	}

	/**
	 * Resets the rate limit for an identifier.
	 *
	 * @since 1.0.0
	 * @param string $identifier Unique identifier for the rate limit.
	 * @param string $action Action being rate limited.
	 * @return bool True on success, false on failure.
	 */
	public function reset( $identifier, $action = 'default' ) {
		if ( empty( $identifier ) || empty( $action ) ) {
			return false;
		}

		$transient_key = $this->get_transient_key( $identifier, $action );
		return delete_transient( $transient_key );
	}

	/**
	 * Clears all rate limit data for an identifier.
	 *
	 * @since 1.0.0
	 * @param string $identifier Unique identifier for the rate limit.
	 * @return int Number of transients deleted.
	 */
	public function clear_all( $identifier ) {
		global $wpdb;

		if ( empty( $identifier ) ) {
			return 0;
		}

		$pattern = self::TRANSIENT_PREFIX . sanitize_key( $identifier ) . '_%';
		
		// Delete transients.
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} 
				WHERE option_name LIKE %s 
				OR option_name LIKE %s",
				'_transient_' . $pattern,
				'_transient_timeout_' . $pattern
			)
		);

		return (int) $deleted;
	}

	/**
	 * Generates a transient key for rate limiting.
	 *
	 * @since 1.0.0
	 * @param string $identifier Unique identifier.
	 * @param string $action Action being rate limited.
	 * @return string Transient key.
	 */
	private function get_transient_key( $identifier, $action ) {
		$sanitized_identifier = sanitize_key( $identifier );
		$sanitized_action     = sanitize_key( $action );
		
		return self::TRANSIENT_PREFIX . $sanitized_identifier . '_' . $sanitized_action;
	}

	/**
	 * Gets a unique identifier for the current request.
	 *
	 * @since 1.0.0
	 * @return string Unique identifier (user ID or IP address).
	 */
	public static function get_request_identifier() {
		// Use user ID if logged in.
		if ( is_user_logged_in() ) {
			return 'user_' . get_current_user_id();
		}

		// Fall back to IP address.
		return 'ip_' . self::get_client_ip();
	}

	/**
	 * Gets the client's IP address.
	 *
	 * @since 1.0.0
	 * @return string Client IP address.
	 */
	private static function get_client_ip() {
		$ip_keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				
				// Handle multiple IPs (take the first one).
				if ( strpos( $ip, ',' ) !== false ) {
					$ip_list = explode( ',', $ip );
					$ip      = trim( $ip_list[0] );
				}

				// Validate IP address.
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Logs rate limit exceeded event.
	 *
	 * @since 1.0.0
	 * @param string $identifier Unique identifier.
	 * @param string $action Action being rate limited.
	 * @param int    $attempts Number of attempts made.
	 * @return void
	 */
	private function log_rate_limit_exceeded( $identifier, $action, $attempts ) {
		$message = sprintf(
			'Rate limit exceeded - Identifier: %s, Action: %s, Attempts: %d',
			$identifier,
			$action,
			$attempts
		);

		error_log( $message );

		/**
		 * Fires when rate limit is exceeded.
		 *
		 * @since 1.0.0
		 * @param string $identifier Unique identifier.
		 * @param string $action Action being rate limited.
		 * @param int    $attempts Number of attempts made.
		 */
		do_action( 'mondays_work_ai_rate_limit_exceeded', $identifier, $action, $attempts );
	}

	/**
	 * Creates a rate limit response array.
	 *
	 * @since 1.0.0
	 * @param string $identifier Unique identifier.
	 * @param string $action Action being rate limited.
	 * @param int    $max_attempts Maximum number of attempts allowed.
	 * @return array Rate limit status information.
	 */
	public function get_status( $identifier, $action = 'default', $max_attempts = null ) {
		$max_attempts = $max_attempts ?? self::DEFAULT_MAX_ATTEMPTS;
		
		return array(
			'allowed'           => $this->is_allowed( $identifier, $action, $max_attempts ),
			'remaining'         => $this->get_remaining_attempts( $identifier, $action, $max_attempts ),
			'max_attempts'      => $max_attempts,
			'time_until_reset'  => $this->get_time_until_reset( $identifier, $action ),
		);
	}
}
