<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function wppus_update_to_1_2_1() {
	$executed = false;

	if ( ! wp_doing_ajax() && is_admin() ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		WP_Filesystem();

		global $wp_filesystem;

		if ( $wp_filesystem ) {
			$new_filepath    = WPPUS_PLUGIN_PATH . 'optimisation/wppus-endpoint-optimizer.php';
			$old_filepath    = trailingslashit( wp_normalize_path( WPMU_PLUGIN_DIR ) ) . 'wppus-endpoint-optimizer.php';
			$backup_filepath = str_replace( '.php', '.php.backup', $old_filepath );

			if ( $wp_filesystem->is_file( $old_filepath ) ) {
				$executed = $wp_filesystem->move( $old_filepath, $backup_filepath, true );
				$executed = ( $executed ) ? $wp_filesystem->copy( $new_filepath, $old_filepath, true ) : $executed;

				if ( ! $executed ) {
					// translators: %1$s is the path to the MU Plugin
					$error_message = sprintf( __( '<p>Failed to update the MU Plugin <code>%1$s</code> - please check the file and parent directory are writable by the server.</p>', 'wppus' ), $old_filepath );

					$executed = new WP_Error( __FUNCTION__, $error_message );
				} else {
					add_action( 'admin_notices', 'wppus_update_to_1_2_1_optimizer_updated', 15, 0 );
				}
			} else {
				$executed = true;
			}
		} else {
			$error_message = __( '<p>File system not available.</p>', 'wppus' );
			$executed      = new WP_Error( __FUNCTION__, $error_message );
		}
	}

	return $executed;
}

function wppus_update_to_1_2_1_optimizer_updated() {
	$class = 'notice notice-info is-dismissible';

	// translators: %1$s is the path to the MU Plugin backup
	$message = '<p>' . sprintf( __( 'The MU Plugin <code>wppus-endpoint-optimizer.php</code> has been updated. A backup of the previous version has been created at <code>%1$s</code> in case it was customised before the update.', 'wp-update-migrate' ), trailingslashit( wp_normalize_path( WPMU_PLUGIN_DIR ) ) . 'wppus-endpoint-optimizer.php.backup' ) . '</p>';

	printf( '<div class="%1$s">%2$s</div>', $class, $message ); // @codingStandardsIgnoreLine
}
