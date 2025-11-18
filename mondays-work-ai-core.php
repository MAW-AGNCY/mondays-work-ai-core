<?php
/**
 * Plugin Name:       Monday's Work AI Core
 * Plugin URI:        https://github.com/MAW-AGNCY/mondays-work-ai-core
 * Description:       Core AI system for WooCommerce - Modular artificial intelligence integration
 * Version:           1.0.1
  * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Mondays at Work
 * Author URI:        https://mondaysatwork.com
 * License:           Proprietary
 * License URI:       https://github.com/MAW-AGNCY/mondays-work-ai-core/blob/main/LICENSE
 * Text Domain:       mondays-work-ai-core
 * Domain Path:       /languages
 *
 * @package           MondaysWork\AI\Core
 * @author            Mondays at Work <info@mondaysatwork.com>
 * @copyright         2025 Mondays at Work
 * @license           Proprietary
 *
 * This plugin is proprietary software and may not be distributed, modified,
 * or used without explicit permission from Mondays at Work.
 * Este plugin es software propietario y no puede ser distribuido, modificado
 * o usado sin permiso explícito de Mondays at Work.
 */

// Exit if accessed directly / Salir si se accede directamente
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Use statements at file level / Declaraciones use al nivel del archivo
use MondaysWork\AI\Core\Core\Plugin;
use MondaysWork\AI\Core\Core\Activator;
use MondaysWork\AI\Core\Core\Deactivator;

// Load custom PSR-4 autoloader / Cargar autoloader PSR-4 personalizado
require_once __DIR__ . '/includes/autoload.php';
// Initialize plugin / Inicializar plugin
	if ( class_exists( 'MondaysWork\AI\Core\Core\Plugin' ) ) {    // Get plugin instance / Obtener instancia del plugin
    $plugin = Plugin::get_instance();
    
    // Initialize the plugin / Inicializar el plugin
    $plugin->init();
}

/**
 * Activation hook / Hook de activación
 * Runs when the plugin is activated
 * Se ejecuta cuando el plugin es activado
 */
register_activation_hook( __FILE__, function() {
		if ( class_exists( 'MondaysWork\AI\Core\Core\Activator' ) ) {        Activator::activate();
    }
} );

/**
 * Deactivation hook / Hook de desactivación
 * Runs when the plugin is deactivated
 * Se ejecuta cuando el plugin es desactivado
 */
register_deactivation_hook( __FILE__, function() {
		if ( class_exists( 'MondaysWork\AI\Core\Core\Deactivator' ) ) {        Deactivator::deactivate();
    }
} );

/**
 * Define plugin constants / Definir constantes del plugin
 */
if ( ! defined( 'MWAI_CORE_VERSION' ) ) {
	define( 'MWAI_CORE_VERSION', '1.0.1' );}

if ( ! defined( 'MWAI_CORE_PLUGIN_FILE' ) ) {
    define( 'MWAI_CORE_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'MWAI_CORE_PLUGIN_DIR' ) ) {
    define( 'MWAI_CORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'MWAI_CORE_PLUGIN_URL' ) ) {
    define( 'MWAI_CORE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'MWAI_CORE_PLUGIN_BASENAME' ) ) {
    define( 'MWAI_CORE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Check for required dependencies / Verificar dependencias requeridas
 */
add_action( 'admin_init', function() {
    // Check if WooCommerce is active / Verificar si WooCommerce está activo
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            ?>
				<div class="notice notice-warning">                <p>
                    <strong><?php esc_html_e( 'Monday\'s Work AI Core:', 'mondays-work-ai-core' ); ?></strong>
					<?php esc_html_e( 'This plugin works best with WooCommerce installed. Some features may be limited without it.', 'mondays-work-ai-core' ); ?>                </p>
            </div>
            <?php
        } );
        
} );

/**
 * Load plugin text domain for translations / Cargar dominio de texto para traducciones
 */
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain(
        'mondays-work-ai-core',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
} );

/**
 * Add settings link on plugins page / Añadir enlace de configuración en página de plugins
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url( 'admin.php?page=mondays-work-ai-core' ),
        esc_html__( 'Configuración', 'mondays-work-ai-core' )
    );
    
    array_unshift( $links, $settings_link );
    
    return $links;
} );

/**
 * Add plugin row meta links / Añadir enlaces de meta en fila del plugin
 */
add_filter( 'plugin_row_meta', function( $links, $file ) {
    if ( plugin_basename( __FILE__ ) === $file ) {
        $row_meta = array(
            'docs' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://github.com/MAW-AGNCY/mondays-work-ai-core/blob/main/docs/CONFIGURATION.md',
                esc_html__( 'Documentación', 'mondays-work-ai-core' )
            ),
            'support' => sprintf(
                '<a href="%s">%s</a>',
                'mailto:info@mondaysatwork.com',
                esc_html__( 'Soporte', 'mondays-work-ai-core' )
            ),
        );
        
        return array_merge( $links, $row_meta );
    }
    
    return $links;
}, 10, 2 );

/**
 * Display admin notice if Composer dependencies are missing
 * Mostrar aviso de admin si faltan dependencias de Composer
 */
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    add_action( 'admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'Monday\'s Work AI Core:', 'mondays-work-ai-core' ); ?></strong>
                <?php esc_html_e( 'Faltan dependencias de Composer. Por favor ejecuta:', 'mondays-work-ai-core' ); ?>
                <code>composer install --no-dev</code>
            </p>
            <p>
                <strong>English:</strong>
                <?php esc_html_e( 'Composer dependencies are missing. Please run:', 'mondays-work-ai-core' ); ?>
                <code>composer install --no-dev</code>
            </p>
        </div>
        <?php
    } );
}
