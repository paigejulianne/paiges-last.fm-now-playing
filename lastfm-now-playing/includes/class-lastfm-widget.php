<?php
/**
 * WordPress widget for Last.fm Now Playing.
 *
 * @package LastFM_Now_Playing
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget class.
 */
class LastFM_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'lastfm_now_playing_widget',
			__( "Paige's Last.FM Now Playing", 'lastfm-now-playing' ),
			array(
				'description'                 => __( 'Display your recently played tracks from Last.fm.', 'lastfm-now-playing' ),
				'customize_selective_refresh' => true,
				'show_instance_in_rest'       => true,
			)
		);
	}

	/**
	 * Front-end display of the widget.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$settings = LastFM_Settings::get_settings();

		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$render_args = array(
			'count'         => ! empty( $instance['count'] ) ? (int) $instance['count'] : $settings['default_count'],
			'theme'         => ! empty( $instance['theme'] ) ? $instance['theme'] : $settings['default_theme'],
			'show_album'    => isset( $instance['show_album'] ) ? (bool) $instance['show_album'] : $settings['show_album'],
			'show_duration' => isset( $instance['show_duration'] ) ? (bool) $instance['show_duration'] : $settings['show_duration'],
		);

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		echo LastFM_Renderer::render( $render_args );

		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$settings = LastFM_Settings::get_settings();

		$title         = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$count         = ! empty( $instance['count'] ) ? (int) $instance['count'] : $settings['default_count'];
		$theme         = ! empty( $instance['theme'] ) ? $instance['theme'] : $settings['default_theme'];
		$show_album    = isset( $instance['show_album'] ) ? (bool) $instance['show_album'] : $settings['show_album'];
		$show_duration = isset( $instance['show_duration'] ) ? (bool) $instance['show_duration'] : $settings['show_duration'];

		$themes = array(
			'light'       => __( 'Light', 'lastfm-now-playing' ),
			'dark'        => __( 'Dark', 'lastfm-now-playing' ),
			'transparent' => __( 'Transparent', 'lastfm-now-playing' ),
		);
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'lastfm-now-playing' ); ?>
			</label>
			<input
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				type="text"
				value="<?php echo esc_attr( $title ); ?>"
			/>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>">
				<?php esc_html_e( 'Number of Tracks:', 'lastfm-now-playing' ); ?>
			</label>
			<input
				class="tiny-text"
				id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
				type="number"
				min="1"
				max="50"
				value="<?php echo esc_attr( $count ); ?>"
			/>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'theme' ) ); ?>">
				<?php esc_html_e( 'Theme:', 'lastfm-now-playing' ); ?>
			</label>
			<select
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'theme' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'theme' ) ); ?>"
			>
				<?php foreach ( $themes as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $theme, $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<p>
			<input
				class="checkbox"
				type="checkbox"
				id="<?php echo esc_attr( $this->get_field_id( 'show_album' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'show_album' ) ); ?>"
				<?php checked( $show_album ); ?>
			/>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_album' ) ); ?>">
				<?php esc_html_e( 'Show Album Name', 'lastfm-now-playing' ); ?>
			</label>
		</p>

		<p>
			<input
				class="checkbox"
				type="checkbox"
				id="<?php echo esc_attr( $this->get_field_id( 'show_duration' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'show_duration' ) ); ?>"
				<?php checked( $show_duration ); ?>
			/>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_duration' ) ); ?>">
				<?php esc_html_e( 'Show Track Duration', 'lastfm-now-playing' ); ?>
			</label>
		</p>

		<?php if ( empty( $settings['api_key'] ) || empty( $settings['username'] ) ) : ?>
			<p class="notice notice-warning" style="padding: 8px; margin: 8px 0;">
				<?php
				printf(
					/* translators: %s: URL to settings page */
					esc_html__( 'Please configure your Last.fm API settings in the %s.', 'lastfm-now-playing' ),
					'<a href="' . esc_url( admin_url( 'options-general.php?page=lastfm-now-playing' ) ) . '">' . esc_html__( 'plugin settings', 'lastfm-now-playing' ) . '</a>'
				);
				?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title'] = ! empty( $new_instance['title'] )
			? sanitize_text_field( $new_instance['title'] )
			: '';

		$instance['count'] = ! empty( $new_instance['count'] )
			? max( 1, min( 50, absint( $new_instance['count'] ) ) )
			: 5;

		$valid_themes       = array( 'light', 'dark', 'transparent' );
		$instance['theme']  = ! empty( $new_instance['theme'] ) && in_array( $new_instance['theme'], $valid_themes, true )
			? $new_instance['theme']
			: 'dark';

		$instance['show_album']    = isset( $new_instance['show_album'] ) && $new_instance['show_album'];
		$instance['show_duration'] = isset( $new_instance['show_duration'] ) && $new_instance['show_duration'];

		return $instance;
	}
}
