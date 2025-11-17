<?php
/**
 * Custom PSR-4 Autoloader / Autoloader PSR-4 Personalizado
 *
 * @package    MondaysWork\AI\Core
 * @author     Mondays at Work <info@mondaysatwork.com>
 * @copyright  2025 Mondays at Work
 * @license    Proprietary
 * @link       https://github.com/MAW-AGNCY/mondays-work-ai-core
 * @since      1.0.0
 *
 * This file implements a PSR-4 compliant autoloader to eliminate Composer dependency.
 * Este archivo implementa un autoloader compatible con PSR-4 para eliminar la dependencia de Composer.
 */

// Exit if accessed directly / Salir si se accede directamente
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PSR-4 Autoloader function / Función autoloader PSR-4
 *
 * Automatically loads classes following PSR-4 standard.
 * Carga automáticamente las clases siguiendo el estándar PSR-4.
 *
 * @param string $class_name Fully qualified class name / Nombre completo de la clase.
 * @return void
 */
spl_autoload_register(
	function ( $class_name ) {
		// Namespace prefix / Prefijo del namespace
		$prefix = 'MondaysWork\\AI\\Core\\';

		// Base directory for the namespace prefix / Directorio base para el prefijo del namespace
		$base_dir = __DIR__ . '/';

		// Does the class use the namespace prefix? / ¿La clase usa el prefijo del namespace?
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
			// No, move to the next registered autoloader / No, pasar al siguiente autoloader registrado
			return;
		}

		// Get the relative class name / Obtener el nombre relativo de la clase
		$relative_class = substr( $class_name, $len );

		// Replace namespace separators with directory separators in the relative class name
		// Reemplazar separadores de namespace con separadores de directorio en el nombre relativo de la clase
		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		// If the file exists, require it / Si el archivo existe, incluirlo
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);
