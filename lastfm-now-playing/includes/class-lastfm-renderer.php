<?php
/**
 * Renderer class for Last.fm Now Playing output.
 *
 * @package LastFM_Now_Playing
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renderer class.
 */
class LastFM_Renderer {

	/**
	 * Render the now playing display.
	 *
	 * @param array $args Display arguments.
	 * @return string HTML output.
	 */
	public static function render( $args = array() ) {
		$settings = LastFM_Settings::get_settings();

		$defaults = array(
			'count'         => $settings['default_count'],
			'theme'         => $settings['default_theme'],
			'show_album'    => $settings['show_album'],
			'show_duration' => $settings['show_duration'],
			'username'      => $settings['username'],
			'class'         => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Initialize API.
		$api = new LastFM_API( '', $args['username'] );

		if ( ! $api->is_configured() ) {
			return self::render_error( __( 'Please configure your Last.fm API settings.', 'lastfm-now-playing' ) );
		}

		// Get user info.
		$user = $api->get_user_info();
		if ( is_wp_error( $user ) ) {
			return self::render_error( $user->get_error_message() );
		}

		// Get recent tracks.
		$tracks = $api->get_recent_tracks( $args['count'] );
		if ( is_wp_error( $tracks ) ) {
			return self::render_error( $tracks->get_error_message() );
		}

		// Build output.
		$theme_class = 'lastfm-np-theme-' . esc_attr( $args['theme'] );
		$extra_class = ! empty( $args['class'] ) ? ' ' . esc_attr( $args['class'] ) : '';

		ob_start();
		?>
		<div class="lastfm-np-container <?php echo esc_attr( $theme_class . $extra_class ); ?>">
			<?php echo self::render_header( $user ); ?>
			<div class="lastfm-np-tracks">
				<?php
				if ( empty( $tracks ) ) {
					echo '<p class="lastfm-np-no-tracks">' . esc_html__( 'No recent tracks found.', 'lastfm-now-playing' ) . '</p>';
				} else {
					foreach ( $tracks as $track ) {
						echo self::render_track( $track, $args, $api );
					}
				}
				?>
			</div>
			<?php echo self::render_footer( $user ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the header with user info.
	 *
	 * @param array $user User data.
	 * @return string HTML output.
	 */
	private static function render_header( $user ) {
		$avatar_url = ! empty( $user['image'] ) ? $user['image'] : '';
		$profile_url = esc_url( $user['url'] );
		$display_name = ! empty( $user['realname'] ) ? $user['realname'] : $user['name'];

		ob_start();
		?>
		<div class="lastfm-np-header">
			<a href="<?php echo $profile_url; ?>" target="_blank" rel="noopener noreferrer" class="lastfm-np-user-link">
				<?php if ( $avatar_url ) : ?>
					<img
						src="<?php echo esc_url( $avatar_url ); ?>"
						alt="<?php echo esc_attr( $display_name ); ?>"
						class="lastfm-np-avatar"
						loading="lazy"
					/>
				<?php else : ?>
					<div class="lastfm-np-avatar lastfm-np-avatar-placeholder">
						<svg viewBox="0 0 24 24" fill="currentColor">
							<path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
						</svg>
					</div>
				<?php endif; ?>
				<div class="lastfm-np-user-info">
					<span class="lastfm-np-username"><?php echo esc_html( $display_name ); ?></span>
					<span class="lastfm-np-label"><?php esc_html_e( 'Recent Tracks', 'lastfm-now-playing' ); ?></span>
				</div>
			</a>
			<div class="lastfm-np-logo">
				<svg viewBox="0 0 24 24" fill="currentColor" class="lastfm-np-lastfm-icon">
					<path d="M10.584 17.209l-.88-2.392s-1.43 1.595-3.573 1.595c-1.897 0-3.244-1.649-3.244-4.288 0-3.381 1.704-4.591 3.381-4.591 2.42 0 3.189 1.567 3.849 3.574l.88 2.749c.88 2.666 2.529 4.81 7.285 4.81 3.409 0 5.718-1.044 5.718-3.793 0-2.227-1.265-3.381-3.629-3.932l-1.758-.385c-1.21-.275-1.567-.77-1.567-1.595 0-.934.742-1.484 1.952-1.484 1.32 0 2.034.495 2.144 1.677l2.749-.33c-.22-2.474-1.924-3.491-4.729-3.491-2.474 0-4.893.935-4.893 3.932 0 1.87.907 3.051 3.189 3.602l1.87.44c1.402.33 1.869.907 1.869 1.704 0 1.017-.99 1.43-2.86 1.43-2.776 0-3.932-1.456-4.591-3.464l-.907-2.749c-1.155-3.574-2.997-4.894-6.653-4.894C2.144 5.333 0 7.616 0 12.096c0 4.287 2.144 6.433 6.05 6.433 3.107 0 4.534-1.32 4.534-1.32z"/>
				</svg>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single track.
	 *
	 * @param array      $track Track data.
	 * @param array      $args  Display arguments.
	 * @param LastFM_API $api   API instance.
	 * @return string HTML output.
	 */
	private static function render_track( $track, $args, $api ) {
		$image_url = ! empty( $track['image'] ) ? $track['image'] : '';
		$track_url = esc_url( $track['url'] );
		$is_playing = $track['now_playing'];

		// Get duration if enabled.
		$duration = '';
		if ( $args['show_duration'] && ! empty( $track['artist'] ) && ! empty( $track['name'] ) ) {
			$track_info = $api->get_track_info( $track['artist'], $track['name'] );
			if ( ! is_wp_error( $track_info ) && ! empty( $track_info['duration'] ) ) {
				$duration = LastFM_API::format_duration( $track_info['duration'] );
			}
		}

		ob_start();
		?>
		<div class="lastfm-np-track<?php echo $is_playing ? ' lastfm-np-now-playing' : ''; ?>">
			<a href="<?php echo $track_url; ?>" target="_blank" rel="noopener noreferrer" class="lastfm-np-track-link">
				<div class="lastfm-np-track-image">
					<?php if ( $image_url ) : ?>
						<img
							src="<?php echo esc_url( $image_url ); ?>"
							alt="<?php echo esc_attr( $track['album'] ); ?>"
							loading="lazy"
						/>
					<?php else : ?>
						<div class="lastfm-np-track-image-placeholder">
							<svg viewBox="0 0 24 24" fill="currentColor">
								<path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
							</svg>
						</div>
					<?php endif; ?>
					<?php if ( $is_playing ) : ?>
						<div class="lastfm-np-playing-indicator">
							<span></span>
							<span></span>
							<span></span>
						</div>
					<?php endif; ?>
				</div>
				<div class="lastfm-np-track-info">
					<span class="lastfm-np-track-name"><?php echo esc_html( $track['name'] ); ?></span>
					<span class="lastfm-np-track-artist"><?php echo esc_html( $track['artist'] ); ?></span>
					<?php if ( $args['show_album'] && ! empty( $track['album'] ) ) : ?>
						<span class="lastfm-np-track-album"><?php echo esc_html( $track['album'] ); ?></span>
					<?php endif; ?>
				</div>
				<div class="lastfm-np-track-meta">
					<?php if ( $is_playing ) : ?>
						<span class="lastfm-np-status"><?php esc_html_e( 'Now Playing', 'lastfm-now-playing' ); ?></span>
					<?php elseif ( ! empty( $track['timestamp'] ) ) : ?>
						<span class="lastfm-np-time"><?php echo esc_html( LastFM_API::format_relative_time( $track['timestamp'] ) ); ?></span>
					<?php endif; ?>
					<?php if ( $duration ) : ?>
						<span class="lastfm-np-duration"><?php echo esc_html( $duration ); ?></span>
					<?php endif; ?>
				</div>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the footer.
	 *
	 * @param array $user User data.
	 * @return string HTML output.
	 */
	private static function render_footer( $user ) {
		ob_start();
		?>
		<div class="lastfm-np-footer">
			<a href="<?php echo esc_url( $user['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="lastfm-np-view-profile">
				<?php esc_html_e( 'View Profile on Last.fm', 'lastfm-now-playing' ); ?>
				<svg viewBox="0 0 24 24" fill="currentColor" class="lastfm-np-external-icon">
					<path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
				</svg>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render error message.
	 *
	 * @param string $message Error message.
	 * @return string HTML output.
	 */
	private static function render_error( $message ) {
		return sprintf(
			'<div class="lastfm-np-container lastfm-np-error"><p>%s</p></div>',
			esc_html( $message )
		);
	}
}
