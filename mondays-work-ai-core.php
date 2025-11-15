<?php
/**
 * Plugin Name: Monday's Work AI Core
 * Plugin URI: https://github.com/MAW-AGNCY/mondays-work-ai-core
 * Description: Core module for AI-powered WordPress/WooCommerce plugin - MVP architecture with modular AI client integration
 * Version: 0.1.0
 * Author: MAW-AGNCY
 * Author URI: https://github.com/MAW-AGNCY
 * License: Proprietary
 * License URI: https://github.com/MAW-AGNCY/mondays-work-ai-core/blob/main/LICENSE
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

namespace MondaysWork\AI\Core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'MWAI_VERSION', '0.1.0' );
define( 'MWAI_PLUGIN_FILE', __FILE__ );
define( 'MWAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MWAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MWAI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Require Composer autoloader
if ( file_exists( MWAI_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once MWAI_PLUGIN_DIR . 'vendor/autoload.php';
}

// Initialize the plugin
function mwai_init() {
    // Load the core plugin class
    if ( class_exists( 'MondaysWork\\AI\\Core\\Plugin' ) ) {
        Plugin::get_instance();
    }
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\mwai_init' );

// Activation hook
register_activation_hook( __FILE__, function() {
    if ( class_exists( 'MondaysWork\\AI\\Core\\Activator' ) ) {
        Activator::activate();
    }
} );

// Deactivation hook
register_deactivation_hook( __FILE__, function() {
    if ( class_exists( 'MondaysWork\\AI\\Core\\Deactivator' ) ) {
        Deactivator::deactivate();
    }
} );
