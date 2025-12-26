<?php
/**
 * Plugin Name: Paige's Last.FM Now Playing
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

/*
 * The following filters run regardless of plugin activation state
 * to ensure View details and Donate links always appear.
 */

/**
 * Add row meta links to plugins page (runs even when plugin is deactivated).
 *
 * @param array  $links Existing meta links.
 * @param string $file  Plugin file path.
 * @return array Modified meta links.
 */
function lastfm_np_add_row_meta_links( $links, $file ) {
	if ( plugin_basename( __FILE__ ) !== $file ) {
		return $links;
	}

	$links[] = sprintf(
		'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s">%s</a>',
		esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=lastfm-now-playing&TB_iframe=true&width=600&height=550' ) ),
		esc_attr__( 'More information about this plugin', 'lastfm-now-playing' ),
		esc_html__( 'View details', 'lastfm-now-playing' )
	);

	$links[] = sprintf(
		'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
		esc_url( 'https://www.paypal.com/donate/?hosted_button_id=3X8QMH7RTRTGN' ),
		esc_html__( 'Donate', 'lastfm-now-playing' )
	);

	return $links;
}
add_filter( 'plugin_row_meta', 'lastfm_np_add_row_meta_links', 10, 2 );

/**
 * Provide plugin information for the details popup (runs even when plugin is deactivated).
 *
 * @param false|object|array $result The result object or array.
 * @param string             $action The type of information being requested.
 * @param object             $args   Plugin API arguments.
 * @return false|object Plugin info or false.
 */
function lastfm_np_plugin_info( $result, $action, $args ) {
	if ( 'plugin_information' !== $action ) {
		return $result;
	}

	if ( ! isset( $args->slug ) || 'lastfm-now-playing' !== $args->slug ) {
		return $result;
	}

	$plugin_info = new stdClass();

	$plugin_info->name           = "Paige's Last.FM Now Playing";
	$plugin_info->slug           = 'lastfm-now-playing';
	$plugin_info->version        = '1.0.0';
	$plugin_info->author         = '<a href="https://paigejulianne.com/">Paige Julianne Sullivan</a>';
	$plugin_info->author_profile = 'https://paigejulianne.com/';
	$plugin_info->requires       = '5.8';
	$plugin_info->tested         = '6.9';
	$plugin_info->requires_php   = '7.4';
	$plugin_info->homepage       = 'https://github.com/paigejulianne/paiges-last.fm-now-playing';
	$plugin_info->donate_link    = 'https://www.paypal.com/donate/?hosted_button_id=3X8QMH7RTRTGN';

	$plugin_info->sections = array(
		'description'  => '<p>Display your recently played tracks from Last.fm with a beautiful Spotify-inspired design.</p>
			<h4>Features</h4>
			<ul>
				<li><strong>Gutenberg Block</strong> - Add your recent tracks to any post or page</li>
				<li><strong>Classic Widget</strong> - Perfect for sidebars and widget areas</li>
				<li><strong>Shortcode Support</strong> - Use <code>[lastfm_now_playing]</code> anywhere</li>
				<li><strong>Spotify-Inspired Themes</strong> - Light, Dark, or Transparent</li>
				<li><strong>Now Playing Indicator</strong> - Animated indicator for current track</li>
				<li><strong>User Profile Header</strong> - Shows Last.fm avatar and profile link</li>
				<li><strong>Configurable Display</strong> - Track count, album names, durations</li>
				<li><strong>Built-in Caching</strong> - Reduces API calls</li>
			</ul>',
		'installation' => '<ol>
				<li>Upload the plugin to <code>/wp-content/plugins/</code></li>
				<li>Activate through the Plugins menu</li>
				<li>Go to Settings → Paige\'s Last.FM Now Playing</li>
				<li>Enter your Last.fm API key and username</li>
				<li>Add the block, widget, or shortcode to your site</li>
			</ol>
			<p><strong>Get your API key:</strong> <a href="https://www.last.fm/api/account/create" target="_blank">Last.fm API</a></p>',
		'changelog'    => '<h4>1.0.0</h4>
			<ul>
				<li>Initial release</li>
				<li>Gutenberg block with per-instance configuration</li>
				<li>Classic widget support</li>
				<li>Shortcode support</li>
				<li>Three Spotify-inspired themes</li>
				<li>Now Playing animation indicator</li>
				<li>Built-in caching</li>
			</ul>',
	);

	$plugin_info->banners = array(
		'low'  => '',
		'high' => '',
	);

	return $plugin_info;
}
add_filter( 'plugins_api', 'lastfm_np_plugin_info', 20, 3 );
