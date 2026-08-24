<?php
/**
 * Contains Component Manager class.
 *
 * @package acf-component-manager
 */

declare( strict_types=1 );

namespace AcfComponentManager\Controller;

// If this file is called directly, short.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use AcfComponentManager\Form\ComponentForm;
use AcfComponentManager\Form\ComponentsExportForm;
use AcfComponentManager\Service\AcfService;
use AcfComponentManager\Service\ComponentService;
use AcfComponentManager\Service\SourceService;
use AcfComponentManager\Service\SyncService;
use AcfComponentManager\View\ComponentView;
use AcfComponentManager\NoticeManager;

/**
 * Provides ComponentManager class.
 */
class ComponentManager {

	/**
	 * AcfComponentManager\NoticeManager definition.
	 *
	 * @var \AcfComponentManager\NoticeManager
	 */
	protected NoticeManager $noticeManager;

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
	 * AcfComponentManager\Service\SourceService definition.
	 *
	 * @since 0.0.7
	 * @var \AcfComponentManager\Service\SourceService
	 */
	protected SourceService $sourceService;

	/**
	 * AcfComponentManager\Service\SyncService definition.
	 *
	 * @since 0.0.9
	 * @var \AcfComponentManager\Service\SyncService
	 */
	protected SyncService $syncService;

	/**
	 * File pattern.
	 * "$settings['active_theme_directory']}/{$settings['components_directory']}{$component['path']}/{$settings['file_directory']}/"
	 *
	 * @var string
	 */
	protected string $file_pattern = '%1$s/%2$s/%3$s/%4$s/';

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
		$this->noticeManager = new NoticeManager();
		$this->acfService = new acfService();
		$this->sourceService = new SourceService();
		$this->componentService = new ComponentService();
		$this->syncService = new SyncService();
	}

	/**
	 * Render page.
	 *
	 * @since 0.0.1
	 *
	 * @param string $action   The current action.
	 * @param string $form_url The form URL.
	 *
	 * @return void
	 */
	public function render_page( string $action = 'view', string $form_url = '' ): void {
		print '<h2>' . __( 'Manage Components', 'acf-component-manager' ) . '</h2>';

		switch ( $action ) {
			case 'view':
				$view = new ComponentView( $form_url );
				$discovered_components = $this->componentService->get_discovered_components();
				$stored_components = $this->componentService->get_stored_components();
				$new_components = array();
				if ( ! empty( $discovered_components ) ) {

					// Filters for performance.
					$new_components = array_filter(
						$discovered_components,
						function ( $discovered_component ) use ( $stored_components ) {
							return ! in_array( $discovered_component['hash'], array_column( $stored_components, 'hash' ) );
						}
					);
				}
				$missing_components = $this->componentService->get_missing_components( $this->componentService->get_stored_components() );

				$view->view( $stored_components, $new_components, $missing_components );
				break;

			case 'edit':
				$form = new ComponentForm( $form_url );
				$discovered_components = $this->componentService->get_discovered_components();
				$form_components = array();
				if ( ! empty( $discovered_components ) ) {
					foreach ( $discovered_components as $discovered_component ) {
						$files = $this->get_acf_files( $discovered_component );
						if ( ! empty( $files ) ) {
							$discovered_component['files'] = $files;
						}

						$discovered_component['stored'] = $this->componentService->get_stored_component( $discovered_component['hash'] );
						$form_components[ $discovered_component['hash'] ] = $discovered_component;
					}
				}

				$form->form( $form_components );
				break;
		}
	}

	/**
	 * Dashboard.
	 *
	 * @return void
	 */
	public function dashboard(): void {
		$enabled_components = $this->componentService->get_enabled_components();
		$view = new ComponentView( '' );
		$view->dashboard( $enabled_components );
	}

	/**
	 * Tools.
	 *
	 * @param string $action    The current action.
	 * @param string $form_url  The form URL.
	 *
	 * @return void
	 */
	public function tools( string $action, string $form_url ): void {
		$export_form = new ComponentsExportForm( $form_url );
		$export_form->form();
	}

	/**
	 * Add menu tab.
	 *
	 * @param array $tabs Existing tabs.
	 *
	 * @return array The tabs.
	 */
	public function add_menu_tab( array $tabs ): array {
		$tabs['manage_components'] = __( 'Manage components', 'acf-component-manager' );
		return $tabs;
	}

	/**
	 * Save form data.
	 *
	 * @since 0.0.1
	 *
	 * @param array $form_data The form data array.
	 *
	 * @return void
	 */
	public function save( array $form_data ): void {
		$discovered_components = $this->componentService->get_discovered_components();

		if ( ! empty( $discovered_components ) ) {
			$merged_components = array();
			foreach ( $discovered_components as $component_properties ) {
				$hash = $component_properties['hash'];
				if ( ! isset( $form_data['file'][ $hash ] ) || ! isset( $form_data['key'][ $hash ] ) ) {
					continue;
				}
				// @todo Validation.

				$save_components = array(
					'file' => $form_data['file'][ $hash ],
					'key' => $form_data['key'][ $hash ],
					'source_id' => $form_data['source_id'][ $hash ],
					'source_name' => $form_data['source_name'][ $hash ],
					'path' => $form_data['path'][ $hash ],
					'modified' => $form_data['modified'][ $hash ] ?? '',

				);

				if ( isset( $form_data['enabled'][ $hash ] ) ) {
					$save_components['enabled'] = $form_data['enabled'][ $hash ];
				} else {
					$save_components['enabled'] = false;
				}
				if ( isset( $form_data['auto_sync'][ $hash ] ) ) {
					$save_components['auto_sync'] = (bool) $form_data['auto_sync'][ $hash ];
				} else {
					$save_components['auto_sync'] = false;
				}
				$save_components['file_path'] = $this->get_component_acf_file_path( $save_components );

				$merged_components[ $hash ] = array_merge( $save_components, $component_properties );

			}
			$this->componentService->set_stored_components( $merged_components );
		}
	}

	/**
	 * Export Components.
	 *
	 * @since 0.0.1
	 * @param array $export_options An array of export options.
	 *
	 * @see \AcfComponentManager\Admin::export().
	 *
	 * @return void
	 */
	public function export( array $export_options ): void {
		$components = $this->componentService->get_stored_components();

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename=managed-components.json' );
		header( 'Pragma: no-cache' );
		print json_encode( $components );
		exit;
	}

	/**
	 * Get Settings.
	 *
	 * @since 0.0.1
	 * @access private
	 *
	 * @return array $settings
	 */
	private function get_settings(): array {
		$settings = array();
		$stored_settings = get_option( SETTINGS_OPTION_NAME );
		if ( $stored_settings ) {
			$settings = $stored_settings;
		}
		return $settings;
	}

	/**
	 * Get theme components.
	 *
	 * @since 0.0.1
	 * @deprecated 0.0.7 Use get_discovered_components().
	 *
	 * @return array
	 *   An array of eligible theme components.
	 */
	public function get_theme_components(): array {
		$components = array();

		$settings = $this->get_settings();

		if ( ! isset( $settings['active_theme_directory'] ) ) {
			return $components;
		}

		$path_parts = array(
			$settings['active_theme_directory'],
			$settings['components_directory'],
		);
		$path_parts = implode( '/', $path_parts );

		foreach ( glob( "{$path_parts}/*/functions.php" ) as $functions_file ) {
			$component = get_file_data( $functions_file, array( 'Component' => 'Component' ) );
			// Get all eligible components.  Components should be in the components
			// theme directory and include the File Header 'Component'.
			if ( ! empty( $component['Component'] ) ) {
				$component_path = str_replace( $path_parts . '/', '', $functions_file );
				$component_path = str_replace( '/functions.php', '', $component_path );

				$components[] = array(
					'name' => $component['Component'],
					'path' => $component_path,
					'hash' => wp_hash( $component_path, '' ),
				);
			}
		}

		return $components;
	}

	/**
	 * Get ACF json files from components.
	 *
	 * @since 0.0.7
	 * @param array $component The ACF component.
	 *
	 * @return array An array of discovered ACF files.
	 */
	public function get_acf_files( array $component ): array {
		$acf_files = array();

		$path_pattern = $this->get_component_acf_file_path( $component, false );
		foreach ( glob( "{$path_pattern}*.json" ) as $files ) {

			$json = $this->acfService->get_acf_json( $files );
			if ( $json ) {

				// Synced theme components have a different structure.
				$key = $this->get_key_from_json( $json );
				if ( ! $key ) {
					$key = $this->get_key_from_json( reset( $json ) );
				}

				if ( $key ) {
					$file_name = str_replace( $path_pattern, '', $files );
					$acf_files[] = array(
						'file_name' => $file_name,
						'path' => $component['path'],
						'key' => $key,
						'modified' => $json['modified'] ?? null,
					);
				}
			}
		}
		return $acf_files;
	}

	/**
	 * Get ACF json files from components.
	 *
	 * @param array $component The ACF theme component.
	 *
	 * @return array
	 *   An array of discovered ACF files.
	 *
	 * @deprecated since 0.0.9
	 */
	public function get_theme_acf_files( array $component ): array {
		$acf_files = array();

		$path_pattern = $this->get_component_acf_file_path( $component, false );

		foreach ( glob( "{$path_pattern}*.json" ) as $files ) {

			$json = $this->acfService->get_acf_json( $files );
			if ( $json ) {

				// Synced theme components have a different structure.
				$key = $this->get_key_from_json( $json );
				if ( ! $key ) {
					$key = $this->get_key_from_json( reset( $json ) );
				}

				if ( $key ) {
					$file_name = str_replace( $path_pattern, '', $files );
					$acf_files[] = array(
						'file_name' => $file_name,
						'path' => $component['path'],
						'key' => $key,
						'modified' => $json['modified'] ?? null,
					);
				}
			}
		}
		return $acf_files;
	}



	/**
	 * Load components.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function load_components(): void {
		$components = $this->componentService->get_stored_components();

		if ( ! $this->is_dev_mode() ) {
			return;
		}

		if ( ! empty( $components ) ) {
			foreach ( $components as $component ) {
				if ( ! $component['enabled'] ) {
					continue;
				}
				if ( ! isset( $component['path'] ) ) {
					continue;
				}
				if ( ! isset( $component['file'] ) ) {
					continue;
				}

				if ( ! isset( $component['file_path'] ) ) {
					$file_path = $this->get_component_acf_file_path( $component );
				} else {
					$file_path = $component['file_path'];
				}

				try {
					$definition = $this->acfService->get_acf_json( $file_path );
					if ( $definition ) {
						acf_add_local_field_group( reset( $definition ) );
					}
				} catch ( \Exception $e ) {
					NoticeManager::add_notice( $e->getMessage() );
				}
			}
		}
	}



	/**
	 * Get key from JSON.
	 *
	 * @param array $json The JSON array.
	 *
	 * @return string|bool
	 *   The key if found.
	 */
	protected function get_key_from_json( array $json ): string|bool {
		if ( isset( $json['key'] ) ) {
			return $json['key'];
		}
		return false;
	}

	/**
	 * Map group properties to post.
	 *
	 * @param array $component The component array.
	 *
	 * @return array The post array.
	 */
	protected function map_group_properties_to_post( array $component ): array {
		$group_properties = array();
		if ( ! isset( $component['key'] ) ) {
			return $group_properties;
		}
		$group_properties['key'] = $component['key'];
		unset( $component['key'] );

		if ( ! isset( $component['title'] ) ) {
			return $group_properties;
		}

		$group_properties['title'] = $component['title'];
		$group_properties['post_name'] = sanitize_title( $component['title'] );
		unset( $component['title'] );

		$group_properties['post_type'] = 'acf-field-group';

		if ( isset( $component['menu_order'] ) ) {
			$group_properties['menu_order'] = $component['menu_order'];
			unset( $component['menu_order'] );
		}

		if ( isset( $component['active'] ) ) {
			if ( true == $component['active'] ) {
				$group_properties['post_status'] = 'publish';
			} else {
				$group_properties['post_status'] = 'acf-disabled';
			}
			unset( $component['active'] );
		}

		// Fields are mapped to a separate post.
		if ( isset( $component['fields'] ) ) {
			unset( $component['fields'] );
		}

		$group_properties['post_content'] = maybe_serialize( $component );

		return $group_properties;
	}

	/**
	 * Filter save path.
	 *
	 * @since 0.0.1
	 * @param array $paths  The ACF JSON save paths.
	 * @param mixed $post   The ACF post.
	 *
	 * @return array
	 *   The altered paths.
	 *
	 * @see acf/json/save_paths
	 */
	public function filter_save_paths( array $paths, mixed $post ): array {

		$acf_post = get_post( $post['ID'] );
		if ( ! $acf_post ) {
			return $paths;
		}
		$post_name = $acf_post->post_name;
		$post_type = $acf_post->post_type;

		if ( ! in_array( $post_type, array( 'acf-field-group', 'acf-taxonomy', 'acf-post-type', 'acf-ui-options-page' ), true ) ) {
			return $paths;
		}

		if ( $this->is_dev_mode() ) {
			$enabled_components = $this->componentService->get_enabled_components();
			if ( ! empty( $enabled_components ) ) {
				foreach ( $enabled_components as $hash => $component ) {
					$path_pattern = $this->get_component_acf_file_path( $component, false );
					$file_path = $component['file_path'];
					$definition = $this->acfService->get_acf_json( $file_path );
					if ( $definition ) {

						// Synced theme components have a different structure.
						$key = $this->get_key_from_json( $definition );
						if ( ! $key ) {
							$key = $this->get_key_from_json( reset( $definition ) );
						}
						if ( $key && $key == $post_name ) {
							$paths = array( $path_pattern );
						}
					}
				}
			}
		}
		return $paths;
	}

	/**
	 * Filter load paths.
	 *
	 * @since 0.0.1
	 * @param array $paths The paths.
	 *
	 * @return array
	 *   The paths.
	 *
	 * @see: acf/settings/load_json
	 */
	public function filter_load_paths( array $paths ): array {

		$enabled_components = $this->componentService->get_enabled_components();

		if ( ! empty( $enabled_components ) ) {
			foreach ( $enabled_components as $component ) {
				$path_pattern = $this->get_component_acf_file_path( $component, false );
				$paths[] = $path_pattern;
			}
		}
		return $paths;
	}

	/**
	 * Filter save file name.
	 *
	 * @since 0.0.1
	 * @param string $filename  The ACF file name.
	 * @param mixed  $post      The ACF post.
	 * @param string $load_path The ACF load path.
	 *
	 * @return string
	 *   The altered file name.
	 *
	 * @see acf/json/save_file
	 */
	public function filter_save_filename( string $filename, mixed $post, string $load_path ): string {
		$settings = $this->get_settings();

		$acf_post = get_post( $post['ID'] );
		if ( ! $acf_post ) {
			return $filename;
		}
		$post_name = $acf_post->post_name;
		$post_type = $acf_post->post_type;
		$acf_post_types = array(
			'acf-field-group',
			'acf-post-type',
			'acf-taxonomy',
			'acf-ui-options-page',
		);
		if ( ! in_array( $post_type, $acf_post_types ) ) {
			return $filename;
		}

		$enabled_components = $this->componentService->get_enabled_components();
		if ( ! empty( $enabled_components ) ) {
			foreach ( $enabled_components as $hash => $component ) {
				if ( ! isset( $component['file_path'] ) ) {
					$file_path = $this->get_component_acf_file_path( $component );
				} else {
					$file_path = $component['file_path'];
				}
				$definition = $this->acfService->get_acf_json( $file_path );

				if ( $definition ) {

					// Synced theme components have a different structure.
					$key = $this->get_key_from_json( $definition );
					if ( ! $key ) {
						$key = $this->get_key_from_json( reset( $definition ) );
					}
					if ( $key && $key == $post_name ) {
						$filename = $component['file'];
					}
				}
			}
		}
		return $filename;
	}

	/**
	 * Get component path.
	 *
	 * @since 0.0.1
	 *
	 * @param string $component_path The path to the component.
	 *
	 * @return string|bool
	 *   The full path to the component.
	 *
	 * @deprecated
	 */
	protected function get_component_path( string $component_path ): string|bool {
		$settings = $this->get_settings();

		if ( isset( $settings['active_theme_directory'] ) ) {
			return sprintf( $this->file_pattern, $settings['active_theme_directory'], $settings['components_directory'], $component_path, $settings['file_directory'] );
		}

		return false;
	}

	/**
	 * Get the Component ACF file path from component.
	 *
	 * @since 0.0.7
	 * @param array $component        The component.
	 * @param bool  $include_filename True include the file name.
	 *
	 * @return string|bool The path if it can be determined.
	 */
	protected function get_component_acf_file_path( array $component, bool $include_filename = true ): string|bool {
		$sources = $this->sourceService->get_sources();

		$sources = array_filter(
			$sources,
			function ( $source ) use ( $component ) {
				return $source['source_id'] === $component['source_id'];
			}
		);

		if ( ! empty( $sources ) ) {
			$source = reset( $sources );
			$file_parts = array(
				$component['path'],
				$source['file_directory'],
			);

			if ( $include_filename ) {
				$file_parts[] = $component['file'];
				return implode( '/', $file_parts );
			}
			return trailingslashit( implode( '/', $file_parts ) );
		}
		return false;
	}

	/**
	 * Deactivate component source.
	 *
	 * @since 0.0.7
	 * @param string $source_id The source id to deactivate.
	 *
	 * @return void
	 */
	public function deactivate_component_source( string $source_id ): void {
		$stored_components = $this->componentService->get_stored_components();
		foreach ( $stored_components as $index => $component ) {
			if ( $component['source_id'] === $source_id ) {
				$name = $component['name'];
				unset( $stored_components[ $index ] );
				$this->noticeManager->add_notice( 'Deactivated component ' . $name );
			}
		}
		$this->componentService->set_stored_components( $stored_components );
	}

	/**
	 * Syncs components to the database.
	 *
	 * @return void
	 */
	public function sync_components(): void {
		$sync_components = $this->componentService->get_auto_sync_components();

		$this->syncService->sync_components( $sync_components );
	}

	/**
	 * Checks if dev mode is enabled.
	 *
	 * @return bool
	 *   True if we are in dev_mode.
	 */
	public function is_dev_mode(): bool {
		$settings = $this->get_settings();
		if ( isset( $settings['dev_mode'] ) && $settings['dev_mode'] ) {
			return true;
		}
		return false;
	}
}
