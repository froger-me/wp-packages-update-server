<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Package_API {
	protected $http_response_code = 200;
	protected $api_key_id;
	protected $api_access;

	protected static $doing_update_api_request = null;
	protected static $instance;
	protected static $config;

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {

			if ( ! self::is_doing_api_request() ) {
				add_action( 'init', array( $this, 'add_endpoints' ), 10, 0 );
			}

			add_action( 'parse_request', array( $this, 'parse_request' ), -99, 0 );
			add_action( 'wppus_saved_remote_package_to_local', array( $this, 'wppus_saved_remote_package_to_local' ), 10, 3 );
			add_action( 'wppus_pre_delete_package', array( $this, 'wppus_pre_delete_package' ), 10, 2 );
			add_action( 'wppus_did_delete_package', array( $this, 'wppus_did_delete_package' ), 10, 3 );
			add_action( 'wppus_did_download_package', array( $this, 'wppus_did_download_package' ), 10, 1 );

			add_filter( 'query_vars', array( $this, 'query_vars' ), -99, 1 );
			add_filter( 'wppus_api_package_actions', array( $this, 'wppus_api_package_actions' ), 0, 1 );
			add_filter( 'wppus_api_webhook_events', array( $this, 'wppus_api_webhook_events' ), 10, 1 );
			add_filter( 'wppus_nonce_api_payload', array( $this, 'wppus_nonce_api_payload' ), 0, 1 );
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	// API action --------------------------------------------------

	public function browse( $query ) {
		$result          = false;
		$query           = empty( $query ) || ! is_string( $query ) ? array() : json_decode( wp_unslash( $query ), true );
		$query['search'] = isset( $query['search'] ) ? trim( esc_html( $query['search'] ) ) : false;
		$result          = wppus_get_batch_package_info( $query['search'], false );
		$result['count'] = is_array( $result ) ? count( $result ) : 0;
		$result          = apply_filters( 'wppus_package_browse', $result, $query );

		do_action( 'wppus_did_browse_package', $result );

		if ( empty( $result ) ) {
			$result = array( 'count' => 0 );
		}

		if ( isset( $result['count'] ) && 0 === $result['count'] ) {
			$this->http_response_code = 404;
		}

		return $result;
	}

	public function read( $package_id, $type ) {
		$result = wppus_get_package_info( $package_id, false );

		if (
			! is_array( $result ) ||
			! isset( $result['type'] ) ||
			$type !== $result['type']
		) {
			$result = false;
		} else {
			unset( $result['file_path'] );
		}

		$result = apply_filters( 'wppus_package_read', $result, $package_id, $type );

		do_action( 'wppus_did_read_package', $result );

		if ( ! $result ) {
			$this->http_response_code = 404;
		}

		return $result;
	}

	public function edit( $package_id, $type ) {
		$result = false;
		$config = self::get_config();

		if ( $config['use_remote_repository'] ) {
			$result = wppus_download_remote_package( $package_id, $type );
			$result = $result ? wppus_get_package_info( $package_id, false ) : $result;
			$result = apply_filters( 'wppus_package_edit', $result, $package_id, $type );

			if ( $result ) {
				do_action( 'wppus_did_edit_package', $result );
			}
		}

		if ( ! $result ) {
			$this->http_response_code = 400;
		}

		return $result;
	}

	public function add( $package_id, $type ) {
		$result = false;
		$config = self::get_config();

		if ( $config['use_remote_repository'] ) {
			$result = wppus_get_package_info( $package_id, false );

			if ( ! empty( $result ) ) {
				$result = false;
			} else {
				$result = wppus_download_remote_package( $package_id, $type );
				$result = $result ? wppus_get_package_info( $package_id, false ) : $result;
			}

			$result = apply_filters( 'wppus_package_add', $result, $package_id, $type );

			if ( $result ) {
				do_action( 'wppus_did_add_package', $result );
			}

			if ( ! $result ) {
				$this->http_response_code = 409;
			}
		} else {
			$this->http_response_code = 400;
		}

		return $result;
	}

	public function delete( $package_id, $type ) {
		do_action( 'wppus_pre_delete_package', $package_id, $type );

		$result = wppus_delete_package( $package_id );
		$result = apply_filters( 'wppus_package_delete', $result, $package_id, $type );

		if ( $result ) {
			do_action( 'wppus_did_delete_package', $result, $package_id, $type );
		} else {
			$this->http_response_code = 404;
		}

		return $result;
	}

	public function download( $package_id, $type ) {
		$path = wppus_get_local_package_path( $package_id );

		if ( ! $path ) {

			if ( ! $this->add( $package_id, $type ) ) {
				return array(
					'message' => __( 'Package not found', 'wppus' ),
				);
			}
		}

		wppus_download_local_package( $package_id, $path, false );
		do_action( 'wppus_did_download_package', $package_id );

		exit;
	}

	public function signed_url( $package_id, $type ) {
		$package_id = filter_var( $package_id, FILTER_SANITIZE_URL );
		$type       = filter_var( $type, FILTER_SANITIZE_URL );
		$token      = apply_filters( 'wppus_package_signed_url_token', false, $package_id, $type );

		if ( ! $token ) {
			$token = wppus_create_nonce(
				false,
				HOUR_IN_SECONDS,
				array(
					'actions'    => array( 'download' ),
					'type'       => $type,
					'package_id' => $package_id,
				),
			);
		}

		$result = apply_filters(
			'wppus_package_signed_url',
			array(
				'url'    => add_query_arg(
					array(
						'token'  => $token,
						'action' => 'download',
					),
					home_url( 'wppus-package-api/' . $type . '/' . $package_id . '/' )
				),
				'token'  => $token,
				'expiry' => wppus_get_nonce_expiry( $token ),
			),
			$package_id,
			$type
		);

		if ( $result ) {
			do_action( 'wppus_did_signed_url_package', $result );
		} else {
			$this->http_response_code = 404;
		}

		return $result;
	}

	// WordPress hooks ---------------------------------------------

	public function add_endpoints() {
		add_rewrite_rule(
			'^wppus-package-api/(plugin|theme)/(.+)/*?$',
			'index.php?type=$matches[1]&package_id=$matches[2]&$matches[3]&__wppus_package_api=1&',
			'top'
		);

		add_rewrite_rule(
			'^wppus-package-api/*?$',
			'index.php?$matches[1]&__wppus_package_api=1&',
			'top'
		);
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wppus_package_api'] ) ) {
			$this->handle_api_request();

			exit;
		}
	}

	public function query_vars( $query_vars ) {
		$query_vars = array_merge(
			$query_vars,
			array(
				'__wppus_package_api',
				'action',
				'api',
				'api_token',
				'api_credentials',
				'package_id',
				'type',
				'browse_query',
			)
		);

		return $query_vars;
	}

	public function wppus_saved_remote_package_to_local( $local_ready, $package_type, $package_slug ) {

		if ( ! $local_ready ) {
			return;
		}

		$payload = array(
			'event'       => 'package_updated',
			// translators: %1$s is the package type, %2$s is the pakage slug
			'description' => sprintf( esc_html__( 'The package of type `%1$s` and slug `%2$s` has been updated on WPPUS' ), $package_type, $package_slug ),
			'content'     => wppus_get_package_info( $package_slug, false ),
		);

		wppus_schedule_webhook( $payload, 'package' );
	}

	public function wppus_pre_delete_package( $package_slug, $package_type ) {
		wp_cache_set(
			'wppus_package_deleted_info' . $package_slug . '_' . $package_type,
			wppus_get_package_info( $package_slug, false ),
			'wppus'
		);
	}

	public function wppus_did_delete_package( $result, $package_slug, $package_type ) {
		$package_info = wp_cache_get(
			'wppus_package_deleted_info' . $package_slug . '_' . $package_type,
			'wppus'
		);

		if ( $package_info ) {
			$payload = array(
				'event'       => 'package_deleted',
				// translators: %1$s is the package type, %2$s is the package slug
				'description' => sprintf( esc_html__( 'The package of type `%1$s` and slug `%2$s` has been deleted on WPPUS' ), $package_type, $package_slug ),
				'content'     => $package_info,
			);

			wppus_schedule_webhook( $payload, 'package' );
		}
	}

	public function wppus_did_download_package( $package_slug ) {
		$payload = array(
			'event'       => 'package_downloaded',
			// translators: %s is the package slug
			'description' => sprintf( esc_html__( 'The package of `%s` has been securely downloaded from WPPUS' ), $package_slug ),
			'content'     => wppus_get_package_info( $package_slug, false ),
		);

		wppus_schedule_webhook( $payload, 'package' );
	}

	public function wppus_api_package_actions( $actions ) {
		$actions['browse']     = __( 'Get information about multiple packages', 'wppus' );
		$actions['read']       = __( 'Get information about a single package', 'wppus' );
		$actions['edit']       = __( 'Force download a package to overwrite the existing one on the file system ; requires using a Remote Repository', 'wppus' );
		$actions['add']        = __( 'Force download a package to the file system if it does not exist ; requires using a Remote Repository', 'wppus' );
		$actions['delete']     = __( 'Delete a package from the file system', 'wppus' );
		$actions['signed_url'] = __( 'Get secure URLs to download packages', 'wppus' );

		return $actions;
	}

	public function wppus_api_webhook_events( $webhook_events ) {

		if ( isset( $webhook_events['package'], $webhook_events['package']['events'] ) ) {
			$webhook_events['package']['events']['package_update']   = __( 'Package added or updated', 'wppus' );
			$webhook_events['package']['events']['package_delete']   = __( 'Package deleted', 'wppus' );
			$webhook_events['package']['events']['package_download'] = __( 'Package downloaded via a signed URL', 'wppus' );
		}

		return $webhook_events;
	}

	public function wppus_fetch_nonce_public( $nonce, $true_nonce, $expiry, $data ) {
		global $wp;

		$current_action = $wp->query_vars['action'];

		if (
			isset( $data['actions'] ) &&
			is_array( $data['actions'] ) &&
			! empty( $data['actions'] )
		) {

			if ( ! in_array( $current_action, $data['actions'], true ) ) {
				$nonce = null;
			} elseif ( isset( $data['type'], $data['package_id'] ) ) {
				$type       = isset( $wp->query_vars['type'] ) ? $wp->query_vars['type'] : null;
				$package_id = isset( $wp->query_vars['package_id'] ) ? $wp->query_vars['package_id'] : null;

				if ( $type !== $data['type'] || $package_id !== $data['package_id'] ) {
					$nonce = null;
				}
			}
		} else {
			$nonce = null;
		}

		return $nonce;
	}

	public function wppus_fetch_nonce_private( $nonce, $true_nonce, $expiry, $data ) {
		$config = self::get_config();
		$valid  = false;

		if (
			! empty( $config['private_api_auth_keys'] ) &&
			isset( $data['package_api'], $data['package_api']['id'], $data['package_api']['access'] )
		) {
			global $wp;

			$action = $wp->query_vars['action'];

			foreach ( $config['private_api_auth_keys'] as $id => $values ) {

				if (
					$id === $data['package_api']['id'] &&
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

		if ( ! isset( $wp->query_vars['api'] ) || 'package' !== $wp->query_vars['api'] ) {
			return $payload;
		}

		$key_id      = false;
		$credentials = array();
		$config      = self::get_config();

		if (
			isset( $_SERVER['HTTP_X_WPPUS_API_CREDENTIALS'] ) &&
			! empty( $_SERVER['HTTP_X_WPPUS_API_CREDENTIALS'] )
		) {
			$credentials = explode( '|', $_SERVER['HTTP_X_WPPUS_API_CREDENTIALS'] );
		} elseif (
			isset( $wp->query_vars['api_credentials'], $wp->query_vars['api'] ) &&
			is_string( $wp->query_vars['api_credentials'] ) &&
			! empty( $wp->query_vars['api_credentials'] )
		) {
			$credentials = explode( '|', $wp->query_vars['api_credentials'] );
		}

		if ( 2 === count( $credentials ) ) {
			$key_id = end( $credentials );
		}

		if ( $key_id && isset( $config['private_api_auth_keys'][ $key_id ]['key'] ) ) {
			$payload['data']['package_api'] = array(
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
			self::$doing_update_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], 'wppus-package-api' ) );
		}

		return self::$doing_update_api_request;
	}

	public static function get_config() {

		if ( ! self::$config ) {
			$keys   = json_decode( get_option( 'wppus_package_private_api_keys', '{}' ), true );
			$config = array(
				'use_remote_repository' => get_option( 'wppus_use_remote_repository' ),
				'private_api_auth_keys' => $keys,
				'ip_whitelist'          => get_option( 'wppus_package_private_api_ip_whitelist' ),
			);

			self::$config = $config;
		}

		return apply_filters( 'wppus_package_api_config', self::$config );
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

	protected function authorize_public() {
		$nonce = filter_input( INPUT_GET, 'token', FILTER_UNSAFE_RAW );

		if ( ! $nonce ) {
			$nonce = filter_input( INPUT_GET, 'nonce', FILTER_UNSAFE_RAW );
		}

		add_filter( 'wppus_fetch_nonce', array( $this, 'wppus_fetch_nonce_public' ), 10, 4 );

		$result = wppus_validate_nonce( $nonce );

		remove_filter( 'wppus_fetch_nonce', array( $this, 'wppus_fetch_nonce_public' ), 10 );

		return $result;
	}

	protected function authorize_private( $action ) {
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
			$is_auth = $is_auth && (
				in_array( 'all', $this->api_access, true ) ||
				in_array( $action, $this->api_access, true )
			);
		}

		return $is_auth;
	}

	protected function is_api_public( $method ) {
		$public_api    = apply_filters(
			'wppus_package_public_api_actions',
			array( 'download' )
		);
		$is_api_public = in_array( $method, $public_api, true );

		return $is_api_public;
	}

	protected function handle_api_request() {
		global $wp;

		if ( isset( $wp->query_vars['action'] ) ) {
			$method = $wp->query_vars['action'];

			if (
				filter_input( INPUT_GET, 'action' ) &&
				! $this->is_api_public( $method )
			) {
				$this->http_response_code = 405;
				$response                 = array(
					'message' => __( 'Unauthorized GET method', 'wppus' ),
				);
			} else {

				if (
					'browse' === $wp->query_vars['action'] &&
					isset( $wp->query_vars['browse_query'] )
				) {
					$payload = $wp->query_vars['browse_query'];
				} else {
					$payload = $wp->query_vars;
				}

				$authorized = apply_filters(
					'wppus_package_api_request_authorized',
					(
						(
							$this->is_api_public( $method ) &&
							$this->authorize_public()
						) ||
						(
							$this->authorize_private( $method ) &&
							$this->authorize_ip()
						)
					),
					$method,
					$payload
				);
				$response   = array(
					'message' => 'OK',
				);

				if ( $authorized ) {
					do_action( 'wppus_package_api_request', $method, $payload );

					if ( method_exists( $this, $method ) ) {
						$type       = isset( $payload['type'] ) ? $payload['type'] : null;
						$package_id = isset( $payload['package_id'] ) ? $payload['package_id'] : null;

						if ( $type && $package_id ) {
							$response = $this->$method( $package_id, $type );
						} else {
							$response = $this->$method( $payload );
						}
					} else {
						$this->http_response_code = 400;
						$response                 = array(
							'message' => __( 'Package API action not found', 'wppus' ),
						);
					}
				} else {
					$this->http_response_code = 403;
					$response                 = array(
						'message' => __( 'Unauthorized access', 'wppus' ),
					);
				}
			}

			wp_send_json( $response, $this->http_response_code );

			exit;
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
}
