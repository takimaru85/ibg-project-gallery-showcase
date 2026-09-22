<?php
/**
 * Plugin Name:       WordPress Project Gallery
 * Plugin URI:        https://example.com/plugins/wordpress-project-gallery
 * Description:       A lightweight, native WordPress plugin for showcasing portfolio projects via the [project_gallery] shortcode. No page builder or block editor required.
 * Version:           1.1.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Ian Olden, IB Golden
 * Author URI:        https://ibgolden.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wordpress-project-gallery
 * Domain Path:       /languages
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'WPG_VERSION', '1.1.1' );
define( 'WPG_PLUGIN_FILE', __FILE__ );
define( 'WPG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Core plugin bootstrap class.
 *
 * Loads all plugin components and wires activation/deactivation hooks.
 */
final class WPG_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var WPG_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return WPG_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Load required files.
	 */
	private function includes() {
		require_once WPG_PLUGIN_DIR . 'includes/class-project-post-type.php';
		require_once WPG_PLUGIN_DIR . 'includes/class-project-taxonomy.php';
		require_once WPG_PLUGIN_DIR . 'includes/class-project-meta.php';
		require_once WPG_PLUGIN_DIR . 'includes/class-project-gallery.php';
		require_once WPG_PLUGIN_DIR . 'includes/class-project-shortcode.php';
		require_once WPG_PLUGIN_DIR . 'includes/class-project-ajax.php';
		require_once WPG_PLUGIN_DIR . 'includes/class-project-assets.php';
		require_once WPG_PLUGIN_DIR . 'includes/class-project-settings.php';
		require_once WPG_PLUGIN_DIR . 'includes/class-project-admin.php';
		require_once WPG_PLUGIN_DIR . 'includes/class-project-template.php';
	}

	/**
	 * Register hooks for activation, deactivation and component init.
	 */
	private function init_hooks() {
		register_activation_hook( WPG_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( WPG_PLUGIN_FILE, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_components' ), 0 );
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 999 );
		add_action( 'init', array( $this, 'maybe_flush_on_version_change' ), 999 );
	}

	/**
	 * Load plugin translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wordpress-project-gallery', false, dirname( WPG_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Instantiate all plugin components.
	 */
	public function register_components() {
		new WPG_Post_Type();
		new WPG_Taxonomy();
		new WPG_Meta();
		new WPG_Shortcode();
		new WPG_Ajax();
		new WPG_Assets();
		new WPG_Settings();
		new WPG_Admin();
		new WPG_Template();
	}

	/**
	 * Plugin activation callback.
	 *
	 * Registers the post type/taxonomy so rewrite rules exist, inserts
	 * default taxonomy terms, then flushes rewrite rules once.
	 */
	public function activate() {
		$post_type = new WPG_Post_Type();
		$post_type->register_post_type();

		$taxonomy = new WPG_Taxonomy();
		$taxonomy->register_taxonomy();
		$taxonomy->maybe_create_default_terms();

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation callback.
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Flush rewrite rules a single time after the project slug setting changes.
	 *
	 * This never runs on a normal request; it only fires once when the
	 * `wpg_needs_rewrite_flush` flag has been set (e.g. after saving settings).
	 */
	public function maybe_flush_rewrite_rules() {
		if ( '1' === get_option( 'wpg_needs_rewrite_flush' ) ) {
			flush_rewrite_rules();
			update_option( 'wpg_needs_rewrite_flush', '0' );
		}
	}

	/**
	 * Flush rewrite rules once whenever the plugin version changes (e.g.
	 * after an update that alters post type registration args such as
	 * `has_archive`). Runs at most once per version, never on every request.
	 */
	public function maybe_flush_on_version_change() {
		if ( get_option( 'wpg_version' ) !== WPG_VERSION ) {
			flush_rewrite_rules();
			update_option( 'wpg_version', WPG_VERSION );
		}
	}
}

WPG_Plugin::instance();
