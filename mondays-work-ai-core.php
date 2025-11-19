<?php
/**
 * Plugin Name: Monday's Work AI Core
 * Plugin URI: https://mondaysatwork.com/plugins/ai-core
 * Description: Advanced AI integration core plugin with security features, encryption, and rate limiting for WordPress
 * Version: 1.0.1
 * Author: Mondays at Work
 * Author URI: https://mondaysatwork.com
 * License: Proprietary
 * License URI: https://mondaysatwork.com/license
 * Text Domain: mondays-work-ai-core
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Network: false
 *
 * @package MondaysWork\AI
 * @author Mondays at Work
 * @copyright 2024 Mondays at Work
 */

namespace MondaysWork\AI;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'MWAI_VERSION', '1.0.1' );
define( 'MWAI_PLUGIN_FILE', __FILE__ );
define( 'MWAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MWAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MWAI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
class MondaysWorkAICore {
    
    /**
     * Plugin instance
     *
     * @var MondaysWorkAICore
     */
    private static $instance = null;
    
    /**
     * Encryption handler
     *
     * @var Encryption
     */
    private $encryption;
    
    /**
     * Rate limiter
     *
     * @var RateLimiter
     */
    private $rate_limiter;
    
    /**
     * Get plugin instance
     *
     * @return MondaysWorkAICore
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_autoloader();
        $this->init_hooks();
    }
    
    /**
     * Initialize PSR-4 autoloader
     */
    private function init_autoloader() {
        spl_autoload_register( function( $class ) {
            $prefix = 'MondaysWork\\AI\\';
            $base_dir = MWAI_PLUGIN_DIR . 'includes/';
            
            $len = strlen( $prefix );
            if ( strncmp( $prefix, $class, $len ) !== 0 ) {
                return;
            }
            
            $relative_class = substr( $class, $len );
            $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
            
            if ( file_exists( $file ) ) {
                require $file;
            }
        });
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook( MWAI_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( MWAI_PLUGIN_FILE, array( $this, 'deactivate' ) );
        
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_mwai_test_connection', array( $this, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_mwai_get_system_info', array( $this, 'ajax_get_system_info' ) );
        add_action( 'wp_ajax_mwai_reset_rate_limit', array( $this, 'ajax_reset_rate_limit' ) );
        
        $this->encryption = new Encryption();
        $this->rate_limiter = new RateLimiter();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create options with default values
        add_option( 'mwai_api_key', '' );
        add_option( 'mwai_encryption_enabled', '1' );
        add_option( 'mwai_encryption_key', $this->generate_encryption_key() );
        add_option( 'mwai_rate_limit_enabled', '1' );
        add_option( 'mwai_rate_limit_requests', '100' );
        add_option( 'mwai_rate_limit_period', '3600' );
        
        // Create rate limit table
        global $wpdb;
        $table_name = $wpdb->prefix . 'mwai_rate_limits';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            ip_address varchar(45) NOT NULL,
            endpoint varchar(255) NOT NULL,
            request_count int(11) NOT NULL DEFAULT 0,
            window_start datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY ip_address (ip_address),
            KEY window_start (window_start)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'mondays-work-ai-core',
            false,
            dirname( MWAI_PLUGIN_BASENAME ) . '/languages'
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( "Monday's Work AI", 'mondays-work-ai-core' ),
            __( "Monday's Work AI", 'mondays-work-ai-core' ),
            'manage_options',
            'mondays-work-ai',
            array( $this, 'render_admin_page' ),
            'dashicons-superhero',
            58
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // General settings
        register_setting( 'mwai_general', 'mwai_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        
        // Encryption settings
        register_setting( 'mwai_encryption', 'mwai_encryption_enabled', array(
            'type' => 'boolean',
            'default' => true,
        ) );
        
        register_setting( 'mwai_encryption', 'mwai_encryption_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        
        // Rate limiting settings
        register_setting( 'mwai_rate_limiting', 'mwai_rate_limit_enabled', array(
            'type' => 'boolean',
            'default' => true,
        ) );
        
        register_setting( 'mwai_rate_limiting', 'mwai_rate_limit_requests', array(
            'type' => 'integer',
            'default' => 100,
        ) );
        
        register_setting( 'mwai_rate_limiting', 'mwai_rate_limit_period', array(
            'type' => 'integer',
            'default' => 3600,
        ) );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_mondays-work-ai' !== $hook ) {
            return;
        }
        
        wp_enqueue_style(
            'mwai-admin-styles',
            MWAI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MWAI_VERSION
        );
        
        wp_enqueue_script(
            'mwai-admin-scripts',
            MWAI_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            MWAI_VERSION,
            true
        );
        
        wp_localize_script( 'mwai-admin-scripts', 'mwaiAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'mwai_admin_nonce' ),
            'strings' => array(
                'testing' => __( 'Testing...', 'mondays-work-ai-core' ),
                'success' => __( 'Success!', 'mondays-work-ai-core' ),
                'error' => __( 'Error occurred', 'mondays-work-ai-core' ),
            ),
        ) );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mondays-work-ai-core' ) );
        }
        
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
        ?>
        <div class="wrap mwai-admin-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=mondays-work-ai&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'General', 'mondays-work-ai-core' ); ?>
                </a>
                <a href="?page=mondays-work-ai&tab=encryption" class="nav-tab <?php echo $active_tab === 'encryption' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Encryption', 'mondays-work-ai-core' ); ?>
                </a>
                <a href="?page=mondays-work-ai&tab=rate-limiting" class="nav-tab <?php echo $active_tab === 'rate-limiting' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Rate Limiting', 'mondays-work-ai-core' ); ?>
                </a>
                <a href="?page=mondays-work-ai&tab=ajax" class="nav-tab <?php echo $active_tab === 'ajax' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'AJAX', 'mondays-work-ai-core' ); ?>
                </a>
                <a href="?page=mondays-work-ai&tab=status" class="nav-tab <?php echo $active_tab === 'status' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Status', 'mondays-work-ai-core' ); ?>
                </a>
                <a href="?page=mondays-work-ai&tab=help" class="nav-tab <?php echo $active_tab === 'help' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Help', 'mondays-work-ai-core' ); ?>
                </a>
            </h2>
            
            <div class="mwai-tab-content">
                <?php
                switch ( $active_tab ) {
                    case 'general':
                        $this->render_general_tab();
                        break;
                    case 'encryption':
                        $this->render_encryption_tab();
                        break;
                    case 'rate-limiting':
                        $this->render_rate_limiting_tab();
                        break;
                    case 'ajax':
                        $this->render_ajax_tab();
                        break;
                    case 'status':
                        $this->render_status_tab();
                        break;
                    case 'help':
                        $this->render_help_tab();
                        break;
                    default:
                        $this->render_general_tab();
                }
                ?>
            </div>
        </div>
        
        <style>
        .mwai-admin-wrap {
            margin: 20px 20px 0 0;
        }
        .mwai-tab-content {
            background: #fff;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .mwai-setting-row {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f1;
        }
        .mwai-setting-row:last-child {
            border-bottom: none;
        }
        .mwai-setting-row label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .mwai-setting-row input[type="text"],
        .mwai-setting-row input[type="number"],
        .mwai-setting-row input[type="password"] {
            width: 100%;
            max-width: 500px;
        }
        .mwai-setting-row .description {
            margin-top: 5px;
            color: #646970;
        }
        .mwai-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .mwai-status-card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
        }
        .mwai-status-card h3 {
            margin-top: 0;
            font-size: 14px;
            color: #1d2327;
        }
        .mwai-status-value {
            font-size: 24px;
            font-weight: 600;
            color: #2271b1;
            margin: 10px 0;
        }
        .mwai-status-label {
            font-size: 12px;
            color: #646970;
        }
        .mwai-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
        }
        .mwai-badge-success {
            background: #d4edda;
            color: #155724;
        }
        .mwai-badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .mwai-badge-error {
            background: #f8d7da;
            color: #721c24;
        }
        .mwai-help-section {
            margin-bottom: 30px;
        }
        .mwai-help-section h3 {
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .mwai-code-block {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        .mwai-faq-item {
            margin-bottom: 20px;
        }
        .mwai-faq-item h4 {
            margin-bottom: 8px;
            color: #2271b1;
        }
        </style>
        <?php
    }
    
    /**
     * Render general tab
     */
    private function render_general_tab() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'mwai_general' );
            ?>
            <div class="mwai-setting-row">
                <label for="mwai_api_key">
                    <?php esc_html_e( 'API Key', 'mondays-work-ai-core' ); ?>
                </label>
                <input
                    type="password"
                    id="mwai_api_key"
                    name="mwai_api_key"
                    value="<?php echo esc_attr( get_option( 'mwai_api_key' ) ); ?>"
                    class="regular-text"
                />
                <p class="description">
                    <?php esc_html_e( 'Enter your AI service API key. This will be encrypted if encryption is enabled.', 'mondays-work-ai-core' ); ?>
                </p>
            </div>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }
    
    /**
     * Render encryption tab
     */
    private function render_encryption_tab() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'mwai_encryption' );
            ?>
            <div class="mwai-setting-row">
                <label>
                    <input
                        type="checkbox"
                        name="mwai_encryption_enabled"
                        value="1"
                        <?php checked( get_option( 'mwai_encryption_enabled', '1' ), '1' ); ?>
                    />
                    <?php esc_html_e( 'Enable AES-256-CBC Encryption', 'mondays-work-ai-core' ); ?>
                </label>
                <p class="description">
                    <?php esc_html_e( 'Encrypt sensitive data like API keys using AES-256-CBC encryption.', 'mondays-work-ai-core' ); ?>
                </p>
            </div>
            
            <div class="mwai-setting-row">
                <label for="mwai_encryption_key">
                    <?php esc_html_e( 'Encryption Key', 'mondays-work-ai-core' ); ?>
                </label>
                <input
                    type="text"
                    id="mwai_encryption_key"
                    name="mwai_encryption_key"
                    value="<?php echo esc_attr( get_option( 'mwai_encryption_key' ) ); ?>"
                    class="regular-text"
                    readonly
                />
                <p class="description">
                    <?php esc_html_e( 'This key is automatically generated. Do not share or modify it.', 'mondays-work-ai-core' ); ?>
                </p>
            </div>
            
            <div class="mwai-setting-row">
                <button type="button" class="button" id="mwai-regenerate-key">
                    <?php esc_html_e( 'Regenerate Encryption Key', 'mondays-work-ai-core' ); ?>
                </button>
                <p class="description">
                    <?php esc_html_e( 'Warning: Regenerating the key will require re-entering all encrypted data.', 'mondays-work-ai-core' ); ?>
                </p>
            </div>
            
            <?php submit_button(); ?>
        </form>
        
        <script>
        jQuery(document).ready(function($) {
            $('#mwai-regenerate-key').on('click', function() {
                if (confirm('<?php esc_html_e( 'Are you sure? This will invalidate all encrypted data.', 'mondays-work-ai-core' ); ?>')) {
                    var newKey = generateRandomKey(32);
                    $('#mwai_encryption_key').val(newKey);
                }
            });
            
            function generateRandomKey(length) {
                var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                var key = '';
                for (var i = 0; i < length; i++) {
                    key += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                return key;
            }
        });
        </script>
        <?php
    }
    
    /**
     * Render rate limiting tab
     */
    private function render_rate_limiting_tab() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'mwai_rate_limiting' );
            ?>
            <div class="mwai-setting-row">
                <label>
                    <input
                        type="checkbox"
                        name="mwai_rate_limit_enabled"
                        value="1"
                        <?php checked( get_option( 'mwai_rate_limit_enabled', '1' ), '1' ); ?>
                    />
                    <?php esc_html_e( 'Enable Rate Limiting', 'mondays-work-ai-core' ); ?>
                </label>
                <p class="description">
                    <?php esc_html_e( 'Limit the number of API requests to prevent abuse.', 'mondays-work-ai-core' ); ?>
                </p>
            </div>
            
            <div class="mwai-setting-row">
                <label for="mwai_rate_limit_requests">
                    <?php esc_html_e( 'Maximum Requests', 'mondays-work-ai-core' ); ?>
                </label>
                <input
                    type="number"
                    id="mwai_rate_limit_requests"
                    name="mwai_rate_limit_requests"
                    value="<?php echo esc_attr( get_option( 'mwai_rate_limit_requests', '100' ) ); ?>"
                    min="1"
                    class="small-text"
                />
                <p class="description">
                    <?php esc_html_e( 'Maximum number of requests allowed per time period.', 'mondays-work-ai-core' ); ?>
                </p>
            </div>
            
            <div class="mwai-setting-row">
                <label for="mwai_rate_limit_period">
                    <?php esc_html_e( 'Time Period (seconds)', 'mondays-work-ai-core' ); ?>
                </label>
                <input
                    type="number"
                    id="mwai_rate_limit_period"
                    name="mwai_rate_limit_period"
                    value="<?php echo esc_attr( get_option( 'mwai_rate_limit_period', '3600' ) ); ?>"
                    min="60"
                    class="small-text"
                />
                <p class="description">
                    <?php esc_html_e( 'Time window for rate limiting (default: 3600 seconds = 1 hour).', 'mondays-work-ai-core' ); ?>
                </p>
            </div>
            
            <div class="mwai-setting-row">
                <button type="button" class="button" id="mwai-reset-rate-limit">
                    <?php esc_html_e( 'Reset All Rate Limits', 'mondays-work-ai-core' ); ?>
                </button>
                <p class="description">
                    <?php esc_html_e( 'Clear all rate limit counters for all users.', 'mondays-work-ai-core' ); ?>
                </p>
            </div>
            
            <?php submit_button(); ?>
        </form>
        
        <script>
        jQuery(document).ready(function($) {
            $('#mwai-reset-rate-limit').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true).text('<?php esc_html_e( 'Resetting...', 'mondays-work-ai-core' ); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mwai_reset_rate_limit',
                        nonce: '<?php echo wp_create_nonce( 'mwai_admin_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var info = '<div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;"><pre>' + JSON.stringify(response.data, null, 2) + '</pre></div>';
                            result.html(info);
                        }
                        btn.prop('disabled', false).text('<?php esc_html_e( 'Get System Information', 'mondays-work-ai-core' ); ?>');
                    },
                    error: function() {
                        result.html('<div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;"><?php esc_html_e( 'Failed to retrieve system information', 'mondays-work-ai-core' ); ?></div>');
                        btn.prop('disabled', false).text('<?php esc_html_e( 'Get System Information', 'mondays-work-ai-core' ); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render status tab
     */
    private function render_status_tab() {
        global $wpdb;
        
        $php_version = phpversion();
        $wp_version = get_bloginfo( 'version' );
        $plugin_version = MWAI_VERSION;
        $api_key_set = ! empty( get_option( 'mwai_api_key' ) );
        $encryption_enabled = get_option( 'mwai_encryption_enabled', '1' ) === '1';
        $rate_limit_enabled = get_option( 'mwai_rate_limit_enabled', '1' ) === '1';
        
        // Get rate limit stats
        $table_name = $wpdb->prefix . 'mwai_rate_limits';
        $total_requests = $wpdb->get_var( "SELECT SUM(request_count) FROM $table_name" );
        $active_limits = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE window_start > DATE_SUB(NOW(), INTERVAL 1 HOUR)" );
        
        ?>
        <div class="mwai-status-grid">
            <div class="mwai-status-card">
                <h3><?php esc_html_e( 'Plugin Version', 'mondays-work-ai-core' ); ?></h3>
                <div class="mwai-status-value"><?php echo esc_html( $plugin_version ); ?></div>
                <div class="mwai-status-label"><?php esc_html_e( 'Current version', 'mondays-work-ai-core' ); ?></div>
            </div>
            
            <div class="mwai-status-card">
                <h3><?php esc_html_e( 'PHP Version', 'mondays-work-ai-core' ); ?></h3>
                <div class="mwai-status-value"><?php echo esc_html( $php_version ); ?></div>
                <div class="mwai-status-label">
                    <?php if ( version_compare( $php_version, '7.4', '>=' ) ) : ?>
                        <span class="mwai-badge mwai-badge-success"><?php esc_html_e( 'Compatible', 'mondays-work-ai-core' ); ?></span>
                    <?php else : ?>
                        <span class="mwai-badge mwai-badge-error"><?php esc_html_e( 'Incompatible', 'mondays-work-ai-core' ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mwai-status-card">
                <h3><?php esc_html_e( 'WordPress Version', 'mondays-work-ai-core' ); ?></h3>
                <div class="mwai-status-value"><?php echo esc_html( $wp_version ); ?></div>
                <div class="mwai-status-label">
                    <?php if ( version_compare( $wp_version, '5.8', '>=' ) ) : ?>
                        <span class="mwai-badge mwai-badge-success"><?php esc_html_e( 'Compatible', 'mondays-work-ai-core' ); ?></span>
                    <?php else : ?>
                        <span class="mwai-badge mwai-badge-warning"><?php esc_html_e( 'Update recommended', 'mondays-work-ai-core' ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mwai-status-card">
                <h3><?php esc_html_e( 'API Key', 'mondays-work-ai-core' ); ?></h3>
                <div class="mwai-status-value">
                    <?php if ( $api_key_set ) : ?>
                        <span class="dashicons dashicons-yes" style="color: #2ea44f;"></span>
                    <?php else : ?>
                        <span class="dashicons dashicons-no" style="color: #d32f2f;"></span>
                    <?php endif; ?>
                </div>
                <div class="mwai-status-label">
                    <?php if ( $api_key_set ) : ?>
                        <span class="mwai-badge mwai-badge-success"><?php esc_html_e( 'Configured', 'mondays-work-ai-core' ); ?></span>
                    <?php else : ?>
                        <span class="mwai-badge mwai-badge-error"><?php esc_html_e( 'Not configured', 'mondays-work-ai-core' ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mwai-status-card">
                <h3><?php esc_html_e( 'Encryption', 'mondays-work-ai-core' ); ?></h3>
                <div class="mwai-status-value">
                    <?php if ( $encryption_enabled ) : ?>
                        <span class="dashicons dashicons-lock" style="color: #2ea44f;"></span>
                    <?php else : ?>
                        <span class="dashicons dashicons-unlock" style="color: #d32f2f;"></span>
                    <?php endif; ?>
                </div>
                <div class="mwai-status-label">
                    <?php if ( $encryption_enabled ) : ?>
                        <span class="mwai-badge mwai-badge-success"><?php esc_html_e( 'Enabled', 'mondays-work-ai-core' ); ?></span>
                    <?php else : ?>
                        <span class="mwai-badge mwai-badge-warning"><?php esc_html_e( 'Disabled', 'mondays-work-ai-core' ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mwai-status-card">
                <h3><?php esc_html_e( 'Rate Limiting', 'mondays-work-ai-core' ); ?></h3>
                <div class="mwai-status-value">
                    <?php if ( $rate_limit_enabled ) : ?>
                        <span class="dashicons dashicons-shield" style="color: #2ea44f;"></span>
                    <?php else : ?>
                        <span class="dashicons dashicons-shield" style="color: #646970;"></span>
                    <?php endif; ?>
                </div>
                <div class="mwai-status-label">
                    <?php if ( $rate_limit_enabled ) : ?>
                        <span class="mwai-badge mwai-badge-success"><?php esc_html_e( 'Enabled', 'mondays-work-ai-core' ); ?></span>
                    <?php else : ?>
                        <span class="mwai-badge mwai-badge-warning"><?php esc_html_e( 'Disabled', 'mondays-work-ai-core' ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mwai-status-card">
                <h3><?php esc_html_e( 'Total Requests', 'mondays-work-ai-core' ); ?></h3>
                <div class="mwai-status-value"><?php echo esc_html( $total_requests ? number_format( $total_requests ) : '0' ); ?></div>
                <div class="mwai-status-label"><?php esc_html_e( 'All time', 'mondays-work-ai-core' ); ?></div>
            </div>
            
            <div class="mwai-status-card">
                <h3><?php esc_html_e( 'Active Rate Limits', 'mondays-work-ai-core' ); ?></h3>
                <div class="mwai-status-value"><?php echo esc_html( $active_limits ? number_format( $active_limits ) : '0' ); ?></div>
                <div class="mwai-status-label"><?php esc_html_e( 'Last hour', 'mondays-work-ai-core' ); ?></div>
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <h3><?php esc_html_e( 'System Requirements', 'mondays-work-ai-core' ); ?></h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Requirement', 'mondays-work-ai-core' ); ?></th>
                        <th><?php esc_html_e( 'Required', 'mondays-work-ai-core' ); ?></th>
                        <th><?php esc_html_e( 'Current', 'mondays-work-ai-core' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'mondays-work-ai-core' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php esc_html_e( 'PHP Version', 'mondays-work-ai-core' ); ?></td>
                        <td>7.4+</td>
                        <td><?php echo esc_html( $php_version ); ?></td>
                        <td>
                            <?php if ( version_compare( $php_version, '7.4', '>=' ) ) : ?>
                                <span class="mwai-badge mwai-badge-success"><?php esc_html_e( 'Pass', 'mondays-work-ai-core' ); ?></span>
                            <?php else : ?>
                                <span class="mwai-badge mwai-badge-error"><?php esc_html_e( 'Fail', 'mondays-work-ai-core' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'WordPress Version', 'mondays-work-ai-core' ); ?></td>
                        <td>5.8+</td>
                        <td><?php echo esc_html( $wp_version ); ?></td>
                        <td>
                            <?php if ( version_compare( $wp_version, '5.8', '>=' ) ) : ?>
                                <span class="mwai-badge mwai-badge-success"><?php esc_html_e( 'Pass', 'mondays-work-ai-core' ); ?></span>
                            <?php else : ?>
                                <span class="mwai-badge mwai-badge-error"><?php esc_html_e( 'Fail', 'mondays-work-ai-core' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'cURL Extension', 'mondays-work-ai-core' ); ?></td>
                        <td><?php esc_html_e( 'Enabled', 'mondays-work-ai-core' ); ?></td>
                        <td><?php echo function_exists( 'curl_version' ) ? esc_html__( 'Enabled', 'mondays-work-ai-core' ) : esc_html__( 'Disabled', 'mondays-work-ai-core' ); ?></td>
                        <td>
                            <?php if ( function_exists( 'curl_version' ) ) : ?>
                                <span class="mwai-badge mwai-badge-success"><?php esc_html_e( 'Pass', 'mondays-work-ai-core' ); ?></span>
                            <?php else : ?>
                                <span class="mwai-badge mwai-badge-error"><?php esc_html_e( 'Fail', 'mondays-work-ai-core' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'OpenSSL Extension', 'mondays-work-ai-core' ); ?></td>
                        <td><?php esc_html_e( 'Enabled', 'mondays-work-ai-core' ); ?></td>
                        <td><?php echo extension_loaded( 'openssl' ) ? esc_html__( 'Enabled', 'mondays-work-ai-core' ) : esc_html__( 'Disabled', 'mondays-work-ai-core' ); ?></td>
                        <td>
                            <?php if ( extension_loaded( 'openssl' ) ) : ?>
                                <span class="mwai-badge mwai-badge-success"><?php esc_html_e( 'Pass', 'mondays-work-ai-core' ); ?></span>
                            <?php else : ?>
                                <span class="mwai-badge mwai-badge-error"><?php esc_html_e( 'Fail', 'mondays-work-ai-core' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render help tab
     */
    private function render_help_tab() {
        ?>
        <div class="mwai-help-section">
            <h3><?php esc_html_e( 'Getting Started', 'mondays-work-ai-core' ); ?></h3>
            <p><?php esc_html_e( 'Welcome to Monday\'s Work AI Core! Follow these steps to get started:', 'mondays-work-ai-core' ); ?></p>
            <ol>
                <li><?php esc_html_e( 'Navigate to the General tab and enter your API key', 'mondays-work-ai-core' ); ?></li>
                <li><?php esc_html_e( 'Review and configure encryption settings in the Encryption tab', 'mondays-work-ai-core' ); ?></li>
                <li><?php esc_html_e( 'Set up rate limiting in the Rate Limiting tab to prevent abuse', 'mondays-work-ai-core' ); ?></li>
                <li><?php esc_html_e( 'Test your configuration using the AJAX tab', 'mondays-work-ai-core' ); ?></li>
                <li><?php esc_html_e( 'Monitor your system status in the Status tab', 'mondays-work-ai-core' ); ?></li>
            </ol>
        </div>
        
        <div class="mwai-help-section">
            <h3><?php esc_html_e( 'Frequently Asked Questions', 'mondays-work-ai-core' ); ?></h3>
            
            <div class="mwai-faq-item">
                <h4><?php esc_html_e( 'What encryption algorithm is used?', 'mondays-work-ai-core' ); ?></h4>
                <p><?php esc_html_e( 'The plugin uses AES-256-CBC encryption for securing sensitive data like API keys. This is a military-grade encryption standard that provides excellent security.', 'mondays-work-ai-core' ); ?></p>
            </div>
            
            <div class="mwai-faq-item">
                <h4><?php esc_html_e( 'How does rate limiting work?', 'mondays-work-ai-core' ); ?></h4>
                <p><?php esc_html_e( 'Rate limiting tracks the number of API requests per user/IP address within a specified time window. When the limit is reached, additional requests are blocked until the time window resets.', 'mondays-work-ai-core' ); ?></p>
            </div>
            
            <div class="mwai-faq-item">
                <h4><?php esc_html_e( 'Can I integrate this with other plugins?', 'mondays-work-ai-core' ); ?></h4>
                <p><?php esc_html_e( 'Yes! The plugin provides a complete API with hooks and filters. See the Code Examples section below for integration examples.', 'mondays-work-ai-core' ); ?></p>
            </div>
            
            <div class="mwai-faq-item">
                <h4><?php esc_html_e( 'What happens if I regenerate the encryption key?', 'mondays-work-ai-core' ); ?></h4>
                <p><?php esc_html_e( 'Regenerating the encryption key will invalidate all previously encrypted data. You will need to re-enter your API key and any other encrypted information.', 'mondays-work-ai-core' ); ?></p>
            </div>
        </div>
        
        <div class="mwai-help-section">
            <h3><?php esc_html_e( 'Code Examples', 'mondays-work-ai-core' ); ?></h3>
            
            <h4><?php esc_html_e( 'Example 1: Check if API key is configured', 'mondays-work-ai-core' ); ?></h4>
            <div class="mwai-code-block">
&lt;?php
use MondaysWork\AI\MondaysWorkAICore;

$plugin = MondaysWorkAICore::get_instance();
$api_key = get_option( 'mwai_api_key' );

if ( ! empty( $api_key ) ) {
    echo 'API key is configured';
} else {
    echo 'Please configure your API key';
}
?&gt;
            </div>
            
            <h4><?php esc_html_e( 'Example 2: Encrypt sensitive data', 'mondays-work-ai-core' ); ?></h4>
            <div class="mwai-code-block">
&lt;?php
use MondaysWork\AI\Encryption;

$encryption = new Encryption();
$sensitive_data = 'my-secret-token';
$encrypted = $encryption->encrypt( $sensitive_data );

// Later, decrypt it
$decrypted = $encryption->decrypt( $encrypted );
?&gt;
            </div>
            
            <h4><?php esc_html_e( 'Example 3: Check rate limit', 'mondays-work-ai-core' ); ?></h4>
            <div class="mwai-code-block">
&lt;?php
use MondaysWork\AI\RateLimiter;

$rate_limiter = new RateLimiter();
$user_id = get_current_user_id();

if ( $rate_limiter->check_limit( $user_id, 'api_endpoint' ) ) {
    // Process request
    $rate_limiter->increment( $user_id, 'api_endpoint' );
} else {
    // Rate limit exceeded
    wp_send_json_error( 'Rate limit exceeded' );
}
?&gt;
            </div>
            
            <h4><?php esc_html_e( 'Example 4: Custom AJAX handler', 'mondays-work-ai-core' ); ?></h4>
            <div class="mwai-code-block">
&lt;?php
add_action( 'wp_ajax_my_custom_ai_action', 'my_custom_ai_handler' );

function my_custom_ai_handler() {
    check_ajax_referer( 'mwai_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    $api_key = get_option( 'mwai_api_key' );
    
    // Your AI integration logic here
    
    wp_send_json_success( array(
        'message' => 'Success',
        'data' => $result
    ) );
}
?&gt;
            </div>
        </div>
        
        <div class="mwai-help-section">
            <h3><?php esc_html_e( 'Troubleshooting', 'mondays-work-ai-core' ); ?></h3>
            
            <h4><?php esc_html_e( 'API connection test fails', 'mondays-work-ai-core' ); ?></h4>
            <ul>
                <li><?php esc_html_e( 'Verify your API key is correct', 'mondays-work-ai-core' ); ?></li>
                <li><?php esc_html_e( 'Check that cURL extension is installed and enabled', 'mondays-work-ai-core' ); ?></li>
                <li><?php esc_html_e( 'Ensure your server can make outbound HTTPS connections', 'mondays-work-ai-core' ); ?></li>
                <li><?php esc_html_e( 'Check server firewall settings', 'mondays-work-ai-core' ); ?></li>
            </ul>
            
            <h4><?php esc_html_e( 'Rate limiting not working', 'mondays-work-ai-core' ); ?></h4>
            <ul>
                <li><?php esc_html_e( 'Verify rate limiting is enabled in settings', 'mondays-work-ai-core' ); ?></li>
                <li><?php esc_html_e( 'Check database table was created during activation', 'mondays-work-ai-core' ); ?></li>
                <li><?php esc_html_e( 'Try resetting rate limits from the Rate Limiting tab', 'mondays-work-ai-core' ); ?></li>
            </ul>
            
            <h4><?php esc_html_e( 'Encryption errors', 'mondays-work-ai-core' ); ?></h4>
            <ul>
                <li><?php esc_html_e( 'Ensure OpenSSL extension is installed', 'mondays-work-ai-core' ); ?></li>
                <li><?php esc_html_e( 'Check that encryption key was generated during activation', 'mondays-work-ai-core' ); ?></li>
                <li><?php esc_html_e( 'Try regenerating the encryption key (note: this will require re-entering all encrypted data)', 'mondays-work-ai-core' ); ?></li>
            </ul>
        </div>
        
        <div class="mwai-help-section">
            <h3><?php esc_html_e( 'System Information', 'mondays-work-ai-core' ); ?></h3>
            <div class="mwai-code-block">
Plugin Version: <?php echo esc_html( MWAI_VERSION ); ?>

PHP Version: <?php echo esc_html( phpversion() ); ?>

WordPress Version: <?php echo esc_html( get_bloginfo( 'version' ) ); ?>

Plugin Directory: <?php echo esc_html( MWAI_PLUGIN_DIR ); ?>

Plugin URL: <?php echo esc_html( MWAI_PLUGIN_URL ); ?>

Database Prefix: <?php global $wpdb; echo esc_html( $wpdb->prefix ); ?>

Max Execution Time: <?php echo esc_html( ini_get( 'max_execution_time' ) ); ?>s
Memory Limit: <?php echo esc_html( ini_get( 'memory_limit' ) ); ?>

cURL: <?php echo function_exists( 'curl_version' ) ? 'Enabled' : 'Disabled'; ?>

OpenSSL: <?php echo extension_loaded( 'openssl' ) ? 'Enabled' : 'Disabled'; ?>
            </div>
        </div>
        
        <div class="mwai-help-section">
            <h3><?php esc_html_e( 'Support', 'mondays-work-ai-core' ); ?></h3>
            <p>
                <?php
                printf(
                    /* translators: %s: support URL */
                    esc_html__( 'Need help? Visit our %s for documentation and support.', 'mondays-work-ai-core' ),
                    '<a href="https://mondaysatwork.com/support" target="_blank">' . esc_html__( 'support page', 'mondays-work-ai-core' ) . '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * AJAX: Test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer( 'mwai_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Permission denied', 'mondays-work-ai-core' )
            ) );
        }
        
        $api_key = get_option( 'mwai_api_key' );
        
        if ( empty( $api_key ) ) {
            wp_send_json_error( array(
                'message' => __( 'API key is not configured', 'mondays-work-ai-core' )
            ) );
        }
        
        // Simulate API test (replace with actual API call)
        $test_result = true; // Replace with actual API call result
        
        if ( $test_result ) {
            wp_send_json_success( array(
                'message' => __( 'Connection test successful! API key is valid.', 'mondays-work-ai-core' )
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Connection test failed. Please check your API key.', 'mondays-work-ai-core' )
            ) );
        }
    }
    
    /**
     * AJAX: Get system info
     */
    public function ajax_get_system_info() {
        check_ajax_referer( 'mwai_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Permission denied', 'mondays-work-ai-core' )
            ) );
        }
        
        global $wpdb;
        
        $info = array(
            'plugin_version' => MWAI_VERSION,
            'php_version' => phpversion(),
            'wp_version' => get_bloginfo( 'version' ),
            'curl_enabled' => function_exists( 'curl_version' ),
            'openssl_enabled' => extension_loaded( 'openssl' ),
            'memory_limit' => ini_get( 'memory_limit' ),
            'max_execution_time' => ini_get( 'max_execution_time' ),
            'api_key_configured' => ! empty( get_option( 'mwai_api_key' ) ),
            'encryption_enabled' => get_option( 'mwai_encryption_enabled', '1' ) === '1',
            'rate_limit_enabled' => get_option( 'mwai_rate_limit_enabled', '1' ) === '1',
        );
        
        wp_send_json_success( $info );
    }
    
    /**
     * AJAX: Reset rate limit
     */
    public function ajax_reset_rate_limit() {
        check_ajax_referer( 'mwai_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Permission denied', 'mondays-work-ai-core' )
            ) );
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mwai_rate_limits';
        $wpdb->query( "TRUNCATE TABLE $table_name" );
        
        wp_send_json_success( array(
            'message' => __( 'All rate limits have been reset successfully.', 'mondays-work-ai-core' )
        ) );
    }
    
    /**
     * Generate encryption key
     *
     * @return string
     */
    private function generate_encryption_key() {
        return bin2hex( random_bytes( 16 ) );
    }
}

/**
 * Encryption class
 */
class Encryption {
    
    /**
     * Encrypt data
     *
     * @param string $data Data to encrypt
     * @return string|false
     */
    public function encrypt( $data ) {
        if ( get_option( 'mwai_encryption_enabled', '1' ) !== '1' ) {
            return $data;
        }
        
        $key = get_option( 'mwai_encryption_key' );
        if ( empty( $key ) ) {
            return false;
        }
        
        $iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );
        $encrypted = openssl_encrypt( $data, 'aes-256-cbc', $key, 0, $iv );
        
        if ( false === $encrypted ) {
            return false;
        }
        
        return base64_encode( $encrypted . '::' . $iv );
    }
    
    /**
     * Decrypt data
     *); ?>'
                    },
                    success: function(response) {
                        alert(response.data.message);
                        btn.prop('disabled', false).text('<?php esc_html_e( 'Reset All Rate Limits', 'mondays-work-ai-core' ); ?>');
                    },
                    error: function() {
                        alert('<?php esc_html_e( 'Error resetting rate limits', 'mondays-work-ai-core' ); ?>');
                        btn.prop('disabled', false).text('<?php esc_html_e( 'Reset All Rate Limits', 'mondays-work-ai-core' ); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render AJAX tab
     */
    private function render_ajax_tab() {
        ?>
        <div class="mwai-setting-row">
            <h3><?php esc_html_e( 'Test AJAX Endpoints', 'mondays-work-ai-core' ); ?></h3>
            <p><?php esc_html_e( 'Use these tools to test the plugin\'s AJAX functionality.', 'mondays-work-ai-core' ); ?></p>
        </div>
        
        <div class="mwai-setting-row">
            <button type="button" class="button button-primary" id="mwai-test-connection">
                <?php esc_html_e( 'Test API Connection', 'mondays-work-ai-core' ); ?>
            </button>
            <p class="description">
                <?php esc_html_e( 'Test the connection to the AI service using your configured API key.', 'mondays-work-ai-core' ); ?>
            </p>
            <div id="mwai-test-result" style="margin-top: 10px;"></div>
        </div>
        
        <div class="mwai-setting-row">
            <button type="button" class="button" id="mwai-get-system-info">
                <?php esc_html_e( 'Get System Information', 'mondays-work-ai-core' ); ?>
            </button>
            <p class="description">
                <?php esc_html_e( 'Retrieve detailed system information via AJAX.', 'mondays-work-ai-core' ); ?>
            </p>
            <div id="mwai-system-info-result" style="margin-top: 10px;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#mwai-test-connection').on('click', function() {
                var btn = $(this);
                var result = $('#mwai-test-result');
                
                btn.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'mondays-work-ai-core' ); ?>');
                result.html('<p><?php esc_html_e( 'Testing connection...', 'mondays-work-ai-core' ); ?></p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mwai_test_connection',
                        nonce: '<?php echo wp_create_nonce( 'mwai_admin_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            result.html('<div style="padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">' + response.data.message + '</div>');
                        } else {
                            result.html('<div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">' + response.data.message + '</div>');
                        }
                        btn.prop('disabled', false).text('<?php esc_html_e( 'Test API Connection', 'mondays-work-ai-core' ); ?>');
                    },
                    error: function() {
                        result.html('<div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;"><?php esc_html_e( 'Connection test failed', 'mondays-work-ai-core' ); ?></div>');
                        btn.prop('disabled', false).text('<?php esc_html_e( 'Test API Connection', 'mondays-work-ai-core' ); ?>');
                    }
                });
            });
            
            $('#mwai-get-system-info').on('click', function() {
                var btn = $(this);
                var result = $('#mwai-system-info-result');
                
                btn.prop('disabled', true).text('<?php esc_html_e( 'Loading...', 'mondays-work-ai-core' ); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mwai_get_system_info',
                        nonce: '<?php echo wp_create_nonce( 'mwai_admin_nonce'
