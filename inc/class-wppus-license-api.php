<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_License_API {

	protected $license_server;
	protected $http_response_code = null;
	protected $api_key_id;
	protected $api_access;

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
				add_filter( 'wppus_api_license_actions', array( $this, 'wppus_api_license_actions' ), 0, 1 );
				add_filter( 'wppus_api_webhook_events', array( $this, 'wppus_api_webhook_events' ), 0, 1 );
				add_filter( 'wppus_nonce_api_payload', array( $this, 'wppus_nonce_api_payload' ), 0, 1 );
			}
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	// API action --------------------------------------------------

	public function browse( $query ) {
		$payload = json_decode( wp_unslash( $query ), true );

		switch ( json_last_error() ) {
			case JSON_ERROR_NONE:
				if ( ! empty( $payload['criteria'] ) ) {

					foreach ( $payload['criteria'] as $index => $criteria ) {

						if ( 'id' === $criteria['field'] ) {
							unset( $payload['criteria'][ $index ] );
						}
					}
				}

				$result = $this->license_server->browse_licenses( $payload );

				if (
					is_array( $result ) &&
					! empty( $result ) &&
					$this->api_access &&
					$this->api_key_id &&
					! in_array( 'other', $this->api_access, true )
				) {

					foreach ( $result as $index => $license ) {

						if (
							! isset( $license->data, $license->data['api_owner'] ) ||
							$license->data['api_owner'] !== $this->api_key_id
						) {
							unset( $result[ $index ] );
						} else {
							unset( $result[ $index ]->id );
						}
					}
				}

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
		} elseif ( empty( $result ) ) {
			$this->http_response_code = 404;
			$result                   = array(
				'message' => __( 'Licenses not found.', 'wppus' ),
			);
		}

		return $result;
	}

	public function read( $license_data ) {
		$result = $this->license_server->read_license( $license_data );

		if ( ! is_object( $result ) ) {
			if ( isset( $result['license_not_found'] ) ) {
				$this->http_response_code = 404;
			} else {
				$this->http_response_code = 400;
			}
		} else {

			if ( ! isset( $result->license_key ) ) {
				$this->http_response_code = 404;
				$result                   = array(
					'message' => __( 'License not found.', 'wppus' ),
				);
			}

			unset( $result->id );
		}

		return $result;
	}

	public function edit( $license_data ) {
		unset( $license_data['data']['api_owner'] );

		$result = $this->license_server->edit_license( $license_data );

		if ( ! is_object( $result ) ) {
			if ( isset( $result['license_not_found'] ) ) {
				$this->http_response_code = 404;
			} else {
				$this->http_response_code = 400;
			}
		} else {

			if ( ! isset( $result->license_key ) ) {
				$this->http_response_code = 404;
				$result                   = array(
					'message' => __( 'License not found.', 'wppus' ),
				);
			}

			unset( $result->id );
		}

		return $result;
	}

	public function add( $license_data ) {

		if ( $this->api_key_id ) {
			$license_data['data']['api_owner'] = $this->api_key_id;
		}

		$result = $this->license_server->add_license( $license_data );

		if ( is_object( $result ) ) {
			$result->result  = 'success';
			$result->message = 'License successfully created';
			$result->key     = $result->license_key;

			unset( $result->id );
		} else {
			$this->http_response_code = 400;
		}

		return $result;
	}

	public function delete( $license_data ) {
		$result = $this->license_server->delete_license( $license_data );

		if ( ! is_object( $result ) ) {
			if ( isset( $license['license_not_found'] ) ) {
				$this->http_response_code = 404;
			} else {
				$this->http_response_code = 400;
			}
		} elseif ( ! isset( $result->license_key ) ) {
			$this->http_response_code = 404;
			$result                   = array(
				'message' => __( 'License not found.', 'wppus' ),
			);
		}

		return $result;
	}

	public function check( $license_data ) {
		$license_data = apply_filters( 'wppus_check_license_dirty_payload', $license_data );
		$result       = $this->license_server->read_license( $license_data );

		if ( is_object( $result ) ) {
			unset( $result->hmac_key );
			unset( $result->crypto_key );
			unset( $result->data );
			unset( $result->owner_name );
			unset( $result->email );
			unset( $result->company_name );
			unset( $result->id );
		} else {
			$result = array(
				'license_key' => isset( $license_data['license_key'] ) ?
					$license_data['license_key'] :
					false,
			);
		}

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
		$request_slug = isset( $license_data['package_slug'] ) ? $license_data['package_slug'] : false;
		$result       = array();
		$license      = $this->license_server->read_license( $license_data );

		do_action( 'wppus_pre_activate_license', $license );

		if ( ! isset( $license_data['allowed_domains'] ) ) {
			$license_data['allowed_domains'] = array();
		} elseif ( ! is_array( $license_data['allowed_domains'] ) ) {
			$license_data['allowed_domains'] = array( $license_data['allowed_domains'] );
		} else {
			$license_data['allowed_domains'] = array( reset( $license_data['allowed_domains'] ) );
		}

		if ( is_object( $license ) && ! empty( $license_data['allowed_domains'] ) && $request_slug === $license->package_slug ) {
			$domain_count = count( $license_data['allowed_domains'] ) + count( $license->allowed_domains );

			if ( in_array( $license->status, array( 'expired', 'blocked', 'on-hold' ), true ) ) {
				$result['status'] = $license->status;
			} elseif ( $domain_count > absint( $license->max_allowed_domains ) ) {
				$result['max_allowed_domains'] = $license->max_allowed_domains;
			}

			if ( empty( $result ) ) {
				$payload                   = array(
					'id'              => $license->id,
					'status'          => 'activated',
					'allowed_domains' => array_unique( array_merge( $license_data['allowed_domains'], $license->allowed_domains ) ),
				);
				$result                    = $this->license_server->edit_license(
					apply_filters( 'wppus_activate_license_payload', $payload )
				);
				$result->license_signature = $this->license_server->generate_license_signature( $license, reset( $license_data['allowed_domains'] ) );

				unset( $result->hmac_key );
				unset( $result->crypto_key );
				unset( $result->data );
				unset( $result->owner_name );
				unset( $result->email );
				unset( $result->company_name );
				unset( $result->id );
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
		$request_slug = isset( $license_data['package_slug'] ) ? $license_data['package_slug'] : false;
		$license      = $this->license_server->read_license( $license_data );
		$result       = array();

		do_action( 'wppus_pre_deactivate_license', $license );

		if ( ! isset( $license_data['allowed_domains'] ) ) {
			$license_data['allowed_domains'] = array();
		} elseif ( ! is_array( $license_data['allowed_domains'] ) ) {
			$license_data['allowed_domains'] = array( $license_data['allowed_domains'] );
		}

		if (
			is_object( $license ) &&
			! empty( $license_data['allowed_domains'] ) &&
			$request_slug === $license->package_slug
		) {

			if ( 'expired' === $license->status ) {
				$result['status']      = $license->status;
				$result['date_expiry'] = $license->date_expiry;
			} elseif ( 'blocked' === $license->status || 'on-hold' === $license->status ) {
				$result['status'] = $license->status;
			} elseif (
					'deactivated' === $license->status ||
					empty( array_intersect( $license_data['allowed_domains'], $license->allowed_domains ) )
			) {
				$result['allowed_domains'] = $license_data['allowed_domains'];
			} elseif (
				isset( $license->data, $license->data['next_deactivate'] ) &&
				$license->data['next_deactivate'] > time()
			) {
				$result['next_deactivate'] = $license->data['next_deactivate'];
			}

			if ( empty( $result ) ) {
				$data                    = isset( $license->data ) ? $license->data : array();
				$data['next_deactivate'] = time() + MONTH_IN_SECONDS;
				$allowed_domains         = array_diff( $license->allowed_domains, $license_data['allowed_domains'] );
				$payload                 = array(
					'id'              => $license->id,
					'status'          => empty( $allowed_domains ) ? 'deactivated' : $license->status,
					'allowed_domains' => $allowed_domains,
					'data'            => $data,
				);
				$result                  = $this->license_server->edit_license(
					apply_filters( 'wppus_deactivate_license_payload', $payload )
				);

				unset( $result->hmac_key );
				unset( $result->crypto_key );
				unset( $result->data );
				unset( $result->owner_name );
				unset( $result->email );
				unset( $result->company_name );
				unset( $result->id );
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
				'api',
				'api_token',
				'api_credentials',
				'browse_query',
				'license_key',
				'license_signature',
			),
			array_keys( WPPUS_License_Server::$license_definition )
		);

		return $query_vars;
	}

	public function wppus_handle_update_request_params( $params ) {
		global $wp;

		$vars                                = $wp->query_vars;
		$request_params['license_key']       = isset( $vars['license_key'] ) ?
			trim( $vars['license_key'] ) :
			null;
		$request_params['license_signature'] = isset( $vars['license_signature'] ) ?
				trim( $vars['license_signature'] ) :
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

	public function wppus_api_license_actions( $actions ) {
		$actions['browse'] = __( 'Browse multiple license records', 'wppus' );
		$actions['read']   = __( 'Get single license records', 'wppus' );
		$actions['edit']   = __( 'Update license records', 'wppus' );
		$actions['add']    = __( 'Create license records', 'wppus' );
		$actions['delete'] = __( 'Delete license records', 'wppus' );

		return $actions;
	}

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
		$event  = 'license_' . str_replace(
			array( 'wppus_did_', '_license' ),
			array( '', '' ),
			current_action()
		);

		if ( ! is_object( $result ) ) {
			// translators: %s is operation slug
			$description = sprintf( esc_html__( 'An error occured for License operation `%s` on WPPUS.' ), $event );
			$content     = array(
				'error'   => true,
				'result'  => $result,
				'payload' => $payload,
			);
		} else {

			if ( null !== $original ) {
				unset( $original->id );
			}

			unset( $result->id );

			$content = null !== $original ?
				array(
					'new'      => $result,
					'original' => $original,
				) :
				$result;

			switch ( $event ) {
				case 'license_edit':
					// translators: %s is the license key
					$format = esc_html__( 'The license `%s` has been edited on WPPUS' );
					break;
				case 'license_add':
					// translators: %s is the license key
					$format = esc_html__( 'The license `%s` has been added on WPPUS' );
					break;
				case 'license_delete':
					// translators: %s is the license key
					$format = esc_html__( 'The license `%s` has been deleted on WPPUS' );
					break;
				case 'license_activate':
					// translators: %s is the license key
					$format = esc_html__( 'The license `%s` has been activated on WPPUS' );
					break;
				case 'license_deactivate':
					// translators: %s is the license key
					$format = esc_html__( 'The license `%s` has been deactivated on WPPUS' );
					break;
				default:
					return;
			}

			$description = sprintf( $format, $result->license_key );
		}

		$payload = array(
			'event'       => $event,
			'description' => $description,
			'content'     => $content,
		);

		add_filter( 'wppus_webhook_fire', array( $this, 'wppus_webhook_fire' ), 10, 4 );
		wppus_schedule_webhook( $payload, 'license' );
		remove_filter( 'wppus_webhook_fire', array( $this, 'wppus_webhook_fire' ), 10 );
	}

	public function wppus_webhook_fire( $fire, $payload, $url, $info ) {

		if ( ! isset( $info['licenseAPIKey'] ) || empty( $info['licenseAPIKey'] ) ) {
			return $fire;
		}

		$owner = false;

		if (
			is_array( $payload['content'] ) &&
			isset( $payload['content']['new'] ) &&
			isset( $payload['content']['new']->data['api_owner'] )
		) {
			$owner = $payload['content']['new']->data['api_owner'];
		} elseif (
			is_object( $payload['content'] ) &&
			isset( $payload['content']->data['api_owner'] )
		) {
			$owner = $payload['content']->data['api_owner'];
		}

		$config     = self::get_config();
		$api_access = false;

		foreach ( $config['private_api_auth_keys'] as $id => $values ) {

			if (
				$id === $info['licenseAPIKey'] &&
				isset( $values['access'] ) &&
				is_array( $values['access'] )
			) {
				$api_access = $values['access'];

				break;
			}
		}

		if ( $api_access && in_array( 'other', $api_access, true ) ) {
			$fire = true;
		} elseif ( $api_access ) {
			$action = str_replace( 'license_', '', $payload['event'] );

			if (
				in_array( 'all', $api_access, true ) ||
				in_array( 'read', $api_access, true ) ||
				in_array( 'browse', $api_access, true ) ||
				(
					in_array( $action, array( 'edit', 'add', 'delete' ), true ) &&
					in_array( $action, $api_access, true )
				)
			) {
				$fire = $owner === $info['licenseAPIKey'];
			} else {
				$fire = false;
			}
		} else {
			$fire = $owner === $info['licenseAPIKey'];
		}

		return $fire;
	}

	public function wppus_fetch_nonce_private( $nonce, $true_nonce, $expiry, $data ) {
		$config = self::get_config();
		$valid  = false;

		if (
			! empty( $config['private_api_auth_keys'] ) &&
			isset( $data['license_api'], $data['license_api']['id'], $data['license_api']['access'] )
		) {
			global $wp;

			$action = $wp->query_vars['action'];

			foreach ( $config['private_api_auth_keys'] as $id => $values ) {

				if (
					$id === $data['license_api']['id'] &&
					isset( $values['access'] ) &&
					is_array( $values['access'] ) &&
					(
						in_array( 'all', $values['access'], true ) ||
						in_array( $action, $values['access'], true )
					)
				) {
					$this->api_key_id = $id;
					$this->api_access = $values['access'];
					$valid            = true;

					break;
				}
			}
		}

		if ( ! $valid ) {
			$nonce = null;
		}

		return $nonce;
	}

	public function wppus_nonce_api_payload( $payload ) {
		global $wp;

		if ( ! isset( $wp->query_vars['api'] ) || 'license' !== $wp->query_vars['api'] ) {
			return $payload;
		}

		$key_id      = false;
		$credentials = array();
		$config      = self::get_config();

		if (
			isset( $_SERVER['HTTP_X_WPPUS_API_CREDENTIALS'] ) &&
			! empty( $_SERVER['HTTP_X_WPPUS_API_CREDENTIALS'] )
		) {
			$credentials = explode( '/', $_SERVER['HTTP_X_WPPUS_API_CREDENTIALS'] );
		} elseif (
			isset( $wp->query_vars['api_credentials'], $wp->query_vars['api'] ) &&
			is_string( $wp->query_vars['api_credentials'] ) &&
			! empty( $wp->query_vars['api_credentials'] )
		) {
			$credentials = explode( '/', $wp->query_vars['api_credentials'] );
		}

		if ( 2 === count( $credentials ) ) {
			$key_id = end( $credentials );
		}

		if ( $key_id && isset( $config['private_api_auth_keys'][ $key_id ] ) ) {
			$values                         = $config['private_api_auth_keys'][ $key_id ];
			$payload['data']['license_api'] = array(
				'id'     => $key_id,
				'access' => isset( $values['access'] ) ? $values['access'] : array(),
			);
		}

		$payload['expiry_length'] = HOUR_IN_SECONDS / 2;

		return $payload;
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
			$keys   = json_decode( get_option( 'wppus_license_private_api_keys', '{}' ), true );
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

	protected function authorize_private( $action, $payload ) {
		$token   = false;
		$is_auth = false;

		if (
			isset( $_SERVER['HTTP_X_WPPUS_TOKEN'] ) &&
			! empty( $_SERVER['HTTP_X_WPPUS_TOKEN'] )
		) {
			$token = $_SERVER['HTTP_X_WPPUS_TOKEN'];
		} else {
			global $wp;

			if (
				isset( $wp->query_vars['api_token'] ) &&
				is_string( $wp->query_vars['api_token'] ) &&
				! empty( $wp->query_vars['api_token'] )
			) {
				$token = $wp->query_vars['api_token'];
			}
		}

		add_filter( 'wppus_fetch_nonce', array( $this, 'wppus_fetch_nonce_private' ), 10, 4 );

		$is_auth = wppus_validate_nonce( $token );

		remove_filter( 'wppus_fetch_nonce', array( $this, 'wppus_fetch_nonce_private' ), 10 );

		if ( $this->api_key_id && $this->api_access ) {

			if ( 'browse' === $action || 'add' === $action ) {
				$is_auth = $is_auth && (
					in_array( 'all', $this->api_access, true ) ||
					in_array( $action, $this->api_access, true )
				);
			} elseif ( isset( $payload['license_key'] ) ) {
				$license = $this->read( $payload );
				$is_auth = $is_auth && (
					! is_object( $license ) ||
					(
						is_object( $license ) &&
						(
							in_array( 'all', $this->api_access, true ) ||
							in_array( $action, $this->api_access, true )
						) &&
						(
							(
								isset( $license->data['api_owner'] ) &&
								$this->api_key_id === $license->data['api_owner']
							) ||
							in_array( 'other', $this->api_access, true )
						)
					)
				);
			}
		}

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

					unset( $payload['id'] );
				}

				if ( ! $malformed_request ) {
					$authorized = apply_filters(
						'wppus_license_api_request_authorized',
						(
							$this->is_api_public( $method ) ||
							(
								$this->authorize_ip() &&
								$this->authorize_private( $method, $payload )
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
