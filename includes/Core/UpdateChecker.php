<?php
/**
 * Update Checker Class - Monday's Work AI Core
 * Clase de Verificación de Actualizaciones - Monday's Work AI Core
 *
 * Handles plugin updates from custom server.
 * Maneja actualizaciones del plugin desde servidor personalizado.
 *
 * @package    MondaysWork\AI\Core
 * @subpackage Core
 * @since      1.0.0
 * @author     Mondays at Work <info@mondaysatwork.com>
 * @license    Proprietary
 * @link       https://github.com/MAW-AGNCY/mondays-work-ai-core
 */

namespace MondaysWork\AI\Core\Core;

// Exit if accessed directly / Salir si se accede directamente
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Update Checker Class
 * Clase de Verificación de Actualizaciones
 *
 * @since 1.0.0
 */
class UpdateChecker {

    /**
     * Plugin file path
     * Ruta del archivo del plugin
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $plugin_file;

    /**
     * Plugin slug
     * Slug del plugin
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $plugin_slug;

    /**
     * Update server URL
     * URL del servidor de actualizaciones
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $update_url;

    /**
     * Current plugin version
     * Versión actual del plugin
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private $current_version;

    /**
     * Constructor
     *
     * @since  1.0.0
     * @access public
     * @param  string $plugin_file Plugin file path / Ruta del archivo
     * @param  string $update_url  Update server URL / URL del servidor
     */
    public function __construct( string $plugin_file, string $update_url ) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = basename( dirname( $plugin_file ) );
        $this->update_url  = $update_url;

        // Get current version / Obtener versión actual
        $plugin_data = get_plugin_data( $plugin_file );
        $this->current_version = $plugin_data['Version'];
    }

    /**
     * Initialize update checker
     * Inicializa verificador de actualizaciones
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function init(): void {
        // Check for updates / Verificar actualizaciones
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_updates' ) );

        // Plugin information / Información del plugin
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );

        // Clear cache on demand / Limpiar caché bajo demanda
        add_action( 'admin_init', array( $this, 'maybe_clear_cache' ) );
    }

    /**
     * Check for plugin updates
     * Verifica actualizaciones del plugin
     *
     * @since  1.0.0
     * @access public
     * @param  object $transient Update transient / Transient de actualización
     * @return object            Modified transient / Transient modificado
     */
    public function check_for_updates( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        // Get remote version / Obtener versión remota
        $remote = $this->get_remote_version();

        if ( ! $remote ) {
            return $transient;
        }

        // Compare versions / Comparar versiones
        if ( $this->is_update_available( $this->current_version, $remote->version ) ) {
            $plugin_basename = plugin_basename( $this->plugin_file );

            $transient->response[ $plugin_basename ] = (object) array(
                'slug'        => $this->plugin_slug,
                'plugin'      => $plugin_basename,
                'new_version' => $remote->version,
                'url'         => $remote->homepage,
                'package'     => $remote->download_url,
                'tested'      => $remote->tested,
                'requires'    => $remote->requires,
                'requires_php' => $remote->requires_php,
            );
        }

        return $transient;
    }

    /**
     * Provide plugin information for WordPress
     * Proporciona información del plugin para WordPress
     *
     * @since  1.0.0
     * @access public
     * @param  false|object|array $result Result object / Objeto de resultado
     * @param  string             $action Action type / Tipo de acción
     * @param  object             $args   Arguments / Argumentos
     * @return object                     Plugin information / Información del plugin
     */
    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        if ( $this->plugin_slug !== $args->slug ) {
            return $result;
        }

        $remote = $this->get_remote_version();

        if ( ! $remote ) {
            return $result;
        }

        return (object) array(
            'name'          => $remote->name,
            'slug'          => $remote->slug,
            'version'       => $remote->version,
            'author'        => $remote->author,
            'author_profile' => $remote->author_profile,
            'homepage'      => $remote->homepage,
            'requires'      => $remote->requires,
            'tested'        => $remote->tested,
            'requires_php'  => $remote->requires_php,
            'download_link' => $remote->download_url,
            'sections'      => $remote->sections,
            'last_updated'  => $remote->last_updated,
        );
    }

    /**
     * Get remote version information
     * Obtiene información de versión remota
     *
     * @since  1.0.0
     * @access public
     * @return object|null Remote version data / Datos de versión remota
     */
    public function get_remote_version(): ?object {
        // Check cache / Verificar caché
        $cache_key = 'mwai_update_' . $this->plugin_slug;
        $cached = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        // Fetch from server / Obtener del servidor
        $response = wp_remote_get(
            $this->update_url,
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/json',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        if ( ! $data ) {
            return null;
        }

        // Cache for 12 hours / Cachear por 12 horas
        set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );

        return $data;
    }

    /**
     * Check if update is available
     * Verifica si hay actualización disponible
     *
     * @since  1.0.0
     * @access public
     * @param  string $current_version Current version / Versión actual
     * @param  string $remote_version  Remote version / Versión remota
     * @return bool                    True if update available / True si hay actualización
     */
    public function is_update_available( string $current_version, string $remote_version ): bool {
        return version_compare( $current_version, $remote_version, '<' );
    }

    /**
     * Maybe clear update cache
     * Posiblemente limpiar caché de actualizaciones
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function maybe_clear_cache(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['mwai_clear_cache'] ) ) {
            $this->clear_cache();
            wp_safe_redirect( admin_url( 'admin.php?page=mondays-work-ai-core' ) );
            exit;
        }
    }

    /**
     * Clear update cache
     * Limpia caché de actualizaciones
     *
     * @since  1.0.0
     * @access public
     * @return void
     */
    public function clear_cache(): void {
        $cache_key = 'mwai_update_' . $this->plugin_slug;
        delete_transient( $cache_key );
    }
}
