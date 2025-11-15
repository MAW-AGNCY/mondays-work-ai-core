<?php
/**
 * Factory de Clientes de IA - Monday's Work AI Core
 * AI Client Factory - Monday's Work AI Core
 *
 * Implementa el patrón Factory para crear instancias de clientes de IA
 * basándose en el proveedor especificado. Gestiona la validación de
 * configuración y la instanciación correcta de las clases concretas.
 *
 * Implements the Factory pattern to create AI client instances
 * based on the specified provider. Manages configuration validation
 * and correct instantiation of concrete classes.
 *
 * @package    MondaysWork\AI\Core
 * @subpackage AI
 * @since      1.0.0
 * @author     Mondays at Work <info@mondaysatwork.com>
 */

namespace MondaysWork\AI\Core\AI;

use MondaysWork\AI\Core\Core\Config;
use MondaysWork\AI\Core\AI\Clients\OpenAIClient;
use MondaysWork\AI\Core\AI\Clients\GeminiClient;
use MondaysWork\AI\Core\AI\Clients\LocalClient;

// Evitar acceso directo / Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Factory de Clientes de IA
 * AI Client Factory
 *
 * Clase responsable de crear instancias de clientes de IA según el proveedor.
 * Centraliza la lógica de creación, validación y configuración de clientes.
 *
 * Class responsible for creating AI client instances by provider.
 * Centralizes client creation, validation, and configuration logic.
 *
 * @since 1.0.0
 */
class AIClientFactory {

    /**
     * Gestor de configuración
     * Configuration manager
     *
     * @since  1.0.0
     * @access private
     * @var    Config|null
     */
    private $config;

    /**
     * Proveedores soportados y sus clases correspondientes
     * Supported providers and their corresponding classes
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    private static $providers = array(
        'openai' => OpenAIClient::class,
        'gemini' => GeminiClient::class,
        'local'  => LocalClient::class,
    );

    /**
     * Configuración requerida por cada proveedor
     * Required configuration for each provider
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    private static $required_config = array(
        'openai' => array( 'api_key', 'model' ),
        'gemini' => array( 'api_key', 'model' ),
        'local'  => array( 'api_endpoint', 'model' ),
    );

    /**
     * Caché de instancias creadas (opcional)
     * Cache of created instances (optional)
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    private static $instances_cache = array();

    /**
     * Constructor
     *
     * @since  1.0.0
     * @access public
     * @param  Config $config Gestor de configuración / Configuration manager
     */
    public function __construct( Config $config ) {
        $this->config = $config;
    }

    /**
     * Crea una instancia de cliente de IA
     * Creates an AI client instance
     *
     * Método principal del factory que crea y configura un cliente de IA
     * basándose en el proveedor especificado. Valida la configuración,
     * verifica que la clase exista y retorna una instancia lista para usar.
     *
     * Main factory method that creates and configures an AI client
     * based on the specified provider. Validates configuration,
     * checks class existence, and returns a ready-to-use instance.
     *
     * Ejemplo de uso / Usage example:
     * ```php
     * $config = new Config('mondays-work-ai-core');
     * $factory = new AIClientFactory($config);
     * 
     * // Crear cliente de OpenAI / Create OpenAI client
     * $client = $factory->create('openai');
     * 
     * // Crear con configuración personalizada / Create with custom config
     * $client = $factory->create('openai', [
     *     'model' => 'gpt-4-turbo',
     *     'temperature' => 0.8
     * ]);
     * ```
     *
     * @since  1.0.0
     * @access public
     *
     * @param  string $provider    Identificador del proveedor / Provider identifier
     *                             Valores soportados / Supported values:
     *                             - 'openai': OpenAI (GPT-4, GPT-3.5, etc.)
     *                             - 'gemini': Google Gemini
     *                             - 'local': Servidor local o auto-hospedado
     *                                       Local or self-hosted server
     *
     * @param  array  $custom_config Configuración personalizada opcional / Optional custom configuration
     *                               Sobrescribe la configuración por defecto.
     *                               Overrides default configuration.
     *                               Puede incluir / May include:
     *                               - 'api_key' (string): Clave API / API key
     *                               - 'model' (string): Modelo a usar / Model to use
     *                               - 'temperature' (float): Temperatura (0-2)
     *                               - 'max_tokens' (int): Tokens máximos / Max tokens
     *                               - Y otros parámetros específicos del proveedor
     *                                 And other provider-specific parameters
     *
     * @return AIClientInterface   Instancia del cliente de IA / AI client instance
     *                             Cliente configurado y listo para usar.
     *                             Configured and ready-to-use client.
     *
     * @throws \InvalidArgumentException Si el proveedor no es válido o está vacío
     *                                  If provider is invalid or empty
     * @throws \RuntimeException        Si la clase del proveedor no existe
     *                                  If provider class doesn't exist
     * @throws \Exception               Si falta configuración requerida
     *                                  If required configuration is missing
     */
    public function create( string $provider, array $custom_config = array() ): AIClientInterface {
        // Validar proveedor / Validate provider
        $provider = $this->validate_provider( $provider );

        // Obtener configuración completa / Get complete configuration
        $config = $this->build_config( $provider, $custom_config );

        // Validar configuración requerida / Validate required configuration
        $this->validate_config( $provider, $config );

        // Obtener clase del proveedor / Get provider class
        $class_name = self::$providers[ $provider ];

        // Verificar que la clase exista / Check class exists
        if ( ! class_exists( $class_name ) ) {
            throw new \RuntimeException(
                sprintf(
                    /* translators: %s: Class name */
                    __( 'La clase del proveedor "%s" no existe o no se puede cargar.', 'mondays-work-ai-core' ),
                    $class_name
                )
            );
        }

        try {
            // Crear instancia / Create instance
            $instance = new $class_name( $config );

            // Verificar que implemente la interfaz / Verify it implements the interface
            if ( ! $instance instanceof AIClientInterface ) {
                throw new \RuntimeException(
                    sprintf(
                        /* translators: %s: Class name */
                        __( 'La clase "%s" no implementa AIClientInterface.', 'mondays-work-ai-core' ),
                        $class_name
                    )
                );
            }

            /**
             * Filtro para modificar la instancia del cliente antes de devolverla
             * Filter to modify client instance before returning
             *
             * @since 1.0.0
             * @param AIClientInterface $instance Cliente creado / Created client
             * @param string            $provider Proveedor / Provider
             * @param array             $config   Configuración / Configuration
             */
            $instance = apply_filters(
                'mondays_work_ai_core_client_created',
                $instance,
                $provider,
                $config
            );

            // Guardar en caché si está habilitado / Cache if enabled
            if ( $this->is_caching_enabled() ) {
                $cache_key = $this->get_cache_key( $provider, $config );
                self::$instances_cache[ $cache_key ] = $instance;
            }

            /**
             * Acción después de crear el cliente
             * Action after creating client
             *
             * @since 1.0.0
             * @param AIClientInterface $instance Cliente creado / Created client
             * @param string            $provider Proveedor / Provider
             */
            do_action( 'mondays_work_ai_core_after_client_created', $instance, $provider );

            return $instance;

        } catch ( \Exception $e ) {
            // Registrar error / Log error
            $this->log_error( 'Error al crear cliente de IA', array(
                'provider' => $provider,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ) );

            throw new \Exception(
                sprintf(
                    /* translators: 1: Provider name, 2: Error message */
                    __( 'No se pudo crear el cliente de IA para "%1$s": %2$s', 'mondays-work-ai-core' ),
                    $provider,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }
    }

    /**
     * Crea o recupera una instancia cacheada
     * Creates or retrieves a cached instance
     *
     * Similar a create() pero utiliza caché para evitar crear múltiples
     * instancias con la misma configuración.
     *
     * Similar to create() but uses cache to avoid creating multiple
     * instances with the same configuration.
     *
     * @since  1.0.0
     * @access public
     *
     * @param  string $provider      Identificador del proveedor / Provider identifier
     * @param  array  $custom_config Configuración personalizada / Custom configuration
     *
     * @return AIClientInterface     Instancia del cliente / Client instance
     *
     * @throws \InvalidArgumentException Si el proveedor no es válido
     *                                  If provider is invalid
     * @throws \Exception               Si hay error al crear el cliente
     *                                  If there's an error creating the client
     */
    public function get_or_create( string $provider, array $custom_config = array() ): AIClientInterface {
        $config = $this->build_config( $provider, $custom_config );
        $cache_key = $this->get_cache_key( $provider, $config );

        // Verificar si existe en caché / Check if exists in cache
        if ( isset( self::$instances_cache[ $cache_key ] ) ) {
            return self::$instances_cache[ $cache_key ];
        }

        // Crear nueva instancia / Create new instance
        return $this->create( $provider, $custom_config );
    }

    /**
     * Valida el identificador del proveedor
     * Validates provider identifier
     *
     * @since  1.0.0
     * @access private
     *
     * @param  string $provider Identificador del proveedor / Provider identifier
     *
     * @return string           Proveedor validado en minúsculas / Validated provider in lowercase
     *
     * @throws \InvalidArgumentException Si el proveedor no es válido
     *                                  If provider is invalid
     */
    private function validate_provider( string $provider ): string {
        // Limpiar y normalizar / Clean and normalize
        $provider = strtolower( trim( $provider ) );

        // Verificar que no esté vacío / Check not empty
        if ( empty( $provider ) ) {
            throw new \InvalidArgumentException(
                __( 'El proveedor de IA no puede estar vacío.', 'mondays-work-ai-core' )
            );
        }

        // Verificar que esté soportado / Check if supported
        if ( ! isset( self::$providers[ $provider ] ) ) {
            throw new \InvalidArgumentException(
                sprintf(
                    /* translators: 1: Provider name, 2: List of supported providers */
                    __( 'El proveedor "%1$s" no es válido. Proveedores soportados: %2$s', 'mondays-work-ai-core' ),
                    $provider,
                    implode( ', ', array_keys( self::$providers ) )
                )
            );
        }

        return $provider;
    }

    /**
     * Construye la configuración completa para el cliente
     * Builds complete configuration for the client
     *
     * Combina la configuración global del plugin con la configuración
     * personalizada proporcionada.
     *
     * Combines global plugin configuration with provided
     * custom configuration.
     *
     * @since  1.0.0
     * @access private
     *
     * @param  string $provider      Identificador del proveedor / Provider identifier
     * @param  array  $custom_config Configuración personalizada / Custom configuration
     *
     * @return array                 Configuración completa / Complete configuration
     */
    private function build_config( string $provider, array $custom_config ): array {
        // Obtener configuración global / Get global configuration
        $global_config = array(
            'api_key'           => $this->config->get( 'api_key', '' ),
            'api_endpoint'      => $this->config->get( 'api_endpoint', '' ),
            'model'             => $this->config->get( 'model', '' ),
            'temperature'       => $this->config->get( 'temperature', 0.7 ),
            'max_tokens'        => $this->config->get( 'max_tokens', 1000 ),
            'top_p'             => $this->config->get( 'top_p', 1.0 ),
            'frequency_penalty' => $this->config->get( 'frequency_penalty', 0.0 ),
            'presence_penalty'  => $this->config->get( 'presence_penalty', 0.0 ),
            'timeout'           => $this->config->get( 'timeout', 30 ),
            'retry_attempts'    => $this->config->get( 'retry_attempts', 3 ),
        );

        // Combinar con configuración personalizada / Merge with custom configuration
        $config = wp_parse_args( $custom_config, $global_config );

        /**
         * Filtro para modificar la configuración antes de crear el cliente
         * Filter to modify configuration before creating client
         *
         * @since 1.0.0
         * @param array  $config   Configuración combinada / Combined configuration
         * @param string $provider Proveedor / Provider
         */
        return apply_filters(
            'mondays_work_ai_core_client_config',
            $config,
            $provider
        );
    }

    /**
     * Valida que la configuración contenga todos los campos requeridos
     * Validates that configuration contains all required fields
     *
     * @since  1.0.0
     * @access private
     *
     * @param  string $provider Identificador del proveedor / Provider identifier
     * @param  array  $config   Configuración a validar / Configuration to validate
     *
     * @return void
     *
     * @throws \Exception Si falta configuración requerida
     *                    If required configuration is missing
     */
    private function validate_config( string $provider, array $config ): void {
        // Obtener campos requeridos para este proveedor / Get required fields for this provider
        $required_fields = isset( self::$required_config[ $provider ] )
            ? self::$required_config[ $provider ]
            : array();

        // Verificar cada campo requerido / Check each required field
        $missing_fields = array();

        foreach ( $required_fields as $field ) {
            if ( empty( $config[ $field ] ) ) {
                $missing_fields[] = $field;
            }
        }

        // Si faltan campos, lanzar excepción / If fields are missing, throw exception
        if ( ! empty( $missing_fields ) ) {
            throw new \Exception(
                sprintf(
                    /* translators: 1: Provider name, 2: List of missing fields */
                    __( 'Configuración incompleta para el proveedor "%1$s". Campos faltantes: %2$s', 'mondays-work-ai-core' ),
                    $provider,
                    implode( ', ', $missing_fields )
                )
            );
        }

        /**
         * Acción para validación personalizada adicional
         * Action for additional custom validation
         *
         * @since 1.0.0
         * @param string $provider Proveedor / Provider
         * @param array  $config   Configuración / Configuration
         */
        do_action( 'mondays_work_ai_core_validate_client_config', $provider, $config );
    }

    /**
     * Registra un nuevo proveedor
     * Registers a new provider
     *
     * Permite a los desarrolladores registrar proveedores personalizados
     * de IA de manera dinámica.
     *
     * Allows developers to dynamically register custom
     * AI providers.
     *
     * Ejemplo de uso / Usage example:
     * ```php
     * AIClientFactory::register_provider(
     *     'custom-ai',
     *     CustomAIClient::class,
     *     ['api_key', 'model']
     * );
     * ```
     *
     * @since  1.0.0
     * @access public
     * @static
     *
     * @param  string $provider        Identificador del proveedor / Provider identifier
     * @param  string $class_name      Nombre completo de la clase / Full class name
     * @param  array  $required_config Configuración requerida / Required configuration
     *
     * @return void
     *
     * @throws \InvalidArgumentException Si el proveedor ya existe
     *                                  If provider already exists
     */
    public static function register_provider( string $provider, string $class_name, array $required_config = array() ): void {
        $provider = strtolower( trim( $provider ) );

        if ( isset( self::$providers[ $provider ] ) ) {
            throw new \InvalidArgumentException(
                sprintf(
                    /* translators: %s: Provider name */
                    __( 'El proveedor "%s" ya está registrado.', 'mondays-work-ai-core' ),
                    $provider
                )
            );
        }

        self::$providers[ $provider ] = $class_name;
        self::$required_config[ $provider ] = $required_config;

        /**
         * Acción después de registrar un proveedor
         * Action after registering a provider
         *
         * @since 1.0.0
         * @param string $provider   Proveedor registrado / Registered provider
         * @param string $class_name Clase del proveedor / Provider class
         */
        do_action( 'mondays_work_ai_core_provider_registered', $provider, $class_name );
    }

    /**
     * Obtiene la lista de proveedores soportados
     * Gets the list of supported providers
     *
     * @since  1.0.0
     * @access public
     * @static
     *
     * @return array Array de proveedores soportados / Array of supported providers
     */
    public static function get_supported_providers(): array {
        return array_keys( self::$providers );
    }

    /**
     * Verifica si un proveedor está soportado
     * Checks if a provider is supported
     *
     * @since  1.0.0
     * @access public
     * @static
     *
     * @param  string $provider Identificador del proveedor / Provider identifier
     *
     * @return bool             True si está soportado / True if supported
     */
    public static function is_provider_supported( string $provider ): bool {
        return isset( self::$providers[ strtolower( $provider ) ] );
    }

    /**
     * Limpia la caché de instancias
     * Clears instance cache
     *
     * @since  1.0.0
     * @access public
     * @static
     *
     * @return void
     */
    public static function clear_cache(): void {
        self::$instances_cache = array();
    }

    /**
     * Obtiene una clave de caché para una configuración
     * Gets a cache key for a configuration
     *
     * @since  1.0.0
     * @access private
     *
     * @param  string $provider Proveedor / Provider
     * @param  array  $config   Configuración / Configuration
     *
     * @return string           Clave de caché / Cache key
     */
    private function get_cache_key( string $provider, array $config ): string {
        return md5( $provider . wp_json_encode( $config ) );
    }

    /**
     * Verifica si el cacheo está habilitado
     * Checks if caching is enabled
     *
     * @since  1.0.0
     * @access private
     *
     * @return bool True si está habilitado / True if enabled
     */
    private function is_caching_enabled(): bool {
        return (bool) $this->config->get( 'cache_enabled', true );
    }

    /**
     * Registra un error
     * Logs an error
     *
     * @since  1.0.0
     * @access private
     *
     * @param  string $message Mensaje de error / Error message
     * @param  array  $context Contexto adicional / Additional context
     *
     * @return void
     */
    private function log_error( string $message, array $context = array() ): void {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log(
                sprintf(
                    '[Monday\'s Work AI Core - Factory] %s | Context: %s',
                    $message,
                    wp_json_encode( $context )
                )
            );
        }

        /**
         * Acción cuando se registra un error
         * Action when an error is logged
         *
         * @since 1.0.0
         * @param string $message Mensaje / Message
         * @param array  $context Contexto / Context
         */
        do_action( 'mondays_work_ai_core_factory_error', $message, $context );
    }
}
