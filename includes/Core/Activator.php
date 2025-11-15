<?php
/**
 * Activador del Plugin - Monday's Work AI Core
 * Plugin Activator - Monday's Work AI Core
 *
 * Esta clase contiene toda la lógica que se ejecuta cuando el plugin
 * es activado. Incluye creación de tablas, configuración inicial,
 * verificación de requisitos del sistema y migraciones.
 *
 * This class contains all logic executed when the plugin
 * is activated. Includes table creation, initial setup,
 * system requirements check, and migrations.
 *
 * @package    MondaysWork\AI\Core
 * @subpackage Core
 * @since      1.0.0
 * @author     Monday's Work <info@mondayswork.com>
 */

namespace MondaysWork\AI\Core\Core;

// Evitar acceso directo / Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase de Activación del Plugin
 * Plugin Activation Class
 *
 * Gestiona todas las tareas necesarias durante la activación del plugin,
 * incluyendo verificaciones de requisitos, inicialización de base de datos
 * y configuración predeterminada.
 *
 * Manages all necessary tasks during plugin activation,
 * including requirement checks, database initialization,
 * and default configuration.
 *
 * @since 1.0.0
 */
class Activator {

    /**
     * Versión del plugin
     * Plugin version
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private static $version = '1.0.0';

    /**
     * Versión mínima de PHP requerida
     * Minimum required PHP version
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private static $min_php_version = '7.4';

    /**
     * Versión mínima de WordPress requerida
     * Minimum required WordPress version
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private static $min_wp_version = '5.8';

    /**
     * Plugins requeridos (slug => nombre)
     * Required plugins (slug => name)
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    private static $required_plugins = array(
        'woocommerce/woocommerce.php' => 'WooCommerce',
    );

    /**
     * Ejecuta la activación del plugin
     * Executes plugin activation
     *
     * Método principal que se ejecuta cuando el plugin es activado.
     * Verifica requisitos, crea estructuras necesarias y establece
     * la configuración inicial.
     *
     * Main method executed when the plugin is activated.
     * Checks requirements, creates necessary structures, and sets
     * initial configuration.
     *
     * @since  1.0.0
     * @access public
     * @static
     * @return void
     */
    public static function activate() {
        try {
            // Verificar requisitos del sistema / Check system requirements
            self::check_requirements();

            // Verificar permisos / Check permissions
            self::check_permissions();

            // Crear tablas de base de datos / Create database tables
            self::create_tables();

            // Configurar opciones predeterminadas / Set default options
            self::setup_default_options();

            // Configurar capabilities / Setup capabilities
            self::setup_capabilities();

            // Programar eventos cron / Schedule cron events
            self::schedule_events();

            // Crear directorios necesarios / Create necessary directories
            self::create_directories();

            // Guardar versión del plugin / Save plugin version
            self::save_version();

            // Registrar activación / Log activation
            self::log_activation();

            // Limpiar caché / Flush cache
            self::flush_cache();

            /**
             * Acción disparada después de la activación exitosa
             * Action fired after successful activation
             *
             * @since 1.0.0
             * @param string $version Versión del plugin / Plugin version
             */
            do_action( 'mondays_work_ai_core_activated', self::$version );

        } catch ( \Exception $e ) {
            // Registrar error / Log error
            self::log_error( 'Error durante la activación', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ) );

            // Desactivar el plugin si hay error crítico / Deactivate plugin on critical error
            deactivate_plugins( plugin_basename( __FILE__ ) );

            // Mostrar mensaje de error / Display error message
            wp_die(
                esc_html( $e->getMessage() ),
                esc_html__( 'Error de Activación', 'mondays-work-ai-core' ),
                array( 'back_link' => true )
            );
        }
    }

    /**
     * Verifica los requisitos del sistema
     * Checks system requirements
     *
     * Valida que el entorno cumple con los requisitos mínimos
     * para ejecutar el plugin correctamente.
     *
     * Validates that the environment meets minimum requirements
     * to run the plugin correctly.
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     * @throws \Exception Si no se cumplen los requisitos / If requirements are not met
     */
    private static function check_requirements() {
        $errors = array();

        // Verificar versión de PHP / Check PHP version
        if ( version_compare( PHP_VERSION, self::$min_php_version, '<' ) ) {
            $errors[] = sprintf(
                /* translators: 1: Current PHP version, 2: Required PHP version */
                __( 'Monday\'s Work AI Core requiere PHP %2$s o superior. Tu versión actual es %1$s.', 'mondays-work-ai-core' ),
                PHP_VERSION,
                self::$min_php_version
            );
        }

        // Verificar versión de WordPress / Check WordPress version
        global $wp_version;
        if ( version_compare( $wp_version, self::$min_wp_version, '<' ) ) {
            $errors[] = sprintf(
                /* translators: 1: Current WP version, 2: Required WP version */
                __( 'Monday\'s Work AI Core requiere WordPress %2$s o superior. Tu versión actual es %1$s.', 'mondays-work-ai-core' ),
                $wp_version,
                self::$min_wp_version
            );
        }

        // Verificar extensiones PHP requeridas / Check required PHP extensions
        $required_extensions = array( 'json', 'curl', 'mbstring' );
        foreach ( $required_extensions as $extension ) {
            if ( ! extension_loaded( $extension ) ) {
                $errors[] = sprintf(
                    /* translators: %s: Extension name */
                    __( 'La extensión de PHP "%s" es requerida pero no está instalada.', 'mondays-work-ai-core' ),
                    $extension
                );
            }
        }

        // Verificar plugins requeridos / Check required plugins
        foreach ( self::$required_plugins as $plugin_file => $plugin_name ) {
            if ( ! is_plugin_active( $plugin_file ) ) {
                $errors[] = sprintf(
                    /* translators: %s: Plugin name */
                    __( 'El plugin "%s" debe estar instalado y activado.', 'mondays-work-ai-core' ),
                    $plugin_name
                );
            }
        }

        // Verificar funciones requeridas / Check required functions
        $required_functions = array( 'curl_init', 'json_encode', 'json_decode' );
        foreach ( $required_functions as $function ) {
            if ( ! function_exists( $function ) ) {
                $errors[] = sprintf(
                    /* translators: %s: Function name */
                    __( 'La función de PHP "%s" es requerida pero no está disponible.', 'mondays-work-ai-core' ),
                    $function
                );
            }
        }

        // Si hay errores, lanzar excepción / If there are errors, throw exception
        if ( ! empty( $errors ) ) {
            throw new \Exception( implode( '<br>', $errors ) );
        }
    }

    /**
     * Verifica los permisos necesarios
     * Checks necessary permissions
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     * @throws \Exception Si no hay permisos suficientes / If insufficient permissions
     */
    private static function check_permissions() {
        // Verificar que el usuario actual puede activar plugins / Check current user can activate plugins
        if ( ! current_user_can( 'activate_plugins' ) ) {
            throw new \Exception(
                __( 'No tienes permisos suficientes para activar plugins.', 'mondays-work-ai-core' )
            );
        }

        // Verificar permisos de escritura en el directorio de uploads / Check write permissions in uploads directory
        $upload_dir = wp_upload_dir();
        if ( ! wp_is_writable( $upload_dir['basedir'] ) ) {
            throw new \Exception(
                __( 'El directorio de uploads no tiene permisos de escritura.', 'mondays-work-ai-core' )
            );
        }
    }

    /**
     * Crea las tablas de base de datos necesarias
     * Creates necessary database tables
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix . 'mw_ai_';

        // Tabla de logs de IA / AI logs table
        $logs_table = $table_prefix . 'logs';
        $logs_sql = "CREATE TABLE IF NOT EXISTS {$logs_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NULL,
            provider varchar(50) NOT NULL,
            model varchar(100) NOT NULL,
            prompt text NOT NULL,
            response longtext NULL,
            tokens_used int(11) unsigned NULL,
            cost decimal(10,4) NULL,
            status varchar(20) NOT NULL DEFAULT 'success',
            error_message text NULL,
            metadata longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY provider (provider),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Tabla de caché de respuestas / Response cache table
        $cache_table = $table_prefix . 'cache';
        $cache_sql = "CREATE TABLE IF NOT EXISTS {$cache_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cache_key varchar(255) NOT NULL,
            provider varchar(50) NOT NULL,
            model varchar(100) NOT NULL,
            prompt_hash varchar(64) NOT NULL,
            response longtext NOT NULL,
            metadata longtext NULL,
            expires_at datetime NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY cache_key (cache_key),
            KEY prompt_hash (prompt_hash),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        // Tabla de configuraciones de módulos / Module configurations table
        $modules_table = $table_prefix . 'modules';
        $modules_sql = "CREATE TABLE IF NOT EXISTS {$modules_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            module_slug varchar(100) NOT NULL,
            module_name varchar(200) NOT NULL,
            module_version varchar(20) NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            config longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY module_slug (module_slug),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Ejecutar queries usando dbDelta / Execute queries using dbDelta
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        dbDelta( $logs_sql );
        dbDelta( $cache_sql );
        dbDelta( $modules_sql );

        /**
         * Acción para crear tablas personalizadas adicionales
         * Action to create additional custom tables
         *
         * @since 1.0.0
         * @param string $table_prefix Prefijo de tablas / Table prefix
         */
        do_action( 'mondays_work_ai_core_create_tables', $table_prefix );
    }

    /**
     * Configura las opciones predeterminadas
     * Sets up default options
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function setup_default_options() {
        $default_options = array(
            'ai_provider'       => 'openai',
            'api_key'           => '',
            'model'             => 'gpt-4',
            'temperature'       => 0.7,
            'max_tokens'        => 1000,
            'enabled'           => true,
            'debug_mode'        => false,
            'cache_enabled'     => true,
            'cache_duration'    => 3600,
            'rate_limit'        => 60,
            'allowed_roles'     => array( 'administrator' ),
        );

        $option_name = 'mondays-work-ai-core_config';
        
        // Solo agregar si no existe / Only add if doesn't exist
        if ( false === get_option( $option_name ) ) {
            add_option( $option_name, $default_options, '', 'no' );
        }

        /**
         * Acción para configurar opciones personalizadas adicionales
         * Action to set up additional custom options
         *
         * @since 1.0.0
         */
        do_action( 'mondays_work_ai_core_setup_options' );
    }

    /**
     * Configura las capacidades de usuario
     * Sets up user capabilities
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function setup_capabilities() {
        // Obtener rol de administrador / Get administrator role
        $admin_role = get_role( 'administrator' );

        if ( $admin_role ) {
            // Agregar capacidades personalizadas / Add custom capabilities
            $capabilities = array(
                'manage_ai_core',
                'view_ai_logs',
                'configure_ai_settings',
                'use_ai_features',
            );

            foreach ( $capabilities as $cap ) {
                $admin_role->add_cap( $cap );
            }
        }

        /**
         * Acción para configurar capacidades personalizadas adicionales
         * Action to set up additional custom capabilities
         *
         * @since 1.0.0
         */
        do_action( 'mondays_work_ai_core_setup_capabilities' );
    }

    /**
     * Programa los eventos cron
     * Schedules cron events
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function schedule_events() {
        // Limpiar caché antiguo diariamente / Clean old cache daily
        if ( ! wp_next_scheduled( 'mondays_work_ai_core_cleanup_cache' ) ) {
            wp_schedule_event( time(), 'daily', 'mondays_work_ai_core_cleanup_cache' );
        }

        // Limpiar logs antiguos semanalmente / Clean old logs weekly
        if ( ! wp_next_scheduled( 'mondays_work_ai_core_cleanup_logs' ) ) {
            wp_schedule_event( time(), 'weekly', 'mondays_work_ai_core_cleanup_logs' );
        }

        /**
         * Acción para programar eventos cron personalizados adicionales
         * Action to schedule additional custom cron events
         *
         * @since 1.0.0
         */
        do_action( 'mondays_work_ai_core_schedule_events' );
    }

    /**
     * Crea los directorios necesarios
     * Creates necessary directories
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function create_directories() {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/mondays-work-ai-core';

        // Crear directorio principal / Create main directory
        if ( ! file_exists( $base_dir ) ) {
            wp_mkdir_p( $base_dir );
        }

        // Crear subdirectorios / Create subdirectories
        $subdirs = array( 'logs', 'cache', 'temp', 'exports' );
        
        foreach ( $subdirs as $subdir ) {
            $dir_path = $base_dir . '/' . $subdir;
            if ( ! file_exists( $dir_path ) ) {
                wp_mkdir_p( $dir_path );
            }

            // Crear archivo .htaccess para proteger el directorio / Create .htaccess to protect directory
            $htaccess_file = $dir_path . '/.htaccess';
            if ( ! file_exists( $htaccess_file ) ) {
                $htaccess_content = "deny from all\n";
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
                file_put_contents( $htaccess_file, $htaccess_content );
            }

            // Crear archivo index.php vacío / Create empty index.php
            $index_file = $dir_path . '/index.php';
            if ( ! file_exists( $index_file ) ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
                file_put_contents( $index_file, '<?php // Silence is golden' );
            }
        }

        /**
         * Acción para crear directorios personalizados adicionales
         * Action to create additional custom directories
         *
         * @since 1.0.0
         * @param string $base_dir Directorio base / Base directory
         */
        do_action( 'mondays_work_ai_core_create_directories', $base_dir );
    }

    /**
     * Guarda la versión del plugin
     * Saves the plugin version
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function save_version() {
        update_option( 'mondays_work_ai_core_version', self::$version );
        update_option( 'mondays_work_ai_core_activated_at', current_time( 'mysql' ) );
    }

    /**
     * Registra la activación en los logs
     * Logs the activation
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function log_activation() {
        $log_data = array(
            'version'    => self::$version,
            'php'        => PHP_VERSION,
            'wordpress'  => get_bloginfo( 'version' ),
            'user_id'    => get_current_user_id(),
            'site_url'   => get_site_url(),
            'activated_at' => current_time( 'mysql' ),
        );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log(
                sprintf(
                    '[Monday\'s Work AI Core] Plugin activado | Versión: %s | PHP: %s | WP: %s',
                    $log_data['version'],
                    $log_data['php'],
                    $log_data['wordpress']
                )
            );
        }

        // Guardar en la tabla de logs si existe / Save to logs table if exists
        global $wpdb;
        $logs_table = $wpdb->prefix . 'mw_ai_logs';
        
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $logs_table ) ) === $logs_table ) {
            $wpdb->insert(
                $logs_table,
                array(
                    'user_id'  => $log_data['user_id'],
                    'provider' => 'system',
                    'model'    => 'activation',
                    'prompt'   => 'Plugin activado',
                    'response' => wp_json_encode( $log_data ),
                    'status'   => 'success',
                    'metadata' => wp_json_encode( array( 'type' => 'activation' ) ),
                ),
                array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
            );
        }
    }

    /**
     * Limpia la caché de WordPress
     * Flushes WordPress cache
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function flush_cache() {
        // Limpiar rewrite rules / Flush rewrite rules
        flush_rewrite_rules();

        // Limpiar caché de objeto / Flush object cache
        wp_cache_flush();

        /**
         * Acción para limpiar cachés personalizados adicionales
         * Action to flush additional custom caches
         *
         * @since 1.0.0
         */
        do_action( 'mondays_work_ai_core_flush_cache' );
    }

    /**
     * Registra un error
     * Logs an error
     *
     * @since  1.0.0
     * @access private
     * @static
     * @param  string $message Mensaje de error / Error message
     * @param  array  $context Contexto adicional / Additional context
     * @return void
     */
    private static function log_error( $message, $context = array() ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log(
                sprintf(
                    '[Monday\'s Work AI Core - Activator] %s | Context: %s',
                    $message,
                    wp_json_encode( $context )
                )
            );
        }
    }
}
