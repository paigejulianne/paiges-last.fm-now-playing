<?php
/**
 * Last.fm API integration class.
 *
 * @package LastFM_Now_Playing
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Last.fm API class.
 */
class LastFM_API {

	/**
	 * API base URL.
	 *
	 * @var string
	 */
	private const API_URL = 'https://ws.audioscrobbler.com/2.0/';

	/**
	 * API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Username.
	 *
	 * @var string
	 */
	private $username;

	/**
	 * Cache duration in seconds.
	 *
	 * @var int
	 */
	private $cache_duration;

	/**
	 * Constructor.
	 *
	 * @param string $api_key        Last.fm API key.
	 * @param string $username       Last.fm username.
	 * @param int    $cache_duration Cache duration in seconds.
	 */
	public function __construct( $api_key = '', $username = '', $cache_duration = 300 ) {
		$settings = LastFM_Settings::get_settings();

		$this->api_key        = ! empty( $api_key ) ? $api_key : $settings['api_key'];
		$this->username       = ! empty( $username ) ? $username : $settings['username'];
		$this->cache_duration = ! empty( $cache_duration ) ? $cache_duration : $settings['cache_duration'];
	}

	/**
	 * Check if API is configured.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return ! empty( $this->api_key ) && ! empty( $this->username );
	}

	/**
	 * Make an API request.
	 *
	 * @param string $method API method.
	 * @param array  $params Additional parameters.
	 * @return array|WP_Error Response data or error.
	 */
	private function request( $method, $params = array() ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error(
				'not_configured',
				__( 'Last.fm API is not configured. Please add your API key and username in the settings.', 'lastfm-now-playing' )
			);
		}

		$default_params = array(
			'method'  => $method,
			'api_key' => $this->api_key,
			'format'  => 'json',
		);

		$params = array_merge( $default_params, $params );

		$url = add_query_arg( $params, self::API_URL );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( 200 !== $code ) {
			$message = isset( $data['message'] ) ? $data['message'] : __( 'Unknown API error', 'lastfm-now-playing' );
			return new WP_Error( 'api_error', $message );
		}

		if ( isset( $data['error'] ) ) {
			return new WP_Error( 'api_error', $data['message'] );
		}

		return $data;
	}

	/**
	 * Get user info.
	 *
	 * @return array|WP_Error User info or error.
	 */
	public function get_user_info() {
		$cache_key = 'lastfm_np_user_' . md5( $this->username );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = $this->request( 'user.getinfo', array( 'user' => $this->username ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['user'] ) ) {
			return new WP_Error( 'invalid_response', __( 'Invalid response from Last.fm API', 'lastfm-now-playing' ) );
		}

		$user_data = array(
			'name'       => $response['user']['name'],
			'realname'   => isset( $response['user']['realname'] ) ? $response['user']['realname'] : '',
			'url'        => $response['user']['url'],
			'image'      => $this->get_image_url( $response['user']['image'], 'medium' ),
			'playcount'  => isset( $response['user']['playcount'] ) ? $response['user']['playcount'] : 0,
			'registered' => isset( $response['user']['registered']['#text'] ) ? $response['user']['registered']['#text'] : '',
		);

		set_transient( $cache_key, $user_data, $this->cache_duration );

		return $user_data;
	}

	/**
	 * Get recent tracks.
	 *
	 * @param int $limit Number of tracks to fetch.
	 * @return array|WP_Error Recent tracks or error.
	 */
	public function get_recent_tracks( $limit = 5 ) {
		$cache_key = 'lastfm_np_tracks_' . md5( $this->username . '_' . $limit );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = $this->request(
			'user.getrecenttracks',
			array(
				'user'     => $this->username,
				'limit'    => $limit,
				'extended' => 1,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['recenttracks']['track'] ) ) {
			return new WP_Error( 'invalid_response', __( 'Invalid response from Last.fm API', 'lastfm-now-playing' ) );
		}

		$tracks     = $response['recenttracks']['track'];
		$track_data = array();

		// Handle single track response (comes as object instead of array).
		if ( isset( $tracks['name'] ) ) {
			$tracks = array( $tracks );
		}

		foreach ( $tracks as $track ) {
			$is_now_playing = isset( $track['@attr']['nowplaying'] ) && 'true' === $track['@attr']['nowplaying'];

			$track_data[] = array(
				'name'        => $track['name'],
				'artist'      => isset( $track['artist']['name'] ) ? $track['artist']['name'] : ( isset( $track['artist']['#text'] ) ? $track['artist']['#text'] : '' ),
				'album'       => isset( $track['album']['#text'] ) ? $track['album']['#text'] : '',
				'url'         => $track['url'],
				'image'       => $this->get_image_url( $track['image'], 'medium' ),
				'image_large' => $this->get_image_url( $track['image'], 'extralarge' ),
				'now_playing' => $is_now_playing,
				'date'        => isset( $track['date']['#text'] ) ? $track['date']['#text'] : '',
				'timestamp'   => isset( $track['date']['uts'] ) ? (int) $track['date']['uts'] : 0,
			);
		}

		// Cache for a shorter time if something is now playing.
		$has_now_playing = ! empty( array_filter( $track_data, function( $t ) {
			return $t['now_playing'];
		} ) );

		$cache_time = $has_now_playing ? min( 60, $this->cache_duration ) : $this->cache_duration;
		set_transient( $cache_key, $track_data, $cache_time );

		return $track_data;
	}

	/**
	 * Get track info with duration.
	 *
	 * @param string $artist Artist name.
	 * @param string $track  Track name.
	 * @return array|WP_Error Track info or error.
	 */
	public function get_track_info( $artist, $track ) {
		$cache_key = 'lastfm_np_track_' . md5( $artist . '_' . $track );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = $this->request(
			'track.getInfo',
			array(
				'artist' => $artist,
				'track'  => $track,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['track'] ) ) {
			return new WP_Error( 'invalid_response', __( 'Invalid response from Last.fm API', 'lastfm-now-playing' ) );
		}

		$track_info = array(
			'duration'   => isset( $response['track']['duration'] ) ? (int) $response['track']['duration'] : 0,
			'listeners'  => isset( $response['track']['listeners'] ) ? (int) $response['track']['listeners'] : 0,
			'playcount'  => isset( $response['track']['playcount'] ) ? (int) $response['track']['playcount'] : 0,
		);

		// Cache track info for longer (1 day) since it rarely changes.
		set_transient( $cache_key, $track_info, DAY_IN_SECONDS );

		return $track_info;
	}

	/**
	 * Get image URL from Last.fm image array.
	 *
	 * @param array  $images Image array from API.
	 * @param string $size   Size to get (small, medium, large, extralarge).
	 * @return string Image URL or empty string.
	 */
	private function get_image_url( $images, $size = 'medium' ) {
		if ( empty( $images ) || ! is_array( $images ) ) {
			return '';
		}

		$sizes = array( 'small', 'medium', 'large', 'extralarge' );
		$size_index = array_search( $size, $sizes, true );

		foreach ( $images as $image ) {
			if ( isset( $image['size'] ) && $image['size'] === $size && ! empty( $image['#text'] ) ) {
				return $image['#text'];
			}
		}

		// Fallback: try to get any available image.
		foreach ( $images as $image ) {
			if ( ! empty( $image['#text'] ) ) {
				return $image['#text'];
			}
		}

		return '';
	}

	/**
	 * Format duration from milliseconds to mm:ss.
	 *
	 * @param int $milliseconds Duration in milliseconds.
	 * @return string Formatted duration.
	 */
	public static function format_duration( $milliseconds ) {
		if ( empty( $milliseconds ) ) {
			return '';
		}

		$seconds = floor( $milliseconds / 1000 );
		$minutes = floor( $seconds / 60 );
		$seconds = $seconds % 60;

		return sprintf( '%d:%02d', $minutes, $seconds );
	}

	/**
	 * Format relative time.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return string Relative time string.
	 */
	public static function format_relative_time( $timestamp ) {
		if ( empty( $timestamp ) ) {
			return '';
		}

		$diff = time() - $timestamp;

		if ( $diff < 60 ) {
			return __( 'Just now', 'lastfm-now-playing' );
		} elseif ( $diff < 3600 ) {
			$minutes = floor( $diff / 60 );
			/* translators: %d: number of minutes */
			return sprintf( _n( '%d minute ago', '%d minutes ago', $minutes, 'lastfm-now-playing' ), $minutes );
		} elseif ( $diff < 86400 ) {
			$hours = floor( $diff / 3600 );
			/* translators: %d: number of hours */
			return sprintf( _n( '%d hour ago', '%d hours ago', $hours, 'lastfm-now-playing' ), $hours );
		} else {
			$days = floor( $diff / 86400 );
			/* translators: %d: number of days */
			return sprintf( _n( '%d day ago', '%d days ago', $days, 'lastfm-now-playing' ), $days );
		}
	}
}
