<?php
/**
 * Frontend Chat Widget
 *
 * Renders the AI chat widget on the frontend - Always visible when plugin is active
 *
 * @package MondaysWork\AI\Core
 * @since   1.0.0
 */

namespace MondaysWork\AI\Core\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ChatWidget class
 *
 * Handles frontend chat widget rendering and initialization
 */
class ChatWidget {

	/**
	 * Initialize the chat widget
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_widget' ) );
		add_action( 'wp_ajax_maw_chat_message', array( __CLASS__, 'handle_chat_message' ) );
		add_action( 'wp_ajax_nopriv_maw_chat_message', array( __CLASS__, 'handle_chat_message' ) );
	}

	/**
	 * Enqueue widget assets
	 */
	public static function enqueue_assets() {
		wp_enqueue_style(
			'maw-chat-widget',
			plugins_url( 'assets/css/chat-widget.css', dirname( dirname( __FILE__ ) ) ),
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'maw-chat-widget',
			plugins_url( 'assets/js/chat-widget.js', dirname( dirname( __FILE__ ) ) ),
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'maw-chat-widget',
			'mawChat',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'maw_chat_nonce' ),
			)
		);
	}

	/**
	 * Render chat widget HTML
	 */
	public static function render_widget() {
		?>
		<div id="maw-chat-widget" class="maw-chat-widget maw-chat-minimized">
			<div class="maw-chat-header">
				<h3>AI Assistant</h3>
				<button class="maw-chat-toggle" aria-label="Toggle chat">_</button>
			</div>
			<div class="maw-chat-body">
				<div class="maw-chat-messages"></div>
				<div class="maw-chat-input-wrapper">
					<input type="text" class="maw-chat-input" placeholder="Type your message..." />
					<button class="maw-chat-send">Send</button>
				</div>
			</div>
			<button class="maw-chat-bubble" aria-label="Open chat">💬</button>
		</div>
		<?php
	}

	/**
	 * Handle AJAX chat message
	 */
	public static function handle_chat_message() {
		check_ajax_referer( 'maw_chat_nonce', 'nonce' );

		$message = sanitize_text_field( $_POST['message'] ?? '' );

		if ( empty( $message ) ) {
			wp_send_json_error( array( 'message' => 'Empty message' ) );
		}

		// TODO: Integrate with AI client
		$response = 'Thank you for your message: ' . esc_html( $message );

		wp_send_json_success( array( 'response' => $response ) );
	}
}
