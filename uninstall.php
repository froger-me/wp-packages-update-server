<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if accessed directly}
}

global $wpdb;

WP_Filesystem();

global $wp_filesystem;

$cron = get_option( 'cron' );

foreach ( $cron as $job ) {

	if ( is_array( $job ) ) {
		$keys = array_keys( $job );

		foreach ( $keys as $key ) {

			if ( 0 === strpos( $key, 'wppus_' ) ) {
				wp_unschedule_hook( $key );
			}
		}
	}
}

$wppus_mu_plugin = trailingslashit( wp_normalize_path( WPMU_PLUGIN_DIR ) ) . 'wppus-endpoint-optimizer.php';
$wppus_dir       = trailingslashit( $wp_filesystem->wp_content_dir() . 'wppus' );

$wp_filesystem->delete( $wppus_mu_plugin );
$wp_filesystem->delete( $wppus_mu_plugin . '.backup' );
$wp_filesystem->delete( $wppus_mdir, true );

wp_clear_scheduled_hook( 'wppus_cleanup', array( 'cache' ) );
wp_clear_scheduled_hook( 'wppus_cleanup', array( 'logs' ) );
wp_clear_scheduled_hook( 'wppus_cleanup', array( 'tmp' ) );
wp_clear_scheduled_hook( 'wppus_cleanup', array( 'update_from_remote_locks' ) );

$sql = "DELETE FROM $wpdb->options WHERE `option_name` LIKE %s";

$wpdb->query( $wpdb->prepare( $sql, '%wppus_%' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}wppus_licenses;";

$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}wppus_nonce;";

$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
