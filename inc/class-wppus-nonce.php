<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Nonce {
	protected static $true_nonce;
	protected static $expiry_length;

	const DEFAULT_EXPIRY_LENGTH = MINUTE_IN_SECONDS / 2;

	public static function register() {
		add_action( 'wp', array( get_class(), 'register_nonce_cleanup' ) );
		add_action( 'wppus_nonce_cleanup', array( get_class(), 'clear_nonces' ) );
	}

	public static function init( $true_nonce = true, $expiry_length = self::DEFAULT_EXPIRY_LENGTH ) {
		self::$true_nonce    = $true_nonce;
		self::$expiry_length = $expiry_length;
	}

	public static function register_nonce_cleanup() {

		if ( ! wp_next_scheduled( 'wppus_nonce_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'wppus_nonce_cleanup' );
		}
	}

	public static function create_nonce(
		$include_expiry = false,
		$store = true,
		$delegate = false,
		$delegate_args = array()
	) {

		if ( $delegate && ( is_array( $delegate ) ) ) {
			$nonce = call_user_func_array( $delegate, $delegate_args );
		} else {
			$id    = self::generate_id();
			$nonce = md5( wp_salt( 'nonce' ) . $id . microtime( true ) );
		}

		if ( $store ) {
			$result = self::store_nonce( $nonce );
		} else {
			$result = array(
				'nonce'  => $nonce,
				'expiry' => time() + self::$expiry_length,
			);
		}

		if ( $include_expiry ) {
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
			$nonce_expires = 0;
		} else {
			$nonce_expires = $row->expiry;
		}

		return $nonce_expires;
	}

	public static function validate_nonce( $value ) {

		if ( empty( $value ) ) {

			return false;
		}

		$nonce = self::fetch_nonce( $value );
		$valid = ( $nonce === $value );

		return $valid;
	}

	public static function store_nonce( $nonce ) {
		global $wpdb;

		$table  = $wpdb->prefix . 'wppus_nonce';
		$data   = array(
			'nonce'  => $nonce,
			'expiry' => time() + self::$expiry_length,
		);
		$result = $wpdb->insert( $table, $data ); // @codingStandardsIgnoreLine

		if ( (bool) $result ) {

			return $data;
		}

		return false;
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

			if ( self::$true_nonce || null === $nonce ) {
				self::delete_nonce( $value );
			}
		}

		return $nonce;
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


	protected static function generate_id() {

		require_once ABSPATH . 'wp-includes/class-phpass.php';

		$hasher = new PasswordHash( 8, false );

		return md5( $hasher->get_random_bytes( 100, false ) );
	}

}
