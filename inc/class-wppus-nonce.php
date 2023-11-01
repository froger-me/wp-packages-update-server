<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Nonce {
	const DEFAULT_EXPIRY_LENGTH = MINUTE_IN_SECONDS / 2;
	const NONCE_ONLY            = 1;
	const NONCE_INFO_ARRAY      = 2;

	protected static $true_nonce;
	protected static $expiry_length;
	protected static $doing_update_api_request = null;
	protected static $private_auth_key;
	protected static $auth_header_name;

	public static function register() {

		if ( ! self::is_doing_api_request() ) {
			add_action( 'init', array( get_class(), 'add_endpoints' ), 10, 0 );
			add_action( 'wp', array( get_class(), 'register_nonce_cleanup' ) );
			add_action( 'wppus_nonce_cleanup', array( get_class(), 'clear_nonces' ) );
		}

		add_action( 'parse_request', array( get_class(), 'parse_request' ), -99, 0 );

		add_filter( 'query_vars', array( get_class(), 'query_vars' ), -99, 1 );
	}

	public static function init_auth( $private_auth_key, $auth_header_name = null ) {
		self::$private_auth_key = $private_auth_key;
		self::$auth_header_name = $auth_header_name;
	}

	public static function register_nonce_cleanup() {

		if ( ! wp_next_scheduled( 'wppus_nonce_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'wppus_nonce_cleanup' );
		}
	}

	public static function is_doing_api_request() {

		if ( null === self::$doing_update_api_request ) {
			self::$doing_update_api_request =
				false !== strpos( $_SERVER['REQUEST_URI'], 'wppus-token' ) &&
				false !== strpos( $_SERVER['REQUEST_URI'], 'wppus-nonce' );
		}

		return self::$doing_update_api_request;
	}

	public static function add_endpoints() {
		add_rewrite_rule(
			'^wppus-token/*?$',
			'index.php?$matches[1]&action=token&__wppus_nonce_api=1&',
			'top'
		);
		add_rewrite_rule(
			'^wppus-nonce/*?$',
			'index.php?$matches[1]&action=nonce&__wppus_nonce_api=1&',
			'top'
		);
	}

	public static function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wppus_nonce_api'] ) ) {
			$response = 'Malformed request';
			$code     = 400;

			if ( isset( $wp->query_vars['action'] ) ) {
				$method = $wp->query_vars['action'];

				if (
					is_string( $wp->query_vars['action'] ) &&
					method_exists(
						get_class(),
						'generate_' . $wp->query_vars['action'] . '_api_response'
					)
				) {
					$method = 'generate_' . $wp->query_vars['action'] . '_api_response';

					unset( $wp->query_vars['action'] );

					$response = self::$method( $wp->query_vars );
					$code     = 200;
				}
			}

			wp_send_json( $response, $code );

			die();
		}
	}

	public static function query_vars( $query_vars ) {
		$query_vars = array_merge(
			$query_vars,
			array(
				'__wppus_nonce_api',
				'api_auth_key',
				'action',
				'expiry_length',
			)
		);

		return $query_vars;
	}

	public static function create_nonce(
		$true_nonce = true,
		$expiry_length = self::DEFAULT_EXPIRY_LENGTH,
		$return_type = self::NONCE_ONLY,
		$store = true,
		$delegate = false,
		$delegate_args = array()
	) {

		if ( $delegate && is_array( $delegate_args ) && is_callable( $delegate ) ) {
			$delegate_args['true_nonce']    = $true_nonce;
			$delegate_args['expiry_length'] = $expiry_length;
			$nonce                          = call_user_func_array( $delegate, $delegate_args );
		} else {
			$id    = self::generate_id();
			$nonce = md5( wp_salt( 'nonce' ) . $id . microtime( true ) );
		}

		if ( $store ) {
			$result = self::store_nonce( $nonce, $true_nonce, $expiry_length );
		} else {
			$result = array(
				'nonce'      => $nonce,
				'true_nonce' => (bool) $true_nonce,
				'expiry'     => time() + abs( intval( $expiry_length ) ),
			);
		}

		if ( self::NONCE_INFO_ARRAY === $return_type ) {
			$return = $result;
		} else {
			$return = ( $result ) ? $result['nonce'] : $result;
		}

		return $return;
	}

	public static function get_nonce_expiry( $nonce ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wppus_nonce';
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE nonce = %s;", // @codingStandardsIgnoreLine
				$nonce
			)
		);

		if ( ! $row ) {
			$nonce_expiry = 0;
		} else {
			$nonce_expiry = $row->expiry;
		}

		return $nonce_expiry;
	}

	public static function validate_nonce( $value ) {

		if ( empty( $value ) ) {

			return false;
		}

		$nonce = self::fetch_nonce( $value );
		$valid = ( $nonce === $value );

		return $valid;
	}

	public static function store_nonce( $nonce, $true_nonce, $expiry_length ) {
		global $wpdb;

		$table  = $wpdb->prefix . 'wppus_nonce';
		$data   = array(
			'nonce'      => $nonce,
			'true_nonce' => (bool) $true_nonce,
			'expiry'     => time() + abs( intval( $expiry_length ) ),
		);
		$result = $wpdb->insert( $table, $data ); // @codingStandardsIgnoreLine

		if ( (bool) $result ) {

			return $data;
		}

		return false;
	}

	public static function delete_nonce( $value ) {
		global $wpdb;

		$table  = $wpdb->prefix . 'wppus_nonce';
		$where  = array( 'nonce' => $value );
		$result = $wpdb->delete( $table, $where ); // @codingStandardsIgnoreLine

		return (bool) $result;
	}

	public static function clear_nonces() {

		if ( defined( 'WP_SETUP_CONFIG' ) || defined( 'WP_INSTALLING' ) ) {

			return;
		}

		global $wpdb;

		$table  = $wpdb->prefix . 'wppus_nonce';
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE expiry < %d;", // @codingStandardsIgnoreLine
				time() - self::DEFAULT_EXPIRY_LENGTH
			)
		);

		return (bool) $result;
	}

	protected static function generate_token_api_response( $payload ) {
		$token = self::create_nonce(
			false,
			isset( $payload['expiry_length'] ) && is_numeric( $payload['expiry_length'] ) ?
				$payload['expiry_length'] :
				self::DEFAULT_EXPIRY_LENGTH,
			self::NONCE_ONLY,
		);

		return $token;
	}

	protected static function generate_nonce_api_response( $payload ) {
		$nonce = self::create_nonce(
			true,
			isset( $payload['expiry_length'] ) && is_numeric( $payload['expiry_length'] ) ?
				$payload['expiry_length'] :
				self::DEFAULT_EXPIRY_LENGTH,
			self::NONCE_ONLY,
		);

		return $nonce;
	}

	protected static function fetch_nonce( $value ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wppus_nonce';
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE nonce = %s;", // @codingStandardsIgnoreLine
				$value
			)
		);

		if ( ! $row ) {
			$nonce = null;
		} else {
			$nonce         = $row->nonce;
			$nonce_expires = $row->expiry;

			if ( $nonce_expires < time() ) {
				$nonce = null;
			}

			if ( $row->true_nonce || null === $nonce ) {
				self::delete_nonce( $value );
			}
		}

		return $nonce;
	}

	protected static function generate_id() {
		require_once ABSPATH . 'wp-includes/class-phpass.php';

		$hasher = new PasswordHash( 8, false );

		return md5( $hasher->get_random_bytes( 100, false ) );
	}

	protected function authorize_private() {
		$key = false;

		if (
			self::$auth_header_name &&
			isset( $_SERVER[ self::$auth_header_name ] ) &&
			! empty( $_SERVER[ self::$auth_header_name ] )
		) {
			$key = $_SERVER[ self::$auth_header_name ];
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

		$is_auth = self::$private_auth_key === $key;

		return $is_auth;
	}

}
