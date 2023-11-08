<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Package_API {
	protected $http_response_code = 200;

	protected static $doing_update_api_request = null;
	protected static $config;

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {

			if ( ! self::is_doing_api_request() ) {
				add_action( 'init', array( $this, 'add_endpoints' ), 10, 0 );
			}

			add_action( 'parse_request', array( $this, 'parse_request' ), -99, 0 );

			add_filter( 'query_vars', array( $this, 'query_vars' ), -99, 1 );
			add_filter( 'wppus_nonce_authorize', array( $this, 'wppus_nonce_authorize' ), 10, 1 );
		}
	}

	public static function is_doing_api_request() {

		if ( null === self::$doing_update_api_request ) {
			self::$doing_update_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], 'wppus-package-api' ) );
		}

		return self::$doing_update_api_request;
	}

	public static function get_config() {

		if ( ! self::$config ) {
			$config = array(
				'use_remote_repository' => get_option( 'wppus_use_remote_repository' ),
				'private_api_auth_key'  => get_option( 'wppus_package_private_api_auth_key' ),
				'ip_whitelist'          => get_option( 'wppus_package_private_api_ip_whitelist' ),
			);

			self::$config = $config;
		}

		//@todo doc
		return apply_filters( 'wppus_package_api_config', self::$config );
	}

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

			die();
		}
	}

	public function query_vars( $query_vars ) {
		$query_vars = array_merge(
			$query_vars,
			array(
				'__wppus_package_api',
				'package_id',
				'type',
				'action',
				'browse_query',
			)
		);

		return $query_vars;
	}

	public function browse( $query ) {
		$result          = false;
		$query           = empty( $query ) || ! is_string( $query ) ? array() : json_decode( wp_unslash( $query ), true );
		$query['search'] = isset( $query['search'] ) ? trim( esc_html( $query['search'] ) ) : false;
		$result          = wppus_get_batch_package_info( $query['search'], false );
		$result['count'] = is_array( $result ) ? count( $result ) : 0;

		//@todo doc
		$result = apply_filters( 'wppus_package_browse', $result, $query );

		//@todo doc
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

		//@todo doc
		$result = apply_filters( 'wppus_package_read', $result, $package_id, $type );

		//@todo doc
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
			//@todo doc
			$result = apply_filters( 'wppus_package_update', $result, $package_id, $type );

			if ( $result ) {
				//@todo doc
				do_action( 'wppus_did_update_package', $result );
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

			//@todo doc
			$result = apply_filters( 'wppus_package_create', $result, $package_id, $type );

			if ( $result ) {
				//@todo doc
				do_action( 'wppus_did_create_package', $result );
			}
		}

		if ( ! $result ) {
			$this->http_response_code = 409;
		}

		return $result;
	}

	public function delete( $package_id, $type ) {
		wppus_delete_package( $package_id );

		$result = ! (bool) $this->read( $package_id, $type );
		//@todo doc
		$result = apply_filters( 'wppus_package_delete', $result, $package_id, $type );

		if ( $result ) {
			//@todo doc
			do_action( 'wppus_did_delete_package', $result );
		} else {
			$this->http_response_code = 400;
		}

		return $result;
	}

	public function download( $package_id, $type ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$path = wppus_get_local_package_path( $package_id );

		if ( ! $path ) {
			$this->http_response_code = 404;

			return array(
				'message' => __( 'Package not found.', 'wppus' ),
			);
		}

		wppus_download_local_package( $package_id, $path, false );
		//@todo doc
		do_action( 'wppus_did_download_package', $package_id );

		exit;
	}

	public function sign_url( $package_id, $type ) {
		$package_id = filter_var( $package_id, FILTER_SANITIZE_URL );
		$type       = filter_var( $type, FILTER_SANITIZE_URL );
		//@todo doc
		$token = apply_filters( 'wppus_package_sign_url_token', false, $package_id, $type );

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

		//@todo doc
		$result = apply_filters(
			'wppus_package_sign_url',
			array(
				'url'    => add_query_arg(
					array(
						'token'  => $token,
						'action' => 'download',
					),
					home_url( 'wppus-package-api/' . $type . '/' . $package_id )
				),
				'token'  => $token,
				'expiry' => wppus_get_nonce_expiry( $token ),
			),
			$package_id,
			$type
		);

		if ( $result ) {
			//@todo doc
			do_action( 'wppus_did_sign_url_package', $result );
		} else {
			$this->http_response_code = 404;
		}

		return $result;
	}

	protected function is_api_public( $method ) {
		// @TODO doc
		$public_api    = apply_filters(
			'wppus_package_public_api_methods',
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
					'message' => __( 'Unauthorized GET method.', 'wppus' ),
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

				//@todo doc
				$authorized = apply_filters(
					'wppus_package_api_request_authorized',
					(
						(
							$this->is_api_public( $method ) &&
							$this->authorize_public()
						) ||
						(
							$this->authorize_private() &&
							$this->authorize_ip()
						)
					),
					$method,
					$payload
				);

				if ( $authorized ) {

					if ( method_exists( $this, $method ) ) {
						$type       = isset( $payload['type'] ) ? $payload['type'] : null;
						$package_id = isset( $payload['package_id'] ) ? $payload['package_id'] : null;

						if ( $type && $package_id ) {
							$response = $this->$method( $package_id, $type );
						} else {
							$response = $this->$method( $payload );
						}
					} else {
						// @todo doc
						do_action( 'wppus_package_api_request', $method, $payload );

						// @todo doc
						$handled = apply_filters(
							'wppus_package_api_request_handled',
							false,
							$method,
							$payload
						);

						if ( ! $handled ) {
							$this->http_response_code = 400;
							$response                 = array(
								'message' => __( 'Package API action not found.', 'wppus' ),
							);
						}
					}
				} else {
					$this->http_response_code = 403;
					$response                 = array(
						'message' => __( 'Unauthorized access', 'wppus' ),
					);
				}
			}

			wp_send_json( $response, $this->http_response_code );

			exit();
		}
	}

	protected function authorize_ip() {
		$result = false;
		$config = self::get_config();

		if ( is_array( $config['ip_whitelist'] ) & ! empty( $config['ip_whitelist'] ) ) {

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

	public function wppus_nonce_authorize( $authorized ) {
		global $wp;

		$data = isset( $wp->query_vars['data'] ) ? $wp->query_vars['data'] : array();

		if (
			isset( $data['actions'] ) &&
			is_array( $data['actions'] ) &&
			! empty( $data['actions'] )
		) {
			$authorized = $authorized && $this->authorize_ip();
		} else {
			$authorized = false;
		}

		return $authorized;
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

	protected function authorize_private() {
		$key = false;

		if (
			isset( $_SERVER['HTTP_X_WPPUS_PRIVATE_PACKAGE_API_KEY'] ) &&
			! empty( $_SERVER['HTTP_X_WPPUS_PRIVATE_PACKAGE_API_KEY'] )
		) {
			$key = $_SERVER['HTTP_X_WPPUS_PRIVATE_PACKAGE_API_KEY'];
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
		$is_auth = $config['private_api_auth_key'] === $key;

		return $is_auth;
	}
}
