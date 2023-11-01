<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'php_log' ) ) {
	function php_log( $message = '', $prefix = '' ) {
		$prefix   = $prefix ? $prefix : ' => ';
		$trace    = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 ); // @codingStandardsIgnoreLine
		$caller   = end( $trace );
		$class    = isset( $caller['class'] ) ? $caller['class'] : '';
		$type     = isset( $caller['type'] ) ? $caller['type'] : '';
		$function = isset( $caller['function'] ) ? $caller['function'] : '';
		$context  = $class . $type . $function . $prefix;

		error_log( $context . print_r( $message, true ) ); // @codingStandardsIgnoreLine
	}
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

if ( ! function_exists( 'wppus_is_doing_update_api_request' ) ) {
	function wppus_is_doing_update_api_request() {

		return WPPUS_Update_API::is_doing_api_request();
	}
}

if ( ! function_exists( 'wppus_check_remote_plugin_update' ) ) {
	function wppus_check_remote_plugin_update( $slug ) {

		return wppus_check_remote_package_update( $slug, 'plugin' );
	}
}

if ( ! function_exists( 'wppus_check_remote_theme_update' ) ) {
	function wppus_check_remote_theme_update( $slug ) {

		return wppus_check_remote_package_update( $slug, 'theme' );
	}
}

// @TODO doc
if ( ! function_exists( 'wppus_check_remote_package_update' ) ) {
	function wppus_check_remote_package_update( $slug, $type ) {
		$api = WPPUS_Update_API::get_instance();

		return $api->check_remote_update( $slug, $type );
	}
}

if ( ! function_exists( 'wppus_download_remote_plugin' ) ) {
	function wppus_download_remote_plugin( $slug ) {

		return wppus_download_remote_package( $slug, 'plugin' );
	}
}

if ( ! function_exists( 'wppus_download_remote_theme' ) ) {
	function wppus_download_remote_theme( $slug ) {

		return wppus_download_remote_package( $slug, 'theme' );
	}
}

// @TODO doc
if ( ! function_exists( 'wppus_download_remote_package' ) ) {
	function wppus_download_remote_package( $slug, $type ) {
		$api = WPPUS_Update_API::get_instance();

		return $api->download_remote_package( $slug, $type, true );
	}
}

// @TODO doc
if ( ! function_exists( 'wppus_delete_package' ) ) {
	function wppus_delete_package( $slug ) {
		$api = WPPUS_Update_Manager::get_instance();

		$api->delete_packages_bulk( array( $slug ) );
	}
}

// @TODO doc
if ( ! function_exists( 'wppus_get_package_info' ) ) {
	function wppus_get_package_info( $package_slug, $json_encode = true ) {
		require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-update-manager.php';

		$result         = $json_encode ? '{}' : array();
		$update_manager = new WPPUS_Update_Manager();
		$package_info   = $update_manager->get_package_info( $package_slug );

		if ( $package_info ) {
			$result = $json_encode ? wp_json_encode( $package_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) : $package_info;
		}

		return $result;
	}
}

// @TODO doc
if ( ! function_exists( 'wppus_get_batch_package_info' ) ) {
	function wppus_get_batch_package_info( $search, $json_encode = true ) {
		require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-update-manager.php';

		$result         = $json_encode ? '{}' : array();
		$update_manager = new WPPUS_Update_Manager();
		$package_info   = $update_manager->get_batch_package_info( $search );

		if ( $package_info ) {
			$result = $json_encode ? wp_json_encode( $package_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) : $package_info;
		}

		return $result;
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

if ( ! function_exists( 'wppus_is_doing_license_api_request' ) ) {
	function wppus_is_doing_license_api_request() {

		return WPPUS_License_API::is_doing_api_request();
	}
}

if ( ! function_exists( 'wppus_browse_licenses' ) ) {
	function wppus_browse_licenses( $browse_query ) {
		$api = WPPUS_License_API::get_instance();

		return $api->browse( $$browse_query );
	}
}

if ( ! function_exists( 'wppus_read_license' ) ) {
	function wppus_read_license( $license_data ) {
		$api = WPPUS_License_API::get_instance();

		return $api->read( $license_data );
	}
}

if ( ! function_exists( 'wppus_add_license' ) ) {
	function wppus_add_license( $license_data ) {
		$api = WPPUS_License_API::get_instance();

		return $api->add( $license_data );
	}
}

if ( ! function_exists( 'wppus_edit_license' ) ) {
	function wppus_edit_license( $license_data ) {
		$api = WPPUS_License_API::get_instance();

		return $api->edit( $license_data );
	}
}

if ( ! function_exists( 'wppus_delete_license' ) ) {
	function wppus_delete_license( $license_data ) {
		$api = WPPUS_License_API::get_instance();

		return $api->delete( $license_data );
	}
}

if ( ! function_exists( 'wppus_check_license' ) ) {
	function wppus_check_license( $license_data ) {
		$api = WPPUS_License_API::get_instance();

		return $api->check( $license_data );
	}
}

if ( ! function_exists( 'wppus_activate_license' ) ) {
	function wppus_activate_license( $license_data ) {
		$api = WPPUS_License_API::get_instance();

		return $api->activate( $license_data );
	}
}

if ( ! function_exists( 'wppus_deactivate_license' ) ) {
	function wppus_deactivate_license( $license_data ) {
		$api = WPPUS_License_API::get_instance();

		return $api->deactivate( $license_data );
	}
}

if ( ! function_exists( 'wppus_get_template' ) ) {
	function wppus_get_template( $template_name, $args = array(), $load = true, $require_once = false ) {
		$template_name = apply_filters( 'wppus_get_template_name', $template_name, $args );
		$template_args = apply_filters( 'wppus_get_template_args', $args, $template_name );

		if ( ! empty( $template_args ) ) {

			foreach ( $template_args as $key => $arg ) {
				$key = is_numeric( $key ) ? 'var_' . $key : $key;

				set_query_var( $key, $arg );
			}
		}

		return WP_Plugin_Update_Server::locate_template( $template_name, $load, $require_once );
	}
}

if ( ! function_exists( 'wppus_get_admin_template' ) ) {
	function wppus_get_admin_template( $template_name, $args = array(), $load = true, $require_once = false ) {
		$template_name = apply_filters( 'wppus_get_admin_template_name', $template_name, $args );
		$template_args = apply_filters( 'wppus_get_admin_template_args', $args, $template_name );

		if ( ! empty( $template_args ) ) {

			foreach ( $template_args as $key => $arg ) {
				$key = is_numeric( $key ) ? 'var_' . $key : $key;

				set_query_var( $key, $arg );
			}
		}

		return WP_Plugin_Update_Server::locate_admin_template( $template_name, $load, $require_once );
	}
}
