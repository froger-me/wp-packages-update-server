<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'wppus_get_root_data_dir' ) ) {

	function wppus_get_root_data_dir() {

		return WPPUS_Data_Manager::get_data_dir();
	}
}

if ( ! function_exists( 'wppus_get_packages_data_dir' ) ) {

	function wppus_get_packages_data_dir() {

		return WPPUS_Data_Manager::get_data_dir( 'packages' );
	}
}

if ( ! function_exists( 'wppus_get_logs_data_dir' ) ) {

	function wppus_get_logs_data_dir() {

		return WPPUS_Data_Manager::get_data_dir( 'logs' );
	}
}

if ( ! function_exists( 'wppus_is_doing_update_api_request' ) ) {

	function wppus_is_doing_update_api_request() {

		return WPPUS_Update_API::is_doing_api_request();
	}
}

if ( ! function_exists( 'wppus_check_remote_plugin_update' ) ) {

	function wppus_check_remote_plugin_update( $slug ) {

		return WPPUS_Update_API::check_remote_update( $slug, 'plugin' );
	}
}

if ( ! function_exists( 'wppus_check_remote_theme_update' ) ) {

	function wppus_check_remote_theme_update( $slug ) {

		return WPPUS_Update_API::check_remote_update( $slug, 'theme' );
	}
}

if ( ! function_exists( 'wppus_download_remote_plugin' ) ) {

	function wppus_download_remote_plugin_to_local( $slug ) {

		return WPPUS_Update_API::download_remote_update( $slug, 'plugin' );
	}
}

if ( ! function_exists( 'wppus_download_remote_theme' ) ) {

	function wppus_download_remote_theme_to_local( $slug ) {

		return WPPUS_Update_API::download_remote_update( $slug, 'theme' );
	}
}

if ( ! function_exists( 'wppus_force_cleanup_cache' ) ) {

	function wppus_force_cleanup_cache() {

		return WPPUS_Data_Manager::maybe_cleanup( 'cache', true );
	}
}

if ( ! function_exists( 'wppus_force_cleanup_logs' ) ) {

	function wppus_force_cleanup_logs() {

		return WPPUS_Data_Manager::maybe_cleanup( 'logs', true );
	}
}

if ( ! function_exists( 'wppus_force_cleanup_tmp' ) ) {

	function wppus_force_cleanup_tmp() {

		return WPPUS_Data_Manager::maybe_cleanup( 'tmp', true );
	}
}

if ( ! function_exists( 'wppus_get_local_package_path' ) ) {

	function wppus_get_local_package_path( $package_slug ) {
		WP_Filesystem();

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {

			wp_die( __FUNCTION__ . ' - WP_Filesystem not available.' );
		}

		$package_path = trailingslashit( WPPUS_Data_Manager::get_data_dir( 'packages' ) ) . $package_slug . '.zip';

		if ( $wp_filesystem->is_file( $package_path ) ) {

			return $package_path;
		}

		return false;
	}
}

if ( ! function_exists( 'wppus_download_local_package' ) ) {

	function wppus_download_local_package( $package_slug, $package_path = null ) {
		require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-update-manager.php';

		$update_manager = new WPPUS_Update_Manager();

		if ( null === $package_path ) {
			$package_path = wppus_get_local_package_path( $package_slug );
		}

		$update_manager->trigger_packages_download( $package_slug, $package_path );
	}
}

if ( ! function_exists( 'wppus_is_doing_license_api_request' ) ) {

	function wppus_is_doing_license_api_request() {

		return WPPUS_License_API::is_doing_api_request();
	}
}

if ( ! function_exists( 'wppus_browse_licenses' ) ) {

	function wppus_browse_licenses( $browse_query ) {

		return WPPUS_License_API::local_request( 'browse', $browse_query );
	}
}

if ( ! function_exists( 'wppus_read_license' ) ) {

	function wppus_read_license( $license_data ) {

		return WPPUS_License_API::local_request( 'read', $license_data );
	}
}

if ( ! function_exists( 'wppus_add_license' ) ) {

	function wppus_add_license( $license_data ) {

		return WPPUS_License_API::local_request( 'add', $license_data );
	}
}

if ( ! function_exists( 'wppus_edit_license' ) ) {

	function wppus_edit_license( $license_data ) {

		return WPPUS_License_API::local_request( 'edit', $license_data );
	}
}

if ( ! function_exists( 'wppus_delete_license' ) ) {

	function wppus_delete_license( $license_data ) {

		return WPPUS_License_API::local_request( 'delete', $license_data );
	}
}

if ( ! function_exists( 'wppus_check_license' ) ) {

	function wppus_check_license( $license_data ) {

		return WPPUS_License_API::local_request( 'check', $license_data );
	}
}

if ( ! function_exists( 'wppus_activate_license' ) ) {

	function wppus_activate_license( $license_data ) {

		return WPPUS_License_API::local_request( 'activate', $license_data );
	}
}

if ( ! function_exists( 'wppus_deactivate_license' ) ) {

	function wppus_deactivate_license( $license_data ) {

		return WPPUS_License_API::local_request( 'deactivate', $license_data );
	}
}
