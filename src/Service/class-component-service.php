<?php
/**
 * Contains the ComponentService class.
 *
 * @since 0.0.9
 * @package acf-component-manager
 */

declare( strict_types = 1 );

namespace AcfComponentManager\Service;

// If this file is called directly, short.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Provides ComponentService class.
 */
class ComponentService {

	/**
	 * AcfComponentManager\Service\SourceService definition.
	 *
	 * @since 0.0.7
	 * @var \AcfComponentManager\Service\SourceService
	 */
	protected SourceService $sourceService;

	/**
	 * AcfComponentManager\Service\AcfService definition.
	 *
	 * @since 0.0.9
	 * @var \AcfComponentManager\Service\AcfService
	 */
	protected AcfService $acfService;

	/**
	 * Discovered components transient name.
	 *
	 * @var string
	 */
	const DISCOVERED_COMPONENTS = 'acf-component-manager-discovered-components';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.1
	 */
	public function __construct() {
		$this->load_dependencies();
	}

	/**
	 * Load dependencies.
	 *
	 * @return void
	 */
	protected function load_dependencies(): void {
		$this->acfService = new AcfService();
		$this->sourceService = new SourceService();
	}

	/**
	 * Set stored components.
	 *
	 * @since 0.0.1
	 *
	 * @param array $components The components to store.
	 *
	 * @return void
	 */
	public function set_stored_components( array $components ): void {
		delete_transient( self::DISCOVERED_COMPONENTS );
		set_transient( STORED_COMPONENTS_OPTION_NAME, $components, HOUR_IN_SECONDS );
		update_option( STORED_COMPONENTS_OPTION_NAME, serialize( $components ) );
	}

	/**
	 * Get stored components.
	 *
	 * @return array
	 *   The components array.
	 */
	public function get_stored_components(): array {

		if ( false === ( $components = get_transient( STORED_COMPONENTS_OPTION_NAME ) ) ) {

			$stored_components = get_option( STORED_COMPONENTS_OPTION_NAME );

			if ( $stored_components ) {
				$components = unserialize( $stored_components );
				set_transient( STORED_COMPONENTS_OPTION_NAME, $stored_components, HOUR_IN_SECONDS );
			} else {
				return array();
			}

		}
		if ( ! is_array( $components ) ) {
			$components = unserialize( $components );
		}
		return $components;
	}

	/**
	 * Get stored component.
	 *
	 * @since 0.0.1
	 * @param string $component_hash The component hash.
	 *
	 * @return array
	 *   The stored component.
	 */
	public function get_stored_component( string $component_hash ): array {
		$stored_component = array();

		$stored_components = $this->get_stored_components();
		if ( ! empty( $stored_components ) ) {
			foreach ( $stored_components as $hash => $stored ) {
				if ( $hash == $component_hash ) {
					$stored_component = $stored;
				}
			}
		}
		return $stored_component;
	}

	/**
	 * Get enabled components.
	 *
	 * @since 0.0.1
	 *
	 * @return array
	 *   An array of enabled components.
	 */
	public function get_enabled_components(): array {
		$enabled_components = array();

		$stored_components = $this->get_stored_components();
		if ( ! empty( $stored_components ) ) {
			foreach ( $stored_components as $hash => $stored ) {
				if ( isset( $stored['enabled'] ) && $stored['enabled'] ) {
					$enabled_components[ $hash ] = $stored;
				}
			}
		}

		return $enabled_components;
	}

	/**
	 * Discover components.
	 *
	 * Discovers components in the file system based on 'sources'.
	 *
	 * @since 0.0.7
	 *
	 * @return array The discovered components.
	 */
	public function get_discovered_components(): array {

		if ( false === ( $components = get_transient( self::DISCOVERED_COMPONENTS ) ) ) {
			$sources = $this->sourceService->get_sources();
			if ( empty( $sources ) ) {
				return array();
			}
			foreach ( $sources as $source ) {
				$path_parts = array(
					$source['source_path'],
					$source['components_directory'],
				);

				$path_parts = implode( '/', $path_parts );

				foreach ( glob( "{$path_parts}/*/functions.php" ) as $functions_file ) {
					$component = get_file_data( $functions_file, array( 'Component' => 'Component' ) );
					// Get all eligible components.  Components should be in the designated
					// directory and include the File Header 'Component'.
					if ( ! empty( $component['Component'] ) ) {
						$component_path = str_replace( '/functions.php', '', $functions_file );

						$components[] = array(
							'source_id' => $source['source_id'],
							'source_name' => $source['source_name'],
							'name' => $component['Component'],
							'path' => $component_path,
							'hash' => wp_hash( $component_path, '' ),
						);
					}
				}
			}
			set_transient( self::DISCOVERED_COMPONENTS, $components, HOUR_IN_SECONDS );
		}

		return $components;
	}

	/**
	 * Get missing components.
	 *
	 * @since 0.0.1
	 *
	 * @param array $managed_components The components currently managed.
	 *
	 * @return array Array of components that only exist in the database.
	 */
	public function get_missing_components( array $managed_components ): array {

		$database_components = $this->acfService->get_acf_posts();
		if ( empty( $managed_components ) ) {
			return $database_components;
		}

		return array_filter(
			$database_components,
			function ( $item ) use ( $managed_components ) {
				return ! in_array( $item['key'], array_column( $managed_components, 'key' ) );
			}
		);
	}

	/**
	 * Get auto-sync components.
	 *
	 * @return array
	 */
	public function get_auto_sync_components(): array {
		$enabled_components = $this->get_enabled_components();
		if ( empty( $enabled_components ) ) {
			return array();
		}
		return array_filter( $enabled_components, function ( $item ) {
			return $item['auto_sync'] == true;
		} );
	}
}
