<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_License_API {

	protected $license_server;
	protected $http_response_code = null;

	protected static $doing_update_api_request = null;
	protected static $instance;
	protected static $config;

	public function __construct( $init_hooks = false, $local_request = true ) {

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
				add_action( 'wppus_pre_activate_license', array( $this, 'wppus_bypass_did_edit_license_action' ), 10, 0 );
				add_action( 'wppus_did_activate_license', array( $this, 'wppus_did_license_action' ), 10, 2 );
				add_action( 'wppus_pre_deactivate_license', array( $this, 'wppus_bypass_did_edit_license_action' ), 10, 0 );
				add_action( 'wppus_did_deactivate_license', array( $this, 'wppus_did_license_action' ), 10, 2 );
				add_action( 'wppus_did_add_license', array( $this, 'wppus_did_license_action' ), 10, 2 );
				add_action( 'wppus_did_edit_license', array( $this, 'wppus_did_license_action' ), 10, 3 );
				add_action( 'wppus_did_delete_license', array( $this, 'wppus_did_license_action' ), 10, 2 );

				add_filter( 'query_vars', array( $this, 'query_vars' ), -99, 1 );
				add_filter( 'wppus_handle_update_request_params', array( $this, 'wppus_handle_update_request_params' ), 0, 1 );
				add_filter( 'wppus_server_class_name', array( $this, 'wppus_server_class_name' ), 0, 2 );
				add_filter( 'wppus_api_webhook_events', array( $this, 'wppus_api_webhook_events' ), 0, 1 );
			}
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public function wppus_api_webhook_events( $webhook_events ) {

		if ( isset( $webhook_events['license'], $webhook_events['license']['events'] ) ) {
			$webhook_events['license']['events']['license_activate']   = __( 'License activated', 'wppus' );
			$webhook_events['license']['events']['license_deactivate'] = __( 'License deactivated', 'wppus' );
			$webhook_events['license']['events']['license_add']        = __( 'License added', 'wppus' );
			$webhook_events['license']['events']['license_edit']       = __( 'License edited', 'wppus' );
			$webhook_events['license']['events']['license_delete']     = __( 'License deleted', 'wppus' );
			$webhook_events['license']['events']['license_require']    = __( 'License becomes required for a package', 'wppus' );
			$webhook_events['license']['events']['license_unrequire']  = __( 'License becomes not required a for package', 'wppus' );
		}

		return $webhook_events;
	}

	public function wppus_bypass_did_edit_license_action() {
		remove_action( 'wppus_did_edit_license', array( $this, 'wppus_did_license_action' ), 10 );
	}

	public function wppus_did_license_action( $result, $payload, $original = null ) {
		$format = '';
		$event  = 'license_' . str_replace( array( 'wppus_did_', '_license' ), array( '', '' ), current_action() );

		if ( ! is_object( $result ) ) {
			// translators: %s is operation slug
			$description = sprintf( esc_html__( 'An error occured for License operation `%s` on WPPUS.' ), $event );
			$content     = array(
				'error'   => true,
				'result'  => $result,
				'payload' => $payload,
			);
		} else {
			$content = null !== $original ?
				array(
					'new'      => $result,
					'original' => $original,
				) :
				$result;

			switch ( $event ) {
				case 'license_edit':
					// translators: %1$s is the license key, %2$s is the licence ID
					$format = esc_html__( 'The license `%1$s` with ID #%2$s has been edited on WPPUS' );
					break;
				case 'license_add':
					// translators: %1$s is the license key, %2$s is the licence ID
					$format = esc_html__( 'The license `%1$s` with ID #%2$s has been added on WPPUS' );
					break;
				case 'license_delete':
					// translators: %1$s is the license key, %2$s is the licence ID
					$format = esc_html__( 'The license `%1$s` with ID #%2$s has been deleted on WPPUS' );
					break;
				case 'license_activate':
					// translators: %1$s is the license key, %2$s is the licence ID
					$format = esc_html__( 'The license `%1$s` with ID #%2$s has been activated on WPPUS' );
					break;
				case 'license_deactivate':
					// translators: %1$s is the license key, %2$s is the licence ID
					$format = esc_html__( 'The license `%1$s` with ID #%2$s has been deactivated on WPPUS' );
					break;
				default:
					return;
			}

			$description = sprintf(
				$format,
				$result->license_key,
				$result->id
			);
		}

		$payload = array(
			'event'       => $event,
			'description' => $description,
			'content'     => $content,
		);

		wppus_schedule_webhook( $payload, 'license' );
	}

	// API action --------------------------------------------------

	public function browse( $query ) {
		$payload = json_decode( wp_unslash( $query ), true );

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
			$result                   = array( $result );
			$this->http_response_code = 400;
		}

		return $result;
	}

	public function read( $license_data ) {
		$result = $this->license_server->read_license( $license_data );

		if ( ! is_object( $result ) ) {
			$this->http_response_code = 400;
		}

		return $result;
	}

	public function edit( $license_data ) {
		$result = $this->license_server->edit_license( $license_data );

		if ( ! is_object( $result ) ) {
			$this->http_response_code = 400;
		}

		return $result;
	}

	public function add( $license_data ) {
		$result = $this->license_server->add_license( $license_data );

		if ( is_object( $result ) ) {
			$result->result  = 'success';
			$result->message = 'License successfully created';
			$result->key     = $result->license_key;
		} else {
			$this->http_response_code = 400;
		}

		return $result;
	}

	public function delete( $license_data ) {
		$result = $this->license_server->delete_license( $license_data );

		if ( ! is_object( $result ) ) {
			$this->http_response_code = 400;
		}

		return $result;
	}

	public function check( $license_data ) {
		$license_data = apply_filters( 'wppus_check_license_dirty_payload', $license_data );

		if ( isset( $license_data['id'] ) ) {
			unset( $license_data['id'] );
		}

		$result = $this->license_server->read_license( $license_data );
		$result = is_object( $result ) ?
			$result :
			array(
				'license_key' => isset( $license_data['license_key'] ) ?
					$license_data['license_key'] :
					false,
			);
		$result = apply_filters( 'wppus_check_license_result', $result, $license_data );

		do_action( 'wppus_did_check_license', $result );

		if ( ! is_object( $result ) ) {
			$this->http_response_code = 400;
		}

		return $result;
	}

	public function activate( $license_data ) {
		$license      = null;
		$license_data = apply_filters( 'wppus_activate_license_dirty_payload', $license_data );
		$result       = array();

		if ( isset( $license_data['id'] ) ) {
			unset( $license_data['id'] );
		}

		add_filter( 'wppus_license_is_public', '__return_false' );

		$license = $this->license_server->read_license( $license_data );

		remove_filter( 'wppus_license_is_public', '__return_false' );

		do_action( 'wppus_pre_activate_license', $license );

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

		do_action( 'wppus_did_activate_license', $result, $license_data );

		if ( ! is_object( $result ) ) {
			$this->http_response_code = 400;
		}

		return $result;
	}

	public function deactivate( $license_data ) {
		$license      = null;
		$license_data = apply_filters( 'wppus_deactivate_license_dirty_payload', $license_data );

		if ( isset( $license_data['id'] ) ) {
			unset( $license_data['id'] );
		}

		$license = $this->license_server->read_license( $license_data );
		$result  = array();

		do_action( 'wppus_pre_deactivate_license', $license );

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

		do_action( 'wppus_did_deactivate_license', $result, $license_data );

		if ( ! is_object( $result ) ) {
			$this->http_response_code = 400;
		}

		return $result;
	}

	// WordPress hooks ---------------------------------------------

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

	public function query_vars( $query_vars ) {
		$query_vars = array_merge(
			$query_vars,
			array(
				'__wppus_license_api',
				'action',
				'api_auth_key',
				'browse_query',
				'update_license_key',
				'update_license_signature',
			),
			array_keys( WPPUS_License_Server::$license_definition )
		);

		return $query_vars;
	}

	public function wppus_handle_update_request_params( $params ) {
		global $wp;

		$vars                                = $wp->query_vars;
		$request_params['license_key']       = isset( $vars['update_license_key'] ) ?
			trim( $vars['update_license_key'] ) :
			null;
		$request_params['license_signature'] = isset( $vars['update_license_signature'] ) ?
				trim( $vars['update_license_signature'] ) :
				null;

		return $params;
	}

	public function wppus_server_class_name( $class_name, $package_id ) {
		$use_licenses        = get_option( 'wppus_use_licenses' );
		$package_use_license = false;

		if ( $use_licenses ) {
			$licensed_package_slugs = apply_filters(
				'wppus_licensed_package_slugs',
				get_option( 'wppus_licensed_package_slugs', array() )
			);

			if ( in_array( $package_id, $licensed_package_slugs, true ) ) {
				$package_use_license = true;
			}
		}

		if ( $package_use_license && $use_licenses ) {
			require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-license-update-server.php';

			$class_name = 'WPPUS_License_Update_Server';
		}

		return $class_name;
	}

	// Misc. -------------------------------------------------------

	public static function is_doing_api_request() {

		if ( null === self::$doing_update_api_request ) {
			self::$doing_update_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], 'wppus-license-api' ) );
		}

		return self::$doing_update_api_request;
	}

	public static function get_config() {

		if ( ! self::$config ) {
			$keys   = json_decode( get_option( 'wppus_license_private_api_auth_keys', '{}' ), true );
			$config = array(
				'private_api_auth_keys' => $keys,
				'ip_whitelist'          => get_option( 'wppus_license_private_api_ip_whitelist' ),
			);

			self::$config = $config;
		}

		return apply_filters( 'wppus_license_api_config', self::$config );
	}

	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function authorize() {
		$key = false;

		if (
			isset( $_SERVER['HTTP_X_WPPUS_PRIVATE_LICENSE_API_KEY'] ) &&
			! empty( $_SERVER['HTTP_X_WPPUS_PRIVATE_LICENSE_API_KEY'] )
		) {
			$key = $_SERVER['HTTP_X_WPPUS_PRIVATE_LICENSE_API_KEY'];
		} else {
			global $wp;

			if (
				isset( $wp->query_vars['api_auth_key'] ) &&
				is_string( $wp->query_vars['api_auth_key'] ) &&
				! empty( $wp->query_vars['api_auth_key'] )
			) {
				$key = $wp->query_vars['api_auth_key'];
			}
		}

		$config  = self::get_config();
		$is_auth = in_array( $key, array_keys( $config['private_api_auth_keys'] ), true );

		return $is_auth;
	}

	protected function is_api_public( $method ) {
		$public_api    = apply_filters(
			'wppus_license_public_api_actions',
			array(
				'check',
				'activate',
				'deactivate',
			)
		);
		$is_api_public = in_array( $method, $public_api, true );

		return $is_api_public;
	}

	protected function handle_api_request() {
		global $wp;

		if ( isset( $wp->query_vars['action'] ) ) {
			$method = $wp->query_vars['action'];

			$this->init_server();

			if ( filter_input( INPUT_GET, 'action' ) && ! $this->is_api_public( $method ) ) {
				$this->http_response_code = 405;
				$response                 = array(
					'message' => __( 'Unauthorized GET method.', 'wppus' ),
				);
			} else {
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
					$authorized = apply_filters(
						'wppus_license_api_request_authorized',
						(
							$this->is_api_public( $method ) ||
							(
								$this->authorize_ip() &&
								$this->authorize()
							)
						),
						$method,
						$payload
					);

					if ( $authorized ) {
						do_action( 'wppus_license_api_request', $method, $payload );

						if ( method_exists( $this, $method ) ) {
							$response = $this->$method( $payload );
						} else {
							$this->http_response_code = 400;
							$response                 = array(
								'message' => __( 'License API action not found.', 'wppus' ),
							);
						}
					} else {
						$this->http_response_code = 403;
						$response                 = array(
							'message' => __( 'Unauthorized access', 'wppus' ),
						);
					}
				} else {
					$this->http_response_code = 400;
					$response                 = array(
						'message' => __( 'Malformed request.', 'wppus' ),
					);
				}
			}

			$this->license_server->dispatch( $response, $this->http_response_code );
		}
	}

	protected function authorize_ip() {
		$result = false;
		$config = self::get_config();

		if ( is_array( $config['ip_whitelist'] ) && ! empty( $config['ip_whitelist'] ) ) {

			foreach ( $config['ip_whitelist'] as $range ) {

				if ( cidr_match( $_SERVER['REMOTE_ADDR'], $range ) ) {
					$result = true;

					break;
				}
			}
		} else {
			$result = true;
		}

		return $result;
	}

	protected function init_server() {
		$this->license_server = apply_filters( 'wppus_license_server', new WPPUS_License_Server() );
	}
}
