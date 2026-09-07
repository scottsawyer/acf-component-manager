<?php
/**
 * Contains the SourceService class.
 *
 * @since 0.0.7
 * @package acf-component-manager.
 */

declare( strict_types=1 );

namespace AcfComponentManager\Service;

// If this file is called directly, short.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Provides SourceService class.
 */
class SourceService {

	/**
	 * Get sources.
	 *
	 * @param string $enabled Defaults to true.
	 *
	 * @return array
	 */
	public function get_sources( string $enabled = 'on' ): array {

		$sources = array();
		$stored_sources = get_option( SOURCES_OPTION_NAME );

		if ( $stored_sources ) {
			$sources = unserialize( $stored_sources );
		}
		if ( 'on' === $enabled && ! empty( $sources ) ) {
			$sources = array_filter(
				$sources,
				function ( $source ) {
					return 'on' === $source['enabled'];
				}
			);

		}
		return $sources;
	}

	/**
	 * Set sources.
	 *
	 * @since 0.0.7
	 * @param array $sources An array of sources.
	 *
	 * @return void
	 */
	public function set_sources( array $sources ): void {
		update_option( SOURCES_OPTION_NAME, serialize( $sources ) );
	}
}
