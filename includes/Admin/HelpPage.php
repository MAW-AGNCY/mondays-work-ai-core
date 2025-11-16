<?php
/**
 * Help Page Class - Monday's Work AI Core
 * Clase de Página de Ayuda - Monday's Work AI Core
 *
 * Contextual help page with system information and quick guides.
 * Página de ayuda contextual con información del sistema y guías rápidas.
 *
 * @package    MondaysWork\AI\Core
 * @subpackage Admin
 * @since      1.0.0
 * @author     Mondays at Work <info@mondaysatwork.com>
 * @license    Proprietary
 * @link       https://github.com/MAW-AGNCY/mondays-work-ai-core
 */

namespace MondaysWork\AI\Core\Admin;

// Exit if accessed directly / Salir si se accede directamente
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Help Page Class
 * Clase de Página de Ayuda
 *
 * @since 1.0.0
 */
class HelpPage {

    /**
     * Plugin version
     * Versión del plugin
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $version = '1.0.0';

    /**
     * Render help page
     * Renderiza página de ayuda
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function render(): void {
        ?>
        <div class="wrap mwai-help-wrap">
            <h1><?php esc_html_e( 'Ayuda - Monday\'s Work AI Core', 'mondays-work-ai-core' ); ?></h1>

            <?php $this->render_quick_guides(); ?>
            <?php $this->render_system_info(); ?>
            <?php $this->render_faq(); ?>
            <?php $this->render_contact(); ?>
        </div>
        <?php
    }

    /**
     * Render quick guides section
     * Renderiza sección de guías rápidas
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function render_quick_guides(): void {
        ?>
        <div class="mwai-card">
            <h2><?php esc_html_e( 'Guías Rápidas', 'mondays-work-ai-core' ); ?></h2>
            <div class="mwai-guides">
                <div class="mwai-guide-item">
                    <h3>🚀 <?php esc_html_e( 'Primeros Pasos', 'mondays-work-ai-core' ); ?></h3>
                    <ol>
                        <li><?php esc_html_e( 'Obtén una API key de OpenAI o Google Gemini', 'mondays-work-ai-core' ); ?></li>
                        <li><?php esc_html_e( 'Ve a Configuración > Proveedores de IA', 'mondays-work-ai-core' ); ?></li>
                        <li><?php esc_html_e( 'Ingresa tu API key y prueba la conexión', 'mondays-work-ai-core' ); ?></li>
                        <li><?php esc_html_e( 'Guarda y comienza a usar las funcionalidades', 'mondays-work-ai-core' ); ?></li>
                    </ol>
                </div>

                <div class="mwai-guide-item">
                    <h3>🔑 <?php esc_html_e( 'Obtener API Keys', 'mondays-work-ai-core' ); ?></h3>
                    <ul>
                        <li><strong>OpenAI:</strong> <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com/api-keys</a></li>
                        <li><strong>Google Gemini:</strong> <a href="https://makersuite.google.com/app/apikey" target="_blank">makersuite.google.com</a></li>
                    </ul>
                </div>

                <div class="mwai-guide-item">
                    <h3>⚙️ <?php esc_html_e( 'Configuración Recomendada', 'mondays-work-ai-core' ); ?></h3>
                    <ul>
                        <li><?php esc_html_e( 'Modelo: gpt-4 o gpt-3.5-turbo', 'mondays-work-ai-core' ); ?></li>
                        <li><?php esc_html_e( 'Temperature: 0.7', 'mondays-work-ai-core' ); ?></li>
                        <li><?php esc_html_e( 'Max Tokens: 1000', 'mondays-work-ai-core' ); ?></li>
                        <li><?php esc_html_e( 'Caché: Habilitado (producción)', 'mondays-work-ai-core' ); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get system information
     * Obtiene información del sistema
     *
     * @since  1.0.0
     * @access public
     * @return array System information / Información del sistema
     */
    public function get_system_info(): array {
        global $wpdb;

        return array(
            'plugin_version'   => $this->version,
            'wordpress'        => get_bloginfo( 'version' ),
            'php'              => PHP_VERSION,
            'mysql'            => $wpdb->db_version(),
            'server'           => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'Unknown',
            'memory_limit'     => WP_MEMORY_LIMIT,
            'max_upload'       => size_format( wp_max_upload_size() ),
            'timezone'         => wp_timezone_string(),
            'curl'             => extension_loaded( 'curl' ),
            'json'             => extension_loaded( 'json' ),
            'mbstring'         => extension_loaded( 'mbstring' ),
        );
    }

    /**
     * Render system information section
     * Renderiza sección de información del sistema
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function render_system_info(): void {
        $info = $this->get_system_info();
        ?>
        <div class="mwai-card">
            <h2><?php esc_html_e( 'Información del Sistema', 'mondays-work-ai-core' ); ?></h2>
            <table class="widefat mwai-system-table">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e( 'Versión del Plugin', 'mondays-work-ai-core' ); ?></strong></td>
                        <td><?php echo esc_html( $info['plugin_version'] ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'WordPress', 'mondays-work-ai-core' ); ?></strong></td>
                        <td><?php echo esc_html( $info['wordpress'] ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'PHP', 'mondays-work-ai-core' ); ?></strong></td>
                        <td><?php echo esc_html( $info['php'] ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'MySQL', 'mondays-work-ai-core' ); ?></strong></td>
                        <td><?php echo esc_html( $info['mysql'] ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Servidor', 'mondays-work-ai-core' ); ?></strong></td>
                        <td><?php echo esc_html( $info['server'] ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Extensiones PHP', 'mondays-work-ai-core' ); ?></strong></td>
                        <td>
                            <span class="<?php echo $info['curl'] ? 'mwai-status-ok' : 'mwai-status-error'; ?>">
                                <?php echo $info['curl'] ? '✓' : '✗'; ?> cURL
                            </span>
                            <span class="<?php echo $info['json'] ? 'mwai-status-ok' : 'mwai-status-error'; ?>">
                                <?php echo $info['json'] ? '✓' : '✗'; ?> JSON
                            </span>
                            <span class="<?php echo $info['mbstring'] ? 'mwai-status-ok' : 'mwai-status-error'; ?>">
                                <?php echo $info['mbstring'] ? '✓' : '✗'; ?> mbstring
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render FAQ section
     * Renderiza sección de FAQ
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function render_faq(): void {
        $faqs = array(
            array(
                'question' => __( '¿Cuánto cuesta usar OpenAI?', 'mondays-work-ai-core' ),
                'answer'   => __( 'Los costos varían según el modelo. GPT-3.5-turbo cuesta aproximadamente $0.002 por 1,000 tokens, mientras que GPT-4 cuesta $0.03 por 1,000 tokens.', 'mondays-work-ai-core' ),
            ),
            array(
                'question' => __( '¿Puedo usar múltiples proveedores?', 'mondays-work-ai-core' ),
                'answer'   => __( 'Sí, puedes configurar múltiples proveedores, pero solo uno estará activo a la vez. Puedes cambiar entre ellos en la configuración.', 'mondays-work-ai-core' ),
            ),
            array(
                'question' => __( '¿Qué es el caché y debo habilitarlo?', 'mondays-work-ai-core' ),
                'answer'   => __( 'El caché almacena respuestas de IA para reducir costos y mejorar rendimiento. Es altamente recomendado habilitarlo en producción.', 'mondays-work-ai-core' ),
            ),
            array(
                'question' => __( '¿Cómo soluciono errores de conexión?', 'mondays-work-ai-core' ),
                'answer'   => __( 'Verifica tu API key, firewall y límites de uso. Activa el modo debug para ver logs detallados.', 'mondays-work-ai-core' ),
            ),
        );

        ?>
        <div class="mwai-card">
            <h2><?php esc_html_e( 'Preguntas Frecuentes', 'mondays-work-ai-core' ); ?></h2>
            <div class="mwai-faq">
                <?php foreach ( $faqs as $faq ) : ?>
                    <div class="mwai-faq-item">
                        <h3><?php echo esc_html( $faq['question'] ); ?></h3>
                        <p><?php echo esc_html( $faq['answer'] ); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render contact section
     * Renderiza sección de contacto
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function render_contact(): void {
        ?>
        <div class="mwai-card mwai-contact-card">
            <h2><?php esc_html_e( 'Soporte y Contacto', 'mondays-work-ai-core' ); ?></h2>
            <p><?php esc_html_e( 'Si necesitas ayuda adicional, no dudes en contactarnos:', 'mondays-work-ai-core' ); ?></p>
            
            <div class="mwai-contact-buttons">
                <a href="mailto:info@mondaysatwork.com" class="button button-primary button-hero">
                    <span class="dashicons dashicons-email"></span>
                    info@mondaysatwork.com
                </a>
                
                <a href="https://github.com/MAW-AGNCY/mondays-work-ai-core" target="_blank" class="button button-secondary button-hero">
                    <span class="dashicons dashicons-admin-links"></span>
                    <?php esc_html_e( 'GitHub Repository', 'mondays-work-ai-core' ); ?>
                </a>
                
                <a href="https://github.com/MAW-AGNCY/mondays-work-ai-core/blob/main/docs/CONFIGURATION.md" target="_blank" class="button button-secondary button-hero">
                    <span class="dashicons dashicons-book"></span>
                    <?php esc_html_e( 'Documentación Completa', 'mondays-work-ai-core' ); ?>
                </a>
            </div>

            <div class="mwai-contact-info">
                <p><strong><?php esc_html_e( 'Mondays at Work', 'mondays-work-ai-core' ); ?></strong></p>
                <p><?php esc_html_e( '© 2025 Todos los derechos reservados. Licencia Propietaria.', 'mondays-work-ai-core' ); ?></p>
            </div>
        </div>
        <?php
    }
}
