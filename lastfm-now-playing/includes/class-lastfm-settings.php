<?php
/**
 * Settings page for Last.fm Now Playing plugin.
 *
 * @package LastFM_Now_Playing
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 */
class LastFM_Settings {

	/**
	 * Single instance of the class.
	 *
	 * @var LastFM_Settings
	 */
	private static $instance = null;

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private $option_name = 'lastfm_now_playing_settings';

	/**
	 * Get single instance of the class.
	 *
	 * @return LastFM_Settings
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
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add settings page to admin menu.
	 */
	public function add_settings_page() {
		add_options_page(
			__( "Paige's Last.FM Now Playing Settings", 'lastfm-now-playing' ),
			__( "Paige's Last.FM Now Playing", 'lastfm-now-playing' ),
			'manage_options',
			'lastfm-now-playing',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_lastfm-now-playing' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'lastfm-now-playing-admin',
			LASTFM_NP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			LASTFM_NP_VERSION
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			'lastfm_now_playing_settings_group',
			$this->option_name,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_defaults(),
			)
		);

		// API Settings Section.
		add_settings_section(
			'lastfm_api_section',
			__( 'Last.fm API Settings', 'lastfm-now-playing' ),
			array( $this, 'render_api_section' ),
			'lastfm-now-playing'
		);

		add_settings_field(
			'api_key',
			__( 'API Key', 'lastfm-now-playing' ),
			array( $this, 'render_api_key_field' ),
			'lastfm-now-playing',
			'lastfm_api_section'
		);

		add_settings_field(
			'username',
			__( 'Last.fm Username', 'lastfm-now-playing' ),
			array( $this, 'render_username_field' ),
			'lastfm-now-playing',
			'lastfm_api_section'
		);

		// Display Defaults Section.
		add_settings_section(
			'lastfm_defaults_section',
			__( 'Default Display Settings', 'lastfm-now-playing' ),
			array( $this, 'render_defaults_section' ),
			'lastfm-now-playing'
		);

		add_settings_field(
			'default_count',
			__( 'Number of Tracks', 'lastfm-now-playing' ),
			array( $this, 'render_count_field' ),
			'lastfm-now-playing',
			'lastfm_defaults_section'
		);

		add_settings_field(
			'default_theme',
			__( 'Default Theme', 'lastfm-now-playing' ),
			array( $this, 'render_theme_field' ),
			'lastfm-now-playing',
			'lastfm_defaults_section'
		);

		add_settings_field(
			'show_album',
			__( 'Show Album Name', 'lastfm-now-playing' ),
			array( $this, 'render_show_album_field' ),
			'lastfm-now-playing',
			'lastfm_defaults_section'
		);

		add_settings_field(
			'show_duration',
			__( 'Show Track Duration', 'lastfm-now-playing' ),
			array( $this, 'render_show_duration_field' ),
			'lastfm-now-playing',
			'lastfm_defaults_section'
		);

		// Cache Settings Section.
		add_settings_section(
			'lastfm_cache_section',
			__( 'Cache Settings', 'lastfm-now-playing' ),
			array( $this, 'render_cache_section' ),
			'lastfm-now-playing'
		);

		add_settings_field(
			'cache_duration',
			__( 'Cache Duration (seconds)', 'lastfm-now-playing' ),
			array( $this, 'render_cache_field' ),
			'lastfm-now-playing',
			'lastfm_cache_section'
		);
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public function get_defaults() {
		return array(
			'api_key'        => '',
			'username'       => '',
			'default_count'  => 5,
			'default_theme'  => 'dark',
			'show_album'     => true,
			'show_duration'  => true,
			'cache_duration' => 300,
		);
	}

	/**
	 * Get settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = array(
			'api_key'        => '',
			'username'       => '',
			'default_count'  => 5,
			'default_theme'  => 'dark',
			'show_album'     => true,
			'show_duration'  => true,
			'cache_duration' => 300,
		);
		return wp_parse_args( get_option( 'lastfm_now_playing_settings', array() ), $defaults );
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Settings input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['api_key'] = isset( $input['api_key'] )
			? sanitize_text_field( $input['api_key'] )
			: '';

		$sanitized['username'] = isset( $input['username'] )
			? sanitize_text_field( $input['username'] )
			: '';

		$sanitized['default_count'] = isset( $input['default_count'] )
			? absint( $input['default_count'] )
			: 5;

		// Clamp count between 1 and 50.
		$sanitized['default_count'] = max( 1, min( 50, $sanitized['default_count'] ) );

		$valid_themes              = array( 'light', 'dark', 'transparent' );
		$sanitized['default_theme'] = isset( $input['default_theme'] ) && in_array( $input['default_theme'], $valid_themes, true )
			? $input['default_theme']
			: 'dark';

		$sanitized['show_album'] = isset( $input['show_album'] ) && $input['show_album'];

		$sanitized['show_duration'] = isset( $input['show_duration'] ) && $input['show_duration'];

		$sanitized['cache_duration'] = isset( $input['cache_duration'] )
			? absint( $input['cache_duration'] )
			: 300;

		// Clamp cache between 60 and 3600 seconds.
		$sanitized['cache_duration'] = max( 60, min( 3600, $sanitized['cache_duration'] ) );

		return $sanitized;
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap lastfm-np-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'lastfm_now_playing_settings_group' );
				do_settings_sections( 'lastfm-now-playing' );
				submit_button( __( 'Save Settings', 'lastfm-now-playing' ) );
				?>
			</form>

			<div class="lastfm-np-info">
				<h2><?php esc_html_e( 'How to Get Your Last.fm API Key', 'lastfm-now-playing' ); ?></h2>
				<ol>
					<li><?php esc_html_e( 'Go to the Last.fm API page:', 'lastfm-now-playing' ); ?> <a href="https://www.last.fm/api/account/create" target="_blank" rel="noopener noreferrer">https://www.last.fm/api/account/create</a></li>
					<li><?php esc_html_e( 'Sign in with your Last.fm account', 'lastfm-now-playing' ); ?></li>
					<li><?php esc_html_e( 'Fill in the application name and description', 'lastfm-now-playing' ); ?></li>
					<li><?php esc_html_e( 'Copy the API Key and paste it above', 'lastfm-now-playing' ); ?></li>
				</ol>
			</div>

			<div class="lastfm-np-usage">
				<h2><?php esc_html_e( 'Usage', 'lastfm-now-playing' ); ?></h2>
				<h3><?php esc_html_e( 'Gutenberg Block', 'lastfm-now-playing' ); ?></h3>
				<p><?php esc_html_e( 'Search for "Last.fm Now Playing" in the block inserter to add the block to any post or page.', 'lastfm-now-playing' ); ?></p>

				<h3><?php esc_html_e( 'Widget', 'lastfm-now-playing' ); ?></h3>
				<p><?php esc_html_e( 'Go to Appearance → Widgets and add the "Last.fm Now Playing" widget to any widget area.', 'lastfm-now-playing' ); ?></p>

				<h3><?php esc_html_e( 'Shortcode', 'lastfm-now-playing' ); ?></h3>
				<p><?php esc_html_e( 'You can also use the shortcode:', 'lastfm-now-playing' ); ?></p>
				<code>[lastfm_now_playing count="5" theme="dark" show_album="true" show_duration="true"]</code>
			</div>
		</div>
		<?php
	}

	/**
	 * Render API section description.
	 */
	public function render_api_section() {
		echo '<p>' . esc_html__( 'Enter your Last.fm API credentials to connect to the service.', 'lastfm-now-playing' ) . '</p>';
	}

	/**
	 * Render defaults section description.
	 */
	public function render_defaults_section() {
		echo '<p>' . esc_html__( 'Configure default settings for blocks and widgets. These can be overridden on a per-instance basis.', 'lastfm-now-playing' ) . '</p>';
	}

	/**
	 * Render cache section description.
	 */
	public function render_cache_section() {
		echo '<p>' . esc_html__( 'Configure how long to cache Last.fm data to reduce API calls.', 'lastfm-now-playing' ) . '</p>';
	}

	/**
	 * Render API key field.
	 */
	public function render_api_key_field() {
		$settings = self::get_settings();
		?>
		<input
			type="text"
			id="lastfm_api_key"
			name="<?php echo esc_attr( $this->option_name ); ?>[api_key]"
			value="<?php echo esc_attr( $settings['api_key'] ); ?>"
			class="regular-text"
			autocomplete="off"
		/>
		<p class="description">
			<?php esc_html_e( 'Your Last.fm API key. Required to fetch data.', 'lastfm-now-playing' ); ?>
		</p>
		<?php
	}

	/**
	 * Render username field.
	 */
	public function render_username_field() {
		$settings = self::get_settings();
		?>
		<input
			type="text"
			id="lastfm_username"
			name="<?php echo esc_attr( $this->option_name ); ?>[username]"
			value="<?php echo esc_attr( $settings['username'] ); ?>"
			class="regular-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Your Last.fm username to display recent tracks from.', 'lastfm-now-playing' ); ?>
		</p>
		<?php
	}

	/**
	 * Render count field.
	 */
	public function render_count_field() {
		$settings = self::get_settings();
		?>
		<input
			type="number"
			id="lastfm_default_count"
			name="<?php echo esc_attr( $this->option_name ); ?>[default_count]"
			value="<?php echo esc_attr( $settings['default_count'] ); ?>"
			min="1"
			max="50"
			class="small-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Default number of tracks to display (1-50).', 'lastfm-now-playing' ); ?>
		</p>
		<?php
	}

	/**
	 * Render theme field.
	 */
	public function render_theme_field() {
		$settings = self::get_settings();
		$themes   = array(
			'light'       => __( 'Light', 'lastfm-now-playing' ),
			'dark'        => __( 'Dark', 'lastfm-now-playing' ),
			'transparent' => __( 'Transparent', 'lastfm-now-playing' ),
		);
		?>
		<select
			id="lastfm_default_theme"
			name="<?php echo esc_attr( $this->option_name ); ?>[default_theme]"
		>
			<?php foreach ( $themes as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['default_theme'], $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Default theme style inspired by Spotify.', 'lastfm-now-playing' ); ?>
		</p>
		<?php
	}

	/**
	 * Render show album field.
	 */
	public function render_show_album_field() {
		$settings = self::get_settings();
		?>
		<label>
			<input
				type="checkbox"
				id="lastfm_show_album"
				name="<?php echo esc_attr( $this->option_name ); ?>[show_album]"
				value="1"
				<?php checked( $settings['show_album'] ); ?>
			/>
			<?php esc_html_e( 'Display album name with each track', 'lastfm-now-playing' ); ?>
		</label>
		<?php
	}

	/**
	 * Render show duration field.
	 */
	public function render_show_duration_field() {
		$settings = self::get_settings();
		?>
		<label>
			<input
				type="checkbox"
				id="lastfm_show_duration"
				name="<?php echo esc_attr( $this->option_name ); ?>[show_duration]"
				value="1"
				<?php checked( $settings['show_duration'] ); ?>
			/>
			<?php esc_html_e( 'Display track duration when available', 'lastfm-now-playing' ); ?>
		</label>
		<?php
	}

	/**
	 * Render cache duration field.
	 */
	public function render_cache_field() {
		$settings = self::get_settings();
		?>
		<input
			type="number"
			id="lastfm_cache_duration"
			name="<?php echo esc_attr( $this->option_name ); ?>[cache_duration]"
			value="<?php echo esc_attr( $settings['cache_duration'] ); ?>"
			min="60"
			max="3600"
			class="small-text"
		/>
		<p class="description">
			<?php esc_html_e( 'How long to cache Last.fm data (60-3600 seconds). Default is 300 (5 minutes).', 'lastfm-now-playing' ); ?>
		</p>
		<?php
	}
}
