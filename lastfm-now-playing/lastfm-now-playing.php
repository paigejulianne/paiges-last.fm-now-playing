<?php
/**
 * Plugin Name: Paige's Last.FM Now Playing
 * Plugin URI: https://github.com/paigejulianne/paiges-last.fm-now-playing
 * Description: Display your recently played tracks from Last.fm with a beautiful Spotify-inspired design. Includes a Gutenberg block and classic widget.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Paige Julianne Sullivan
 * Author URI: https://paigejulianne.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lastfm-now-playing
 * Domain Path: /languages
 * Update URI: https://github.com/paigejulianne/paiges-last.fm-now-playing
 *
 * @package LastFM_Now_Playing
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'LASTFM_NP_VERSION', '1.0.0' );
define( 'LASTFM_NP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LASTFM_NP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LASTFM_NP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 */
final class LastFM_Now_Playing {

	/**
	 * Single instance of the class.
	 *
	 * @var LastFM_Now_Playing
	 */
	private static $instance = null;

	/**
	 * Get single instance of the class.
	 *
	 * @return LastFM_Now_Playing
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		require_once LASTFM_NP_PLUGIN_DIR . 'includes/class-lastfm-api.php';
		require_once LASTFM_NP_PLUGIN_DIR . 'includes/class-lastfm-settings.php';
		require_once LASTFM_NP_PLUGIN_DIR . 'includes/class-lastfm-widget.php';
		require_once LASTFM_NP_PLUGIN_DIR . 'includes/class-lastfm-block.php';
		require_once LASTFM_NP_PLUGIN_DIR . 'includes/class-lastfm-renderer.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );

		// Add settings link to plugins page.
		add_filter( 'plugin_action_links_' . LASTFM_NP_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );

		// Add row meta links (View details, Donate).
		add_filter( 'plugin_row_meta', array( $this, 'add_row_meta_links' ), 10, 2 );

		// Enable auto-updates for this plugin.
		add_filter( 'auto_update_plugin', array( $this, 'enable_auto_update' ), 10, 2 );

		// Initialize components.
		LastFM_Settings::get_instance();
		LastFM_Block::get_instance();
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'lastfm-now-playing',
			false,
			dirname( LASTFM_NP_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_style(
			'lastfm-now-playing',
			LASTFM_NP_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			LASTFM_NP_VERSION
		);
	}

	/**
	 * Register the widget.
	 */
	public function register_widget() {
		register_widget( 'LastFM_Widget' );
	}

	/**
	 * Add settings link to plugins page.
	 *
	 * @param array $links Existing links.
	 * @return array Modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=lastfm-now-playing' ) ),
			esc_html__( 'Settings', 'lastfm-now-playing' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add row meta links to plugins page.
	 *
	 * @param array  $links Existing meta links.
	 * @param string $file  Plugin file path.
	 * @return array Modified meta links.
	 */
	public function add_row_meta_links( $links, $file ) {
		if ( LASTFM_NP_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( 'https://github.com/paigejulianne/paiges-last.fm-now-playing' ),
			esc_html__( 'View details', 'lastfm-now-playing' )
		);

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( 'https://www.paypal.com/donate/?hosted_button_id=3X8QMH7RTRTGN' ),
			esc_html__( 'Donate', 'lastfm-now-playing' )
		);

		return $links;
	}

	/**
	 * Enable auto-updates for this plugin.
	 *
	 * @param bool|null $update Whether to update.
	 * @param object    $item   The update offer.
	 * @return bool|null Whether to update.
	 */
	public function enable_auto_update( $update, $item ) {
		if ( isset( $item->plugin ) && LASTFM_NP_PLUGIN_BASENAME === $item->plugin ) {
			return true;
		}
		return $update;
	}

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		// Set default options.
		$defaults = array(
			'api_key'           => '',
			'username'          => '',
			'default_count'     => 5,
			'default_theme'     => 'dark',
			'show_album'        => true,
			'show_duration'     => true,
			'cache_duration'    => 300,
		);

		if ( false === get_option( 'lastfm_now_playing_settings' ) ) {
			add_option( 'lastfm_now_playing_settings', $defaults );
		}
	}

	/**
	 * Plugin deactivation.
	 */
	public static function deactivate() {
		// Clear any transients.
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'%_transient_lastfm_np_%'
			)
		);
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'%_transient_timeout_lastfm_np_%'
			)
		);
	}
}

// Activation/deactivation hooks.
register_activation_hook( __FILE__, array( 'LastFM_Now_Playing', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LastFM_Now_Playing', 'deactivate' ) );

/**
 * Initialize the plugin.
 *
 * @return LastFM_Now_Playing
 */
function lastfm_now_playing() {
	return LastFM_Now_Playing::get_instance();
}

// Start the plugin.
lastfm_now_playing();
