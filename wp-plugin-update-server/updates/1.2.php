<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function wppus_update_to_1_2() {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$executed = WPPUS_Data_Manager::maybe_setup_directories();

	if ( ! $executed ) {
		// translators: %1$s is the path to the plugin's data directory
		$error_message = sprintf( __( '<p>Failed to update data in <code>%1$s</code> - please check the parent directory is writable.</p>', 'wppus' ), WPPUS_Data_Manager::get_data_dir() );

		$executed = new WP_Error( __FUNCTION__, $error_message );
	}

	return $executed;
}
