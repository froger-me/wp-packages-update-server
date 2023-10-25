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
				require_once WPPUS_PLUGIN_PATH . 'lib/crypto-url/crypto-url.class.php';

				if ( ! self::is_doing_api_request() ) {
					add_action( 'init', array( $this, 'add_endpoints' ), -99, 0 );
				}

				add_action( 'parse_request', array( $this, 'parse_request' ), -99, 0 );
				add_action( 'slm_api_listener_slm_create_new', array( $this, 'deprecated_slm_convert' ), 10, 0 ); // @todo remove in 2.0
				add_action( 'slm_api_listener_slm_activate', array( $this, 'deprecated_slm_convert' ), 10, 0 );   // @todo remove in 2.0
				add_action( 'slm_api_listener_slm_deactivate', array( $this, 'deprecated_slm_convert' ), 10, 0 ); // @todo remove in 2.0
				add_action( 'slm_api_listener_slm_check', array( $this, 'deprecated_slm_convert' ), 10, 0 );      // @todo remove in 2.0

				add_filter( 'query_vars', array( $this, 'addquery_variables' ), -99, 1 );
				add_filter( 'query_vars', array( $this, 'addquery_variables_deprecated' ), -99, 1 ); // @todo remove in 2.0
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
			$return = call_user_func_array( array( $api, $action ), array( $payload, $config['private_api_auth_key'] ) );
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

		$this->deprecated_slm_convert( false ); // @todo remove in 2.0

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

	// @todo remove in 2.0
	public function addquery_variables_deprecated( $query_variables ) {
		$query_variables = array_merge( $query_variables, array(
			'slm_action',
			'lic_status',
			'first_name',
			'last_name',
			'product_ref',
			'secret_key',
			'registered_domain',
		) );

		return $query_variables;
	}

	// @todo remove in 2.0
	public function deprecated_slm_convert( $hooked = true ) {
		global $wp;
		$owner_name = array();

		if ( $hooked ) {
			remove_action( 'parse_request', array( $this, 'parse_request' ), -99 );
			$wp->parse_request();
		}

		if ( isset( $wp->query_vars['slm_action'] ) ) {

			error_log( __METHOD__ . ': Software License Manager plugin integration has been deprecated and will be permanently discontinued from version 2.0. Make sure to include the latest version of the wp-package-updater library and update the code in the distributed plugins and themes before then.' ); // @codingStandardsIgnoreLine

			if ( 'slm_create_new' === $wp->query_vars['slm_action'] ) {
				$wp->set_query_var( 'action', 'add' );
			} else {
				$wp->set_query_var( 'action', str_replace( 'slm_', '', $wp->query_vars['slm_action'] ) );
			}

			if ( isset( $wp->query_vars['lic_status'] ) ) {
				$wp->set_query_var( 'status', $wp->query_vars['lic_status'] );
			}

			if ( isset( $wp->query_vars['registered_domain'] ) ) {
				$wp->set_query_var( 'allowed_domains', array( $wp->query_vars['registered_domain'] ) );
			}

			if ( isset( $wp->query_vars['first_name'] ) ) {
				$owner_name[] = $wp->query_vars['first_name'];
			}

			if ( isset( $wp->query_vars['last_name'] ) ) {
				$owner_name[] = $wp->query_vars['last_name'];
			}

			if ( isset( $wp->query_vars['product_ref'] ) ) {
				$ref_parts = explode( '/', $wp->query_vars['product_ref'] );

				$wp->set_query_var( 'package_slug', reset( $ref_parts ) );
				$wp->set_query_var( 'package_type', ( 'functions.php' === end( $ref_parts ) ) ? 'theme' : 'plugin' );
			}

			if ( ! empty( $owner_name ) ) {
				$wp->set_query_var( 'owner_name', implode( ' ', $owner_name ) );
			}

			if ( isset( $wp->query_vars['secret_key'] ) ) {
				$wp->set_query_var( 'api_auth_key', $wp->query_vars['secret_key'] );
			}

			$wp->set_query_var( '__wppus_license_api', 1 );
		}

		if ( $hooked ) {
			$this->parse_request();
		}
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
			$result = $this->get_unauthorized_access_response( $type /* @todo remove in 2.0 */ );
		}

		return $result;
	}

	public function read( $license_data, $key ) {

		if ( $this->authorize( $key, 'private' ) ) {
			$result = $this->license_server->read_license( $license_data );
		} else {
			$result = $this->get_unauthorized_access_response( $type /* @todo remove in 2.0 */ );
		}

		return $result;
	}

	public function edit( $license_data, $key ) {

		if ( $this->authorize( $key, 'private' ) ) {
			$result = $this->license_server->edit_license( $license_data );
		} else {
			$result = $this->get_unauthorized_access_response( $type /* @todo remove in 2.0 */ );
		}

		return $result;
	}

	public function add( $license_data, $key ) {

		if ( $this->authorize( $key, 'private' ) ) {
			$result = $this->license_server->add_license( $license_data );

			// @todo remove in 2.0
			if ( ! is_object( $result ) ) {
				$result['result']     = 'error';
				$result['message']    = 'License creation failed';
				$result['error_code'] = 10;
			} else {
				$result->result  = 'success';
				$result->message = 'License successfully created';
				$result->key     = $result->license_key;
			}
		} else {
			$result = $this->get_unauthorized_access_response( $type /* @todo remove in 2.0 */ );
		}

		return $result;
	}

	public function delete( $license_data, $key ) {

		if ( $this->authorize( $key, 'private' ) ) {
			$result = $this->license_server->delete_license( $license_data );
		} else {
			$result = $this->get_unauthorized_access_response( $type /* @todo remove in 2.0 */ );
		}

		return $result;
	}

	public function check( $license_data, $key ) {
		$license_data = apply_filters( 'wppus_check_license_dirty_payload', $license_data );

		if ( isset( $license_data['id'] ) ) {
			unset( $license_data['id'] );
		}

		$result = $this->license_server->read_license( $license_data );

		if ( ! is_object( $result ) ) {
			$result['license_key'] = isset( $license_data['license_key'] ) ? $license_data['license_key'] : false;
			$result['result']      = 'error';                        // @todo remove in 2.0
			$result['message']     = 'Invalid license information.'; // @todo remove in 2.0
			$result['error_code']  = 60;                             // @todo remove in 2.0
		} else {                                                     // @todo remove in 2.0
			$name_parts                 = explode( ' ', $result->owner_name );
			$first_name                 = array_shift( $name_parts );
			$last_name                  = implode( ' ', $name_parts );
			$result->result             = 'success';
			$result->first_name         = $first_name;
			$result->last_name          = $last_name;
			$result->registered_domains = $result->allowed_domains;
			$result->message            = 'License key details retrieved.';
			$result->product_ref        = ( 'theme' === $result->package_type ) ? $result->package_slug . '/functions.php' : $result->package_slug . '/' . $result->package_slug . '.php';
		}

		$result = apply_filters( 'wppus_check_license_result', $result, $license_data );

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

			if ( 'expired' === $license->status ) {
				$result['status']     = $license->status;
				$result['result']     = 'error';                    // @todo remove in 2.0
				$result['message']    = 'The license has expired.'; // @todo remove in 2.0
				$result['error_code'] = 30;                         // @todo remove in 2.0
			} elseif ( 'blocked' === $license->status ) {
				$result['status']     = $license->status;
				$result['result']     = 'error';                   // @todo remove in 2.0
				$result['message']    = 'The license is blocked.'; // @todo remove in 2.0
				$result['error_code'] = 20;                        // @todo remove in 2.0
			} elseif ( ! empty( array_intersect( $license_data['allowed_domains'], $license->allowed_domains ) ) ) {
				$result['allowed_domains'] = $license_data['allowed_domains'];
				$result['result']          = 'error';                                                                   // @todo remove in 2.0
				$result['message']         = 'The license is already in use for at least one of the provided domains.'; // @todo remove in 2.0
				$result['error_code']      = 40;                                                                        // @todo remove in 2.0
			} elseif ( ( count( $license_data['allowed_domains'] ) + count( $license->allowed_domains ) ) > absint( $license->max_allowed_domains ) ) {
				$result['max_allowed_domains'] = $license->max_allowed_domains;
				$result['result']              = 'error';                                                           // @todo remove in 2.0
				$result['message']             = 'The license cannot be activated for that many provided domains.'; // @todo remove in 2.0
				$result['error_code']          = 110;                                                               // @todo remove in 2.0
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

				// @todo remove in 2.0
				if ( is_object( $result ) ) {
					$result->result  = 'success';
					$result->message = 'License activated.';
				}
			}
		} else {
			$result['license_key'] = isset( $license_data['license_key'] ) ? $license_data['license_key'] : false;
			$result['result']      = 'error';                        // @todo remove in 2.0
			$result['message']     = 'Invalid license information.'; // @todo remove in 2.0
			$result['error_code']  = 60;                             // @todo remove in 2.0
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
				$result['result']      = 'error';                    // @todo remove in 2.0
				$result['message']     = 'The license has expired.'; // @todo remove in 2.0
				$result['error_code']  = 30;                         // @todo remove in 2.0
			} elseif ( 'blocked' === $license->status ) {
				$result['status']     = $license->status;
				$result['result']     = 'error';                   // @todo remove in 2.0
				$result['message']    = 'The license is blocked.'; // @todo remove in 2.0
				$result['error_code'] = 20;                        // @todo remove in 2.0
			} elseif (
					'deactivated' === $license->status ||
					empty( array_intersect( $license_data['allowed_domains'], $license->allowed_domains ) )
				) {
				$result['allowed_domains'] = $license_data['allowed_domains'];
				$result['result']          = 'error';                                                           // @todo remove in 2.0
				$result['message']         = 'The license is already deactivated for all the provided domains.'; // @todo remove in 2.0
				$result['error_code']      = 40;                                                                // @todo remove in 2.0
			}

			if ( empty( $result ) ) {
				$payload = array(
					'id'              => $license->id,
					'status'          => empty( $license->allowed_domains ) ? 'deactivated' : $license->status,
					'allowed_domains' => array_diff( $license->allowed_domains, $license_data['allowed_domains'] ),
				);
				$result  = $this->license_server->edit_license(
					apply_filters( 'wppus_deactivate_license_payload', $payload )
				);

				// @todo remove in 2.0
				if ( is_object( $result ) ) {
					$result->result  = 'success';
					$result->message = 'License deactivated.';
				}
			}
		} else {
			$result['license_key'] = isset( $license_data['license_key'] ) ? $license_data['license_key'] : false;
			$result['result']      = 'error';                        // @todo remove in 2.0
			$result['message']     = 'Invalid license information.'; // @todo remove in 2.0
			$result['error_code']  = 60;                             // @todo remove in 2.0
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

		// @todo remove in 2.0
		$result['result']     = 'error';
		$result['error_code'] = 100;

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
					$response = call_user_func_array( array( $this, $method ), array( $payload, $key ) );
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
