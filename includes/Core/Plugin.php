<?php
/**
 * Clase Principal del Plugin - Monday's Work AI Core
 * Main Plugin Class - Monday's Work AI Core
 *
 * Esta clase implementa el patrón Singleton y actúa como punto de entrada
 * principal del plugin. Gestiona la inicialización de módulos, registro de
 * hooks de WordPress y proporciona acceso a servicios centrales.
 *
 * This class implements the Singleton pattern and acts as the main entry point
 * for the plugin. It manages module initialization, WordPress hook registration,
 * and provides access to core services.
 *
 * @package    MondaysWork\AI\Core
 * @subpackage Core
 * @since      1.0.0
 * @author     Monday's Work <info@mondayswork.com>
 */

namespace MondaysWork\AI\Core\Core;

use MondaysWork\AI\Core\AI\AIClientFactory;
use MondaysWork\AI\Core\AI\AIClientInterface;

// Evitar acceso directo / Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase Principal del Plugin
 * Main Plugin Class
 *
 * Implementa el patrón Singleton para asegurar una única instancia
 * durante todo el ciclo de vida de la aplicación.
 *
 * Implements Singleton pattern to ensure a single instance
 * throughout the application lifecycle.
 *
 * @since 1.0.0
 */
class Plugin {

    /**
     * Instancia única del plugin (Singleton)
     * Single plugin instance (Singleton)
     *
     * @since  1.0.0
     * @access private
     * @var    Plugin|null
     */
    private static $instance = null;

    /**
     * Gestor de configuración del plugin
     * Plugin configuration manager
     *
     * @since  1.0.0
     * @access private
     * @var    Config|null
     */
    private $config = null;

    /**
     * Cliente de IA activo
     * Active AI client
     *
     * @since  1.0.0
     * @access private
     * @var    AIClientInterface|null
     */
    private $ai_client = null;

    /**
     * Factory para crear clientes de IA
     * Factory for creating AI clients
     *
     * @since  1.0.0
     * @access private
     * @var    AIClientFactory|null
     */
    private $ai_factory = null;

    /**
     * Admin UI instance
     * Instancia de la interfaz de administración
     *
     * @since  1.0.0
     * @access private
     * @var    \MondaysWork\AI\Core\Admin\AdminUI|null
     */
    private $admin_ui = null;

    /**
     * Versión del plugin
     * Plugin version
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $version = '1.0.0';

    /**
     * Slug del plugin
     * Plugin slug
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $plugin_slug = 'mondays-work-ai-core';

    /**
     * Indica si el plugin ha sido inicializado
     * Indicates if the plugin has been initialized
     *
     * @since  1.0.0
     * @access private
     * @var    bool
     */
    private $initialized = false;

    /**
     * Constructor privado (Singleton)
     * Private constructor (Singleton)
     *
     * Previene la creación directa de instancias de la clase.
     * Prevents direct instantiation of the class.
     *
     * @since  1.0.0
     * @access private
     */
    private function __construct() {
        // Constructor vacío - La inicialización se hace en init()
        // Empty constructor - Initialization happens in init()
    }

    /**
     * Obtiene la instancia única del plugin (Singleton)
     * Gets the single plugin instance (Singleton)
     *
     * Crea la instancia si no existe, o devuelve la existente.
     * Creates the instance if it doesn't exist, or returns the existing one.
     *
     * @since  1.0.0
     * @access public
     * @return Plugin Instancia única del plugin / Single plugin instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Inicializa el plugin
     * Initializes the plugin
     *
     * Configura todos los componentes, registra hooks y prepara el plugin
     * para su funcionamiento. Este método debe llamarse solo una vez.
     *
     * Sets up all components, registers hooks, and prepares the plugin
     * for operation. This method should only be called once.
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function init() {
        // Prevenir inicialización múltiple / Prevent multiple initialization
        if ( $this->initialized ) {
            return;
        }

        try {
            // Cargar configuración / Load configuration
            $this->load_config();

            // Inicializar factory de clientes IA / Initialize AI client factory
            $this->setup_ai_factory();

            // Cargar cliente de IA / Load AI client
            $this->load_ai_client();

            // Inicializar interfaz de administración / Initialize admin UI
            $this->setup_admin_ui();

            // Registrar hooks de WordPress / Register WordPress hooks
            $this->register_hooks();

            // Cargar dependencias adicionales / Load additional dependencies
            $this->load_dependencies();

            // Marcar como inicializado / Mark as initialized
            $this->initialized = true;

            /**
             * Acción disparada después de la inicialización del plugin
             * Action fired after plugin initialization
             *
             * @since 1.0.0
             * @param Plugin $plugin Instancia del plugin / Plugin instance
             */
            do_action( 'mondays_work_ai_core_initialized', $this );

        } catch ( \Exception $e ) {
            // Registrar error crítico / Log critical error
            $this->log_error(
                'Error crítico durante la inicialización del plugin',
                array(
                    'error'   => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'trace'   => $e->getTraceAsString(),
                )
            );

            // Mostrar aviso en admin / Show admin notice
            add_action( 'admin_notices', array( $this, 'display_initialization_error' ) );
        }
    }

    /**
     * Carga y configura el gestor de configuración
     * Loads and configures the configuration manager
     *
     * @since  1.0.0
     * @access private
     * @return void
     * @throws \Exception Si no se puede cargar la configuración / If configuration cannot be loaded
     */
    private function load_config() {
        if ( ! class_exists( 'MondaysWork\AI\Core\Core\Config' ) ) {
            throw new \Exception( 'La clase Config no está disponible.' );
        }

        $this->config = new Config( $this->plugin_slug );
        $this->config->load();
    }

    /**
     * Configura el factory de clientes IA
     * Sets up the AI client factory
     *
     * @since  1.0.0
     * @access private
     * @return void
     * @throws \Exception Si no se puede crear el factory / If factory cannot be created
     */
    private function setup_ai_factory() {
        if ( ! class_exists( 'MondaysWork\AI\Core\AI\AIClientFactory' ) ) {
            throw new \Exception( 'La clase AIClientFactory no está disponible.' );
        }

        $this->ai_factory = new AIClientFactory( $this->config );
    }

    /**
     * Carga el cliente de IA configurado
     * Loads the configured AI client
     *
     * Intenta crear el cliente IA basado en la configuración actual.
     * Si falla, registra el error pero no detiene la ejecución.
     *
     * Attempts to create the AI client based on current configuration.
     * If it fails, logs the error but doesn't halt execution.
     *
     * @since  1.0.0
     * @access private
     * @return void
     */
    private function load_ai_client() {
        try {
            $provider = $this->config->get( 'ai_provider', 'openai' );
            $this->ai_client = $this->ai_factory->create( $provider );

            /**
             * Filtro para modificar el cliente IA después de su creación
             * Filter to modify the AI client after creation
             *
             * @since 1.0.0
             * @param AIClientInterface $ai_client Cliente IA / AI client
             * @param string            $provider  Proveedor seleccionado / Selected provider
             */
            $this->ai_client = apply_filters(
                'mondays_work_ai_core_client',
                $this->ai_client,
                $provider
            );

        } catch ( \Exception $e ) {
            $this->log_error(
                'No se pudo cargar el cliente de IA',
                array(
                    'provider' => $provider ?? 'unknown',
                    'error'    => $e->getMessage(),
                )
            );
        }
    }

    /**
     * Setup admin UI instance
     * Configura la instancia de la interfaz de administración
     *
     * @since  1.0.0
     * @access private
     * @return void
     */
    private function setup_admin_ui() {
        if ( ! class_exists( 'MondaysWork\AI\Core\Admin\AdminUI' ) ) {
            $this->log_error( 'La clase AdminUI no está disponible' );
            return;
        }

        try {
            // Create AdminUI instance / Crear instancia de AdminUI
            $this->admin_ui = new \MondaysWork\AI\Core\Admin\AdminUI( $this->config, $this->ai_factory );

            // Initialize AdminUI / Inicializar AdminUI
            $this->admin_ui->init();

            /**
             * Action fired after AdminUI initialization
             * Acción disparada después de inicializar AdminUI
             *
             * @since 1.0.0
             * @param \MondaysWork\AI\Core\Admin\AdminUI $admin_ui Admin UI instance / Instancia de AdminUI
             */
            do_action( 'mondays_work_ai_core_admin_ui_initialized', $this->admin_ui );

        } catch ( \Exception $e ) {
            $this->log_error(
                'Error al inicializar AdminUI',
                array(
                    'error' => $e->getMessage(),
                )
            );
        }
    }

    /**
     * Registra todos los hooks de WordPress
     * Registers all WordPress hooks
     *
     * @since  1.0.0
     * @access private
     * @return void
     */
    private function register_hooks() {
        // Hooks de internacionalización / Internationalization hooks
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Admin hooks are now handled by AdminUI class
        // Los hooks de admin ahora son manejados por la clase AdminUI

        // Hooks de frontend / Frontend hooks
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );

        /**
         * Acción para que otros módulos registren sus propios hooks
         * Action for other modules to register their own hooks
         *
         * @since 1.0.0
         * @param Plugin $plugin Instancia del plugin / Plugin instance
         */
        do_action( 'mondays_work_ai_core_register_hooks', $this );
    }

    /**
     * Carga las dependencias adicionales del plugin
     * Loads additional plugin dependencies
     *
     * @since  1.0.0
     * @access private
     * @return void
     */
    private function load_dependencies() {
        /**
         * Acción para cargar dependencias personalizadas
         * Action to load custom dependencies
         *
         * @since 1.0.0
         * @param Plugin $plugin Instancia del plugin / Plugin instance
         */
        do_action( 'mondays_work_ai_core_load_dependencies', $this );
    }

    /**
     * Carga el dominio de texto para internacionalización
     * Loads text domain for internationalization
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            $this->plugin_slug,
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }

    /**
     * The following admin methods are now handled by AdminUI class
     * Los siguientes métodos de admin ahora son manejados por la clase AdminUI
     *
     * - register_admin_menu()
     * - register_settings()
     * - enqueue_admin_assets()
     * - render_admin_page()
     * - display_initialization_error()
     */

    /**
     * Muestra un aviso de error de inicialización
     * Displays an initialization error notice
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function display_initialization_error() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'Monday\'s Work AI Core:', 'mondays-work-ai-core' ); ?></strong>
                <?php esc_html_e( 'Hubo un error durante la inicialización del plugin. Por favor, verifica los logs.', 'mondays-work-ai-core' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Encola los assets públicos (frontend)
     * Enqueues public (frontend) assets
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function enqueue_public_assets() {
        /**
         * Filtro para habilitar/deshabilitar assets públicos
         * Filter to enable/disable public assets
         *
         * @since 1.0.0
         * @param bool $load_assets Si cargar los assets / Whether to load assets
         */
        if ( ! apply_filters( 'mondays_work_ai_core_load_public_assets', true ) ) {
            return;
        }

        // Aquí se encolarían CSS y JS públicos
        // Here public CSS and JS would be enqueued
    }

    /**
     * Obtiene el gestor de configuración
     * Gets the configuration manager
     *
     * @since  1.0.0
     * @access public
     * @return Config|null Gestor de configuración / Configuration manager
     */
    public function get_config() {
        return $this->config;
    }

    /**
     * Obtiene el cliente de IA activo
     * Gets the active AI client
     *
     * @since  1.0.0
     * @access public
     * @return AIClientInterface|null Cliente de IA / AI client
     */
    public function get_ai_client() {
        return $this->ai_client;
    }

    /**
     * Obtiene el factory de clientes IA
     * Gets the AI client factory
     *
     * @since  1.0.0
     * @access public
     * @return AIClientFactory|null Factory de clientes IA / AI client factory
     */
    public function get_ai_factory() {
        return $this->ai_factory;
    }

    /**
     * Obtiene la instancia de la interfaz de administración
     * Gets the admin UI instance
     *
     * @since  1.0.0
     * @access public
     * @return \MondaysWork\AI\Core\Admin\AdminUI|null Admin UI instance / Instancia de AdminUI
     */
    public function get_admin_ui() {
        return $this->admin_ui;
    }

    /**
     * Obtiene la versión del plugin
     * Gets the plugin version
     *
     * @since  1.0.0
     * @access public
     * @return string Versión del plugin / Plugin version
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Obtiene el slug del plugin
     * Gets the plugin slug
     *
     * @since  1.0.0
     * @access public
     * @return string Slug del plugin / Plugin slug
     */
    public function get_plugin_slug() {
        return $this->plugin_slug;
    }

    /**
     * Verifica si el plugin está inicializado
     * Checks if the plugin is initialized
     *
     * @since  1.0.0
     * @access public
     * @return bool True si está inicializado / True if initialized
     */
    public function is_initialized() {
        return $this->initialized;
    }

    /**
     * Registra un error en el log de WordPress
     * Logs an error to WordPress log
     *
     * @since  1.0.0
     * @access private
     * @param  string $message Mensaje de error / Error message
     * @param  array  $context Contexto adicional / Additional context
     * @return void
     */
    private function log_error( $message, $context = array() ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log(
                sprintf(
                    '[Monday\'s Work AI Core] %s | Context: %s',
                    $message,
                    wp_json_encode( $context )
                )
            );
        }

        /**
         * Acción disparada cuando se registra un error
         * Action fired when an error is logged
         *
         * @since 1.0.0
         * @param string $message Mensaje de error / Error message
         * @param array  $context Contexto del error / Error context
         */
        do_action( 'mondays_work_ai_core_error', $message, $context );
    }

    /**
     * Previene la clonación de la instancia (Singleton)
     * Prevents cloning of the instance (Singleton)
     *
     * @since  1.0.0
     * @access private
     * @return void
     */
    private function __clone() {
        _doing_it_wrong(
            __FUNCTION__,
            esc_html__( 'No se permite clonar esta clase.', 'mondays-work-ai-core' ),
            '1.0.0'
        );
    }

    /**
     * Previene la deserialización de la instancia (Singleton)
     * Prevents unserialization of the instance (Singleton)
     *
     * @since  1.0.0
     * @access private
     * @return void
     */
    private function __wakeup() {
        _doing_it_wrong(
            __FUNCTION__,
            esc_html__( 'No se permite deserializar esta clase.', 'mondays-work-ai-core' ),
            '1.0.0'
        );
    }
}
