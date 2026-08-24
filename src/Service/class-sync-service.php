<?php
/**
 * Contains the SyncService class.
 *
 * @since 0.0.9
 * @package acf-component-manager
 */

declare( strict_types=1 );

namespace AcfComponentManager\Service;

// If this file is called directly, short.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Provides SyncService class.
 */
class SyncService {

	/**
	 * AcfComponentManager\Service\AcfService definition.
	 *
	 * @since 0.0.9
	 * @var \AcfComponentManager\Service\AcfService
	 */
	protected AcfService $acfService;

	/**
	 * AcfComponentManager\Service\ComponentService definition.
	 *
	 * @since 0.0.9
	 * @var \AcfComponentManager\Service\ComponentService
	 */
	protected ComponentService $componentService;

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
		$this->componentService = new ComponentService();
	}

	/**
	 * Auto-sync components.
	 *
	 * Runs on admin init to sync auto-synced components, if the modified data is >
	 * the corresponding updated date.
	 *
	 * @since 0.0.9
	 * @param array $components An array of components to sync.
	 * return void.
	 */
	public function sync_components( array $components ): void {

		if ( ! empty( $components ) ) {
			print '<pre>';
			print_r( $components );
			print '</pre>';
			foreach ( $components as $hash => $component ) {
				$needs_sync = false;
				$db_post = null;
				$post_type = null;

				print '<pre>';
				print 'Component: <br>';
				print_r( $component );
				print '</pre>';

				$json = $this->acfService->get_acf_json( $component['file_path'] );

				if ( ! $json ) {
					continue;
				}

				$modified_date = $json['modified'];
				$key = $component['key'];

				// Find the matching post.
				if ( str_starts_with( $key, 'post_type_' ) ) {
					$post_type = 'acf-post-type';
				}
				if ( str_starts_with( $key, 'group_' ) ) {
					$post_type = 'acf-field-group';
				}
				if ( str_starts_with( $key, 'ui_options_page_' ) ) {
					$post_type = 'acf-ui-options-page';
				}
				if ( str_starts_with( $key, 'taxonomy_' ) ) {
					$post_type = 'acf-taxonomy';
				}

				if ( $post_type ) {
					$db_post = $this->acfService->get_acf_post_by_key( $key, $post_type );
				}
				if ( $db_post ) {
					$needs_sync = $this->should_sync_component( $modified_date, $db_post );
				} else {
					$needs_sync = true;
				}
				if ( $needs_sync && $post_type == 'acf-post-type' ) {
					$json = acf_prepare_post_type_for_import( $json, $post_type );
					//$valid = acf_validate_post_type( $prepared );
					$json['local']  = 'json';
				  $json['local_file'] = $component['file_path'];

					print '<pre>';
					print '$JSON<BR>';
					print_r($json);
					print '</pre>';
					$success = acf_add_local_internal_post_type( $json, $post_type );
					$success = false;
					if ( $success ) {
						print 'Did it?';
					}
					else {
						print 'nope';
						// Disable "Local JSON" controller to prevent the .json file from being modified during import.
						acf_update_setting( 'json', false );
						$prepared = acf_prepare_internal_post_type_for_import( $json, $post_type );
						print '<pre>';
						print '$prepared<br>';
						print_r( $prepared );
						print '</pre>';
						//$store = acf_get_local_store('', $post_type)->get( $key );
						//$result = acf_import_internal_post_type( $json, $post_type );
						print '<pre>';
						print '$result: <br>';
						//print_r($result);
						print '</pre>';
					}

				}
			}
		}
		else {
			print 'Empty components';
		}
	}

	/**
	 * Component needs db sync.
	 *
	 * @since 0.0.9
	 * @param string   $modified The timestamp when the JSON was last modified.
	 * @param \WP_Post $post     The ACF post.
	 *
	 * @return bool
	 */
	protected function should_sync_component( string $modified, \WP_Post $post ): bool {
		$need_sync = false;
		// WordPress stores this as a 'YYYY-MM-DD HH:MM:SS' string.
		$db_modified_date = ! empty( $post->post_modified_gmt ) ? $post->post_modified_gmt : $db_group->post_modified;
		$db_modified      = strtotime( $db_modified_date . ' GMT' );
		if ( $modified > $db_modified ) {
			$need_sync = true;
		}
		return $need_sync;
	}

	/**
	 * Sync JSON to db.
	 *
	 * @param array         $component The raw component data.
	 * @param \WP_Post|null $post      The post object if exists.
	 *
	 * @return boolean
	 */
	protected function sync_component_to_db( array $component, ?\WP_Post $post = null ): bool {

	}

}
