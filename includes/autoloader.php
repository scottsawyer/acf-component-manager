<?php
/**
 * Provides autoloader.
 *
 * @package acf-component-manager
 *
 * @since 0.0.1
 */

spl_autoload_register( 'acf_component_manager_autoloader' );

/**
 * Auto load function.
 *
 * @param string $class The class to load.
 *
 * @return void
 */
function acf_component_manager_autoloader( $class ) {
	$namespace = 'AcfComponentManager';

	if ( strpos( $class, $namespace ) !== 0 ) {

		return;
	}

	$class = trim( str_replace( $namespace, '', $class ), '\\' );
	$class_parts = explode( '\\', $class );
	$last_index = count( $class_parts ) - 1;
	$caps = '/(?=[A-Z])/';
	$class_file_parts = preg_split( $caps, lcfirst( $class_parts[ $last_index ] ) );
	$class_file_name  = 'class-' . implode( '-', $class_file_parts ) . '.php';
	$class_parts[ $last_index ] = strtolower( $class_file_name );
	$class = implode( DIRECTORY_SEPARATOR, $class_parts );

	$path = ACF_COMPONENT_MANAGER_PATH . 'src' . DIRECTORY_SEPARATOR . $class;

	if ( file_exists( $path ) ) {
		require_once $path;
	}
}
