<?php
/**
 * Encryption Class
 *
 * Handles secure encryption and decryption of sensitive data using AES-256-CBC.
 *
 * @package MondaysWork\AI\Core
 * @subpackage Security
 * @since 1.0.0
 */

namespace MondaysWork\AI\Core\Security;

use Exception;

/**
 * Class Encryption
 *
 * Provides encryption and decryption functionality for sensitive data
 * such as API keys using AES-256-CBC encryption algorithm.
 *
 * @since 1.0.0
 */
class Encryption {

	/**
	 * Encryption cipher method.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const CIPHER_METHOD = 'AES-256-CBC';

	/**
	 * Encryption key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $encryption_key;

	/**
	 * Constructor.
	 *
	 * Initializes the encryption key from WordPress constants or generates one.
	 *
	 * @since 1.0.0
	 * @throws Exception If encryption key cannot be generated.
	 */
	public function __construct() {
		$this->encryption_key = $this->get_encryption_key();
		
		if ( empty( $this->encryption_key ) ) {
			throw new Exception( 'Encryption key is required and cannot be empty.' );
		}
	}

	/**
	 * Encrypts data using AES-256-CBC.
	 *
	 * @since 1.0.0
	 * @param string $data Data to encrypt.
	 * @return string|false Encrypted data in base64 format, or false on failure.
	 */
	public function encrypt( $data ) {
		if ( empty( $data ) ) {
			return false;
		}

		try {
			// Generate initialization vector.
			$iv_length = openssl_cipher_iv_length( self::CIPHER_METHOD );
			if ( false === $iv_length ) {
				return false;
			}

			$iv = openssl_random_pseudo_bytes( $iv_length );
			if ( false === $iv ) {
				return false;
			}

			// Encrypt the data.
			$encrypted = openssl_encrypt(
				$data,
				self::CIPHER_METHOD,
				$this->encryption_key,
				OPENSSL_RAW_DATA,
				$iv
			);

			if ( false === $encrypted ) {
				return false;
			}

			// Create HMAC for authentication.
			$hmac = hash_hmac( 'sha256', $iv . $encrypted, $this->encryption_key, true );

			// Combine IV, HMAC, and encrypted data.
			$result = base64_encode( $iv . $hmac . $encrypted );

			return $result;

		} catch ( Exception $e ) {
			error_log( 'Encryption error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Decrypts data encrypted with AES-256-CBC.
	 *
	 * @since 1.0.0
	 * @param string $encrypted_data Encrypted data in base64 format.
	 * @return string|false Decrypted data, or false on failure.
	 */
	public function decrypt( $encrypted_data ) {
		if ( empty( $encrypted_data ) ) {
			return false;
		}

		try {
			// Decode base64 data.
			$data = base64_decode( $encrypted_data, true );
			if ( false === $data ) {
				return false;
			}

			// Get IV length.
			$iv_length = openssl_cipher_iv_length( self::CIPHER_METHOD );
			if ( false === $iv_length ) {
				return false;
			}

			// Extract IV, HMAC, and encrypted data.
			$iv        = substr( $data, 0, $iv_length );
			$hmac      = substr( $data, $iv_length, 32 );
			$encrypted = substr( $data, $iv_length + 32 );

			// Verify HMAC.
			$calc_hmac = hash_hmac( 'sha256', $iv . $encrypted, $this->encryption_key, true );
			if ( ! hash_equals( $hmac, $calc_hmac ) ) {
				error_log( 'Decryption error: HMAC verification failed.' );
				return false;
			}

			// Decrypt the data.
			$decrypted = openssl_decrypt(
				$encrypted,
				self::CIPHER_METHOD,
				$this->encryption_key,
				OPENSSL_RAW_DATA,
				$iv
			);

			if ( false === $decrypted ) {
				return false;
			}

			return $decrypted;

		} catch ( Exception $e ) {
			error_log( 'Decryption error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Gets or generates the encryption key.
	 *
	 * @since 1.0.0
	 * @return string Encryption key.
	 */
	private function get_encryption_key() {
		// Try to get from WordPress constant first.
		if ( defined( 'MONDAYS_WORK_AI_ENCRYPTION_KEY' ) ) {
			return MONDAYS_WORK_AI_ENCRYPTION_KEY;
		}

		// Fall back to WordPress salts.
		if ( defined( 'AUTH_KEY' ) && defined( 'SECURE_AUTH_KEY' ) ) {
			return hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY, true );
		}

		// Generate from site URL as last resort (not recommended for production).
		$site_url = get_site_url();
		return hash( 'sha256', $site_url . 'mondays-work-ai-salt', true );
	}

	/**
	 * Securely stores encrypted data in WordPress options.
	 *
	 * @since 1.0.0
	 * @param string $option_name Option name.
	 * @param string $data Data to encrypt and store.
	 * @return bool True on success, false on failure.
	 */
	public function store_encrypted( $option_name, $data ) {
		$encrypted = $this->encrypt( $data );
		
		if ( false === $encrypted ) {
			return false;
		}

		return update_option( $option_name, $encrypted, false );
	}

	/**
	 * Retrieves and decrypts data from WordPress options.
	 *
	 * @since 1.0.0
	 * @param string $option_name Option name.
	 * @return string|false Decrypted data, or false on failure.
	 */
	public function retrieve_encrypted( $option_name ) {
		$encrypted = get_option( $option_name );
		
		if ( false === $encrypted || empty( $encrypted ) ) {
			return false;
		}

		return $this->decrypt( $encrypted );
	}

	/**
	 * Checks if OpenSSL extension is available.
	 *
	 * @since 1.0.0
	 * @return bool True if OpenSSL is available, false otherwise.
	 */
	public static function is_available() {
		return extension_loaded( 'openssl' );
	}
}
