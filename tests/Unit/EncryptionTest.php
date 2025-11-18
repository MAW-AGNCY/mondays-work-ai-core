<?php
/**
 * Encryption Test Class
 *
 * Unit tests for the Encryption class.
 *
 * @package MondaysWork\AI\Core
 * @subpackage Tests\Unit
 * @since 1.0.0
 */

namespace MondaysWork\AI\Tests\Unit;

use MondaysWork\AI\Core\Security\Encryption;
use WP_UnitTestCase;

/**
 * Class EncryptionTest
 *
 * Tests encryption and decryption functionality.
 *
 * @since 1.0.0
 */
class EncryptionTest extends WP_UnitTestCase {

	/**
	 * Encryption instance.
	 *
	 * @since 1.0.0
	 * @var Encryption
	 */
	private $encryption;

	/**
	 * Set up test environment.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->encryption = new Encryption();
	}

	/**
	 * Tear down test environment.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->encryption = null;
	}

	/**
	 * Test that OpenSSL extension is available.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_openssl_is_available() {
		$this->assertTrue(
			Encryption::is_available(),
			'OpenSSL extension must be available for tests to run.'
		);
	}

	/**
	 * Test basic encryption.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_encrypt_returns_string() {
		$data      = 'sensitive-api-key-12345';
		$encrypted = $this->encryption->encrypt( $data );

		$this->assertIsString( $encrypted, 'Encrypted data should be a string.' );
		$this->assertNotEmpty( $encrypted, 'Encrypted data should not be empty.' );
		$this->assertNotEquals( $data, $encrypted, 'Encrypted data should differ from original.' );
	}

	/**
	 * Test encryption and decryption cycle.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_encrypt_decrypt_cycle() {
		$original  = 'test-api-key-xyz-789';
		$encrypted = $this->encryption->encrypt( $original );
		$decrypted = $this->encryption->decrypt( $encrypted );

		$this->assertEquals( $original, $decrypted, 'Decrypted data should match original.' );
	}

	/**
	 * Test encryption with empty data.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_encrypt_empty_data_returns_false() {
		$result = $this->encryption->encrypt( '' );
		$this->assertFalse( $result, 'Encrypting empty data should return false.' );
	}

	/**
	 * Test decryption with empty data.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_decrypt_empty_data_returns_false() {
		$result = $this->encryption->decrypt( '' );
		$this->assertFalse( $result, 'Decrypting empty data should return false.' );
	}

	/**
	 * Test decryption with invalid data.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_decrypt_invalid_data_returns_false() {
		$result = $this->encryption->decrypt( 'invalid-encrypted-data' );
		$this->assertFalse( $result, 'Decrypting invalid data should return false.' );
	}

	/**
	 * Test encryption produces different ciphertext for same input.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_encrypt_produces_unique_ciphertext() {
		$data       = 'test-data';
		$encrypted1 = $this->encryption->encrypt( $data );
		$encrypted2 = $this->encryption->encrypt( $data );

		$this->assertNotEquals(
			$encrypted1,
			$encrypted2,
			'Each encryption should produce unique ciphertext due to IV.'
		);

		// Both should decrypt to original.
		$this->assertEquals( $data, $this->encryption->decrypt( $encrypted1 ) );
		$this->assertEquals( $data, $this->encryption->decrypt( $encrypted2 ) );
	}

	/**
	 * Test encryption with special characters.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_encrypt_with_special_characters() {
		$data      = 'API-Key!@#$%^&*()_+=[]{}|;:,.<>?';
		$encrypted = $this->encryption->encrypt( $data );
		$decrypted = $this->encryption->decrypt( $encrypted );

		$this->assertEquals( $data, $decrypted, 'Should handle special characters.' );
	}

	/**
	 * Test encryption with Unicode characters.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_encrypt_with_unicode_characters() {
		$data      = 'API-Key-日本語-中文-한국어-🔐';
		$encrypted = $this->encryption->encrypt( $data );
		$decrypted = $this->encryption->decrypt( $encrypted );

		$this->assertEquals( $data, $decrypted, 'Should handle Unicode characters.' );
	}

	/**
	 * Test encryption with long data.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_encrypt_with_long_data() {
		$data      = str_repeat( 'A', 10000 );
		$encrypted = $this->encryption->encrypt( $data );
		$decrypted = $this->encryption->decrypt( $encrypted );

		$this->assertEquals( $data, $decrypted, 'Should handle long data.' );
	}

	/**
	 * Test store and retrieve encrypted data.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_store_and_retrieve_encrypted() {
		$option_name = 'test_encrypted_option';
		$data        = 'secret-api-key-abc123';

		// Store encrypted.
		$stored = $this->encryption->store_encrypted( $option_name, $data );
		$this->assertTrue( $stored, 'Store encrypted should return true.' );

		// Retrieve encrypted.
		$retrieved = $this->encryption->retrieve_encrypted( $option_name );
		$this->assertEquals( $data, $retrieved, 'Retrieved data should match original.' );

		// Clean up.
		delete_option( $option_name );
	}

	/**
	 * Test retrieve non-existent encrypted option.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_retrieve_encrypted_nonexistent_returns_false() {
		$result = $this->encryption->retrieve_encrypted( 'nonexistent_option' );
		$this->assertFalse( $result, 'Retrieving non-existent option should return false.' );
	}

	/**
	 * Test HMAC verification failure.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_decrypt_with_tampered_data_returns_false() {
		$data      = 'test-data';
		$encrypted = $this->encryption->encrypt( $data );

		// Tamper with encrypted data (change one character).
		$tampered = substr( $encrypted, 0, -1 ) . 'X';

		$result = $this->encryption->decrypt( $tampered );
		$this->assertFalse( $result, 'Decrypting tampered data should return false.' );
	}

	/**
	 * Test encryption constructor with missing key throws exception.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_constructor_with_empty_key_throws_exception() {
		// This test would require mocking the get_encryption_key method
		// or modifying constants, which is complex in WordPress environment.
		// Marking as skipped for now.
		$this->markTestSkipped( 'Requires complex mocking of WordPress constants.' );
	}

	/**
	 * Test multiple encrypt/decrypt operations.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_multiple_encrypt_decrypt_operations() {
		$test_data = array(
			'api_key_1'     => 'sk-abc123xyz',
			'api_key_2'     => 'pk-test-456',
			'secret_token'  => 'secret-token-789',
			'password'      => 'P@ssw0rd!2024',
		);

		foreach ( $test_data as $key => $value ) {
			$encrypted = $this->encryption->encrypt( $value );
			$decrypted = $this->encryption->decrypt( $encrypted );

			$this->assertEquals(
				$value,
				$decrypted,
				"Failed for key: {$key}"
			);
		}
	}

	/**
	 * Test encrypted data is base64 encoded.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_encrypted_data_is_base64_encoded() {
		$data      = 'test-data';
		$encrypted = $this->encryption->encrypt( $data );

		$decoded = base64_decode( $encrypted, true );
		$this->assertNotFalse( $decoded, 'Encrypted data should be valid base64.' );
	}

	/**
	 * Test encryption consistency across instances.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function test_encryption_consistency_across_instances() {
		$data = 'consistent-data-test';

		$encryption1 = new Encryption();
		$encrypted   = $encryption1->encrypt( $data );

		$encryption2 = new Encryption();
		$decrypted   = $encryption2->decrypt( $encrypted );

		$this->assertEquals(
			$data,
			$decrypted,
			'Different instances should be able to decrypt each other\'s data.'
		);
	}
}
