<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Package_API {
	protected $http_response_code = 200;

	protected static $doing_update_api_request = null;

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {

			if ( ! self::is_doing_api_request() ) {
				add_action( 'init', array( $this, 'add_endpoints' ), 10, 0 );
			}

			add_action( 'parse_request', array( $this, 'parse_request' ), -99, 0 );

			add_filter( 'query_vars', array( $this, 'addquery_variables' ), -99, 1 );
		}
	}

	public static function is_doing_api_request() {

		if ( null === self::$doing_update_api_request ) {
			self::$doing_update_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], 'wppus-package-api' ) );
		}

		return self::$doing_update_api_request;
	}

	public static function get_config() {
		$config = array(
			'use_remote_repository'          => get_option( 'wppus_use_remote_repository', false ),
			'repository_service_self_hosted' => get_option( 'wppus_remote_repository_self_hosted', false ),
		);

		return apply_filters( 'wppus_package_api_config', $config );
	}

	public function add_endpoints() {
		add_rewrite_rule( '^wppus-package-api/(plugin|theme)/(.+)/*?$', 'index.php?type=$matches[1]&package_id=$matches[2]&$matches[3]&__wppus_package_api=1&', 'top' );
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wppus_package_api'] ) ) {
			$this->handle_api_request();

			die();
		}
	}

	public function addquery_variables( $query_variables ) {
		$query_variables = array_merge(
			$query_variables,
			array(
				'__wppus_package_api',
				'package_id',
				'type',
				'action',
			)
		);

		return $query_variables;
	}

	public function create( $package_id, $type ) {
		$result = wppus_get_package_info( $package_id, false );

		if ( is_array( $result ) ) {
			$result = false;
		} else {
			$result = wppus_download_remote_package( $package_id, $type );
			$result = $result ? wppus_get_package_info( $package_id, false ) : $result;
		}

		$result = apply_filters( 'wppus_package_create', $result, $package_id, $type );

		if ( $result ) {
			do_action( 'wppus_did_create_package', $result );
		}

		if ( ! $result ) {
			$this->http_response_code = 409;
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

	public function update( $package_id, $type ) {
		$result = wppus_download_remote_package( $package_id, $type );
		$result = $result ? wppus_get_package_info( $package_id, false ) : $result;
		$result = apply_filters( 'wppus_package_update', $result, $package_id, $type );

		if ( $result ) {
			do_action( 'wppus_did_update_package', $result );
		}

		if ( ! $result ) {
			$this->http_response_code = 400;
		}

		return $result;
	}

	public function delete( $package_id, $type ) {
		wppus_delete_package( $package_id );

		$result = ! (bool) $this->read( $package_id, $type );
		$result = apply_filters( 'wppus_package_delete', $result, $package_id, $type );

		if ( $result ) {
			do_action( 'wppus_did_delete_package', $result );
		}

		if ( ! $result ) {
			$this->http_response_code = 400;
		}

		return $result;
	}

	protected function is_api_public( $method ) {
		// @TODO doc
		$public_api    = apply_filters(
			'wppus_package_public_api_methods',
			array( 'read' )
		);
		$is_api_public = in_array( $method, $public_api, true );

		return $is_api_public;
	}

	protected function handle_api_request() {
		global $wp;

		if ( isset( $wp->query_vars['action'] ) ) {
			$method = $wp->query_vars['action'];

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

					if ( method_exists( $this, $method ) ) {
						$type       = isset( $payload['type'] ) ? $payload['type'] : null;
						$package_id = isset( $payload['package_id'] ) ? $payload['package_id'] : null;

						if ( $this->is_api_public( $method ) || $this->authorize() ) {
							$response = $this->$method( $package_id, $type );
						} else {
							$this->http_response_code = 403;
							$response                 = array(
								'message' => __( 'Unauthorized access - check the provided API key', 'wppus' ),
							);
						}
					} else {
						$this->http_response_code = 400;
						$response                 = array(
							'message' => __( 'Package API action not found.', 'wppus' ),
						);
					}
				} else {
					$this->http_response_code = 400;
					$response                 = array(
						'message' => __( 'Malformed request.', 'wppus' ),
					);
				}
			}

			wp_send_json( $response, $this->http_response_code );

			exit();
		}
	}

	protected function authorize() {
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
