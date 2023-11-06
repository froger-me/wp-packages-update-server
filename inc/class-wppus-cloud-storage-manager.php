<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Cloud_Storage_Manager {

	protected $cloud_storage;

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {
			if ( ! wppus_is_doing_api_request() ) {
				add_action( 'wp_ajax_wppus_cloud_storage_test', array( $this, 'cloud_storage_test' ), 10, 0 );
			}
		}
	}

	public function cloud_storage_test() {
		$result = false;

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'wppus_plugin_options' ) ) {
			$data = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY );
		}

		if ( $result ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}
}
