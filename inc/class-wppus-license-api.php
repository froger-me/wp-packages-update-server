<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_License_API {

	protected $license_server;
	protected $http_response_code = null;

	protected static $doing_update_api_request = null;
	protected static $static_license_server;

	public function __construct( $init_hooks = false, $local_request = false ) {

		if ( get_option( 'wppus_use_licenses' ) ) {

			if ( $local_request ) {
				require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-license-server.php';

				$this->init_server();
			}

			if ( $init_hooks ) {
				require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-license-server.php';
				require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-crypto.php';

				if ( ! self::is_doing_api_request() ) {
					add_action( 'init', array( $this, 'add_endpoints' ), -99, 0 );
				}

				add_action( 'parse_request', array( $this, 'parse_request' ), -99, 0 );

				add_filter( 'query_vars', array( $this, 'addquery_variables' ), -99, 1 );
			}
		}
	}

	public static function is_doing_api_request() {

		if ( null === self::$doing_update_api_request ) {
			self::$doing_update_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], 'wppus-license-api' ) );
		}

		return self::$doing_update_api_request;
	}

	public static function get_config() {
		$config = array();

		$config['private_api_auth_key']     = get_option( 'wppus_license_private_api_auth_key' );
		$config['licenses_hmac_key']        = get_option( 'wppus_license_hmac_key', 'hmac' );
		$config['licenses_crypto_key']      = get_option( 'wppus_license_crypto_key', 'crypto' );
		$config['licenses_check_signature'] = get_option( 'wppus_license_check_signature', 1 );

		return apply_filters( 'wppus_license_api_config', $config );
	}

	public static function local_request( $action, $payload ) {
		$api    = new self( false, true );
		$config = self::get_config();

		if ( 'browse' === $action ) {
			$payload = wp_json_encode( $payload );
		}

		if ( method_exists( $api, $action ) ) {
			$return = $api->$action( $payload, $config['private_api_auth_key'] );
		} else {
			$return = array(
				'message' => __( 'License API action not found.', 'wppus' ),
			);
		}

		return $return;
	}

	public function add_endpoints() {
		add_rewrite_rule( '^wppus-license-api/*$', 'index.php?$matches[1]&__wppus_license_api=1&', 'top' );
		add_rewrite_rule( '^wppus-license-api$', 'index.php?&__wppus_license_api=1&', 'top' );
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wppus_license_api'] ) ) {
			$this->handle_api_request();
		}
	}

	public function addquery_variables( $query_variables ) {
		$query_variables = array_merge(
			$query_variables,
			array(
				'__wppus_license_api',
				'action',
				'api_auth_key',
				'browse_query',
			),
			array_keys( WPPUS_License_Server::$license_definition )
		);

		return $query_variables;
	}

	public function browse( $browse_query, $key ) {

		if ( $this->authorize( $key, 'private' ) ) {
			$payload = json_decode( wp_unslash( $browse_query ), true );

			switch ( json_last_error() ) {
				case JSON_ERROR_NONE:
					$result = $this->license_server->browse_licenses( $payload );
					break;
				case JSON_ERROR_DEPTH:
					$result = 'JSON parse error - Maximum stack depth exceeded';
					break;
				case JSON_ERROR_STATE_MISMATCH:
					$result = 'JSON parse error - Underflow or the modes mismatch';
					break;
				case JSON_ERROR_CTRL_CHAR:
					$result = 'JSON parse error - Unexpected control character found';
					break;
				case JSON_ERROR_SYNTAX:
					$result = 'JSON parse error - Syntax error, malformed JSON';
					break;
				case JSON_ERROR_UTF8:
					$result = 'JSON parse error - Malformed UTF-8 characters, possibly incorrectly encoded';
					break;
				default:
					$result = 'JSON parse error - Unknown error';
					break;
			}

			if ( ! is_array( $result ) ) {
				$result = array( $result );
			}
		} else {
			$result = $this->get_unauthorized_access_response();
		}

		return $result;
	}

	public function read( $license_data, $key ) {

		if ( $this->authorize( $key, 'private' ) ) {
			$result = $this->license_server->read_license( $license_data );
		} else {
			$result = $this->get_unauthorized_access_response();
		}

		return $result;
	}

	public function edit( $license_data, $key ) {

		if ( $this->authorize( $key, 'private' ) ) {
			$result = $this->license_server->edit_license( $license_data );
		} else {
			$result = $this->get_unauthorized_access_response();
		}

		return $result;
	}

	public function add( $license_data, $key ) {

		if ( $this->authorize( $key, 'private' ) ) {
			$result          = $this->license_server->add_license( $license_data );
			$result->result  = 'success';
			$result->message = 'License successfully created';
			$result->key     = $result->license_key;
		} else {
			$result = $this->get_unauthorized_access_response();
		}

		return $result;
	}

	public function delete( $license_data, $key ) {

		if ( $this->authorize( $key, 'private' ) ) {
			$result = $this->license_server->delete_license( $license_data );
		} else {
			$result = $this->get_unauthorized_access_response();
		}

		return $result;
	}

	public function check( $license_data, $key ) {
		$license_data = apply_filters( 'wppus_check_license_dirty_payload', $license_data );

		if ( isset( $license_data['id'] ) ) {
			unset( $license_data['id'] );
		}

		$result = $this->license_server->read_license( $license_data );

		if ( is_object( $result ) ) {
			$result = json_decode( wp_json_encode( $result ), true );
		} else {
			$result = array();
		}

		$result['license_key'] = isset( $license_data['license_key'] ) ? $license_data['license_key'] : false;
		$result                = apply_filters( 'wppus_check_license_result', $result, $license_data );

		do_action( 'wppus_did_check_license', $result );

		return $result;
	}

	public function activate( $license_data, $key = null ) {
		$license      = null;
		$license_data = apply_filters( 'wppus_activate_license_dirty_payload', $license_data );

		if ( isset( $license_data['id'] ) ) {
			unset( $license_data['id'] );
		}

		$license = $this->license_server->read_license( $license_data );
		$result  = array();

		if ( ! isset( $license_data['allowed_domains'] ) ) {
			$license_data['allowed_domains'] = array();
		} elseif ( ! is_array( $license_data['allowed_domains'] ) ) {
			$license_data['allowed_domains'] = array( $license_data['allowed_domains'] );
		} else {
			$license_data['allowed_domains'] = array( reset( $license_data['allowed_domains'] ) );
		}

		if ( is_object( $license ) && ! empty( $license_data['allowed_domains'] ) ) {
			$domain_count = count( $license_data['allowed_domains'] ) + count( $license->allowed_domains );

			if ( 'expired' === $license->status || 'blocked' === $license->status ) {
				$result['status'] = $license->status;
			} elseif ( ! empty( array_intersect( $license_data['allowed_domains'], $license->allowed_domains ) ) ) {
				$result['allowed_domains'] = $license_data['allowed_domains'];
			} elseif ( $domain_count > absint( $license->max_allowed_domains ) ) {
				$result['max_allowed_domains'] = $license->max_allowed_domains;
			}

			if ( empty( $result ) ) {
				$payload                   = array(
					'id'              => $license->id,
					'status'          => 'activated',
					'allowed_domains' => array_merge( $license_data['allowed_domains'], $license->allowed_domains ),
				);
				$result                    = $this->license_server->edit_license(
					apply_filters( 'wppus_activate_license_payload', $payload )
				);
				$result->license_signature = $this->license_server->generate_license_signature( $license, reset( $license_data['allowed_domains'] ) );
			}
		} else {
			$result['license_key'] = isset( $license_data['license_key'] ) ? $license_data['license_key'] : false;
		}

		$result = apply_filters( 'wppus_activate_license_result', $result, $license_data, $license );

		do_action( 'wppus_did_activate_license', $result );

		return $result;
	}

	public function deactivate( $license_data, $key = null ) {
		$license      = null;
		$license_data = apply_filters( 'wppus_deactivate_license_dirty_payload', $license_data );

		if ( isset( $license_data['id'] ) ) {
			unset( $license_data['id'] );
		}

		$license = $this->license_server->read_license( $license_data );
		$result  = array();

		if ( ! isset( $license_data['allowed_domains'] ) ) {
			$license_data['allowed_domains'] = array();
		} elseif ( ! is_array( $license_data['allowed_domains'] ) ) {
			$license_data['allowed_domains'] = array( $license_data['allowed_domains'] );
		}

		if ( is_object( $license ) && ! empty( $license_data['allowed_domains'] ) ) {

			if ( 'expired' === $license->status ) {
				$result['status']      = $license->status;
				$result['date_expiry'] = $license->date_expiry;
			} elseif ( 'blocked' === $license->status ) {
				$result['status'] = $license->status;
			} elseif (
					'deactivated' === $license->status ||
					empty( array_intersect( $license_data['allowed_domains'], $license->allowed_domains ) )
			) {
				$result['allowed_domains'] = $license_data['allowed_domains'];
			}

			if ( empty( $result ) ) {
				$allowed_domains = array_diff( $license->allowed_domains, $license_data['allowed_domains'] );
				$payload         = array(
					'id'              => $license->id,
					'status'          => empty( $allowed_domains ) ? 'deactivated' : $license->status,
					'allowed_domains' => $allowed_domains,
				);
				$result          = $this->license_server->edit_license(
					apply_filters( 'wppus_deactivate_license_payload', $payload )
				);
			}
		} else {
			$result['license_key'] = isset( $license_data['license_key'] ) ? $license_data['license_key'] : false;
		}

		$result = apply_filters( 'wppus_deactivate_license_result', $result, $license_data, $license );

		do_action( 'wppus_did_deactivate_license', $result );

		return $result;
	}

	protected function authorize( $key ) {
		$config  = self::get_config();
		$is_auth = $config['private_api_auth_key'] === $key;

		return $is_auth;
	}

	protected function action_not_found_response() {
		$this->http_response_code = 400;

		return array(
			'message' => __( 'License API action not found.', 'wppus' ),
		);
	}

	protected function malformed_request_response() {
		$this->http_response_code = 400;

		return array(
			'message' => __( 'Malformed request.', 'wppus' ),
		);
	}

	protected function get_unauthorized_access_response() {
		$this->http_response_code = 403;

		$result = array(
			'message' => __( 'Unauthorized access - check the provided API key', 'wppus' ),
		);

		return $result;
	}

	protected function handle_api_request() {
		global $wp;

		if ( isset( $wp->query_vars['action'] ) ) {
			$malformed_request = false;

			if ( 'browse' === $wp->query_vars['action'] ) {

				if ( isset( $wp->query_vars['browse_query'] ) ) {
					$payload = $wp->query_vars['browse_query'];
				} else {
					$malformed_request = true;
				}
			} else {
				$payload = $wp->query_vars;
			}

			if ( ! $malformed_request ) {
				$key    = isset( $wp->query_vars['api_auth_key'] ) ? $wp->query_vars['api_auth_key'] : false;
				$method = $wp->query_vars['action'];

				$this->init_server();

				if ( method_exists( $this, $method ) ) {
					$response = $this->$method( $payload, $key );
				} else {
					$response = $this->action_not_found_response();
				}

				if ( 403 !== $this->http_response_code && ! is_object( $response ) ) {
					$this->http_response_code = 400;
				}
			} else {
				$this->http_response_code = 400;

				$response = $this->malformed_request_response();
			}

			$this->license_server->dispatch( $response, $this->http_response_code );
		}
	}

	protected function init_server() {
		$this->license_server = apply_filters( 'wppus_license_server', new WPPUS_License_Server() );
	}
}
