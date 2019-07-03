<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_License_Server {

	const DATA_SEPARATOR       = '|';
	const CRYPT_HMAC_SEPARATOR = '-';

	public static $license_definition = array(
		'id'                  => 0,
		'license_key'         => '',
		'max_allowed_domains' => 1,
		'allowed_domains'     => array(),
		'status'              => '',
		'owner_name'          => '',
		'email'               => '',
		'company_name'        => '',
		'txn_id'              => '',
		'date_created'        => '',
		'date_renewed'        => '',
		'date_expiry'         => '',
		'package_slug'        => '',
		'package_type'        => '',
	);

	public static $browsing_query = array(
		'relationship' => 'AND',
		'limit'        => 10,
		'offset'       => 0,
		'order_by'     => 'date_created',
		'criteria'     => array(),
	);

	public static $browsing_operators = array(
		'=',
		'>',
		'<',
		'>=',
		'<=',
		'BETWEEN',
		'NOT BETWEEN',
		'IN',
		'NOT IN',
		'LIKE',
		'NOT LIKE',
	);

	public static $license_statuses = array(
		'pending',
		'activated',
		'deactivated',
		'blocked',
		'expired',
	);

	protected $private_api_auth_key;

	public function __construct() {}

	public function build_license( $payload ) {
		$payload = $this->extend_license_payload( $this->filter_license_payload( $payload ) );

		if ( isset( $payload['id'] ) ) {
			unset( $payload['id'] );
		}

		return $this->cleanup_license_payload( $payload );
	}

	public function browse_licenses( $payload ) {
		global $wpdb;

		$result         = array();
		$prepare_args   = array();
		$payload        = apply_filters( 'wppus_browse_licenses_payload', $payload );
		$browsing_query = $this->build_browsing_query( $payload );

		$sql = "SELECT * FROM {$wpdb->prefix}wppus_licenses WHERE 1 = 1 ";

		foreach ( $browsing_query['criteria'] as $crit ) {
			$sql .= $browsing_query['relationship'] . ' ' . $crit['field'] . ' ';

			if ( 'id' === $crit['field'] || 'max_allowed_domains' === $crit['field'] ) {
				$placeholder = '%d';
			} else {
				$placeholder = '%s';
			}

			if ( 'IN' === $crit['operator'] || 'NOT IN' === $crit['operator'] ) {
				$sql .= $crit['operator'] . ' (' . implode( ', ', array_fill( 0, count( $crit['value'] ), $placeholder ) ) . ')';
			} elseif ( 'BETWEEN' === $crit['operator'] || 'NOT BETWEEN' === $crit['operator'] ) {
				$sql .= $crit['operator'] . ' ' . $placeholder . ' AND ' . $placeholder;
			} else {
				$sql .= $crit['operator'] . ' ' . $placeholder;
			}

			if ( ! is_array( $crit['value'] ) ) {
				$prepare_args[] = $crit['value'];
			} else {
				$prepare_args = array_merge( $prepare_args, $crit['value'] );
			}
		}

		$sql .= ' ORDER BY ' . $browsing_query['order_by'] . ' LIMIT %d OFFSET %d';

		$prepare_args[] = $browsing_query['limit'];
		$prepare_args[] = $browsing_query['offset'];

		$licenses = $wpdb->get_results( $wpdb->prepare( $sql, $prepare_args ) ); // @codingStandardsIgnoreLine

		if ( ! empty( $licenses ) ) {

			foreach ( $licenses as $index => $license ) {
				$licenses[ $index ]->allowed_domains = maybe_unserialize( $license->allowed_domains );
			}
		}

		do_action( 'wppus_did_browse_licenses', $licenses );

		return $licenses;
	}

	public function read_license( $payload ) {
		$payload    = $this->filter_license_payload( $payload );
		$payload    = apply_filters( 'wppus_read_license_payload', $payload );
		$validation = ( isset( $payload['id'] ) && ! empty( $payload['id'] ) );
		$validation = $validation || ( isset( $payload['license_key'] ) && ! empty( $payload['license_key'] ) );
		$return     = array();

		if ( true === $validation ) {
			global $wpdb;

			$where_field = ( isset( $payload['id'] ) && ! empty( $payload['id'] ) ) ? 'id' : 'license_key';
			$where_value = $payload[ $where_field ];
			$payload     = $this->sanitize_license( $payload );

			$sql     = "SELECT * FROM {$wpdb->prefix}wppus_licenses WHERE {$where_field} = %s;";
			$license = $wpdb->get_row( $wpdb->prepare( $sql, $where_value ) ); // @codingStandardsIgnoreLine

			if ( is_object( $license ) ) {
				$license->allowed_domains = maybe_unserialize( $license->allowed_domains );

				$return = $license;
			}
		}

		do_action( 'wppus_did_read_license', $return );

		return $return;
	}

	public function edit_license( $payload ) {
		$payload    = $this->cleanup_license_payload( $this->filter_license_payload( $payload ) );
		$payload    = apply_filters( 'wppus_edit_license_payload', $payload );
		$validation = $this->validate_license( $payload, true );
		$return     = $validation;

		if ( true === $validation ) {
			global $wpdb;

			$field   = isset( $payload['id'] ) ? 'id' : 'license_key';
			$where   = array( $field => $payload[ $field ] );
			$payload = $this->sanitize_license( $payload );

			if ( isset( $payload['allowed_domains'] ) ) {
				$payload['allowed_domains'] = maybe_serialize( $payload['allowed_domains'] );
			}

			$result = $wpdb->update(
				$wpdb->prefix . 'wppus_licenses',
				$payload,
				$where
			);

			if ( false !== $result ) {
				$return = $this->read_license( $payload );
			} else {
				$return = array();

				error_log( __METHOD__ . ': License update failed - database update error.' ); // @codingStandardsIgnoreLine
			}
		} else {
			$return = array( 'errors' => $return );
		}

		do_action( 'wppus_did_edit_license', $return );

		return $return;
	}

	public function add_license( $payload ) {
		$payload    = $this->build_license( $payload );
		$license    = apply_filters( 'wppus_add_license_payload', $payload );
		$validation = $this->validate_license( $license );
		$return     = $validation;

		if ( true === $validation ) {
			global $wpdb;

			$license = $this->sanitize_license( $license );

			$license['id']              = null;
			$license['allowed_domains'] = maybe_serialize( $license['allowed_domains'] );

			$result = $wpdb->insert(
				$wpdb->prefix . 'wppus_licenses',
				$license
			);

			if ( false !== $result ) {
				$return = $this->read_license( $license );
			} else {
				$return = array();

				error_log( __METHOD__ . ': License creation failed - database insertion error.' ); // @codingStandardsIgnoreLine
			}
		} else {
			$return = array( 'errors' => $return );
		}

		do_action( 'wppus_did_add_license', $return );

		return $return;
	}

	public function delete_license( $payload ) {
		$payload    = $this->filter_license_payload( $payload );
		$payload    = apply_filters( 'wppus_delete_license_payload', $payload );
		$validation = ( isset( $payload['id'] ) && ! empty( $payload['id'] ) );
		$validation = $validation || ( isset( $payload['license_key'] ) && ! empty( $payload['license_key'] ) );
		$return     = array();

		if ( true === $validation ) {
			global $wpdb;

			$field   = ( isset( $payload['id'] ) && ! empty( $payload['id'] ) ) ? 'id' : 'license_key';
			$where   = array( $field => $payload[ $field ] );
			$payload = $this->sanitize_license( $payload );
			$license = $this->read_license( $payload );

			$result = $wpdb->delete(
				$wpdb->prefix . 'wppus_licenses',
				$where
			);

			if ( false !== $result ) {
				$return = $license;
			} else {
				$return = array();

				error_log( __METHOD__ . ': License removal failed - database deletion error.' ); // @codingStandardsIgnoreLine
			}
		}

		do_action( 'wppus_did_delete_license', $return );

		return $return;
	}

	public function generate_license_signature( $license, $domain ) {

		$crypt_payload = array(
			$domain,
			$license->package_slug,
		);

		$hmac_payload = array(
			$license->license_key,
			$license->id,
		);

		$crypt = CryptoUrl::encrypt(
			implode( self::DATA_SEPARATOR, $crypt_payload ),
			get_option( 'wppus_license_crypto_key', 'crypto' )
		);

		$hmac = CryptoUrl::hmac_sign(
			implode( self::DATA_SEPARATOR, $hmac_payload ),
			get_option( 'wppus_license_hmac_key', 'hmac' )
		);

		$signature = $crypt . self::CRYPT_HMAC_SEPARATOR . $hmac;

		return $signature;
	}

	public function is_signature_valid( $license, $license_signature ) {
		$config     = WPPUS_License_API::get_config();
		$valid      = false;
		$raw_data   = explode( self::CRYPT_HMAC_SEPARATOR, $license_signature );
		$hmac       = end( $raw_data );
		$crypt      = reset( $raw_data );
		$hmac_key   = $config['licenses_hmac_key'];
		$crypto_key = $config['licenses_crypto_key'];

		if ( ! ( empty( $crypt ) || empty( $hmac ) || ! CryptoUrl::hmac_verify( $hmac, $hmac_key ) ) ) {
			$payload = null;

			try {
				$payload = CryptoUrl::decrypt( $crypt, $crypto_key );
			} catch ( Exception $e ) {
				$payload = false;
			}

			if ( $payload ) {
				$data         = explode( self::DATA_SEPARATOR, $payload );
				$domain       = isset( $data[0] ) ? $data[0] : null;
				$package_slug = isset( $data[1] ) ? $data[1] : null;

				$condition = in_array( $domain, $license->allowed_domains, true );
				$condition = $condition && $license->package_slug === $package_slug;

				// @todo remove in 2.0
				if ( ! $condition ) {
					$condition = in_array( $domain, $license->allowed_domains, true );

					if ( 'plugin' === $license->package_type ) {
						$item_reference = $license->package_slug . '/' . $license->package_slug . '.php';
					} else {
						$item_reference = $license->package_slug . '/functions.php';
					}

					$condition = $condition && $item_reference === $package_slug;
				}

				if ( $condition ) {
					$valid = true;
				}
			}
		}

		return $valid;
	}

	public function switch_expired_licenses_status() {
		global $wpdb;

		$sql = "UPDATE {$wpdb->prefix}wppus_licenses 
				SET status = 'expired' 
				WHERE date_expiry <= %s 
				AND status != 'blocked'
				AND date_expiry != '0000-00-00'";

		$wpdb->query( $wpdb->prepare( $sql, mysql2date( 'Y-m-d', current_time( 'mysql' ), false ) ) ); // @codingStandardsIgnoreLine
	}

	public function update_licenses_status( $status, $license_ids = array() ) {
		global $wpdb;

		$where = '';

		if ( ! empty( $license_ids ) ) {
			$where = " AND id IN ('" . implode( "','", $license_ids ) . "')";
		}

		$sql = "UPDATE {$wpdb->prefix}wppus_licenses SET status = %s WHERE 1=1" . $where;

		$wpdb->query( $wpdb->prepare( $sql, $status ) ); // @codingStandardsIgnoreLine
	}

	public function purge_licenses( $license_ids = array() ) {
		global $wpdb;

		$where = '';

		if ( ! empty( $license_ids ) ) {
			$where = " AND id IN ('" . implode( "','", $license_ids ) . "')";
		}

		$sql = "DELETE FROM {$wpdb->prefix}wppus_licenses WHERE 1=1" . $where;

		$wpdb->query( $sql ); // @codingStandardsIgnoreLine
	}

	public function dispatch( $response, $response_status_code ) {
		wp_send_json( $response, $response_status_code );

		exit();
	}

	protected function build_browsing_query( $payload ) {
		$payload         = array_intersect_key( $payload, self::$browsing_query );
		$payload         = array_merge( self::$browsing_query, $payload );
		$faulty_criteria = array(
			'operator' => '=',
			'field'    => 0,
			'value'    => 1,
		);

		if ( empty( $payload['relationship'] ) ) {
			$payload['relationship'] = self::$browsing_query['relationship'];
		} else {
			$payload['relationship'] = strtoupper( $payload['relationship'] );

			if ( 'AND' !== $payload['relationship'] || 'OR' !== $payload['relationship'] ) {
				$payload['relationship'] = self::$browsing_query['relationship'];
			}
		}

		if ( ! is_numeric( $payload['limit'] ) ) {
			$payload['limit'] = self::$browsing_query['limit'];
		} else {
			$payload['limit'] = absint( $payload['limit'] );
		}

		if ( ! is_numeric( $payload['offset'] ) ) {
			$payload['offset'] = self::$browsing_query['offset'];
		} else {
			$payload['offset'] = absint( $payload['offset'] );
		}

		if ( ! in_array( $payload['order_by'], array_keys( self::$license_definition ), true ) ) {
			$payload['order_by'] = 'date_created';
		}

		if ( ! isset( $payload['criteria'][0] ) ) {
			$payload['criteria'] = self::$browsing_query['criteria'];
		}

		if ( isset( $payload['criteria'] ) && ! empty( $payload['criteria'] ) ) {

			foreach ( $payload['criteria'] as $index => $crit ) {
				$crit = array_intersect_key( $crit, $faulty_criteria );

				if (
					! isset( $crit['operator'], $crit['value'], $crit['field'] ) ||
					empty( $crit['operator'] ) || empty( $crit['value'] ) || empty( $crit['field'] )
				) {
					$crit = $faulty_criteria;
				}

				if ( ! in_array( $crit['operator'], self::$browsing_operators, true ) ) {
					$crit = $faulty_criteria;
				}

				if ( ! in_array( $crit['field'], array_keys( self::$license_definition ), true ) ) {
					$crit = $faulty_criteria;
				}

				if (
					( 'BETWEEN' === $crit['operator'] || 'NOT BETWEEN' === $crit['operator'] ) &&
					( ! is_array( $crit['value'] ) || 2 !== count( $crit['value'] ) )
				) {
					$crit = $faulty_criteria;
				} elseif (
					( 'IN' === $crit['operator'] || 'NOT IN' === $crit['operator'] ) &&
					! is_array( $crit['value'] )
				) {
					$crit['value'] = array( $crit['value'] );
				} elseif (
					( 'IN' === $crit['operator'] || 'NOT IN' === $crit['operator'] ) &&
					empty( $crit['value'] )
				) {
					$crit = $faulty_criteria;
				} elseif ( is_array( $crit['value'] ) ) {
					$crit = $faulty_criteria;
				}

				$payload['criteria'][ $index ] = $crit;
			}
		}

		return $payload;
	}

	protected function cleanup_license_payload( $payload ) {

		if ( isset( $payload['license_key'] ) && empty( $payload['license_key'] ) ) {
			$payload['license_key'] = bin2hex( openssl_random_pseudo_bytes( 12 ) );
		}

		if ( isset( $payload['date_created'] ) && empty( $payload['date_created'] ) ) {
			$payload['date_created'] = mysql2date( 'Y-m-d', current_time( 'mysql' ), false );
		}

		if ( isset( $payload['status'] ) && empty( $payload['status'] ) ) {
			$payload['status'] = 'pending';
		}

		return $payload;
	}

	protected function filter_license_payload( $payload ) {

		return array_intersect_key( $payload, self::$license_definition );
	}

	protected function extend_license_payload( $payload ) {

		return array_merge( self::$license_definition, $payload );
	}

	protected function sanitize_license( $license ) {

		foreach ( $license as $key => $value ) {

			if ( 'allowed_domains' === $key ) {

				if ( is_array( $value ) && ! empty( $value ) ) {

					foreach ( $value as $index => $domain ) {

						if ( ! filter_var( 'admin@' . $domain, FILTER_VALIDATE_EMAIL ) ) {
							unset( $license['allowed_domains'][ $index ] );
						}
					}
				} else {
					$license['allowed_domains'] = array();
				}
			} else {
				$license[ $key ] = wp_strip_all_tags( $value );
			}
		}

		if ( ! empty( $license['date_expiry'] ) &&
			'blocked' !== $license['status'] &&
			strtotime( $license['date_expiry'] ) <= strtotime( mysql2date( 'Y-m-d', current_time( 'mysql' ), false ) ) ) {
			$license['status'] = 'expired';
		}

		return $license;
	}

	protected function validate_license( $license, $partial = false ) {
		global $wpdb;

		$errors = array();
		$return = true;

		if ( ! is_array( $license ) ) {
			$errors[] = __( 'An unexpected error has occured. Please try again. If the problem persists, please contact the author of the plugin.', 'wppus' );
		} else {
			$date_regex = '/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/';

			if ( $partial ) {

				if ( ! isset( $license['id'] ) && ! isset( $license['license_key'] ) ) {
					$errors[] = __( 'An ID or a license key is required to identify the license.', 'wppus' );
				}

				if ( isset( $license['id'] ) ) {

					if ( ! is_numeric( $license['id'] ) ) {
						$errors[] = __( 'The license key is required and must be a string.', 'wppus' );
					} else {
						$sql    = "SELECT COUNT(*) FROM {$wpdb->prefix}wppus_licenses WHERE id = %s;";
						$exists = ( '1' === $wpdb->get_var( $wpdb->prepare( $sql, $license['id'] ) ) ); // @codingStandardsIgnoreLine

						if ( ! $exists ) {
							$errors[] = __( 'The license cannot be found.', 'wppus' );
						}
					}
				} else {
					$sql    = "SELECT COUNT(*) FROM {$wpdb->prefix}wppus_licenses WHERE license_key = %s;";
					$exists = ( '1' === $wpdb->get_var( $wpdb->prepare( $sql, $license['license_key'] ) ) ); // @codingStandardsIgnoreLine

					if ( ! $exists ) {
						$errors[] = __( 'The license cannot be found.', 'wppus' );
					}
				}
			}

			if ( ! ( $partial && ! isset( $license['license_key'] ) ) && ( ! is_string( $license['license_key'] ) || empty( $license['license_key'] ) ) ) {
				$errors[] = __( 'The license key is required and must be a string.', 'wppus' );
			} elseif ( ! $partial && isset( $license['license_key'] ) ) {
				$sql    = "SELECT COUNT(*) FROM {$wpdb->prefix}wppus_licenses WHERE license_key = %s;";
				$exists = ( '0' !== $wpdb->get_var( $wpdb->prepare( $sql, $license['license_key'] ) ) ); // @codingStandardsIgnoreLine

				if ( $exists ) {
					$errors[] = __( 'A value already exists for the given license key. Each key must be unique.', 'wppus' );
				}
			}

			if ( ! ( $partial && ! isset( $license['max_allowed_domains'] ) ) && ( ! is_numeric( $license['max_allowed_domains'] ) || $license['max_allowed_domains'] < 1 ) ) {
				$errors[] = __( 'The number of allowed domains is required and must be greater than 1.', 'wppus' );
			}

			if ( ! ( $partial && ! isset( $license['status'] ) ) && ( ! in_array( $license['status'], self::$license_statuses, true ) ) ) {
				$errors[] = __( 'The license status is invalid.', 'wppus' );
			}

			if ( ! ( $partial && ! isset( $license['email'] ) ) && ( ! filter_var( $license['email'] ) ) ) {
				$errors[] = __( 'The registered email is required and must be a valid email address.', 'wppus' );
			}

			if ( ! ( $partial && ! isset( $license['date_created'] ) ) && ( empty( $license['date_created'] ) || ! preg_match( $date_regex, $license['date_created'] ) ) ) {
				$errors[] = __( 'The creation date is required and must follow the following format: YYYY-MM-DD', 'wppus' );
			}

			if ( ! ( $partial && ! isset( $license['date_renewed'] ) ) && ( ! empty( $license['date_renewed'] ) && ! preg_match( $date_regex, $license['date_renewed'] ) ) ) {
				$errors[] = __( 'The renewal date must follow the following format: YYYY-MM-DD', 'wppus' );
			}

			if ( ! ( $partial && ! isset( $license['date_expiry'] ) ) && ( ! empty( $license['date_expiry'] ) && ! preg_match( $date_regex, $license['date_expiry'] ) ) ) {
				$errors[] = __( 'The expiry date must follow the following format: YYYY-MM-DD', 'wppus' );
			}

			if ( ! ( $partial && ! isset( $license['package_slug'] ) ) && ( empty( $license['package_slug'] ) || ! preg_match( '/[a-z0-9-]*/', $license['package_slug'] ) ) ) {
				$errors[] = __( 'The package slug is required and must contain only alphanumeric characters or dashes.', 'wppus' );
			}

			if ( ! ( $partial && ! isset( $license['package_type'] ) ) && ( 'plugin' !== $license['package_type'] && 'theme' !== $license['package_type'] ) ) {
				$errors[] = __( 'The package type is required and must be "plugin" or "theme".', 'wppus' );
			}

			if ( ! ( $partial && ! isset( $license['owner_name'] ) ) && ! empty( $license['owner_name'] ) && ! is_string( $license['owner_name'] ) ) {
				$errors[] = __( 'The license owner name must be a string.', 'wppus' );
			}

			if ( ! ( $partial && ! isset( $license['company_name'] ) ) && ! empty( $license['company_name'] ) && ! is_string( $license['company_name'] ) ) {
				$errors[] = __( 'The company name must be a string.', 'wppus' );
			}

			if ( ! ( $partial && ! isset( $license['txn_id'] ) ) && ! empty( $license['txn_id'] ) && ! is_string( $license['txn_id'] ) ) {
				$errors[] = __( 'The transaction ID must be a string.', 'wppus' );
			}

			if ( ! ( $partial && ! isset( $license['allowed_domains'] ) ) ) {

				if ( ! is_array( $license['allowed_domains'] ) ) {
					$errors[] = __( 'The allowed domains must be an array.', 'wppus' );
				} elseif ( ! empty( $license['allowed_domains'] ) ) {

					foreach ( $license['allowed_domains'] as $key => $value ) {

						if ( ! filter_var( 'admin@' . $value, FILTER_VALIDATE_EMAIL ) ) {
							$errors[] = __( 'All allowed domains values must be valid domains or subdomains without protocol.', 'wppus' );

							break;
						}
					}
				}
			}
		}

		if ( ! empty( $errors ) ) {
			$return = $errors;
		}

		return $return;
	}
}
