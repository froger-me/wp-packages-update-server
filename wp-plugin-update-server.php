<?php
/*
Plugin Name: WP Plugin Update Server
Plugin URI: https://github.com/froger-me/wp-plugin-update-server/
Description: Run your own update server for plugins and themes.
Version: 1.4.16
Author: Alexandre Froger
Author URI: https://froger.me/
Text Domain: wppus
Domain Path: /languages
*/

if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
	global $wpdb, $wppus_mem_before, $wppus_scripts_before, $wppus_queries_before;

	$wppus_mem_before     = memory_get_peak_usage();
	$wppus_scripts_before = get_included_files();
	$wppus_queries_before = $wpdb->queries;
}

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'WPPUS_PLUGIN_PATH' ) ) {
	define( 'WPPUS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WPPUS_PLUGIN_FILE' ) ) {
	define( 'WPPUS_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WPPUS_PLUGIN_URL' ) ) {
	define( 'WPPUS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'WPPUS_MB_TO_B' ) ) {
	define( 'WPPUS_MB_TO_B', 1000000 );
}

if ( ! defined( 'WPPUS_DEFAULT_LOGS_MAX_SIZE' ) ) {
	define( 'WPPUS_DEFAULT_LOGS_MAX_SIZE', 10 );
}

if ( ! defined( 'WPPUS_DEFAULT_CACHE_MAX_SIZE' ) ) {
	define( 'WPPUS_DEFAULT_CACHE_MAX_SIZE', 100 );
}

require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-data-manager.php';
require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-scheduler.php';
require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-update-api.php';
require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-license-api.php';

if ( ! WPPUS_Update_API::is_doing_api_request() && ! WPPUS_License_API::is_doing_api_request() ) {
	require_once WPPUS_PLUGIN_PATH . 'inc/class-wp-plugin-update-server.php';
	require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-remote-sources-manager.php';
	require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-update-manager.php';
	require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-license-manager.php';
	require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-nonce.php';

	register_activation_hook( WPPUS_PLUGIN_FILE, array( 'WP_Plugin_Update_Server', 'activate' ) );
	register_deactivation_hook( WPPUS_PLUGIN_FILE, array( 'WP_Plugin_Update_Server', 'deactivate' ) );
	register_uninstall_hook( WPPUS_PLUGIN_FILE, array( 'WP_Plugin_Update_Server', 'uninstall' ) );
	register_activation_hook( WPPUS_PLUGIN_FILE, array( 'WPPUS_Nonce', 'activate' ) );
	register_deactivation_hook( WPPUS_PLUGIN_FILE, array( 'WPPUS_Nonce', 'deactivate' ) );
	register_uninstall_hook( WPPUS_PLUGIN_FILE, array( 'WPPUS_Nonce', 'uninstall' ) );
}

function wppus_run() {
	wp_cache_add_non_persistent_groups( 'wppus' );

	require_once WPPUS_PLUGIN_PATH . 'functions.php';

	$is_license_api_request = WPPUS_License_API::is_doing_api_request();
	$is_update_api_request  = WPPUS_Update_API::is_doing_api_request();
	$is_api_request         = $is_license_api_request || $is_update_api_request;

	if ( ! $is_license_api_request ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once WPPUS_PLUGIN_PATH . 'lib/wp-update-server/loader.php';
		require_once WPPUS_PLUGIN_PATH . 'lib/wp-update-server-extended/loader.php';
		require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-update-server.php';
		require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-webhook-api.php';
		require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-package-api.php';

		$is_webhook_api_request = WPPUS_Webhook_API::is_doing_api_request();
		$is_package_api_request = WPPUS_Package_API::is_doing_api_request();
		$is_api_request         = $is_api_request ||
			$is_webhook_api_request ||
			$is_package_api_request;
	}

	if ( ! $is_api_request ) {

		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}

		require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-packages-table.php';
		require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-licenses-table.php';
	}

	$objects = array(
		'license_api'            => new WPPUS_License_API( true, false ),
		'update_api'             => ( $is_license_api_request ) ? false : new WPPUS_Update_API( true ),
		'webhook_api'            => ( $is_license_api_request ) ? false : new WPPUS_Webhook_API( true ),
		'package_api'            => ( $is_license_api_request ) ? false : new WPPUS_Package_API( true ),
		'data_manager'           => ( $is_api_request ) ? false : new WPPUS_Data_Manager( true ),
		'remote_sources_manager' => ( $is_api_request ) ? false : new WPPUS_Remote_Sources_Manager( true ),
		'update_manager'         => ( $is_api_request ) ? false : new WPPUS_Update_Manager( true ),
		'license_manager'        => ( $is_api_request ) ? false : new WPPUS_License_Manager( true ),
		'plugin'                 => ( $is_api_request ) ? false : new WP_Plugin_Update_Server( true ),
	);

	WPPUS_Nonce::register();
	WPPUS_Nonce::init_auth(
		get_option( 'wppus_package_private_api_auth_key' ),
		'HTTP_X_WPPUS_PRIVATE_PACKAGE_API_KEY'
	);

	do_action( 'wppus_ready', $objects );
}
add_action( 'plugins_loaded', 'wppus_run', -99, 0 );

if ( ! WPPUS_Update_API::is_doing_api_request() && ! WPPUS_License_API::is_doing_api_request() ) {
	require_once plugin_dir_path( WPPUS_PLUGIN_FILE ) . 'lib/wp-update-migrate/class-wp-update-migrate.php';

	if ( ! wp_doing_ajax() && is_admin() && ! wp_doing_cron() ) {

		add_action(
			'plugins_loaded',
			function() {
				$wppus_update_migrate = WP_Update_Migrate::get_instance( WPPUS_PLUGIN_FILE, 'wppus' );

				if ( false === $wppus_update_migrate->get_result() && '1.2' !== get_option( 'wppus_plugin_version' ) ) {

					if ( false !== has_action( 'plugins_loaded', 'wppus_run' ) ) {
						remove_action( 'plugins_loaded', 'wppus_run', -99 );
					}
				}

			},
			PHP_INT_MIN
		);
	}
}

if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {

	if ( WPPUS_Update_API::is_doing_api_request() || WPPUS_License_API::is_doing_api_request() ) {
		require_once WPPUS_PLUGIN_PATH . 'tests.php';
	}
}
