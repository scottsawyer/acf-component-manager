<?php
/**
 * Core plugin class.
 *
 * @package acf-component-manager
 *
 * @since 0.0.1
 */

declare( strict_types=1 );

namespace AcfComponentManager;

// If this file is called directly, short.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use AcfComponentManager\Controller\ComponentManager;
use AcfComponentManager\Controller\DashboardManager;
use AcfComponentManager\Controller\SettingsManager;
use AcfComponentManager\Controller\SourceManager;
use AcfComponentManager\Controller\ToolsManager;
use AcfComponentManager\NoticeManager;
use AcfComponentManager\Upgrader;

/**
 * Main plugin class.
 */
class AcfComponentManager {

	/**
	 * Plugin name
	 *
	 * @since 0.0.1
	 * @var string $plugin_name
	 */
	protected string $plugin_name;

	/**
	 * Version
	 *
	 * @since 0.0.1
	 * @var string $version
	 */
	protected string $version;

	/**
	 * Plugin file name
	 *
	 * @since 0.0.1
	 * @var string $plugin_file_name
	 */
	protected string $plugin_file_name;

	/**
	 * RateCalculator\Admin definition.
	 *
	 * @var \AcfComponentManager\Admin
	 * @since 0.0.1
	 * @access protected
	 */
	protected Admin $admin;

	/**
	 * AcfComponentManager\Loader definition.
	 *
	 * @var \AcfComponentManager\Loader
	 * @since 0.0.1
	 * @access protected
	 */
	protected Loader $loader;

	/**
	 * AcfComponentManager\Controller\ComponentManager definition.
	 *
	 * @var \AcfComponentManager\Controller\ComponentManager
	 *
	 * @since 0.0.1
	 */
	protected ComponentManager $componentManager;

	/**
	 * AcfComponentManager\Controller\SettingsManager definition.
	 *
	 * @var \AcfComponentManager\Controller\SettingsManager
	 *
	 * @since 0.0.1
	 */
	protected SettingsManager $settingsManager;

	/**
	 * AcfComponentManager\Controller\SourceManager definition.
	 *
	 * @var \AcfComponentManager\Controller\SourceManager
	 *
	 * @since 0.0.7
	 */
	protected SourceManager $sourceManager;

	/**
	 * AcfComponentManager\NoticeManager definition.
	 *
	 * @var \AcfComponentManager\NoticeManager
	 */
	protected NoticeManager $noticeManager;

	/**
	 * AcfComponentManager\Controller\DashboardManager definition.
	 *
	 * @var \AcfComponentManager\Controller\DashboardManager
	 */
	protected DashboardManager $dashboardManager;

	/**
	 * AcfComponentManager\Controller\ToolsManager definition.
	 *
	 * @var \AcfComponentManager\Controller\ToolsManager
	 */
	protected ToolsManager $toolsManager;

	/**
	 * AcfComponentManager\Upgrader definition.
	 *
	 * @var \AcfComponentManager\Upgrader
	 */
	protected Upgrader $upgrader;

	/**
	 * Options.
	 *
	 * @since 0.0.1
	 * @access protected
	 * @var array $options
	 */
	protected array $options;

	/**
	 * Constructs a new Acf_Component_Manager object.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		if ( defined( 'ACF_COMPONENT_MANAGER_VERSION' ) ) {
			$this->version = ACF_COMPONENT_MANAGER_VERSION;
		} else {
			$this->version = '0.0.1';
		}

		$this->plugin_name = 'acf-component-manager';

		$this->options = $this->get_options();

		$this->load_dependencies();
		$this->define_admin_hooks();

		// Check for updates.
		$this->check_updates();
	}

	/**
	 * Load Dependencies.
	 *
	 * @since 0.0.1
	 * @access private
	 *
	 * @return void
	 */
	private function load_dependencies(): void {

		$this->admin = new Admin();
		$this->loader = new Loader();
		$this->componentManager = new ComponentManager();
		$this->settingsManager = new SettingsManager();
		$this->sourceManager = new SourceManager();
		$this->dashboardManager = new DashboardManager();
		$this->toolsManager = new ToolsManager();
		$this->noticeManager = new NoticeManager();
		$this->upgrader = new Upgrader();
	}

	/**
	 * Check for updates.
	 *
	 * @since 0.0.7
	 * @access protected
	 *
	 * @return void
	 */
	protected function check_updates(): void {
		$available_updates = $this->upgrader->get_upgrades();
		if ( ! empty( $available_updates ) ) {
			$this->upgrader->run_upgrades( $available_updates );
		}
	}

	/**
	 * Get 'Options'.
	 *
	 * @since 0.0.1
	 *
	 * @return array The plugin options.
	 */
	public function get_options(): array {

		$options = array();
		return $options;
	}

	/**
	 * Define admin hooks.
	 *
	 * @since 0.0.1
	 * @access private
	 * @return void
	 */
	private function define_admin_hooks(): void {

		$plugin_admin = $this->admin;
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );

		$component_manager = $this->componentManager;
		$this->loader->add_action( 'acf_component_manager_render_page_manage_components', $component_manager, 'render_page', 10, 2 );
		$this->loader->add_action( 'acf_component_manager_dashboard', $component_manager, 'dashboard', 10 );
		$this->loader->add_action( 'acf_component_manager_tools', $component_manager, 'tools', 10, 2 );
		$this->loader->add_action( 'acf_component_manager_save_manage_components', $component_manager, 'save', 10, 1 );
		$this->loader->add_action( 'acf_component_manager_deactivate_component_source', $component_manager, 'deactivate_component_source', 10, 1 );
		$this->loader->add_action( 'acf_component_manager_export_manage_components', $component_manager, 'export', 10, 1 );
		$this->loader->add_filter( 'acf_component_manager_tabs', $component_manager, 'add_menu_tab', 15 );
		$this->loader->add_filter( 'acf/json/save_file_name', $component_manager, 'filter_save_filename', 10, 3 );
		$this->loader->add_filter( 'acf/json/save_paths', $component_manager, 'filter_save_paths', 10, 2 );
		$this->loader->add_filter( 'acf/settings/load_json', $component_manager, 'filter_load_paths', 10, 1 );
		$this->loader->add_filter( 'acf/settings/show_admin', $component_manager, 'is_dev_mode' );

		$dashboard_manager = $this->dashboardManager;
		$this->loader->add_filter( 'acf_component_manager_render_page_dashboard', $dashboard_manager, 'render_page' );
		$this->loader->add_filter( 'acf_component_manager_tabs', $dashboard_manager, 'add_menu_tab', 5 );

		$tools_manager = $this->toolsManager;
		$this->loader->add_action( 'acf_component_manager_render_page_tools', $tools_manager, 'render_page', 10, 2 );
		$this->loader->add_filter( 'acf_component_manager_tabs', $tools_manager, 'add_menu_tab', 15 );

		$settings_manager = $this->settingsManager;
		$this->loader->add_action( 'acf_component_manager_render_page_manage_settings', $settings_manager, 'render_page', 10, 2 );
		$this->loader->add_action( 'acf_component_manager_dashboard', $settings_manager, 'dashboard', 5 );
		$this->loader->add_action( 'acf_component_manager_save_manage_settings', $settings_manager, 'save', 10, 1 );
		$this->loader->add_filter( 'acf_component_manager_tabs', $settings_manager, 'add_menu_tab', 10 );

		$source_manager = $this->sourceManager;
		$this->loader->add_action( 'acf_component_manager_render_page_manage_sources', $source_manager, 'render_page', 10, 2 );
		$this->loader->add_action( 'acf_component_manager_dashboard', $source_manager, 'dashboard', 5 );
		$this->loader->add_filter( 'acf_component_manager_tabs', $source_manager, 'add_menu_tab', 10 );
		$this->loader->add_action( 'acf_component_manager_save_manage_sources', $source_manager, 'save', 10, 1 );
		$this->loader->add_action( 'acf_component_manager_delete_manage_sources', $source_manager, 'delete', 10 );

		$notice_manager = $this->noticeManager;
		$this->loader->add_action( 'admin_init', $notice_manager, 'dismiss_notice' );
		$this->loader->add_action( 'admin_notices', $notice_manager, 'show_notices' );
	}

	/**
	 * Run the loader to execute all WordPress hooks.
	 *
	 * @since    0.0.1
	 * @return void
	 */
	public function run(): void {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     0.0.1
	 *
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     0.0.1
	 *
	 * @return   \AcfComponentManager\Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader(): Loader {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     0.0.1
	 *
	 * @return    string    The version number of the plugin.
	 */
	public function get_version(): string {
		return $this->version;
	}
}
