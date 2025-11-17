<?php
/**
 * AI Prompt Templates Management
 *
 * Manages AI prompt templates for different scenarios
 *
 * @package MondaysWork\AI\Core
 * @since   1.0.0
 */

namespace MondaysWork\AI\Core\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PromptTemplates class
 *
 * Handles creation, storage, and retrieval of AI prompt templates
 */
class PromptTemplates {

	/**
	 * Option name for storing templates
	 */
	const OPTION_NAME = 'maw_ai_prompt_templates';

	/**
	 * Default prompt templates
	 */
	private static $default_templates = array(
		'general_customer_support' => array(
			'name'        => 'General Customer Support',
			'description' => 'General customer service inquiries',
			'prompt'      => 'You are a helpful customer support assistant for a WooCommerce store. Be friendly, professional, and provide accurate information about products, orders, and policies.',
			'active'      => true,
		),
		'product_recommendations' => array(
			'name'        => 'Product Recommendations',
			'description' => 'Help customers find the right products',
			'prompt'      => 'You are a product recommendation specialist. Help customers find products that match their needs by asking relevant questions and suggesting appropriate items from the catalog.',
			'active'      => true,
		),
		'order_tracking' => array(
			'name'        => 'Order Tracking',
			'description' => 'Assist with order status inquiries',
			'prompt'      => 'You are an order tracking assistant. Help customers check their order status, shipping information, and expected delivery dates.',
			'active'      => true,
		),
		'returns_refunds' => array(
			'name'        => 'Returns & Refunds',
			'description' => 'Handle return and refund requests',
			'prompt'      => 'You are a returns and refunds specialist. Guide customers through the return process, explain refund policies, and help resolve any issues.',
			'active'      => true,
		),
	);

	/**
	 * Get all templates
	 *
	 * @return array Templates
	 */
	public static function get_templates() {
		$templates = get_option( self::OPTION_NAME, array() );

		// Merge with defaults if empty
		if ( empty( $templates ) ) {
			$templates = self::$default_templates;
			update_option( self::OPTION_NAME, $templates );
		}

		return $templates;
	}

	/**
	 * Get a specific template
	 *
	 * @param string $template_id Template ID
	 * @return array|null Template data or null
	 */
	public static function get_template( $template_id ) {
		$templates = self::get_templates();
		return isset( $templates[ $template_id ] ) ? $templates[ $template_id ] : null;
	}

	/**
	 * Save a template
	 *
	 * @param string $template_id Template ID
	 * @param array  $data Template data
	 * @return bool Success
	 */
	public static function save_template( $template_id, $data ) {
		$templates = self::get_templates();

		$templates[ sanitize_key( $template_id ) ] = array(
			'name'        => sanitize_text_field( $data['name'] ?? '' ),
			'description' => sanitize_text_field( $data['description'] ?? '' ),
			'prompt'      => sanitize_textarea_field( $data['prompt'] ?? '' ),
			'active'      => (bool) ( $data['active'] ?? true ),
		);

		return update_option( self::OPTION_NAME, $templates );
	}

	/**
	 * Delete a template
	 *
	 * @param string $template_id Template ID
	 * @return bool Success
	 */
	public static function delete_template( $template_id ) {
		$templates = self::get_templates();

		if ( isset( $templates[ $template_id ] ) ) {
			unset( $templates[ $template_id ] );
			return update_option( self::OPTION_NAME, $templates );
		}

		return false;
	}

	/**
	 * Get active template for current context
	 *
	 * @param string $context Context (default: 'general_customer_support')
	 * @return array|null Active template
	 */
	public static function get_active_template( $context = 'general_customer_support' ) {
		$template = self::get_template( $context );

		if ( $template && $template['active'] ) {
			return $template;
		}

		// Fallback to first active template
		$templates = self::get_templates();
		foreach ( $templates as $id => $tmpl ) {
			if ( $tmpl['active'] ) {
				return $tmpl;
			}
		}

		return null;
	}

	/**
	 * Activate a template
	 *
	 * @param string $template_id Template ID
	 * @return bool Success
	 */
	public static function activate_template( $template_id ) {
		$template = self::get_template( $template_id );

		if ( $template ) {
			$template['active'] = true;
			return self::save_template( $template_id, $template );
		}

		return false;
	}

	/**
	 * Deactivate a template
	 *
	 * @param string $template_id Template ID
	 * @return bool Success
	 */
	public static function deactivate_template( $template_id ) {
		$template = self::get_template( $template_id );

		if ( $template ) {
			$template['active'] = false;
			return self::save_template( $template_id, $template );
		}

		return false;
	}

	/**
	 * Reset to default templates
	 *
	 * @return bool Success
	 */
	public static function reset_to_defaults() {
		return update_option( self::OPTION_NAME, self::$default_templates );
	}
}
