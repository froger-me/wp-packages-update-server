<?php
require_once ABSPATH . 'wp-admin/includes/file.php';
global $wppus_functions_to_test_params, $wppus_functions_to_test, $wppus_actions_to_test, $wppus_filters_to_test, $wppus_output_log, $wppus_tests_show_queries_details, $wppus_tests_show_scripts_details;

// $wppus_output_log                 = 'serverlog';
// $wppus_output_log                 = 'filelog';
// $wppus_tests_show_queries_details = true;
// $wppus_tests_show_scripts_details = true;

$wppus_functions_to_test_params = array(
	'test_plugin_slug'                => 'dummy-plugin',
	'test_theme_slug'                 => 'dummy-theme',
	'test_package_slug'               => 'dummy-plugin',
	'test_package_path'               => null,
	'test_browse_licenses_payload'    => array(
		'relationship' => 'AND',
		'limit'        => 10,
		'offset'       => 0,
		'order_by'     => 'date_created',
		'criteria'     => array(
			array(
				'field'    => 'email',
				'value'    => 'test@%',
				'operator' => 'LIKE',
			),
			array(
				'field'    => 'license_key',
				'value'    => 'test-license',
				'operator' => '=',
			),
		),
	),
	'test_add_license_payload'        => array(
		'license_key'         => 'test-license',
		'status'              => 'blocked',
		'max_allowed_domains' => '3',
		'allowed_domains'     => array(
			'test.test.com',
			'test2.test.com',
		),
		'owner_name'          => 'Test Owner',
		'email'               => 'test@test.com',
		'company_name'        => 'Test Company',
		'txn_id'              => '1111-test-license',
		'date_created'        => '2018-07-09',
		'date_expiry'         => '2099-07-09',
		'date_renewed'        => '2098-07-09',
		'package_slug'        => 'test-license-create',
		'package_type'        => 'plugin',
	),
	'test_read_license_payload'       => array( 'license_key' => 'test-license' ),
	'test_edit_license_payload'       => array(
		'license_key' => 'test-license',
		'status'      => 'pending',
	),
	'test_check_license_payload'      => array( 'license_key' => 'test-license' ),
	'test_activate_license_payload'   => array(
		'license_key'     => 'test-license',
		'allowed_domains' => 'test3.test.com',
	),
	'test_deactivate_license_payload' => array(
		'license_key'     => 'test-license',
		'allowed_domains' => 'test3.test.com',
	),
	'test_delete_license_payload'     => array( 'license_key' => 'test-license' ),
);
$wppus_functions_to_test        = array(
	// /** Functions **/                       /** Parameters **/
	// /** Plugin Data functions **/
	// 'wppus_get_root_data_dir'               => array(),
	// 'wppus_get_packages_data_dir'           => array(),
	// 'wppus_get_logs_data_dir'               => array(),
	// 'wppus_force_cleanup_cache'             => array(),
	// 'wppus_force_cleanup_logs'              => array(),
	// 'wppus_force_cleanup_tmp'               => array(),
	// /** Update Server functions **/
	// 'wppus_is_doing_update_api_request'     => array(),
	// 'wppus_check_remote_theme_update'       => array( $wppus_functions_to_test_params['test_theme_slug'] ),
	// 'wppus_download_remote_theme_to_local'  => array( $wppus_functions_to_test_params['test_theme_slug'] ),
	// 'wppus_check_remote_plugin_update'      => array( $wppus_functions_to_test_params['test_plugin_slug'] ),
	// 'wppus_download_remote_plugin_to_local' => array( $wppus_functions_to_test_params['test_plugin_slug'] ),
	// 'wppus_get_local_package_path'          => array( $wppus_functions_to_test_params['test_package_slug'] ),
	// 'wppus_download_local_package'          => array( $wppus_functions_to_test_params['test_package_slug'], $wppus_functions_to_test_params['test_package_path'] ),
	// /** Licenses functions **/
	// 'wppus_is_doing_license_api_request'    => array(),
	// 'wppus_add_license'                     => array( $wppus_functions_to_test_params['test_add_license_payload'] ),
	// 'wppus_check_license'                   => array( $wppus_functions_to_test_params['test_check_license_payload'] ),
	// 'wppus_read_license'                    => array( $wppus_functions_to_test_params['test_read_license_payload'] ),
	// 'wppus_edit_license'                    => array( $wppus_functions_to_test_params['test_edit_license_payload'] ),
	// 'wppus_browse_licenses'                 => array( $wppus_functions_to_test_params['test_browse_licenses_payload'] ),
	// 'wppus_activate_license'                => array( $wppus_functions_to_test_params['test_activate_license_payload'] ),
	// 'wppus_deactivate_license'              => array( $wppus_functions_to_test_params['test_deactivate_license_payload'] ),
	// 'wppus_delete_license'                  => array( $wppus_functions_to_test_params['test_delete_license_payload'] ),
);

$wppus_actions_to_test = array(
	// /** Actions **/                                      /** Parameters **/
	// /** Plugin Data actions **/
	// 'wppus_scheduled_cleanup_event'                      => 6, // bool $result, string $type, int $timestamp, string $frequency, string $hook, array $params
	// 'wppus_registered_cleanup_schedule'                  => 2, // string $type, array $params
	// 'wppus_cleared_cleanup_schedule'                     => 2, // string $type, array $params
	// 'wppus_did_cleanup'                                  => 4, // bool $result, string $type, int $size, bool $force
	// /** Update Server actions **/
	// 'wppus_primed_package_from_remote'                   => 2, // bool $result, string $slug
	// 'wppus_scheduled_check_remote_event'                 => 6, // bool $result, string $slug, int $timestamp, string $frequency, string $hook, array $params
	// 'wppus_registered_check_remote_schedule'             => 3, // string $slug, string $scheduled_hook, string $action_hook
	// 'wppus_cleared_check_remote_schedule'                => 3, // string $slug, string $scheduled_hook, array $params
	// 'wppus_downloaded_remote_package'                    => 3, // mixed $package, string $type, string $slug
	// 'wppus_saved_remote_package_to_local'                => 3, // bool $result, string $type, string $slug
	// 'wppus_checked_remote_package_update'                => 3, // bool $has_update, string $type, string $slug
	// 'wppus_did_manual_upload_package'                    => 3, // bool $result, string $type, string $slug
	// 'wppus_before_packages_download'                     => 3, // string $archive_name, string $archive_path, array $package_slugs
	// 'wppus_triggered_package_download'                   => 2, // string $archive_name, string $archive_path
	// 'wppus_before_handle_update_request'                 => 1, // array $request_params
	// 'wppus_deleted_package'                              => 3, // bool $result, string $type, string $slug
	// 'wppus_registered_renew_download_url_token_schedule' => 1, // array $scheduled_hook
	// 'wppus_cleared_renew_download_url_token_schedule'    => 1, // array $scheduled_hook
	// 'wppus_scheduled_renew_download_url_token_event'     => 4, // bool $result, int $timestamp, string $frequency, string $hook
	// 'wppus_before_remote_package_zip'                    => 3, // string $package_slug, string $files_path, string $archive_path
	// /** Licenses actions **/
	// 'wppus_added_license_check'                          => 1, // string $package_slug
	// 'wppus_removed_license_check'                        => 1, // string $package_slug
	// 'wppus_registered_license_schedule'                  => 1, // array $scheduled_hook
	// 'wppus_cleared_license_schedule'                     => 1, // array $scheduled_hook
	// 'wppus_scheduled_license_event'                      => 4, // bool $result, int $timestamp, string $frequency, string $hook
	// 'wppus_browse_licenses'                              => 1, // array $payload
	// 'wppus_did_browse_licenses'                          => 1, // stdClass $license
	// 'wppus_did_read_license'                             => 1, // stdClass $license
	// 'wppus_did_edit_license'                             => 1, // stdClass $license
	// 'wppus_did_add_license'                              => 1, // stdClass $license
	// 'wppus_did_delete_license'                           => 1, // stdClass $license
	// 'wppus_did_check_license'                            => 1, // mixed $result
	// 'wppus_did_activate_license'                         => 1, // mixed $result
	// 'wppus_did_deactivate_license'                       => 1, // mixed $result
);

$wppus_filters_to_test = array(
	// /** Filters **/                                     /** Parameters **/
	// /** Plugin Data filters **/
	// 'wppus_submitted_data_config'                       => 1, // array $config
	// 'wppus_schedule_cleanup_frequency'                  => 2, // string $frequency, string $type
	// /** Update Server filters **/
	// 'wppus_update_server'                               => 4, // mixed $update_server, array $config, string $slug, bool $use_license
	// 'wppus_handle_update_request_params'                => 1, // array $params
	// 'wppus_update_checker'                              => 8, // mixed $update_checker, string $slug, string $type, string $package_file_name, string $repository_service_url, string $repository_branch, mixed $repository_credentials, bool $repository_service_self_hosted
	// 'wppus_update_api_config'                           => 1, // array $config
	// 'wppus_submitted_remote_sources_config'             => 1, // array $config
	// 'wppus_check_remote_frequency'                      => 2, // string $frequency, string $slug
	// 'wppus_schedule_renew_download_url_token_frequency' => 1, // string $frequency
	// /** Licenses filters **/
	// 'wppus_license_valid'                               => 2, // bool $isValid, mixed $license, string $license_signature
	// 'wppus_license_server'                              => 1, // mixed $license_server
	// 'wppus_license_api_config'                          => 1, // array $config
	// 'wppus_licensed_package_slugs'                      => 1, // array $package_slugs
	// 'wppus_submitted_licenses_config'                   => 1, // array $config
	// 'wppus_check_license_result'                        => 2, // mixed $result, array $license_data
	// 'wppus_activate_license_result'                     => 3, // mixed $result, array $license_data, mixed $license
	// 'wppus_deactivate_license_result'                   => 3, // mixed $result, array $license_data, mixed $license
	// 'wppus_activate_license_dirty_payload'              => 1, // array $dirty_payload
	// 'wppus_deactivate_license_dirty_payload'            => 1, // array $dirty_payload
	// 'wppus_browse_licenses_payload'                     => 1, // array $payload
	// 'wppus_read_license_payload'                        => 1, // array $payload
	// 'wppus_edit_license_payload'                        => 1, // array $payload
	// 'wppus_add_license_payload'                         => 1, // array $payload
	// 'wppus_delete_license_payload'                      => 1, // array $payload
	// 'wppus_check_license_dirty_payload'                 => 1, // array $payload
	// 'wppus_activate_license_payload'                    => 1, // array $payload
	// 'wppus_deactivate_license_payload'                  => 1, // array $payload
);

if ( ! empty( $wppus_functions_to_test ) && ! has_action( 'plugins_loaded', 'wppus_ready_for_function_tests' ) ) {
	function wppus_ready_for_function_tests() {
		wppus_run_tests( 'functions' );
	}
	add_action( 'plugins_loaded', 'wppus_ready_for_function_tests', 6 );
}

if ( ! empty( $wppus_actions_to_test ) && ! has_action( 'init', 'wppus_ready_for_action_tests' ) ) {
	function wppus_ready_for_action_tests() {
		wppus_run_tests( 'actions' );
	}
	add_action( 'init', 'wppus_ready_for_action_tests', 10 );
}

if ( ! empty( $wppus_filters_to_test ) && ! has_filter( 'init', 'wppus_ready_for_filter_tests' ) ) {
	function wppus_ready_for_filter_tests() {
		wppus_run_tests( 'filters' );
	}
	add_action( 'init', 'wppus_ready_for_filter_tests', 10 );
}

function wppus_run_tests( $test ) {

	if ( wp_doing_ajax() ) {

		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once WPPUS_PLUGIN_PATH . 'functions.php';
	global $wppus_functions_to_test_params, $wppus_functions_to_test, $wppus_actions_to_test, $wppus_filters_to_test;

	if ( 'functions' === $test ) {
		wppus_functions_test_func( $wppus_functions_to_test, $wppus_functions_to_test_params );
	}

	if ( 'actions' === $test ) {

		if ( ! empty( $wppus_actions_to_test ) ) {

			foreach ( $wppus_actions_to_test as $action => $num_params ) {
				add_action( $action, 'wppus_action_test_hook', 10, $num_params );
			}
		}
	}

	if ( 'filters' === $test ) {

		if ( ! empty( $wppus_filters_to_test ) ) {

			foreach ( $wppus_filters_to_test as $filter => $num_params ) {
				add_action( $filter, 'wppus_filter_test_hook', 10, $num_params );
			}
		}
	}
}

function wppus_tests_log( $message, $array_or_object = null ) {
	global $wppus_output_log;

	date_default_timezone_set( @date_default_timezone_get() ); // @codingStandardsIgnoreLine

	$line = date( '[Y-m-d H:i:s O]' ) . ' ' . $message;

	if ( 'serverlog' === $wppus_output_log ) {
		error_log( $line ); // @codingStandardsIgnoreLine

		if ( null !== $array_or_object ) {
			error_log( print_r( $array_or_object, true ) ); // @codingStandardsIgnoreLine
		}
	}

	if ( 'filelog' === $wppus_output_log ) {
		$log_file = WPPUS_Data_Manager::get_data_dir( 'logs' ) . 'tests.log';
		$handle   = fopen( $log_file, 'a' ); // @codingStandardsIgnoreLine

		if ( $handle && flock( $handle, LOCK_EX ) ) {
			$line .= "\n";

			fwrite( $handle, $line ); // @codingStandardsIgnoreLine

			if ( null !== $array_or_object ) {
				fwrite( $handle, print_r( $array_or_object, true ) ); // @codingStandardsIgnoreLine
			}

			flock( $handle, LOCK_UN );
		}

		if ( $handle ) {
			fclose( $handle ); // @codingStandardsIgnoreLine
		}
	}
}

function wppus_functions_test_func( $functions, $test_params ) {
	$start_message            = '========================================================';
	$header_delimiter_message = '--------------------------------------------------------';
	$delimiter_message        = '------';

	if ( ! empty( $functions ) ) {
		wppus_tests_log( $start_message );
		wppus_tests_log( 'Start functions test with the following parameters: ', $test_params );
		wppus_tests_log( $header_delimiter_message );

		foreach ( $functions as $function_name => $params ) {
			$message = $function_name . ' called with params: ';

			wppus_tests_log( $message, $params );

			$result = call_user_func_array( $function_name, $params );

			if ( ! is_array( $result ) ) {
				$result = array( $result );
			}

			$message = 'Result: ';

			wppus_tests_log( $message, $result );
			wppus_tests_log( $delimiter_message );
		}

		wppus_tests_log( '--- End functions test ---' );
	}
}

function wppus_action_test_hook() {
	$start_message = '========================================================';
	$message       = current_filter() . ' called with params: ';

	wppus_tests_log( $start_message );
	wppus_tests_log( '--- Start ' . current_filter() . ' action test ---' );
	wppus_tests_log( $message, func_get_args() );
	wppus_tests_log( '--- End ' . current_filter() . ' action test ---' );
}

function wppus_filter_test_hook() {
	$start_message = '========================================================';
	$message       = current_filter() . ' called with params: ';
	$params        = func_get_args();

	wppus_tests_log( $start_message );
	wppus_tests_log( '--- Start ' . current_filter() . ' filter test ---' );
	wppus_tests_log( $message, $params );
	wppus_tests_log( '--- End ' . current_filter() . ' filter test ---' );

	return reset( $params );
}

function wppus_get_formatted_memory( $bytes, $precision = 2 ) {
	$units  = array( 'B', 'K', 'M', 'G', 'T' );
	$bytes  = max( $bytes, 0 );
	$pow    = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
	$pow    = min( $pow, count( $units ) - 1 );
	$bytes /= ( 1 << ( 10 * $pow ) );

	return round( $bytes, $precision ) . ' ' . $units[ $pow ];
}

function wppus_performance_stats_log() {
	global $wpdb, $wppus_mem_before, $wppus_scripts_before, $wppus_queries_before, $wppus_tests_show_queries_details, $wppus_tests_show_scripts_details;

	$mem_after     = memory_get_peak_usage();
	$query_list    = array();
	$scripts_after = get_included_files();
	$scripts       = array_diff( $scripts_after, $wppus_scripts_before );
	$query_stats   = 'Number of queries executed by the plugin: ' . ( count( $wpdb->queries ) - count( $wppus_queries_before ) );
	$scripts_stats = 'Number of included/required scripts by the plugin: ' . count( $scripts );
	$mem_stats     = 'Server memory used to run the plugin: ' . wppus_get_formatted_memory( $mem_after - $wppus_mem_before ) . ' / ' . ini_get( 'memory_limit' );

	foreach ( $wpdb->queries as $query ) {
		$query_list[] = reset( $query );
	}

	wppus_tests_log( '========================================================' );
	wppus_tests_log( '--- Start load tests ---' );
	wppus_tests_log( 'Time elapsed: ' . timer_stop() );
	wppus_tests_log( 'Total server memory used: ' . wppus_get_formatted_memory( $mem_after ) . ' / ' . ini_get( 'memory_limit' ) );
	wppus_tests_log( 'Total number of queries: ' . count( $wpdb->queries ) );
	wppus_tests_log( 'Total number of scripts: ' . count( $scripts_after ) );
	wppus_tests_log( $mem_stats );
	wppus_tests_log( $query_stats );
	wppus_tests_log( $scripts_stats );

	if ( $wppus_tests_show_queries_details ) {
		wppus_tests_log( 'Queries: ', $query_list );
	}

	if ( $wppus_tests_show_scripts_details ) {
		wppus_tests_log( 'Scripts: ', $scripts );
	}

	wppus_tests_log( '--- End load tests ---' );
}
add_action( 'shutdown', 'wppus_performance_stats_log' );
