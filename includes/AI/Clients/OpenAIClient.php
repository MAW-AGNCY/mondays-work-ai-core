<?php
/**
 * Cliente OpenAI - Monday's Work AI Core
 * OpenAI Client - Monday's Work AI Core
 *
 * Implementación concreta del cliente de IA para OpenAI.
 * Proporciona acceso a modelos GPT-4, GPT-4-Turbo y GPT-3.5-Turbo
 * con gestión completa de errores, rate limiting y validación.
 *
 * Concrete AI client implementation for OpenAI.
 * Provides access to GPT-4, GPT-4-Turbo, and GPT-3.5-Turbo models
 * with complete error handling, rate limiting, and validation.
 *
 * @package    MondaysWork\AI\Core
 * @subpackage AI\Clients
 * @since      1.0.0
 * @author     Monday's Work <info@mondayswork.com>
 */

namespace MondaysWork\AI\Core\AI\Clients;

use MondaysWork\AI\Core\AI\AIClientInterface;

// Evitar acceso directo / Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cliente OpenAI
 * OpenAI Client
 *
 * Implementa la interfaz AIClientInterface para proporcionar
 * integración completa con la API de OpenAI.
 *
 * Implements AIClientInterface to provide
 * complete integration with OpenAI API.
 *
 * Ejemplo de uso / Usage example:
 * ```php
 * $config = [
 *     'api_key' => 'sk-...',
 *     'model' => 'gpt-4',
 *     'temperature' => 0.7,
 *     'max_tokens' => 1000
 * ];
 * $client = new OpenAIClient($config);
 * $response = $client->generateText('Escribe una descripción de producto');
 * ```
 *
 * @since 1.0.0
 */
class OpenAIClient implements AIClientInterface {

    /**
     * Clave API de OpenAI
     * OpenAI API key
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $api_key;

    /**
     * Modelo de IA a utilizar
     * AI model to use
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $model;

    /**
     * Temperatura para generación de texto (0.0-2.0)
     * Temperature for text generation (0.0-2.0)
     *
     * @since  1.0.0
     * @access private
     * @var    float
     */
    private $temperature;

    /**
     * Número máximo de tokens a generar
     * Maximum number of tokens to generate
     *
     * @since  1.0.0
     * @access private
     * @var    int
     */
    private $max_tokens;

    /**
     * Top P para nucleus sampling (0.0-1.0)
     * Top P for nucleus sampling (0.0-1.0)
     *
     * @since  1.0.0
     * @access private
     * @var    float
     */
    private $top_p;

    /**
     * Penalización por frecuencia (-2.0 a 2.0)
     * Frequency penalty (-2.0 to 2.0)
     *
     * @since  1.0.0
     * @access private
     * @var    float
     */
    private $frequency_penalty;

    /**
     * Penalización por presencia (-2.0 a 2.0)
     * Presence penalty (-2.0 to 2.0)
     *
     * @since  1.0.0
     * @access private
     * @var    float
     */
    private $presence_penalty;

    /**
     * Timeout para peticiones HTTP (en segundos)
     * HTTP request timeout (in seconds)
     *
     * @since  1.0.0
     * @access private
     * @var    int
     */
    private $timeout;

    /**
     * Número de intentos de reintento
     * Number of retry attempts
     *
     * @since  1.0.0
     * @access private
     * @var    int
     */
    private $retry_attempts;

    /**
     * URL base de la API de OpenAI
     * OpenAI API base URL
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $api_base_url = 'https://api.openai.com/v1';

    /**
     * Modelos soportados
     * Supported models
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    private static $supported_models = array(
        'gpt-4',
        'gpt-4-turbo',
        'gpt-4-turbo-preview',
        'gpt-3.5-turbo',
        'gpt-3.5-turbo-16k',
    );

    /**
     * Constructor
     *
     * Inicializa el cliente con la configuración proporcionada.
     * Initializes the client with provided configuration.
     *
     * @since  1.0.0
     * @access public
     *
     * @param  array $config Configuración del cliente / Client configuration
     *                       Debe incluir / Must include:
     *                       - 'api_key' (string): Clave API de OpenAI
     *                       - 'model' (string): Modelo a usar
     *                       Opcional / Optional:
     *                       - 'temperature' (float): Default 0.7
     *                       - 'max_tokens' (int): Default 1000
     *                       - 'top_p' (float): Default 1.0
     *                       - 'frequency_penalty' (float): Default 0.0
     *                       - 'presence_penalty' (float): Default 0.0
     *                       - 'timeout' (int): Default 30
     *                       - 'retry_attempts' (int): Default 3
     *
     * @throws \InvalidArgumentException Si falta configuración requerida
     *                                  If required configuration is missing
     */
    public function __construct( array $config ) {
        // Validar configuración requerida / Validate required configuration
        if ( empty( $config['api_key'] ) ) {
            throw new \InvalidArgumentException(
                __( 'La API key de OpenAI es requerida.', 'mondays-work-ai-core' )
            );
        }

        if ( empty( $config['model'] ) ) {
            throw new \InvalidArgumentException(
                __( 'El modelo de OpenAI es requerido.', 'mondays-work-ai-core' )
            );
        }

        // Asignar configuración / Assign configuration
        $this->api_key           = sanitize_text_field( $config['api_key'] );
        $this->model             = sanitize_text_field( $config['model'] );
        $this->temperature       = isset( $config['temperature'] ) ? floatval( $config['temperature'] ) : 0.7;
        $this->max_tokens        = isset( $config['max_tokens'] ) ? absint( $config['max_tokens'] ) : 1000;
        $this->top_p             = isset( $config['top_p'] ) ? floatval( $config['top_p'] ) : 1.0;
        $this->frequency_penalty = isset( $config['frequency_penalty'] ) ? floatval( $config['frequency_penalty'] ) : 0.0;
        $this->presence_penalty  = isset( $config['presence_penalty'] ) ? floatval( $config['presence_penalty'] ) : 0.0;
        $this->timeout           = isset( $config['timeout'] ) ? absint( $config['timeout'] ) : 30;
        $this->retry_attempts    = isset( $config['retry_attempts'] ) ? absint( $config['retry_attempts'] ) : 3;

        // Validar modelo / Validate model
        $this->validate_model( $this->model );

        // Validar API key / Validate API key
        $this->validate_api_key( $this->api_key );
    }

    /**
     * Genera texto basado en un prompt
     * Generates text based on a prompt
     *
     * @since  1.0.0
     * @access public
     *
     * @param  string $prompt Texto de entrada / Input text
     * @param  array  $params Parámetros adicionales / Additional parameters
     *
     * @return string         Texto generado / Generated text
     *
     * @throws \Exception     Si hay error en la petición
     *                        If there's an error in the request
     */
    public function generateText( string $prompt, array $params = array() ): string {
        // Validar prompt / Validate prompt
        if ( empty( trim( $prompt ) ) ) {
            throw new \InvalidArgumentException(
                __( 'El prompt no puede estar vacío.', 'mondays-work-ai-core' )
            );
        }

        // Preparar parámetros de la petición / Prepare request parameters
        $request_params = $this->prepare_completion_params( $prompt, $params );

        // Realizar petición a la API / Make API request
        $response = $this->make_request( '/chat/completions', $request_params );

        // Extraer y retornar el texto generado / Extract and return generated text
        return $this->extract_text_from_response( $response );
    }

    /**
     * Mantiene una conversación con mensajes múltiples
     * Maintains a conversation with multiple messages
     *
     * @since  1.0.0
     * @access public
     *
     * @param  array $messages Array de mensajes / Array of messages
     * @param  array $params   Parámetros adicionales / Additional parameters
     *
     * @return string          Respuesta generada / Generated response
     *
     * @throws \Exception      Si hay error en la petición
     *                         If there's an error in the request
     */
    public function chat( array $messages, array $params = array() ): string {
        // Validar mensajes / Validate messages
        if ( empty( $messages ) ) {
            throw new \InvalidArgumentException(
                __( 'El array de mensajes no puede estar vacío.', 'mondays-work-ai-core' )
            );
        }

        // Validar estructura de mensajes / Validate message structure
        $this->validate_messages( $messages );

        // Preparar parámetros de la petición / Prepare request parameters
        $request_params = array_merge(
            array(
                'model'             => $this->model,
                'messages'          => $messages,
                'temperature'       => $this->temperature,
                'max_tokens'        => $this->max_tokens,
                'top_p'             => $this->top_p,
                'frequency_penalty' => $this->frequency_penalty,
                'presence_penalty'  => $this->presence_penalty,
            ),
            $params
        );

        // Realizar petición a la API / Make API request
        $response = $this->make_request( '/chat/completions', $request_params );

        // Extraer y retornar la respuesta / Extract and return response
        return $this->extract_text_from_response( $response );
    }

    /**
     * Analiza texto y extrae información estructurada
     * Analyzes text and extracts structured information
     *
     * @since  1.0.0
     * @access public
     *
     * @param  string $text Texto a analizar / Text to analyze
     *
     * @return array        Resultados del análisis / Analysis results
     *
     * @throws \Exception   Si hay error en la petición
     *                      If there's an error in the request
     */
    public function analyzeText( string $text ): array {
        // Validar texto / Validate text
        if ( empty( trim( $text ) ) ) {
            throw new \InvalidArgumentException(
                __( 'El texto a analizar no puede estar vacío.', 'mondays-work-ai-core' )
            );
        }

        // Construir prompt de análisis / Build analysis prompt
        $prompt = sprintf(
            'Analiza el siguiente texto y proporciona un análisis estructurado en formato JSON con: sentiment (positive/neutral/negative), score (0-1), keywords (array), categories (array), language (código ISO). Texto: "%s"',
            $text
        );

        // Configurar para respuesta JSON / Configure for JSON response
        $params = array(
            'temperature' => 0.3, // Baja temperatura para respuestas más consistentes
            'max_tokens'  => 500,
        );

        // Generar análisis / Generate analysis
        $response = $this->generateText( $prompt, $params );

        // Intentar parsear como JSON / Try to parse as JSON
        $analysis = json_decode( $response, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            // Si falla el parseo, retornar estructura básica / If parsing fails, return basic structure
            return array(
                'sentiment'  => 'neutral',
                'score'      => 0.5,
                'keywords'   => array(),
                'categories' => array(),
                'language'   => 'unknown',
                'raw'        => $response,
            );
        }

        return $analysis;
    }

    /**
     * Prueba la conexión con el servicio de IA
     * Tests the connection with the AI service
     *
     * @since  1.0.0
     * @access public
     *
     * @return bool True si la conexión es exitosa / True if connection is successful
     *
     * @throws \Exception Si hay error crítico / If there's a critical error
     */
    public function testConnection(): bool {
        try {
            // Intentar una petición simple a la API de modelos / Try a simple request to models API
            $response = wp_remote_get(
                $this->api_base_url . '/models',
                array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $this->api_key,
                        'Content-Type'  => 'application/json',
                    ),
                    'timeout' => 10,
                )
            );

            // Verificar respuesta / Check response
            if ( is_wp_error( $response ) ) {
                $this->log_error( 'Error de conexión', array(
                    'error' => $response->get_error_message(),
                ) );
                return false;
            }

            $status_code = wp_remote_retrieve_response_code( $response );

            // 200 = conexión exitosa / 200 = successful connection
            return $status_code === 200;

        } catch ( \Exception $e ) {
            $this->log_error( 'Excepción al probar conexión', array(
                'error' => $e->getMessage(),
            ) );
            return false;
        }
    }

    /**
     * Obtiene el modelo de IA actual
     * Gets the current AI model
     *
     * @since  1.0.0
     * @access public
     *
     * @return string Identificador del modelo / Model identifier
     */
    public function getModel(): string {
        return $this->model;
    }

    /**
     * Establece el modelo de IA a utilizar
     * Sets the AI model to use
     *
     * @since  1.0.0
     * @access public
     *
     * @param  string $model Identificador del modelo / Model identifier
     *
     * @return void
     *
     * @throws \InvalidArgumentException Si el modelo no es válido
     *                                  If the model is not valid
     */
    public function setModel( string $model ): void {
        $this->validate_model( $model );
        $this->model = sanitize_text_field( $model );
    }

    /**
     * Obtiene el límite máximo de tokens
     * Gets the maximum token limit
     *
     * @since  1.0.0
     * @access public
     *
     * @return int Número máximo de tokens / Maximum number of tokens
     */
    public function getMaxTokens(): int {
        return $this->max_tokens;
    }

    /**
     * Establece el límite máximo de tokens
     * Sets the maximum token limit
     *
     * @since  1.0.0
     * @access public
     *
     * @param  int $tokens Número máximo de tokens / Maximum number of tokens
     *
     * @return void
     *
     * @throws \InvalidArgumentException Si el número de tokens es inválido
     *                                  If the number of tokens is invalid
     */
    public function setMaxTokens( int $tokens ): void {
        if ( $tokens < 1 ) {
            throw new \InvalidArgumentException(
                __( 'El número de tokens debe ser mayor a 0.', 'mondays-work-ai-core' )
            );
        }

        if ( $tokens > 32000 ) {
            throw new \InvalidArgumentException(
                __( 'El número de tokens no puede exceder 32000.', 'mondays-work-ai-core' )
            );
        }

        $this->max_tokens = $tokens;
    }

    /**
     * Obtiene la temperatura actual
     * Gets the current temperature
     *
     * @since  1.0.0
     * @access public
     *
     * @return float Temperatura / Temperature
     */
    public function getTemperature(): float {
        return $this->temperature;
    }

    /**
     * Establece la temperatura
     * Sets the temperature
     *
     * @since  1.0.0
     * @access public
     *
     * @param  float $temperature Temperatura (0.0-2.0) / Temperature (0.0-2.0)
     *
     * @return void
     *
     * @throws \InvalidArgumentException Si la temperatura es inválida
     *                                  If temperature is invalid
     */
    public function setTemperature( float $temperature ): void {
        if ( $temperature < 0.0 || $temperature > 2.0 ) {
            throw new \InvalidArgumentException(
                __( 'La temperatura debe estar entre 0.0 y 2.0.', 'mondays-work-ai-core' )
            );
        }

        $this->temperature = $temperature;
    }

    /**
     * Prepara los parámetros para una petición de completado
     * Prepares parameters for a completion request
     *
     * @since  1.0.0
     * @access private
     *
     * @param  string $prompt Prompt de texto / Text prompt
     * @param  array  $params Parámetros adicionales / Additional parameters
     *
     * @return array          Parámetros preparados / Prepared parameters
     */
    private function prepare_completion_params( string $prompt, array $params ): array {
        // Convertir prompt simple a formato de mensajes / Convert simple prompt to messages format
        $messages = array(
            array(
                'role'    => 'user',
                'content' => $prompt,
            ),
        );

        return array_merge(
            array(
                'model'             => $this->model,
                'messages'          => $messages,
                'temperature'       => $this->temperature,
                'max_tokens'        => $this->max_tokens,
                'top_p'             => $this->top_p,
                'frequency_penalty' => $this->frequency_penalty,
                'presence_penalty'  => $this->presence_penalty,
            ),
            $params
        );
    }

    /**
     * Realiza una petición a la API de OpenAI
     * Makes a request to OpenAI API
     *
     * @since  1.0.0
     * @access private
     *
     * @param  string $endpoint        Endpoint de la API / API endpoint
     * @param  array  $params          Parámetros de la petición / Request parameters
     * @param  int    $attempt         Número de intento actual / Current attempt number
     *
     * @return array                   Respuesta de la API / API response
     *
     * @throws \Exception              Si hay error en la petición
     *                                 If there's an error in the request
     */
    private function make_request( string $endpoint, array $params, int $attempt = 1 ): array {
        $url = $this->api_base_url . $endpoint;

        // Preparar headers / Prepare headers
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type'  => 'application/json',
        );

        // Realizar petición / Make request
        $response = wp_remote_post(
            $url,
            array(
                'headers' => $headers,
                'body'    => wp_json_encode( $params ),
                'timeout' => $this->timeout,
            )
        );

        // Verificar errores de WordPress / Check WordPress errors
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            
            $this->log_error( 'Error en petición HTTP', array(
                'endpoint' => $endpoint,
                'error'    => $error_message,
                'attempt'  => $attempt,
            ) );

            // Reintentar si es posible / Retry if possible
            if ( $attempt < $this->retry_attempts ) {
                sleep( $attempt ); // Espera incremental / Incremental wait
                return $this->make_request( $endpoint, $params, $attempt + 1 );
            }

            throw new \Exception(
                sprintf(
                    /* translators: %s: Error message */
                    __( 'Error de conexión con OpenAI: %s', 'mondays-work-ai-core' ),
                    $error_message
                )
            );
        }

        // Obtener código de estado / Get status code
        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $decoded_body = json_decode( $body, true );

        // Manejar diferentes códigos de error / Handle different error codes
        if ( $status_code !== 200 ) {
            return $this->handle_error_response( $status_code, $decoded_body, $endpoint, $params, $attempt );
        }

        // Verificar que la respuesta sea válida / Verify response is valid
        if ( ! is_array( $decoded_body ) ) {
            throw new \Exception(
                __( 'Respuesta inválida de OpenAI.', 'mondays-work-ai-core' )
            );
        }

        return $decoded_body;
    }

    /**
     * Maneja respuestas de error de la API
     * Handles API error responses
     *
     * @since  1.0.0
     * @access private
     *
     * @param  int    $status_code Status HTTP / HTTP status
     * @param  array  $body        Cuerpo de la respuesta / Response body
     * @param  string $endpoint    Endpoint / Endpoint
     * @param  array  $params      Parámetros / Parameters
     * @param  int    $attempt     Número de intento / Attempt number
     *
     * @return array               Respuesta / Response
     *
     * @throws \Exception          Si el error es irrecuperable
     *                             If error is unrecoverable
     */
    private function handle_error_response( int $status_code, $body, string $endpoint, array $params, int $attempt ): array {
        $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Error desconocido', 'mondays-work-ai-core' );

        $this->log_error( 'Error de API OpenAI', array(
            'status_code' => $status_code,
            'error'       => $error_message,
            'endpoint'    => $endpoint,
            'attempt'     => $attempt,
        ) );

        switch ( $status_code ) {
            case 401:
                // Error de autenticación - no reintentar / Authentication error - don't retry
                throw new \Exception(
                    __( 'API key de OpenAI inválida o expirada.', 'mondays-work-ai-core' )
                );

            case 429:
                // Rate limit - reintentar con backoff / Rate limit - retry with backoff
                if ( $attempt < $this->retry_attempts ) {
                    $wait_time = $attempt * 2; // Espera exponencial / Exponential wait
                    sleep( $wait_time );
                    return $this->make_request( $endpoint, $params, $attempt + 1 );
                }
                throw new \Exception(
                    __( 'Límite de tasa de OpenAI alcanzado. Intenta de nuevo más tarde.', 'mondays-work-ai-core' )
                );

            case 500:
            case 502:
            case 503:
                // Error del servidor - reintentar / Server error - retry
                if ( $attempt < $this->retry_attempts ) {
                    sleep( $attempt );
                    return $this->make_request( $endpoint, $params, $attempt + 1 );
                }
                throw new \Exception(
                    __( 'Error del servidor de OpenAI. Intenta de nuevo más tarde.', 'mondays-work-ai-core' )
                );

            default:
                throw new \Exception(
                    sprintf(
                        /* translators: 1: Status code, 2: Error message */
                        __( 'Error de OpenAI (código %1$d): %2$s', 'mondays-work-ai-core' ),
                        $status_code,
                        $error_message
                    )
                );
        }
    }

    /**
     * Extrae el texto de la respuesta de la API
     * Extracts text from API response
     *
     * @since  1.0.0
     * @access private
     *
     * @param  array $response Respuesta de la API / API response
     *
     * @return string          Texto extraído / Extracted text
     *
     * @throws \Exception      Si la respuesta no contiene texto
     *                         If response doesn't contain text
     */
    private function extract_text_from_response( array $response ): string {
        if ( ! isset( $response['choices'][0]['message']['content'] ) ) {
            throw new \Exception(
                __( 'La respuesta de OpenAI no contiene contenido válido.', 'mondays-work-ai-core' )
            );
        }

        return trim( $response['choices'][0]['message']['content'] );
    }

    /**
     * Valida la estructura de los mensajes
     * Validates message structure
     *
     * @since  1.0.0
     * @access private
     *
     * @param  array $messages Array de mensajes / Array of messages
     *
     * @return void
     *
     * @throws \InvalidArgumentException Si la estructura es inválida
     *                                  If structure is invalid
     */
    private function validate_messages( array $messages ): void {
        foreach ( $messages as $index => $message ) {
            if ( ! isset( $message['role'] ) || ! isset( $message['content'] ) ) {
                throw new \InvalidArgumentException(
                    sprintf(
                        /* translators: %d: Message index */
                        __( 'El mensaje en el índice %d debe tener "role" y "content".', 'mondays-work-ai-core' ),
                        $index
                    )
                );
            }

            $valid_roles = array( 'system', 'user', 'assistant' );
            if ( ! in_array( $message['role'], $valid_roles, true ) ) {
                throw new \InvalidArgumentException(
                    sprintf(
                        /* translators: 1: Message index, 2: List of valid roles */
                        __( 'El rol del mensaje en el índice %1$d debe ser uno de: %2$s', 'mondays-work-ai-core' ),
                        $index,
                        implode( ', ', $valid_roles )
                    )
                );
            }
        }
    }

    /**
     * Valida que el modelo sea soportado
     * Validates that the model is supported
     *
     * @since  1.0.0
     * @access private
     *
     * @param  string $model Modelo a validar / Model to validate
     *
     * @return void
     *
     * @throws \InvalidArgumentException Si el modelo no es soportado
     *                                  If model is not supported
     */
    private function validate_model( string $model ): void {
        if ( ! in_array( $model, self::$supported_models, true ) ) {
            throw new \InvalidArgumentException(
                sprintf(
                    /* translators: 1: Model name, 2: List of supported models */
                    __( 'El modelo "%1$s" no es soportado. Modelos soportados: %2$s', 'mondays-work-ai-core' ),
                    $model,
                    implode( ', ', self::$supported_models )
                )
            );
        }
    }

    /**
     * Valida el formato de la API key
     * Validates API key format
     *
     * @since  1.0.0
     * @access private
     *
     * @param  string $api_key API key a validar / API key to validate
     *
     * @return void
     *
     * @throws \InvalidArgumentException Si la API key no es válida
     *                                  If API key is not valid
     */
    private function validate_api_key( string $api_key ): void {
        // Las API keys de OpenAI comienzan con "sk-" / OpenAI API keys start with "sk-"
			if ( ! preg_match( '/^sk-(?:proj-)?[a-zA-Z0-9\-]{20,}$', $api_key ) ) {            throw new \InvalidArgumentException(
                __( 'El formato de la API key de OpenAI no es válido.', 'mondays-work-ai-core' )
            );
        }
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
                    '[Monday\'s Work AI Core - OpenAI] %s | Context: %s',
                    $message,
                    wp_json_encode( $context )
					                )

				            );
			        }
