<?php
/**
 * Gestor de Configuración - Monday's Work AI Core
 * Configuration Manager - Monday's Work AI Core
 *
 * Esta clase gestiona todas las opciones y configuraciones del plugin,
 * incluyendo credenciales de API, parámetros de modelos de IA y
 * preferencias generales. Utiliza la API de opciones de WordPress
 * con validación y sanitización robusta.
 *
 * This class manages all plugin options and configurations,
 * including API credentials, AI model parameters, and
 * general preferences. Uses WordPress Options API
 * with robust validation and sanitization.
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
 * Clase de Gestión de Configuración
 * Configuration Management Class
 *
 * Proporciona una interfaz consistente para trabajar con opciones
 * de WordPress, asegurando validación y sanitización de datos.
 *
 * Provides a consistent interface to work with WordPress options,
 * ensuring data validation and sanitization.
 *
 * @since 1.0.0
 */
class Config {

    /**
     * Slug del plugin
     * Plugin slug
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $plugin_slug;

    /**
     * Nombre de la opción en la base de datos
     * Option name in the database
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $option_name;

    /**
     * Caché de configuración cargada
     * Loaded configuration cache
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    private $config_cache = array();

    /**
     * Indica si la configuración ha sido cargada
     * Indicates if configuration has been loaded
     *
     * @since  1.0.0
     * @access private
     * @var    bool
     */
    private $is_loaded = false;

    /**
     * Valores predeterminados de configuración
     * Default configuration values
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    private $defaults = array(
        // Configuración del proveedor de IA / AI provider configuration
        'ai_provider'       => 'openai',
        'api_key'           => '',
        'api_endpoint'      => '',
        
        // Configuración del modelo / Model configuration
        'model'             => 'gpt-4',
        'temperature'       => 0.7,
        'max_tokens'        => 1000,
        'top_p'             => 1.0,
        'frequency_penalty' => 0.0,
        'presence_penalty'  => 0.0,
        
        // Configuración general / General configuration
        'enabled'           => true,
        'debug_mode'        => false,
        'cache_enabled'     => true,
        'cache_duration'    => 3600,
        'rate_limit'        => 60,
        
        // Configuración de seguridad / Security configuration
        'allowed_roles'     => array( 'administrator' ),
        'require_nonce'     => true,
    );

    /**
     * Reglas de validación para cada campo
     * Validation rules for each field
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    private $validation_rules = array(
        'ai_provider'       => 'string',
        'api_key'           => 'string',
        'api_endpoint'      => 'url',
        'model'             => 'string',
        'temperature'       => 'float',
        'max_tokens'        => 'integer',
        'top_p'             => 'float',
        'frequency_penalty' => 'float',
        'presence_penalty'  => 'float',
        'enabled'           => 'boolean',
        'debug_mode'        => 'boolean',
        'cache_enabled'     => 'boolean',
        'cache_duration'    => 'integer',
        'rate_limit'        => 'integer',
        'allowed_roles'     => 'array',
        'require_nonce'     => 'boolean',
    );

    /**
     * Constructor
     *
     * Inicializa el gestor de configuración con el slug del plugin.
     * Initializes the configuration manager with the plugin slug.
     *
     * @since  1.0.0
     * @access public
     * @param  string $plugin_slug Slug del plugin / Plugin slug
     */
    public function __construct( $plugin_slug ) {
        $this->plugin_slug = sanitize_key( $plugin_slug );
        $this->option_name = $this->plugin_slug . '_config';
    }

    /**
     * Carga la configuración desde la base de datos
     * Loads configuration from database
     *
     * Lee las opciones guardadas y las combina con los valores predeterminados.
     * Reads saved options and merges them with default values.
     *
     * @since  1.0.0
     * @access public
     * @return bool True si se cargó correctamente / True if loaded successfully
     */
    public function load() {
        if ( $this->is_loaded ) {
            return true;
        }

        try {
            // Obtener opciones de la base de datos / Get options from database
            $saved_config = get_option( $this->option_name, array() );

            // Asegurar que es un array / Ensure it's an array
            if ( ! is_array( $saved_config ) ) {
                $saved_config = array();
            }

            // Combinar con defaults / Merge with defaults
            $this->config_cache = wp_parse_args( $saved_config, $this->defaults );

            // Validar toda la configuración / Validate all configuration
            $this->config_cache = $this->validate_config( $this->config_cache );

            $this->is_loaded = true;

            /**
             * Acción disparada después de cargar la configuración
             * Action fired after loading configuration
             *
             * @since 1.0.0
             * @param array  $config Configuración cargada / Loaded configuration
             * @param Config $this   Instancia de Config / Config instance
             */
            do_action( 'mondays_work_ai_core_config_loaded', $this->config_cache, $this );

            return true;

        } catch ( \Exception $e ) {
            $this->log_error( 'Error al cargar configuración', array(
                'error' => $e->getMessage(),
            ) );

            // Usar valores predeterminados en caso de error / Use defaults on error
            $this->config_cache = $this->defaults;
            $this->is_loaded = true;

            return false;
        }
    }

    /**
     * Obtiene un valor de configuración
     * Gets a configuration value
     *
     * @since  1.0.0
     * @access public
     * @param  string $key     Clave de configuración / Configuration key
     * @param  mixed  $default Valor predeterminado / Default value
     * @return mixed           Valor de configuración / Configuration value
     */
    public function get( $key, $default = null ) {
        // Cargar si no está cargado / Load if not loaded
        if ( ! $this->is_loaded ) {
            $this->load();
        }

        // Sanitizar la clave / Sanitize the key
        $key = sanitize_key( $key );

        // Verificar si existe la clave / Check if key exists
        if ( ! isset( $this->config_cache[ $key ] ) ) {
            // Usar default proporcionado o el default de la clase / Use provided default or class default
            if ( null !== $default ) {
                return $default;
            }

            if ( isset( $this->defaults[ $key ] ) ) {
                return $this->defaults[ $key ];
            }

            return null;
        }

        /**
         * Filtro para modificar el valor obtenido
         * Filter to modify the retrieved value
         *
         * @since 1.0.0
         * @param mixed  $value Valor de configuración / Configuration value
         * @param string $key   Clave de configuración / Configuration key
         */
        return apply_filters(
            'mondays_work_ai_core_config_get',
            $this->config_cache[ $key ],
            $key
        );
    }

    /**
     * Establece un valor de configuración
     * Sets a configuration value
     *
     * @since  1.0.0
     * @access public
     * @param  string $key   Clave de configuración / Configuration key
     * @param  mixed  $value Valor a establecer / Value to set
     * @return bool          True si se guardó correctamente / True if saved successfully
     */
    public function set( $key, $value ) {
        // Cargar si no está cargado / Load if not loaded
        if ( ! $this->is_loaded ) {
            $this->load();
        }

        // Sanitizar la clave / Sanitize the key
        $key = sanitize_key( $key );

        try {
            // Validar el valor / Validate the value
            $validated_value = $this->validate_field( $key, $value );

            /**
             * Filtro para modificar el valor antes de guardarlo
             * Filter to modify the value before saving
             *
             * @since 1.0.0
             * @param mixed  $value Valor a guardar / Value to save
             * @param string $key   Clave de configuración / Configuration key
             */
            $validated_value = apply_filters(
                'mondays_work_ai_core_config_set',
                $validated_value,
                $key
            );

            // Actualizar caché / Update cache
            $this->config_cache[ $key ] = $validated_value;

            // Guardar en base de datos / Save to database
            $result = update_option( $this->option_name, $this->config_cache, false );

            if ( $result ) {
                /**
                 * Acción disparada después de actualizar una configuración
                 * Action fired after updating a configuration
                 *
                 * @since 1.0.0
                 * @param string $key   Clave actualizada / Updated key
                 * @param mixed  $value Nuevo valor / New value
                 */
                do_action( 'mondays_work_ai_core_config_updated', $key, $validated_value );
            }

            return $result;

        } catch ( \Exception $e ) {
            $this->log_error( 'Error al guardar configuración', array(
                'key'   => $key,
                'error' => $e->getMessage(),
            ) );

            return false;
        }
    }

    /**
     * Elimina un valor de configuración
     * Deletes a configuration value
     *
     * Restaura el valor predeterminado para la clave especificada.
     * Restores the default value for the specified key.
     *
     * @since  1.0.0
     * @access public
     * @param  string $key Clave de configuración / Configuration key
     * @return bool        True si se eliminó correctamente / True if deleted successfully
     */
    public function delete( $key ) {
        // Cargar si no está cargado / Load if not loaded
        if ( ! $this->is_loaded ) {
            $this->load();
        }

        // Sanitizar la clave / Sanitize the key
        $key = sanitize_key( $key );

        // Verificar si existe / Check if exists
        if ( ! isset( $this->config_cache[ $key ] ) ) {
            return false;
        }

        // Restaurar al valor predeterminado / Restore to default value
        if ( isset( $this->defaults[ $key ] ) ) {
            $this->config_cache[ $key ] = $this->defaults[ $key ];
        } else {
            unset( $this->config_cache[ $key ] );
        }

        // Guardar en base de datos / Save to database
        $result = update_option( $this->option_name, $this->config_cache, false );

        if ( $result ) {
            /**
             * Acción disparada después de eliminar una configuración
             * Action fired after deleting a configuration
             *
             * @since 1.0.0
             * @param string $key Clave eliminada / Deleted key
             */
            do_action( 'mondays_work_ai_core_config_deleted', $key );
        }

        return $result;
    }

    /**
     * Obtiene toda la configuración
     * Gets all configuration
     *
     * @since  1.0.0
     * @access public
     * @return array Configuración completa / Complete configuration
     */
    public function get_all() {
        // Cargar si no está cargado / Load if not loaded
        if ( ! $this->is_loaded ) {
            $this->load();
        }

        /**
         * Filtro para modificar toda la configuración antes de devolverla
         * Filter to modify all configuration before returning
         *
         * @since 1.0.0
         * @param array $config Configuración completa / Complete configuration
         */
        return apply_filters(
            'mondays_work_ai_core_config_get_all',
            $this->config_cache
        );
    }

    /**
     * Restablece toda la configuración a valores predeterminados
     * Resets all configuration to default values
     *
     * @since  1.0.0
     * @access public
     * @return bool True si se restableció correctamente / True if reset successfully
     */
    public function reset() {
        $this->config_cache = $this->defaults;
        $result = update_option( $this->option_name, $this->config_cache, false );

        if ( $result ) {
            /**
             * Acción disparada después de restablecer la configuración
             * Action fired after resetting configuration
             *
             * @since 1.0.0
             */
            do_action( 'mondays_work_ai_core_config_reset' );
        }

        return $result;
    }

    /**
     * Valida toda la configuración
     * Validates all configuration
     *
     * @since  1.0.0
     * @access private
     * @param  array $config Configuración a validar / Configuration to validate
     * @return array         Configuración validada / Validated configuration
     * @throws \Exception    Si la validación falla / If validation fails
     */
    private function validate_config( $config ) {
        $validated = array();

        foreach ( $config as $key => $value ) {
            try {
                $validated[ $key ] = $this->validate_field( $key, $value );
            } catch ( \Exception $e ) {
                // Usar valor predeterminado si la validación falla / Use default if validation fails
                if ( isset( $this->defaults[ $key ] ) ) {
                    $validated[ $key ] = $this->defaults[ $key ];
                    
                    $this->log_error( 'Error de validación, usando default', array(
                        'key'   => $key,
                        'error' => $e->getMessage(),
                    ) );
                }
            }
        }

        return $validated;
    }

    /**
     * Valida un campo individual
     * Validates an individual field
     *
     * @since  1.0.0
     * @access private
     * @param  string $key   Clave del campo / Field key
     * @param  mixed  $value Valor a validar / Value to validate
     * @return mixed         Valor validado / Validated value
     * @throws \Exception    Si la validación falla / If validation fails
     */
    private function validate_field( $key, $value ) {
        // Obtener tipo de validación / Get validation type
        $type = isset( $this->validation_rules[ $key ] ) 
            ? $this->validation_rules[ $key ] 
            : 'string';

        // Validar según el tipo / Validate by type
        switch ( $type ) {
            case 'string':
                return $this->validate_string( $value );

            case 'integer':
                return $this->validate_integer( $value );

            case 'float':
                return $this->validate_float( $value );

            case 'boolean':
                return $this->validate_boolean( $value );

            case 'array':
                return $this->validate_array( $value );

            case 'url':
                return $this->validate_url( $value );

            default:
                return sanitize_text_field( $value );
        }
    }

    /**
     * Valida un valor string
     * Validates a string value
     *
     * @since  1.0.0
     * @access private
     * @param  mixed $value Valor a validar / Value to validate
     * @return string       Valor validado / Validated value
     * @throws \Exception   Si no es un string válido / If not a valid string
     */
    private function validate_string( $value ) {
        if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
            throw new \Exception( 'El valor debe ser un string' );
        }

        return sanitize_text_field( (string) $value );
    }

    /**
     * Valida un valor integer
     * Validates an integer value
     *
     * @since  1.0.0
     * @access private
     * @param  mixed $value Valor a validar / Value to validate
     * @return int          Valor validado / Validated value
     * @throws \Exception   Si no es un integer válido / If not a valid integer
     */
    private function validate_integer( $value ) {
        if ( ! is_numeric( $value ) ) {
            throw new \Exception( 'El valor debe ser un número entero' );
        }

        return absint( $value );
    }

    /**
     * Valida un valor float
     * Validates a float value
     *
     * @since  1.0.0
     * @access private
     * @param  mixed $value Valor a validar / Value to validate
     * @return float        Valor validado / Validated value
     * @throws \Exception   Si no es un float válido / If not a valid float
     */
    private function validate_float( $value ) {
        if ( ! is_numeric( $value ) ) {
            throw new \Exception( 'El valor debe ser un número decimal' );
        }

        return floatval( $value );
    }

    /**
     * Valida un valor boolean
     * Validates a boolean value
     *
     * @since  1.0.0
     * @access private
     * @param  mixed $value Valor a validar / Value to validate
     * @return bool         Valor validado / Validated value
     */
    private function validate_boolean( $value ) {
        return (bool) $value;
    }

    /**
     * Valida un valor array
     * Validates an array value
     *
     * @since  1.0.0
     * @access private
     * @param  mixed $value Valor a validar / Value to validate
     * @return array        Valor validado / Validated value
     * @throws \Exception   Si no es un array válido / If not a valid array
     */
    private function validate_array( $value ) {
        if ( ! is_array( $value ) ) {
            throw new \Exception( 'El valor debe ser un array' );
        }

        // Sanitizar cada elemento del array / Sanitize each array element
        return array_map( 'sanitize_text_field', $value );
    }

    /**
     * Valida un valor URL
     * Validates a URL value
     *
     * @since  1.0.0
     * @access private
     * @param  mixed $value Valor a validar / Value to validate
     * @return string       Valor validado / Validated value
     * @throws \Exception   Si no es una URL válida / If not a valid URL
     */
    private function validate_url( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        $url = esc_url_raw( $value );

        if ( empty( $url ) ) {
            throw new \Exception( 'La URL no es válida' );
        }

        return $url;
    }

    /**
     * Registra los ajustes en WordPress
     * Registers settings in WordPress
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function register_settings() {
        register_setting(
            $this->plugin_slug . '_settings',
            $this->option_name,
            array(
                'type'              => 'array',
                'description'       => __( 'Configuración de Monday\'s Work AI Core', 'mondays-work-ai-core' ),
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => $this->defaults,
            )
        );

        /**
         * Acción para registrar secciones y campos adicionales
         * Action to register additional sections and fields
         *
         * @since 1.0.0
         * @param Config $this Instancia de Config / Config instance
         */
        do_action( 'mondays_work_ai_core_register_settings', $this );
    }

    /**
     * Sanitiza los ajustes antes de guardar
     * Sanitizes settings before saving
     *
     * @since  1.0.0
     * @access public
     * @param  array $input Datos de entrada / Input data
     * @return array        Datos sanitizados / Sanitized data
     */
    public function sanitize_settings( $input ) {
        if ( ! is_array( $input ) ) {
            return $this->defaults;
        }

        try {
            return $this->validate_config( $input );
        } catch ( \Exception $e ) {
            $this->log_error( 'Error al sanitizar configuración', array(
                'error' => $e->getMessage(),
            ) );

            add_settings_error(
                $this->option_name,
                'validation_error',
                __( 'Hubo un error al validar la configuración. Se usaron los valores predeterminados.', 'mondays-work-ai-core' ),
                'error'
            );

            return $this->defaults;
        }
    }

    /**
     * Registra un error en el log
     * Logs an error
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
                    '[Monday\'s Work AI Core - Config] %s | Context: %s',
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
        do_action( 'mondays_work_ai_core_config_error', $message, $context );
    }

    /**
     * Exporta la configuración a un array
     * Exports configuration to an array
     *
     * @since  1.0.0
     * @access public
     * @param  bool $include_sensitive Incluir datos sensibles / Include sensitive data
     * @return array                   Configuración exportada / Exported configuration
     */
    public function export( $include_sensitive = false ) {
        $config = $this->get_all();

        // Remover datos sensibles si es necesario / Remove sensitive data if needed
        if ( ! $include_sensitive ) {
            $sensitive_keys = array( 'api_key' );
            
            foreach ( $sensitive_keys as $key ) {
                if ( isset( $config[ $key ] ) ) {
                    $config[ $key ] = '***HIDDEN***';
                }
            }
        }

        return $config;
    }

    /**
     * Importa configuración desde un array
     * Imports configuration from an array
     *
     * @since  1.0.0
     * @access public
     * @param  array $config Configuración a importar / Configuration to import
     * @return bool          True si se importó correctamente / True if imported successfully
     */
    public function import( $config ) {
        if ( ! is_array( $config ) ) {
            return false;
        }

        try {
            // Validar toda la configuración / Validate all configuration
            $validated_config = $this->validate_config( $config );

            // Actualizar caché / Update cache
            $this->config_cache = $validated_config;

            // Guardar en base de datos / Save to database
            $result = update_option( $this->option_name, $this->config_cache, false );

            if ( $result ) {
                /**
                 * Acción disparada después de importar configuración
                 * Action fired after importing configuration
                 *
                 * @since 1.0.0
                 * @param array $config Configuración importada / Imported configuration
                 */
                do_action( 'mondays_work_ai_core_config_imported', $validated_config );
            }

            return $result;

        } catch ( \Exception $e ) {
            $this->log_error( 'Error al importar configuración', array(
                'error' => $e->getMessage(),
            ) );

            return false;
        }
    }
}
