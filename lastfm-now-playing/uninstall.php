<?php
/**
 * Uninstall script for Last.fm Now Playing plugin.
 *
 * This file runs when the plugin is deleted from WordPress.
 * It cleans up all plugin data from the database.
 *
 * @package LastFM_Now_Playing
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'lastfm_now_playing_settings' );

// Delete all transients created by the plugin.
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

// Delete widget settings.
delete_option( 'widget_lastfm_now_playing_widget' );
