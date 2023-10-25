<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function wppus_update_to_1_3() {
	$executed = false;

	if ( ! wp_doing_ajax() && is_admin() ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		WP_Filesystem();

		global $wp_filesystem;

		if ( $wp_filesystem ) {
			$executed = WP_Plugin_Update_Server::maybe_setup_mu_plugin();

			if ( ! $executed ) {
				add_filter( 'wpum_update_success_extra_notice_hooks', function( $notices ) {
					$notices[] = array( 'WP_Plugin_Update_Server', 'setup_mu_plugin_failure_notice' );

					return $notices;
				} );
			} else {
				add_filter( 'wpum_update_success_extra_notice_hooks', function( $notices ) {
					$notices[] = array( 'WP_Plugin_Update_Server', 'setup_mu_plugin_success_notice' );

					return $notices;
				} );
			}

			add_filter( 'wpum_update_success_extra_notice_hooks', function( $notices ) {
				$notices[] = 'wppus_update_to_1_3_whats_new';

				return $notices;
			} );

			$executed = true;
		} else {
			$error_message = __( '<p>File system not available.</p>', 'wppus' );
			$executed      = new WP_Error( __FUNCTION__, $error_message );
		}
	}

	return $executed;
}

function wppus_update_to_1_3_whats_new() {
	$class = 'notice notice-info is-dismissible';
	// translators: %1$s is the package version to update to
	$title    = __( 'What\'s new in version 1.3?', 'wppus' );
	$content  = '<p>' . __( 'The update includes the following changes:', 'wppus' ) . '</p>';
	$content .= '<ul>';
	$content .= '<li>' . __( '- Support for plugin icons and banner via plugin metadata', 'wppus' ) . '</li>';
	$content .= '<li>' . __( '- Update of Dummy Plugin and Dummy Theme - check them out!', 'wppus' ) . '</li>';
	$content .= '<li>' . __( '- The MU Plugin <code>wppus-endpoint-optimizer.php</code> is now automatically included on plugin activation (still can be customized, still can safely be deleted, still optional - just very much recommended).', 'wppus' ) . '</li>';
	$content .= '<li>' . __( '- WP Plugin Update Server\'s interface has been revamped, with truely separated pages for each section.', 'wppus' ) . '</li>';
	$content .= '</ul>';
	$message  = '<h4>' . $title . '</h4><p>' . $content . '</p>';

	printf( '<div class="%1$s">%2$s</div>', $class, $message ); // @codingStandardsIgnoreLine
}
