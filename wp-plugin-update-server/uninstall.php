<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if accessed directly}
}

global $wpdb;

WP_Filesystem();

global $wp_filesystem;

$package_directory = trailingslashit( $wp_filesystem->wp_content_dir() . 'wppus' ) . 'packages';

if ( $wp_filesystem->is_dir( $package_directory ) ) {
	$package_paths = glob( trailingslashit( $package_directory ) . '*.zip' );

	if ( ! empty( $package_paths ) ) {

		foreach ( $package_paths as $package_path ) {
			$package_path_parts = explode( DIRECTORY_SEPARATOR, $package_path );
			$safe_slug          = str_replace( '.zip', '', end( $package_path_parts ) );

			wp_clear_scheduled_hook( 'wppus_check_remote_' . $safe_slug, array( $safe_slug ) );
		}
	}
}

$mu_plugin = trailingslashit( wp_normalize_path( WPMU_PLUGIN_DIR ) ) . 'wppus-endpoint-optimizer.php';

$wp_filesystem->delete( $mu_plugin );
$wp_filesystem->delete( $mu_plugin . '.backup' );

wp_clear_scheduled_hook( 'wppus_cleanup', array( 'cache' ) );
wp_clear_scheduled_hook( 'wppus_cleanup', array( 'logs' ) );
wp_clear_scheduled_hook( 'wppus_cleanup', array( 'tmp' ) );
wp_clear_scheduled_hook( 'wppus_cleanup', array( 'update_from_remote_locks' ) );

$option_prefix = 'wppus_';
$sql           = "DELETE FROM $wpdb->options WHERE `option_name` LIKE %s";

$wpdb->query( $wpdb->prepare( $sql, '%' . $option_prefix . '%' ) ); // @codingStandardsIgnoreLine

$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}wppus_licenses;";
$wpdb->query( $sql ); // @codingStandardsIgnoreLine
