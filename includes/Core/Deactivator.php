<?php
/**
 * Desactivador del Plugin - Monday's Work AI Core
 * Plugin Deactivator - Monday's Work AI Core
 *
 * Esta clase contiene toda la lógica que se ejecuta cuando el plugin
 * es desactivado. Incluye limpieza de tareas programadas, caché temporal
 * y otros recursos sin eliminar datos permanentes del usuario.
 *
 * This class contains all logic executed when the plugin
 * is deactivated. Includes cleanup of scheduled tasks, temporary cache,
 * and other resources without deleting permanent user data.
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
 * Clase de Desactivación del Plugin
 * Plugin Deactivation Class
 *
 * Gestiona todas las tareas de limpieza necesarias durante la desactivación
 * del plugin. Se enfoca en limpiar recursos temporales y tareas programadas
 * sin afectar los datos permanentes del usuario.
 *
 * Manages all cleanup tasks necessary during plugin deactivation.
 * Focuses on cleaning temporary resources and scheduled tasks
 * without affecting permanent user data.
 *
 * @since 1.0.0
 */
class Deactivator {

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
     * Opciones que deben preservarse durante la desactivación
     * Options that should be preserved during deactivation
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    private static $preserve_options = array(
        'mondays-work-ai-core_config',
        'mondays_work_ai_core_version',
        'mondays_work_ai_core_activated_at',
    );

    /**
     * Ejecuta la desactivación del plugin
     * Executes plugin deactivation
     *
     * Método principal que se ejecuta cuando el plugin es desactivado.
     * Limpia recursos temporales, detiene tareas programadas y registra
     * el evento sin eliminar configuraciones o datos del usuario.
     *
     * Main method executed when the plugin is deactivated.
     * Cleans temporary resources, stops scheduled tasks, and logs
     * the event without deleting user configurations or data.
     *
     * @since  1.0.0
     * @access public
     * @static
     * @return void
     */
    public static function deactivate() {
        try {
            // Verificar permisos / Check permissions
            self::check_permissions();

            // Cancelar eventos cron programados / Cancel scheduled cron events
            self::unschedule_events();

            // Limpiar caché temporal / Clean temporary cache
            self::cleanup_temporary_cache();

            // Limpiar archivos temporales / Clean temporary files
            self::cleanup_temporary_files();

            // Limpiar transients / Clean transients
            self::cleanup_transients();

            // Flush rewrite rules / Flush rewrite rules
            self::flush_rewrite_rules();

            // Limpiar caché de objetos / Clear object cache
            self::flush_object_cache();

            // Registrar desactivación / Log deactivation
            self::log_deactivation();

            // Actualizar estado / Update status
            self::update_deactivation_status();

            /**
             * Acción disparada después de la desactivación exitosa
             * Action fired after successful deactivation
             *
             * @since 1.0.0
             * @param string $version Versión del plugin / Plugin version
             */
            do_action( 'mondays_work_ai_core_deactivated', self::$version );

        } catch ( \Exception $e ) {
            // Registrar error pero no detener desactivación / Log error but don't stop deactivation
            self::log_error( 'Error durante la desactivación', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ) );

            /**
             * Acción disparada cuando ocurre un error durante la desactivación
             * Action fired when an error occurs during deactivation
             *
             * @since 1.0.0
             * @param string     $message Mensaje de error / Error message
             * @param \Exception $e       Excepción / Exception
             */
            do_action( 'mondays_work_ai_core_deactivation_error', $e->getMessage(), $e );
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
        // Verificar que el usuario actual puede desactivar plugins / Check current user can deactivate plugins
        if ( ! current_user_can( 'activate_plugins' ) ) {
            throw new \Exception(
                __( 'No tienes permisos suficientes para desactivar plugins.', 'mondays-work-ai-core' )
            );
        }
    }

    /**
     * Cancela todos los eventos cron programados
     * Cancels all scheduled cron events
     *
     * Elimina todas las tareas programadas del plugin para evitar
     * que se ejecuten después de la desactivación.
     *
     * Removes all plugin scheduled tasks to prevent
     * them from running after deactivation.
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function unschedule_events() {
        $cron_events = array(
            'mondays_work_ai_core_cleanup_cache',
            'mondays_work_ai_core_cleanup_logs',
        );

        foreach ( $cron_events as $event ) {
            $timestamp = wp_next_scheduled( $event );
            
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $event );
            }

            // Limpiar todas las recurrencias / Clear all recurrences
            wp_clear_scheduled_hook( $event );
        }

        /**
         * Acción para cancelar eventos cron personalizados adicionales
         * Action to cancel additional custom cron events
         *
         * @since 1.0.0
         */
        do_action( 'mondays_work_ai_core_unschedule_events' );

        self::log_message( 'Eventos cron cancelados exitosamente' );
    }

    /**
     * Limpia la caché temporal del plugin
     * Cleans temporary plugin cache
     *
     * Elimina entradas de caché expiradas o temporales de la base de datos
     * sin afectar configuraciones o datos importantes.
     *
     * Removes expired or temporary cache entries from database
     * without affecting important configurations or data.
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function cleanup_temporary_cache() {
        global $wpdb;

        try {
            $cache_table = $wpdb->prefix . 'mw_ai_cache';

            // Verificar si la tabla existe / Check if table exists
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $cache_table ) ) === $cache_table ) {
                // Eliminar entradas expiradas / Delete expired entries
                $deleted = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$cache_table} WHERE expires_at < %s",
                        current_time( 'mysql' )
                    )
                );

                if ( false !== $deleted ) {
                    self::log_message( sprintf( 'Eliminadas %d entradas de caché expiradas', $deleted ) );
                }

                // Eliminar entradas temporales (creadas en las últimas 24 horas) / Delete temporary entries
                $temp_deleted = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$cache_table} WHERE created_at > %s AND metadata LIKE %s",
                        gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) ),
                        '%"temporary":true%'
                    )
                );

                if ( false !== $temp_deleted ) {
                    self::log_message( sprintf( 'Eliminadas %d entradas de caché temporales', $temp_deleted ) );
                }
            }

            /**
             * Acción para limpiar caché personalizada adicional
             * Action to clean additional custom cache
             *
             * @since 1.0.0
             */
            do_action( 'mondays_work_ai_core_cleanup_cache' );

        } catch ( \Exception $e ) {
            self::log_error( 'Error al limpiar caché temporal', array(
                'error' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Limpia archivos temporales del plugin
     * Cleans temporary plugin files
     *
     * Elimina archivos temporales del directorio de uploads
     * preservando logs y exportaciones importantes.
     *
     * Removes temporary files from uploads directory
     * while preserving important logs and exports.
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function cleanup_temporary_files() {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/mondays-work-ai-core';

        try {
            // Limpiar directorio temporal / Clean temp directory
            $temp_dir = $base_dir . '/temp';
            
            if ( file_exists( $temp_dir ) && is_dir( $temp_dir ) ) {
                $files = glob( $temp_dir . '/*' );
                
                if ( is_array( $files ) ) {
                    foreach ( $files as $file ) {
                        if ( is_file( $file ) ) {
                            // Eliminar archivos más antiguos de 24 horas / Delete files older than 24 hours
                            if ( filemtime( $file ) < strtotime( '-24 hours' ) ) {
                                // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
                                if ( unlink( $file ) ) {
                                    self::log_message( sprintf( 'Archivo temporal eliminado: %s', basename( $file ) ) );
                                }
                            }
                        }
                    }
                }
            }

            /**
             * Acción para limpiar archivos temporales personalizados adicionales
             * Action to clean additional custom temporary files
             *
             * @since 1.0.0
             * @param string $base_dir Directorio base / Base directory
             */
            do_action( 'mondays_work_ai_core_cleanup_files', $base_dir );

        } catch ( \Exception $e ) {
            self::log_error( 'Error al limpiar archivos temporales', array(
                'error' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Limpia los transients del plugin
     * Cleans plugin transients
     *
     * Elimina todos los transients creados por el plugin
     * para liberar espacio en la base de datos.
     *
     * Removes all transients created by the plugin
     * to free up database space.
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function cleanup_transients() {
        global $wpdb;

        try {
            // Obtener todos los transients del plugin / Get all plugin transients
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $transients = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options} 
                    WHERE option_name LIKE %s 
                    OR option_name LIKE %s",
                    $wpdb->esc_like( '_transient_mw_ai_' ) . '%',
                    $wpdb->esc_like( '_transient_timeout_mw_ai_' ) . '%'
                )
            );

            if ( ! empty( $transients ) ) {
                foreach ( $transients as $transient ) {
                    // Extraer el nombre del transient / Extract transient name
                    $transient_name = str_replace(
                        array( '_transient_', '_transient_timeout_' ),
                        '',
                        $transient
                    );

                    // Eliminar transient / Delete transient
                    delete_transient( $transient_name );
                }

                self::log_message( sprintf( 'Eliminados %d transients', count( $transients ) ) );
            }

            /**
             * Acción para limpiar transients personalizados adicionales
             * Action to clean additional custom transients
             *
             * @since 1.0.0
             */
            do_action( 'mondays_work_ai_core_cleanup_transients' );

        } catch ( \Exception $e ) {
            self::log_error( 'Error al limpiar transients', array(
                'error' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Limpia las reglas de reescritura
     * Flushes rewrite rules
     *
     * Limpia las reglas de reescritura de WordPress para asegurar
     * que no queden endpoints o rutas personalizadas del plugin.
     *
     * Flushes WordPress rewrite rules to ensure
     * no custom plugin endpoints or routes remain.
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function flush_rewrite_rules() {
        try {
            // Limpiar reglas de reescritura / Flush rewrite rules
            flush_rewrite_rules();

            self::log_message( 'Reglas de reescritura limpiadas' );

        } catch ( \Exception $e ) {
            self::log_error( 'Error al limpiar reglas de reescritura', array(
                'error' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Limpia la caché de objetos de WordPress
     * Flushes WordPress object cache
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function flush_object_cache() {
        try {
            // Limpiar caché de objetos / Flush object cache
            wp_cache_flush();

            self::log_message( 'Caché de objetos limpiada' );

            /**
             * Acción para limpiar cachés personalizados adicionales
             * Action to flush additional custom caches
             *
             * @since 1.0.0
             */
            do_action( 'mondays_work_ai_core_flush_cache_on_deactivation' );

        } catch ( \Exception $e ) {
            self::log_error( 'Error al limpiar caché de objetos', array(
                'error' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Registra el evento de desactivación
     * Logs the deactivation event
     *
     * Guarda información sobre la desactivación del plugin
     * en los logs para auditoría y debugging.
     *
     * Saves information about plugin deactivation
     * in logs for auditing and debugging.
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function log_deactivation() {
        $log_data = array(
            'version'      => self::$version,
            'php'          => PHP_VERSION,
            'wordpress'    => get_bloginfo( 'version' ),
            'user_id'      => get_current_user_id(),
            'site_url'     => get_site_url(),
            'deactivated_at' => current_time( 'mysql' ),
        );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log(
                sprintf(
                    '[Monday\'s Work AI Core] Plugin desactivado | Versión: %s | Usuario: %d',
                    $log_data['version'],
                    $log_data['user_id']
                )
            );
        }

        // Guardar en la tabla de logs si existe / Save to logs table if exists
        global $wpdb;
        $logs_table = $wpdb->prefix . 'mw_ai_logs';

        try {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $logs_table ) ) === $logs_table ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->insert(
                    $logs_table,
                    array(
                        'user_id'  => $log_data['user_id'],
                        'provider' => 'system',
                        'model'    => 'deactivation',
                        'prompt'   => 'Plugin desactivado',
                        'response' => wp_json_encode( $log_data ),
                        'status'   => 'success',
                        'metadata' => wp_json_encode( array( 'type' => 'deactivation' ) ),
                    ),
                    array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
                );
            }

            /**
             * Acción para registrar información personalizada adicional de desactivación
             * Action to log additional custom deactivation information
             *
             * @since 1.0.0
             * @param array $log_data Datos de desactivación / Deactivation data
             */
            do_action( 'mondays_work_ai_core_log_deactivation', $log_data );

        } catch ( \Exception $e ) {
            // Silenciar errores durante el logging / Silence logging errors
            self::log_error( 'Error al registrar desactivación', array(
                'error' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Actualiza el estado de desactivación
     * Updates deactivation status
     *
     * Guarda información sobre el estado de desactivación
     * sin eliminar la configuración del usuario.
     *
     * Saves information about deactivation status
     * without deleting user configuration.
     *
     * @since  1.0.0
     * @access private
     * @static
     * @return void
     */
    private static function update_deactivation_status() {
        try {
            // Actualizar timestamp de desactivación / Update deactivation timestamp
            update_option( 'mondays_work_ai_core_deactivated_at', current_time( 'mysql' ), false );

            // Guardar conteo de desactivaciones / Save deactivation count
            $deactivation_count = get_option( 'mondays_work_ai_core_deactivation_count', 0 );
            update_option( 'mondays_work_ai_core_deactivation_count', $deactivation_count + 1, false );

            self::log_message( 'Estado de desactivación actualizado' );

        } catch ( \Exception $e ) {
            self::log_error( 'Error al actualizar estado de desactivación', array(
                'error' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Registra un mensaje informativo
     * Logs an informational message
     *
     * @since  1.0.0
     * @access private
     * @static
     * @param  string $message Mensaje a registrar / Message to log
     * @return void
     */
    private static function log_message( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log(
                sprintf(
                    '[Monday\'s Work AI Core - Deactivator] %s',
                    $message
                )
            );
        }
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
                    '[Monday\'s Work AI Core - Deactivator] ERROR: %s | Context: %s',
                    $message,
                    wp_json_encode( $context )
                )
            );
        }

        /**
         * Acción disparada cuando se registra un error durante la desactivación
         * Action fired when an error is logged during deactivation
         *
         * @since 1.0.0
         * @param string $message Mensaje de error / Error message
         * @param array  $context Contexto del error / Error context
         */
        do_action( 'mondays_work_ai_core_deactivator_error', $message, $context );
    }

    /**
     * Limpieza completa del plugin (solo en desinstalación)
     * Complete plugin cleanup (only on uninstall)
     *
     * NOTA: Este método NO debe ser llamado durante la desactivación.
     * Solo debe usarse en el archivo uninstall.php para eliminar
     * completamente todos los datos del plugin.
     *
     * NOTE: This method should NOT be called during deactivation.
     * It should only be used in uninstall.php to completely
     * remove all plugin data.
     *
     * @since  1.0.0
     * @access public
     * @static
     * @return void
     */
    public static function uninstall() {
        // Este método está aquí como referencia pero debe ser
        // implementado en el archivo uninstall.php del plugin
        // This method is here as reference but should be
        // implemented in the plugin's uninstall.php file

        /**
         * Acción disparada durante la desinstalación completa
         * Action fired during complete uninstallation
         *
         * @since 1.0.0
         */
        do_action( 'mondays_work_ai_core_uninstall' );
    }
}
