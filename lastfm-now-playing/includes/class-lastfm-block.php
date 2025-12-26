<?php
/**
 * Gutenberg block registration and handling.
 *
 * @package LastFM_Now_Playing
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block class.
 */
class LastFM_Block {

	/**
	 * Single instance of the class.
	 *
	 * @var LastFM_Block
	 */
	private static $instance = null;

	/**
	 * Get single instance of the class.
	 *
	 * @return LastFM_Block
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
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_shortcode( 'lastfm_now_playing', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Register the block.
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'lastfm-now-playing/recent-tracks',
			array(
				'api_version'     => 2,
				'editor_script'   => 'lastfm-now-playing-block-editor',
				'editor_style'    => 'lastfm-now-playing-block-editor-style',
				'style'           => 'lastfm-now-playing-frontend',
				'render_callback' => array( $this, 'render_block' ),
				'attributes'      => array(
					'count'        => array(
						'type'    => 'number',
						'default' => 0, // 0 means use default from settings.
					),
					'theme'        => array(
						'type'    => 'string',
						'default' => '', // Empty means use default from settings.
					),
					'showAlbum'    => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showDuration' => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);
	}

	/**
	 * Enqueue block editor assets.
	 */
	public function enqueue_editor_assets() {
		$settings = LastFM_Settings::get_settings();

		wp_enqueue_script(
			'lastfm-now-playing-block-editor',
			LASTFM_NP_PLUGIN_URL . 'blocks/recent-tracks/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			LASTFM_NP_VERSION,
			true
		);

		wp_localize_script(
			'lastfm-now-playing-block-editor',
			'lastfmNowPlayingSettings',
			array(
				'defaultCount'    => $settings['default_count'],
				'defaultTheme'    => $settings['default_theme'],
				'showAlbum'       => $settings['show_album'],
				'showDuration'    => $settings['show_duration'],
				'isConfigured'    => ! empty( $settings['api_key'] ) && ! empty( $settings['username'] ),
				'settingsUrl'     => admin_url( 'options-general.php?page=lastfm-now-playing' ),
			)
		);

		wp_set_script_translations( 'lastfm-now-playing-block-editor', 'lastfm-now-playing' );

		wp_enqueue_style(
			'lastfm-now-playing-block-editor-style',
			LASTFM_NP_PLUGIN_URL . 'assets/css/block-editor.css',
			array( 'lastfm-now-playing-frontend' ),
			LASTFM_NP_VERSION
		);

		wp_enqueue_style(
			'lastfm-now-playing-frontend',
			LASTFM_NP_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			LASTFM_NP_VERSION
		);
	}

	/**
	 * Render the block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Block HTML.
	 */
	public function render_block( $attributes ) {
		$settings = LastFM_Settings::get_settings();

		$args = array(
			'count'         => ! empty( $attributes['count'] ) ? (int) $attributes['count'] : $settings['default_count'],
			'theme'         => ! empty( $attributes['theme'] ) ? $attributes['theme'] : $settings['default_theme'],
			'show_album'    => isset( $attributes['showAlbum'] ) ? (bool) $attributes['showAlbum'] : $settings['show_album'],
			'show_duration' => isset( $attributes['showDuration'] ) ? (bool) $attributes['showDuration'] : $settings['show_duration'],
			'class'         => 'wp-block-lastfm-now-playing-recent-tracks',
		);

		return LastFM_Renderer::render( $args );
	}

	/**
	 * Render shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode HTML.
	 */
	public function render_shortcode( $atts ) {
		$settings = LastFM_Settings::get_settings();

		$atts = shortcode_atts(
			array(
				'count'         => $settings['default_count'],
				'theme'         => $settings['default_theme'],
				'show_album'    => $settings['show_album'] ? 'true' : 'false',
				'show_duration' => $settings['show_duration'] ? 'true' : 'false',
			),
			$atts,
			'lastfm_now_playing'
		);

		$args = array(
			'count'         => (int) $atts['count'],
			'theme'         => $atts['theme'],
			'show_album'    => filter_var( $atts['show_album'], FILTER_VALIDATE_BOOLEAN ),
			'show_duration' => filter_var( $atts['show_duration'], FILTER_VALIDATE_BOOLEAN ),
		);

		return LastFM_Renderer::render( $args );
	}
}
