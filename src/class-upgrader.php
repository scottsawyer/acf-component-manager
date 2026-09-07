<?php
/**
 * The plugin upgrader.
 *
 * @package acf-component-manager
 *
 * @since 0.0.8
 */

namespace AcfComponentManager;

// If this file is called directly, short.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use AcfComponentManager\Controller\ComponentManager;
use AcfComponentManager\Controller\SettingsManager;
use AcfComponentManager\Service\ComponentService;
use AcfComponentManager\Service\SourceService;

/**
 * Contains Upgrader class.
 */
class Upgrader {

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
	 * AcfComponentManager\NoticeManager definition.
	 *
	 * @since 0.0.7
	 * @var \AcfComponentManager\NoticeManager
	 */
	protected NoticeManager $noticeManager;

	/**
	 * AcfComponentManager\Controller\ComponentManager definition.
	 *
	 * @since 0.0.7
	 * @var \AcfComponentManager\Controller\ComponentManager
	 */
	protected ComponentManager $componentManager;

	/**
	 * AcfComponentManager\Controller\SettingsManager definition.
	 *
	 * @since 0.0.7
	 * @var \AcfComponentManager\Controller\SettingsManager
	 */
	protected SettingsManager $settingsManager;

	/**
	 * Array of upgrade versions and functions.
	 *
	 * @var array
	 */
	protected $upgrade_versions = array(
		'0.0.7' => 'upgrade_007',
	);

	/**
	 * Constructs a new Upgrader object.
	 *
	 * @since 0.0.7
	 */
	public function __construct() {
		$this->load_dependencies();
	}

	/**
	 * Load dependencies.
	 *
	 * @since 0.0.7
	 * @access private
	 */
	private function load_dependencies() {

		$this->noticeManager = new NoticeManager();
		$this->componentService = new ComponentService();
		$this->settingsManager = new SettingsManager();
		$this->sourceService = new SourceService();
	}

	/**
	 * Runs the upgrade complete hook.
	 *
	 * @since 0.0.7
	 * @param array $upgrader_object The upgrader object.
	 * @param array $options         The options array.
	 */
	public static function upgrade_complete( array $upgrader_object, array $options ) {
		// The path to our plugin's main file.
		$our_plugin = plugin_basename( __FILE__ );
		// If an update has taken place and the updated type is plugins and the plugins element exists.
		if ( 'update' == $options['action'] && 'plugin' == $options['type'] && isset( $options['plugins'] ) ) {
			// Iterate through the plugins being updated and check if ours is present.
			foreach ( $options['plugins'] as $plugin ) {
				if ( $plugin == $our_plugin ) {
					// Set a transient to record that our plugin has just been updated.
					set_transient( 'acf_updated', 1 );
				}
			}
		}
	}

	/**
	 * Check upgrades.
	 *
	 * @since 0.0.7
	 *
	 * @return array An array of upgrades.
	 */
	public function get_upgrades(): array {
		$settings = $this->settingsManager->get_settings();
		$available_upgrades = array();
		if ( isset( $settings['version'] ) && $settings['version'] > ACF_COMPONENT_MANAGER_VERSION ) {
			foreach ( $this->upgrade_versions as $version => $callback ) {
				if ( version_compare( $version, $settings['version'], '<' ) ) {
					$available_upgrades[ $version ] = $callback;
				}
			}
		}
		return $available_upgrades;
	}

	/**
	 * Run available upgrades.
	 *
	 * @since 0.0.7
	 * @param array $available_upgrades An array of available upgrades.
	 */
	public function run_upgrades( array $available_upgrades ) {
		$fails = array();
		$successes = array();
		foreach ( $available_upgrades as $version => $callback ) {
			$success = call_user_func( array( $this, $callback ) );
			if ( ! $success ) {
				$fails[] = $version;
			} else {
				$successes[] = $version;
			}
		}
		if ( ! empty( $fails ) ) {
			foreach ( $fails as $fail ) {
				$this->noticeManager->add_notice( 'Upgrade failed. ' . $fail, 'error' );
			}
		}
		if ( ! empty( $successes ) ) {
			foreach ( $successes as $success ) {
				$this->noticeManager->add_notice( 'Upgrade completed. ' . $success, 'success' );
			}
		}
	}

	/**
	 * Upgrade 007.
	 *
	 * @since 0.0.7
	 *
	 * @return bool If successful.
	 */
	public function upgrade_007(): bool {
		$settings = $this->settingsManager->get_settings();
		$new_source = array();
		$parent_theme_directory = get_template_directory();
		$child_theme_directory = get_stylesheet_directory();

		if ( isset( $settings['active_theme_directory'] ) ) {
			if ( $settings['active_theme_directory'] === $parent_theme_directory ) {
				$new_source = array(
					'source_id' => uniqid(),
					'source_type' => 'parent_theme',
					'source_path' => $parent_theme_directory,
					'source_name' => wp_get_theme( get_template() )->get( 'Name' ),
					'file_directory' => $settings['file_directory'] ?? '',
					'components_directory' => $settings['components_directory'] ?? '',
				);
			} else {
				$new_source = array(
					'source_id' => uniqid(),
					'source_type' => 'child_theme',
					'source_path' => $child_theme_directory,
					'source_name' => wp_get_theme( get_stylesheet() )->get( 'Name' ),
					'file_directory' => $settings['file_directory'] ?? '',
					'components_directory' => $settings['components_directory'] ?? '',
				);
			}
			if ( ! empty( $new_source ) ) {
				$this->sourceService->set_sources( array( $new_source['source_id'] => $new_source ) );
				// Get any stored components, add the new source key.
				$components = $this->componentService->get_stored_components();

				if ( ! empty( $components ) ) {
					foreach ( $components as $component ) {
						$component['source_id'] = $new_source['source_id'];
						$component['source_name'] = $new_source['source_name'];
					}

					$this->componentService->set_stored_components( $components );
				}
			}
		}
		$new_settings = array(
			'version' => ACF_COMPONENT_MANAGER_VERSION,
			'dev_mode' => $settings['dev_mode'] ?? false,
		);
		return update_option( SETTINGS_OPTION_NAME, $new_settings );
	}
}
