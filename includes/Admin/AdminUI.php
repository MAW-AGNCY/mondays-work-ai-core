<?php
/**
 * Admin UI Class - Monday's Work AI Core
 * Clase de Interfaz de Administración - Monday's Work AI Core
 *
 * Main administrative interface for the plugin with corporate Mondays at Work identity.
 * Interfaz administrativa principal del plugin con identidad corporativa Mondays at Work.
 *
 * @package    MondaysWork\AI\Core
 * @subpackage Admin
 * @since      1.0.0
 * @author     Mondays at Work <info@mondaysatwork.com>
 * @license    Proprietary
 * @link       https://github.com/MAW-AGNCY/mondays-work-ai-core
 */

namespace MondaysWork\AI\Core\Admin;

use MondaysWork\AI\Core\Core\Config;
use MondaysWork\AI\Core\AI\AIClientFactory;

// Exit if accessed directly / Salir si se accede directamente
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin UI Class
 * Clase de Interfaz de Administración
 *
 * Manages the administrative interface, settings, and user interactions
 * in WordPress admin panel with Mondays at Work branding.
 *
 * Gestiona la interfaz administrativa, configuraciones e interacciones
 * de usuario en el panel de WordPress con marca Mondays at Work.
 *
 * @since 1.0.0
 */
class AdminUI {

    /**
     * Configuration manager instance
     * Instancia del gestor de configuración
     *
     * @since  1.0.0
     * @access private
     * @var    Config
     */
    private $config;

    /**
     * AI Client Factory instance
     * Instancia del Factory de clientes IA
     *
     * @since  1.0.0
     * @access private
     * @var    AIClientFactory
     */
    private $ai_factory;

    /**
     * Plugin slug
     * Slug del plugin
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $plugin_slug = 'mondays-work-ai-core';

    /**
     * Current active tab
     * Pestaña activa actual
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $active_tab;

    /**
     * Available tabs configuration
     * Configuración de pestañas disponibles
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    private $tabs = array();

    /**
     * Constructor
     *
     * Initializes the admin UI with required dependencies.
     * Inicializa la interfaz administrativa con las dependencias requeridas.
     *
     * @since  1.0.0
     * @access public
     * @param  Config          $config     Configuration manager / Gestor de configuración
     * @param  AIClientFactory $ai_factory AI client factory / Factory de clientes IA
     */
    public function __construct( Config $config, AIClientFactory $ai_factory ) {
        $this->config     = $config;
        $this->ai_factory = $ai_factory;

        // Setup tabs / Configurar pestañas
        $this->setup_tabs();

        // Get active tab / Obtener pestaña activa
        $this->active_tab = $this->get_active_tab();
    }

    /**
     * Initialize admin UI hooks
     * Inicializa hooks de interfaz administrativa
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function init(): void {
        // Add admin menu / Añadir menú de administración
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

        // Register settings / Registrar configuraciones
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Enqueue admin assets / Encolar assets de admin
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // Handle AJAX requests / Manejar peticiones AJAX
        add_action( 'wp_ajax_mwai_test_connection', array( $this, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_mwai_save_settings', array( $this, 'ajax_save_settings' ) );
    }

    /**
     * Setup available tabs
     * Configura las pestañas disponibles
     *
     * @since  1.0.0
     * @access private
     * @return void
     */
    private function setup_tabs(): void {
        $this->tabs = array(
            'general' => array(
                'label' => __( 'General', 'mondays-work-ai-core' ),
                'icon'  => 'dashicons-admin-generic',
            ),
            'providers' => array(
                'label' => __( 'Proveedores de IA', 'mondays-work-ai-core' ),
                'icon'  => 'dashicons-cloud',
            ),
            'cache' => array(
                'label' => __( 'Caché y Rendimiento', 'mondays-work-ai-core' ),
                'icon'  => 'dashicons-performance',
            ),
            'help' => array(
                'label' => __( 'Ayuda', 'mondays-work-ai-core' ),
                'icon'  => 'dashicons-sos',
            ),
        );

        /**
         * Filter to modify available tabs
         * Filtro para modificar las pestañas disponibles
         *
         * @since 1.0.0
         * @param array $tabs Available tabs / Pestañas disponibles
         */
        $this->tabs = apply_filters( 'mondays_work_ai_core_admin_tabs', $this->tabs );
    }

    /**
     * Get current active tab
     * Obtiene la pestaña activa actual
     *
     * @since  1.0.0
     * @access private
     * @return string Active tab slug / Slug de pestaña activa
     */
    private function get_active_tab(): string {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

        // Validate tab exists / Validar que la pestaña existe
        if ( ! array_key_exists( $tab, $this->tabs ) ) {
            $tab = 'general';
        }

        return $tab;
    }

    /**
     * Add admin menu pages
     * Añade páginas al menú de administración
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function add_admin_menu(): void {
        // Main menu page / Página de menú principal
        add_menu_page(
            __( 'Monday\'s Work AI Core', 'mondays-work-ai-core' ),
            __( 'AI Core', 'mondays-work-ai-core' ),
            'manage_options',
            $this->plugin_slug,
            array( $this, 'render_admin_page' ),
            'dashicons-superhero',
            80
        );

        // Settings submenu / Submenú de configuración
        add_submenu_page(
            $this->plugin_slug,
            __( 'Configuración', 'mondays-work-ai-core' ),
            __( 'Configuración', 'mondays-work-ai-core' ),
            'manage_options',
            $this->plugin_slug,
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Enqueue admin assets (CSS and JS)
     * Encola assets de administración (CSS y JS)
     *
     * @since  1.0.0
     * @access public
     * @param  string $hook Current admin page hook / Hook de página actual
     * @return void
     */
    public function enqueue_admin_assets( string $hook ): void {
        // Only load on plugin pages / Solo cargar en páginas del plugin
        if ( false === strpos( $hook, $this->plugin_slug ) ) {
            return;
        }

        $plugin_url = plugin_dir_url( dirname( dirname( __FILE__ ) ) );
        $version    = '1.0.0';

        // Enqueue CSS / Encolar CSS
        wp_enqueue_style(
            'mwai-admin-style',
            $plugin_url . 'assets/css/admin-style.css',
            array(),
            $version,
            'all'
        );

        // Enqueue JS / Encolar JS
        wp_enqueue_script(
            'mwai-admin-script',
            $plugin_url . 'assets/js/admin-script.js',
            array( 'jquery' ),
            $version,
            true
        );

        // Localize script / Localizar script
        wp_localize_script(
            'mwai-admin-script',
            'mwaiAdmin',
            array(
                'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
                'nonce'            => wp_create_nonce( 'mwai_admin_nonce' ),
                'i18n'             => array(
                    'saving'           => __( 'Guardando...', 'mondays-work-ai-core' ),
                    'saved'            => __( 'Configuración guardada', 'mondays-work-ai-core' ),
                    'error'            => __( 'Error al guardar', 'mondays-work-ai-core' ),
                    'testing'          => __( 'Probando conexión...', 'mondays-work-ai-core' ),
                    'connectionOk'     => __( 'Conexión exitosa', 'mondays-work-ai-core' ),
                    'connectionFailed' => __( 'Error de conexión', 'mondays-work-ai-core' ),
                    'confirmDelete'    => __( '¿Estás seguro de eliminar esta configuración?', 'mondays-work-ai-core' ),
                ),
            )
        );
    }

    /**
     * Render main admin page
     * Renderiza página principal de administración
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function render_admin_page(): void {
        // Check permissions / Verificar permisos
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos suficientes para acceder a esta página.', 'mondays-work-ai-core' ) );
        }

        ?>
        <div class="wrap mwai-admin-wrap">
            <!-- Header with Mondays at Work branding / Cabecera con marca Mondays at Work -->
            <div class="mwai-header">
                <h1 class="mwai-title">
                    <span class="mwai-logo">MONDAYS AT WORK</span>
                    <span class="mwai-subtitle"><?php esc_html_e( 'AI Core', 'mondays-work-ai-core' ); ?></span>
                </h1>
                <p class="mwai-description">
                    <?php esc_html_e( 'Sistema modular de Inteligencia Artificial para WooCommerce', 'mondays-work-ai-core' ); ?>
                </p>
            </div>

            <!-- Navigation tabs / Pestañas de navegación -->
            <nav class="mwai-tabs">
                <?php $this->render_tabs(); ?>
            </nav>

            <!-- Tab content / Contenido de pestañas -->
            <div class="mwai-tab-content">
                <?php
                switch ( $this->active_tab ) {
                    case 'providers':
                        $this->render_providers_tab();
                        break;
                    case 'cache':
                        $this->render_cache_tab();
                        break;
                    case 'help':
                        $this->render_help_tab();
                        break;
                    case 'general':
                    default:
                        $this->render_general_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render navigation tabs
     * Renderiza pestañas de navegación
     *
     * @since  1.0.0
     * @access private
     * @return void
     */
    private function render_tabs(): void {
        foreach ( $this->tabs as $tab_slug => $tab_data ) {
            $active_class = $this->active_tab === $tab_slug ? 'mwai-tab-active' : '';
            $tab_url = add_query_arg(
                array(
                    'page' => $this->plugin_slug,
                    'tab'  => $tab_slug,
                ),
                admin_url( 'admin.php' )
            );

            printf(
                '<a href="%s" class="mwai-tab %s">
                    <span class="dashicons %s"></span>
                    <span class="mwai-tab-label">%s</span>
                </a>',
                esc_url( $tab_url ),
                esc_attr( $active_class ),
                esc_attr( $tab_data['icon'] ),
                esc_html( $tab_data['label'] )
            );
        }
    }

    /**
     * Register plugin settings
     * Registra configuraciones del plugin
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function register_settings(): void {
        // Register main settings / Registrar configuración principal
        register_setting(
            $this->plugin_slug . '_settings',
            $this->plugin_slug . '_config',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => array(),
            )
        );

        /**
         * Action to register additional settings
         * Acción para registrar configuraciones adicionales
         *
         * @since 1.0.0
         */
        do_action( 'mondays_work_ai_core_register_admin_settings' );
    }

    /**
     * Sanitize settings before saving
     * Sanitiza configuraciones antes de guardar
     *
     * @since  1.0.0
     * @access public
     * @param  array $input Raw input data / Datos de entrada sin procesar
     * @return array        Sanitized data / Datos sanitizados
     */
    public function sanitize_settings( array $input ): array {
        $sanitized = array();

        // Sanitize AI provider / Sanitizar proveedor de IA
        if ( isset( $input['ai_provider'] ) ) {
            $sanitized['ai_provider'] = sanitize_text_field( $input['ai_provider'] );
        }

        // Sanitize API keys / Sanitizar claves API
        if ( isset( $input['api_key'] ) ) {
            $sanitized['api_key'] = sanitize_text_field( $input['api_key'] );
        }

        // Sanitize model / Sanitizar modelo
        if ( isset( $input['model'] ) ) {
            $sanitized['model'] = sanitize_text_field( $input['model'] );
        }

        // Sanitize numeric values / Sanitizar valores numéricos
        $numeric_fields = array( 'temperature', 'max_tokens', 'cache_duration', 'rate_limit' );
        foreach ( $numeric_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = floatval( $input[ $field ] );
            }
        }

        // Sanitize boolean values / Sanitizar valores booleanos
        $boolean_fields = array( 'enabled', 'debug_mode', 'cache_enabled' );
        foreach ( $boolean_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = (bool) $input[ $field ];
            }
        }

        /**
         * Filter sanitized settings
         * Filtro para configuraciones sanitizadas
         *
         * @since 1.0.0
         * @param array $sanitized Sanitized settings / Configuraciones sanitizadas
         * @param array $input     Raw input / Entrada sin procesar
         */
        return apply_filters( 'mondays_work_ai_core_sanitize_settings', $sanitized, $input );
    }

    /**
     * Render General tab content
     * Renderiza contenido de pestaña General
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function render_general_tab(): void {
        ?>
        <form method="post" action="options.php" class="mwai-form">
            <?php settings_fields( $this->plugin_slug . '_settings' ); ?>

            <div class="mwai-card">
                <h2><?php esc_html_e( 'Configuración General', 'mondays-work-ai-core' ); ?></h2>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="mwai_enabled">
                                <?php esc_html_e( 'Estado del Plugin', 'mondays-work-ai-core' ); ?>
                            </label>
                        </th>
                        <td>
                            <label class="mwai-switch">
                                <input
                                    type="checkbox"
                                    id="mwai_enabled"
                                    name="<?php echo esc_attr( $this->plugin_slug . '_config' ); ?>[enabled]"
                                    value="1"
                                    <?php checked( $this->config->get( 'enabled', true ) ); ?>
                                />
                                <span class="mwai-slider"></span>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Habilita o deshabilita todas las funcionalidades de IA', 'mondays-work-ai-core' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="mwai_debug">
                                <?php esc_html_e( 'Modo Debug', 'mondays-work-ai-core' ); ?>
                            </label>
                        </th>
                        <td>
                            <label class="mwai-switch">
                                <input
                                    type="checkbox"
                                    id="mwai_debug"
                                    name="<?php echo esc_attr( $this->plugin_slug . '_config' ); ?>[debug_mode]"
                                    value="1"
                                    <?php checked( $this->config->get( 'debug_mode', false ) ); ?>
                                />
                                <span class="mwai-slider"></span>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Activa logging detallado para debugging', 'mondays-work-ai-core' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button( __( 'Guardar Cambios', 'mondays-work-ai-core' ), 'primary mwai-button-primary' ); ?>
        </form>
        <?php
    }

    /**
     * Render Providers tab content
     * Renderiza contenido de pestaña Proveedores
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function render_providers_tab(): void {
        $current_provider = $this->config->get( 'ai_provider', 'openai' );
        ?>
        <form method="post" action="options.php" class="mwai-form">
            <?php settings_fields( $this->plugin_slug . '_settings' ); ?>

            <div class="mwai-card">
                <h2><?php esc_html_e( 'Proveedor de IA', 'mondays-work-ai-core' ); ?></h2>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="mwai_provider">
                                <?php esc_html_e( 'Seleccionar Proveedor', 'mondays-work-ai-core' ); ?>
                            </label>
                        </th>
                        <td>
                            <select
                                id="mwai_provider"
                                name="<?php echo esc_attr( $this->plugin_slug . '_config' ); ?>[ai_provider]"
                                class="regular-text"
                            >
                                <option value="openai" <?php selected( $current_provider, 'openai' ); ?>>
                                    OpenAI (GPT-4, GPT-3.5)
                                </option>
                                <option value="gemini" <?php selected( $current_provider, 'gemini' ); ?>>
                                    Google Gemini
                                </option>
                                <option value="local" <?php selected( $current_provider, 'local' ); ?>>
                                    <?php esc_html_e( 'Modelo Local', 'mondays-work-ai-core' ); ?>
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="mwai_api_key">
                                <?php esc_html_e( 'API Key', 'mondays-work-ai-core' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="password"
                                id="mwai_api_key"
                                name="<?php echo esc_attr( $this->plugin_slug . '_config' ); ?>[api_key]"
                                value="<?php echo esc_attr( $this->config->get( 'api_key', '' ) ); ?>"
                                class="regular-text"
                                placeholder="sk-..."
                            />
                            <button type="button" class="button mwai-test-connection" data-provider="<?php echo esc_attr( $current_provider ); ?>">
                                <?php esc_html_e( 'Probar Conexión', 'mondays-work-ai-core' ); ?>
                            </button>
                            <p class="description">
                                <?php esc_html_e( 'Ingresa tu clave API del proveedor seleccionado', 'mondays-work-ai-core' ); ?>
                            </p>
                            <div class="mwai-connection-status"></div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="mwai_model">
                                <?php esc_html_e( 'Modelo', 'mondays-work-ai-core' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="mwai_model"
                                name="<?php echo esc_attr( $this->plugin_slug . '_config' ); ?>[model]"
                                value="<?php echo esc_attr( $this->config->get( 'model', 'gpt-4' ) ); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                <?php esc_html_e( 'Ejemplos: gpt-4, gpt-3.5-turbo, gemini-pro', 'mondays-work-ai-core' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="mwai_temperature">
                                <?php esc_html_e( 'Temperature', 'mondays-work-ai-core' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="mwai_temperature"
                                name="<?php echo esc_attr( $this->plugin_slug . '_config' ); ?>[temperature]"
                                value="<?php echo esc_attr( $this->config->get( 'temperature', 0.7 ) ); ?>"
                                min="0"
                                max="2"
                                step="0.1"
                                class="small-text"
                            />
                            <p class="description">
                                <?php esc_html_e( 'Controla la creatividad de las respuestas (0.0 - 2.0)', 'mondays-work-ai-core' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="mwai_max_tokens">
                                <?php esc_html_e( 'Tokens Máximos', 'mondays-work-ai-core' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="mwai_max_tokens"
                                name="<?php echo esc_attr( $this->plugin_slug . '_config' ); ?>[max_tokens]"
                                value="<?php echo esc_attr( $this->config->get( 'max_tokens', 1000 ) ); ?>"
                                min="50"
                                max="32000"
                                step="50"
                                class="small-text"
                            />
                            <p class="description">
                                <?php esc_html_e( 'Longitud máxima de las respuestas generadas', 'mondays-work-ai-core' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button( __( 'Guardar Cambios', 'mondays-work-ai-core' ), 'primary mwai-button-primary' ); ?>
        </form>
        <?php
    }

    /**
     * Render Cache tab content
     * Renderiza contenido de pestaña Caché
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function render_cache_tab(): void {
        ?>
        <form method="post" action="options.php" class="mwai-form">
            <?php settings_fields( $this->plugin_slug . '_settings' ); ?>

            <div class="mwai-card">
                <h2><?php esc_html_e( 'Configuración de Caché', 'mondays-work-ai-core' ); ?></h2>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="mwai_cache_enabled">
                                <?php esc_html_e( 'Habilitar Caché', 'mondays-work-ai-core' ); ?>
                            </label>
                        </th>
                        <td>
                            <label class="mwai-switch">
                                <input
                                    type="checkbox"
                                    id="mwai_cache_enabled"
                                    name="<?php echo esc_attr( $this->plugin_slug . '_config' ); ?>[cache_enabled]"
                                    value="1"
                                    <?php checked( $this->config->get( 'cache_enabled', true ) ); ?>
                                />
                                <span class="mwai-slider"></span>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Cachea respuestas de IA para mejorar rendimiento y reducir costos', 'mondays-work-ai-core' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="mwai_cache_duration">
                                <?php esc_html_e( 'Duración del Caché', 'mondays-work-ai-core' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="mwai_cache_duration"
                                name="<?php echo esc_attr( $this->plugin_slug . '_config' ); ?>[cache_duration]"
                                value="<?php echo esc_attr( $this->config->get( 'cache_duration', 3600 ) ); ?>"
                                min="300"
                                max="86400"
                                step="300"
                                class="small-text"
                            />
                            <span><?php esc_html_e( 'segundos', 'mondays-work-ai-core' ); ?></span>
                            <p class="description">
                                <?php esc_html_e( 'Tiempo que se mantienen las respuestas en caché (300 = 5 min, 3600 = 1 hora)', 'mondays-work-ai-core' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="mwai_rate_limit">
                                <?php esc_html_e( 'Límite de Peticiones', 'mondays-work-ai-core' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="mwai_rate_limit"
                                name="<?php echo esc_attr( $this->plugin_slug . '_config' ); ?>[rate_limit]"
                                value="<?php echo esc_attr( $this->config->get( 'rate_limit', 60 ) ); ?>"
                                min="10"
                                max="1000"
                                step="10"
                                class="small-text"
                            />
                            <span><?php esc_html_e( 'peticiones/hora', 'mondays-work-ai-core' ); ?></span>
                            <p class="description">
                                <?php esc_html_e( 'Número máximo de peticiones por hora', 'mondays-work-ai-core' ); ?>

        				</p>
			</td>
		</tr>
	</table>

	<?php submit_button(); ?>
</form>
		</div>
		<?php
	}
	}
