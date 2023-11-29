<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'php_log' ) ) {
	function php_log( $message = '', $prefix = '' ) {
		$prefix   = $prefix ? ' ' . $prefix . ' => ' : ' => ';
		$trace    = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$caller   = end( $trace );
		$class    = isset( $caller['class'] ) ? $caller['class'] : '';
		$type     = isset( $caller['type'] ) ? $caller['type'] : '';
		$function = isset( $caller['function'] ) ? $caller['function'] : '';
		$context  = $class . $type . $function . $prefix;

		error_log( $context . print_r( $message, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r, WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}

if ( ! function_exists( 'cidr_match' ) ) {
	function cidr_match( $ip, $range ) {
		list ( $subnet, $bits ) = explode( '/', $range );
		$ip                     = ip2long( $ip );
		$subnet                 = ip2long( $subnet );

		if ( ! $ip || ! $subnet || ! $bits ) {
			return false;
		}

		$mask    = -1 << ( 32 - $bits );
		$subnet &= $mask; // in case the supplied subnet was not correctly aligned

		return ( $ip & $mask ) === $subnet;
	}
}

if ( ! function_exists( 'wppus_assets_suffix' ) ) {
	function wppus_assets_suffix() {
		return (bool) ( constant( 'WP_DEBUG' ) ) ? '' : '.min';
	}
}

if ( ! function_exists( 'wppus_is_doing_license_api_request' ) ) {
	function wppus_is_doing_license_api_request() {
		return WPPUS_License_API::is_doing_api_request();
	}
}

if ( ! function_exists( 'wppus_is_doing_update_api_request' ) ) {
	function wppus_is_doing_update_api_request() {
		return WPPUS_Update_API::is_doing_api_request();
	}
}

if ( ! function_exists( 'wppus_is_doing_webhook_api_request' ) ) {
	function wppus_is_doing_webhook_api_request() {
		return WPPUS_Webhook_API::is_doing_api_request();
	}
}

if ( ! function_exists( 'wppus_is_doing_package_api_request' ) ) {
	function wppus_is_doing_package_api_request() {
		return WPPUS_Package_API::is_doing_api_request();
	}
}

if ( ! function_exists( 'wppus_is_doing_api_request' ) ) {
	function wppus_is_doing_api_request() {
		$is_api_request = (
			wppus_is_doing_license_api_request() ||
			wppus_is_doing_update_api_request() ||
			wppus_is_doing_webhook_api_request() ||
			wppus_is_doing_package_api_request()
		);

		return apply_filters( 'wppus_is_api_request', $is_api_request );
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

if ( ! function_exists( 'wppus_download_remote_package' ) ) {
	function wppus_download_remote_package( $slug, $type ) {
		$api = WPPUS_Update_API::get_instance();

		return $api->download_remote_package( $slug, $type, true );
	}
}

if ( ! function_exists( 'wppus_delete_package' ) ) {
	function wppus_delete_package( $slug ) {
		$api = WPPUS_Package_Manager::get_instance();

		return (bool) $api->delete_packages_bulk( array( $slug ) );
	}
}

if ( ! function_exists( 'wppus_get_package_info' ) ) {
	function wppus_get_package_info( $package_slug, $json_encode = true ) {
		require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-package-manager.php';

		$result          = $json_encode ? '{}' : array();
		$package_manager = new WPPUS_Package_Manager();
		$package_info    = $package_manager->get_package_info( $package_slug );

		if ( $package_info ) {
			$result = $json_encode ? wp_json_encode( $package_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) : $package_info;
		}

		return $result;
	}
}

if ( ! function_exists( 'wppus_get_batch_package_info' ) ) {
	function wppus_get_batch_package_info( $search, $json_encode = true ) {
		require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-package-manager.php';

		$result          = $json_encode ? '{}' : array();
		$package_manager = new WPPUS_Package_Manager();
		$package_info    = $package_manager->get_batch_package_info( $search );

		if ( $package_info ) {
			$result = $json_encode ? wp_json_encode( $package_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) : $package_info;
		}

		return $result;
	}
}

if ( ! function_exists( 'wppus_download_local_package' ) ) {
	function wppus_download_local_package( $package_slug, $package_path = null, $exit_or_die = true ) {
		require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-package-manager.php';

		$package_manager = new WPPUS_Package_Manager();

		if ( null === $package_path ) {
			$package_path = wppus_get_local_package_path( $package_slug );
		}

		$package_manager->trigger_packages_download( $package_slug, $package_path, $exit_or_die );
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
	function wppus_get_template( $template_name, $args = array(), $load = true, $require_file = false ) {
		$template_name = apply_filters( 'wppus_get_template_name', $template_name, $args );
		$template_args = apply_filters( 'wppus_get_template_args', $args, $template_name );

		if ( ! empty( $template_args ) ) {

			foreach ( $template_args as $key => $arg ) {
				$key = is_numeric( $key ) ? 'var_' . $key : $key;

				set_query_var( $key, $arg );
			}
		}

		return WP_Packages_Update_Server::locate_template( $template_name, $load, $require_file );
	}
}

if ( ! function_exists( 'wppus_get_admin_template' ) ) {
	function wppus_get_admin_template( $template_name, $args = array(), $load = true, $require_file = false ) {
		$template_name = apply_filters( 'wppus_get_admin_template_name', $template_name, $args );
		$template_args = apply_filters( 'wppus_get_admin_template_args', $args, $template_name );

		if ( ! empty( $template_args ) ) {

			foreach ( $template_args as $key => $arg ) {
				$key = is_numeric( $key ) ? 'var_' . $key : $key;

				set_query_var( $key, $arg );
			}
		}

		return WP_Packages_Update_Server::locate_admin_template( $template_name, $load, $require_file );
	}
}

if ( ! function_exists( 'wppus_init_nonce_auth' ) ) {
	function wppus_init_nonce_auth( $private_auth_key ) {
		require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-nonce.php';

		WPPUS_Nonce::init_auth( $private_auth_key );
	}
}

if ( ! function_exists( 'wppus_create_nonce' ) ) {
	function wppus_create_nonce(
		$true_nonce = true,
		$expiry_length = WPPUS_Nonce::DEFAULT_EXPIRY_LENGTH,
		$data = array(),
		$return_type = WPPUS_Nonce::NONCE_ONLY,
		$store = true
	) {
		require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-nonce.php';

		return WPPUS_Nonce::create_nonce( $true_nonce, $expiry_length, $data, $return_type, $store );
	}
}

if ( ! function_exists( 'wppus_get_nonce_expiry' ) ) {
	function wppus_get_nonce_expiry( $nonce ) {
		return WPPUS_Nonce::get_nonce_expiry( $nonce );
	}
}

if ( ! function_exists( 'wppus_get_nonce_data' ) ) {
	function wppus_get_nonce_data( $nonce ) {
		return WPPUS_Nonce::get_nonce_data( $nonce );
	}
}

if ( ! function_exists( 'wppus_validate_nonce' ) ) {
	function wppus_validate_nonce( $value ) {
		return WPPUS_Nonce::validate_nonce( $value );
	}
}

if ( ! function_exists( 'wppus_delete_nonce' ) ) {
	function wppus_delete_nonce( $value ) {
		return WPPUS_Nonce::delete_nonce( $value );
	}
}

if ( ! function_exists( 'wppus_clear_nonces' ) ) {
	function wppus_clear_nonces() {
		return WPPUS_Nonce::wppus_nonce_cleanup();
	}
}

if ( ! function_exists( 'wppus_build_nonce_api_signature' ) ) {
	function wppus_build_nonce_api_signature( $api_key_id, $api_key, $timestamp, $payload ) {
		unset( $payload['api_signature'] );
		unset( $payload['api_credentials'] );

		( function ( &$arr ) {
			$recur_ksort = function ( &$arr ) use ( &$recur_ksort ) {

				foreach ( $arr as &$value ) {

					if ( is_array( $value ) ) {
						$recur_ksort( $value );
					}
				}

				ksort( $arr );
			};

			$recur_ksort( $arr );
		} )( $payload );

		$str         = base64_encode( $api_key_id . json_encode( $payload, JSON_NUMERIC_CHECK ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.WP.AlternativeFunctions.json_encode_json_encode
		$credentials = $timestamp . '/' . $api_key_id;
		$time_key    = hash_hmac( 'sha256', $timestamp, $api_key, true );
		$signature   = hash_hmac( 'sha256', $str, $time_key );

		return array(
			'credentials' => $credentials,
			'signature'   => $signature,
		);
	}
}

if ( ! function_exists( 'wppus_schedule_webhook' ) ) {
	function wppus_schedule_webhook( $payload, $event_type ) {

		if ( isset( $payload['event'], $payload['content'] ) ) {
			$api = WPPUS_Webhook_API::get_instance();

			return $api->schedule_webhook( $payload, $event_type );
		}

		return new WP_Error(
			__FUNCTION__,
			__( 'The webhook payload must contain an event string and a content.', 'wppus' )
		);
	}
}

if ( ! function_exists( 'wppus_fire_webhook' ) ) {
	function wppus_fire_webhook( $url, $secret, $body, $action ) {

		if (
			filter_var( $url, FILTER_VALIDATE_URL ) &&
			null !== json_decode( $body )
		) {
			$api = WPPUS_Webhook_API::get_instance();

			return $api->fire_webhook( $url, $secret, $body, $action );
		}

		return new WP_Error(
			__FUNCTION__,
			__( '$url must be a valid url and $body must be a JSON string.', 'wppus' )
		);
	}
}
