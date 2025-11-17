<?php
/**
 * WooCommerce Data Access Layer
 *
 * Provides secure access to WooCommerce data for AI processing
 *
 * @package MondaysWork\AI\Core
 * @since   1.0.0
 */

namespace MondaysWork\AI\Core\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DataAccess class
 *
 * Handles WooCommerce data retrieval and formatting for AI context
 */
class DataAccess {

	/**
	 * Get customer data
	 *
	 * @param int $customer_id Customer ID
	 * @return array Customer data
	 */
	public function get_customer_data( $customer_id ) {
		if ( ! function_exists( 'wc_get_customer' ) ) {
			return array( 'error' => 'WooCommerce not active' );
		}

		try {
			$customer = new \WC_Customer( $customer_id );

			return array(
				'id'              => $customer->get_id(),
				'email'           => $customer->get_email(),
				'first_name'      => $customer->get_first_name(),
				'last_name'       => $customer->get_last_name(),
				'billing_city'    => $customer->get_billing_city(),
				'billing_country' => $customer->get_billing_country(),
				'total_spent'     => $customer->get_total_spent(),
				'order_count'     => $customer->get_order_count(),
			);
		} catch ( \Exception $e ) {
			error_log( 'MondaysWork AI: Error getting customer data - ' . $e->getMessage() );
			return array( 'error' => 'Could not retrieve customer data' );
		}
	}

	/**
	 * Get product information
	 *
	 * @param int $product_id Product ID
	 * @return array Product data
	 */
	public function get_product_data( $product_id ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array( 'error' => 'WooCommerce not active' );
		}

		try {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				return array( 'error' => 'Product not found' );
			}

			return array(
				'id'          => $product->get_id(),
				'name'        => $product->get_name(),
				'price'       => $product->get_price(),
				'regular_price' => $product->get_regular_price(),
				'sale_price'  => $product->get_sale_price(),
				'description' => $product->get_description(),
				'short_description' => $product->get_short_description(),
				'stock_status' => $product->get_stock_status(),
				'stock_quantity' => $product->get_stock_quantity(),
				'categories'  => wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) ),
				'tags'        => wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) ),
			);
		} catch ( \Exception $e ) {
			error_log( 'MondaysWork AI: Error getting product data - ' . $e->getMessage() );
			return array( 'error' => 'Could not retrieve product data' );
		}
	}

	/**
	 * Get order details
	 *
	 * @param int $order_id Order ID
	 * @return array Order data
	 */
	public function get_order_data( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return array( 'error' => 'WooCommerce not active' );
		}

		try {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				return array( 'error' => 'Order not found' );
			}

			$items = array();
			foreach ( $order->get_items() as $item ) {
				$items[] = array(
					'name'     => $item->get_name(),
					'quantity' => $item->get_quantity(),
					'total'    => $item->get_total(),
				);
			}

			return array(
				'id'           => $order->get_id(),
				'status'       => $order->get_status(),
				'total'        => $order->get_total(),
				'currency'     => $order->get_currency(),
				'date_created' => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
				'customer_id'  => $order->get_customer_id(),
				'items'        => $items,
			);
		} catch ( \Exception $e ) {
			error_log( 'MondaysWork AI: Error getting order data - ' . $e->getMessage() );
			return array( 'error' => 'Could not retrieve order data' );
		}
	}

	/**
	 * Search products by query
	 *
	 * @param string $query Search query
	 * @param int    $limit Max results
	 * @return array Products data
	 */
	public function search_products( $query, $limit = 10 ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array( 'error' => 'WooCommerce not active' );
		}

		try {
			$args = array(
				's'      => sanitize_text_field( $query ),
				'limit'  => absint( $limit ),
				'status' => 'publish',
			);

			$products = wc_get_products( $args );
			$results  = array();

			foreach ( $products as $product ) {
				$results[] = array(
					'id'    => $product->get_id(),
					'name'  => $product->get_name(),
					'price' => $product->get_price(),
					'url'   => $product->get_permalink(),
				);
			}

			return $results;
		} catch ( \Exception $e ) {
			error_log( 'MondaysWork AI: Error searching products - ' . $e->getMessage() );
			return array( 'error' => 'Could not search products' );
		}
	}

	/**
	 * Get cart information (current session)
	 *
	 * @return array Cart data
	 */
	public function get_cart_data() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array( 'error' => 'Cart not available' );
		}

		try {
			$cart = WC()->cart;
			$items = array();

			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				$product = $cart_item['data'];
				$items[] = array(
					'name'     => $product->get_name(),
					'quantity' => $cart_item['quantity'],
					'price'    => $product->get_price(),
					'subtotal' => $cart_item['line_subtotal'],
				);
			}

			return array(
				'items_count' => $cart->get_cart_contents_count(),
				'total'       => $cart->get_cart_total(),
				'subtotal'    => $cart->get_cart_subtotal(),
				'items'       => $items,
			);
		} catch ( \Exception $e ) {
			error_log( 'MondaysWork AI: Error getting cart data - ' . $e->getMessage() );
			return array( 'error' => 'Could not retrieve cart data' );
		}
	}
}
