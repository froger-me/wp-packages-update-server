<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_CLI extends WP_CLI_Command {
	protected const RESOURCE_NOT_FOUND = 3;
	protected const DEFAULT_ERROR      = 1;
	protected const LOG_METHODS        = array(
		'line',
		'log',
		'success',
		'debug',
		'warning',
		'error',
		'halt',
		'error_multi_line',
	);
	protected const PACKAGE_TYPES      = array(
		'plugin',
		'theme',
		'generic',
	);

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	/**
	 * Cleans up the cache folder in wp-content/wppus.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus cleanup_cache
	 */
	public function cleanup_cache() {
		$this->cleanup( 'cache' );
	}

	/**
	 * Cleans up the logs folder in wp-content/wppus.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus cleanup_logs
	 */
	public function cleanup_logs() {
		$this->cleanup( 'logs' );
	}

	/**
	 * Cleans up the tmp folder in wp-content/wppus.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus cleanup_tmp
	 */
	public function cleanup_tmp() {
		$this->cleanup( 'tmp' );
	}

	/**
	 * Cleans up the cache, logs and tmp folders in wp-content/wppus.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus cleanup-all
	 */
	public function cleanup_all() {
		$this->cleanup( 'cache' );
		$this->cleanup( 'logs' );
		$this->cleanup( 'tmp' );
	}

	/**
	 * Checks for updates for a package.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The package slug.
	 *
	 * <type>
	 * : The package type.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus check_remote_package_update my-plugin plugin
	 */
	public function check_remote_package_update( $args, $assoc_args ) {
		$slug = $args[0];
		$type = $args[1];

		if ( ! in_array( $type, self::PACKAGE_TYPES, true ) ) {
			$this->process_result( false, '', 'Invalid package type', self::DEFAULT_ERROR, 'error' );
		}

		$result          = wppus_check_remote_package_update( $slug, $type );
		$success_message = $result ? 'Update available' : 'No update needed';
		$error_message   = 'Unknown package slug';
		$result          = null !== $result;

		$this->process_result( $result, $success_message, $error_message, self::RESOURCE_NOT_FOUND );
	}

	/**
	 * Downloads a package.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The package slug.
	 *
	 * <type>
	 * : The package type.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus download_remote_package my-plugin plugin
	 */

	public function download_remote_package( $args, $assoc_args ) {
		$slug = $args[0];
		$type = $args[1];

		if ( ! in_array( $type, self::PACKAGE_TYPES, true ) ) {
			$this->process_result( false, '', 'Invalid package type', self::DEFAULT_ERROR, 'error' );
		}

		$result          = wppus_download_remote_package( $slug, $type, true );
		$success_message = 'Package downloaded';
		$error_message   = 'Unable to download package';

		$this->process_result( $result, $success_message, $error_message, self::RESOURCE_NOT_FOUND );
	}

	/**
	 * Deletes a package.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The package slug.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus delete_package my-plugin
	 */
	public function delete_package( $args, $assoc_args ) {
		$slug            = $args[0];
		$result          = wppus_delete_package( $slug );
		$success_message = 'Package deleted';
		$error_message   = 'Unable to delete package';

		$this->process_result( $result, $success_message, $error_message, self::RESOURCE_NOT_FOUND );
	}

	/**
	 * Gets package info.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The package slug.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus get_package_info my-plugin
	 */
	public function get_package_info( $args, $assoc_args ) {
		$slug          = $args[0];
		$result        = wppus_get_package_info( $slug );
		$error_message = 'Unable to get package info';

		$this->process_result( $result, $result, $error_message, self::RESOURCE_NOT_FOUND );
	}

	/**
	 * Creates a nonce.
	 *
	 * ## OPTIONS
	 *
	 * <true_nonce>
	 * : Whether to create a true nonce, or a reusable token.
	 *
	 * <expiry_length>
	 * : The expiry length.
	 *
	 * <data>
	 * : The data to store along the nonce, in JSON.
	 *
	 * <return_type>
	 * : The return type - nonce_only or nonce_info_array.
	 *
	 * <store>
	 * : Whether to store the nonce.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus create_nonce --true_nonce=true --expiry_length=30 --data='{}' --return=nonce_only --store=true
	 */
	public function create_nonce( $args, $assoc_args ) {
		$assoc_args = wp_parse_args(
			$assoc_args,
			array(
				'true_nonce'    => true,
				'expiry_length' => WPPUS_Nonce::DEFAULT_EXPIRY_LENGTH,
				'data'          => array(),
				'return_type'   => WPPUS_Nonce::NONCE_ONLY,
				'store'         => true,
			)
		);

		if ( 'nonce_info_array' === $assoc_args['return_type'] ) {
			$assoc_args['return_type'] = WPPUS_Nonce::NONCE_INFO_ARRAY;
		} else {
			$assoc_args['return_type'] = WPPUS_Nonce::NONCE_ONLY;
		}

		if ( ! is_numeric( $assoc_args['expiry_length'] ) ) {
			$assoc_args['expiry_length'] = WPPUS_Nonce::DEFAULT_EXPIRY_LENGTH;
		}

		if ( ! is_bool( $assoc_args['true_nonce'] ) ) {
			$assoc_args['true_nonce'] = true;
		}

		if ( ! is_bool( $assoc_args['store'] ) ) {
			$assoc_args['store'] = true;
		}

		$assoc_args['data'] = json_decode( $assoc_args['data'], true );

		if ( ! is_array( $assoc_args['data'] ) ) {
			$assoc_args['data'] = array();
		}

		$result        = wppus_create_nonce(
			$assoc_args['true_nonce'],
			$assoc_args['expiry_length'],
			$assoc_args['data'],
			$assoc_args['return_type'],
			$assoc_args['store']
		);
		$error_message = 'Unable to create nonce';

		$this->process_result( $result, $result, $error_message, self::DEFAULT_ERROR, 'error' );
	}

	/**
	 * Build a Nonce API signature.
	 *
	 * ## OPTIONS
	 *
	 * <api_key_id>
	 * : The ID of the API key.
	 *
	 * <api_key>
	 * : The API key.
	 *
	 * <timestamp>
	 * : The timestamp.
	 *
	 * <payload>
	 * : The payload.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus build_nonce_api_signature --api_key_id='L**api_key' --timestamp=1704067200 --api_key=da9d20647163a1f3c04844387f91e2c3 --payload='{"key": "value"}'
	 *
	 */
	public function build_nonce_api_signature( $args, $assoc_args ) {
		$assoc_args            = wp_parse_args(
			$assoc_args,
			array(
				'timestamp' => time(),
			)
		);
		$assoc_args['payload'] = json_decode( $assoc_args['payload'], true );

		if (
			empty( $assoc_args['api_key_id'] ) ||
			empty( $assoc_args['timestamp'] ) ||
			empty( $assoc_args['api_key'] ) ||
			empty( $assoc_args['payload'] )
		) {
			$this->process_result( false, '', 'Invalid arguments', self::DEFAULT_ERROR, 'error' );
		}

		$result = wppus_build_nonce_api_signature(
			$assoc_args['api_key_id,'],
			$assoc_args['api_key,'],
			$assoc_args['timestamp,'],
			$assoc_args['payload']
		);

		$this->process_result( $result, $result, 'Unable to create signature', self::DEFAULT_ERROR, 'error' );
	}

	/**
	 * Clears nonces.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus clear_nonces
	 */
	public function clear_nonces() {
		$result          = wppus_clear_nonces();
		$success_message = 'Expired nonce cleared';
		$error_message   = 'Unable to create nonce';

		$this->process_result( $result, $success_message, $error_message, self::DEFAULT_ERROR, 'error' );
	}

	/**
	 * Gets the expiry time of a nonce.
	 *
	 * ## OPTIONS
	 *
	 * <nonce>
	 * : The nonce.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus get_nonce_expiry <nonce>
	 */
	public function get_nonce_expiry( $args, $assoc_args ) {
		$nonce           = $args[0];
		$result          = wppus_get_nonce_expiry( $nonce );
		$error_message   = 'Unable to get nonce expiry';
		$success_message = $result;

		$this->process_result( $result, $success_message, $error_message, self::RESOURCE_NOT_FOUND );
	}

	/**
	 * Gets data saved along with a nonce.
	 *
	 * ## OPTIONS
	 *
	 * <nonce>
	 * : The nonce.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus get_nonce_data <nonce>
	 */
	public function get_nonce_data( $args, $assoc_args ) {
		$nonce           = $args[0];
		$result          = wppus_get_nonce_data( $nonce );
		$error_message   = 'Unable to get nonce data';
		$success_message = $result;

		$this->process_result( $result, $success_message, $error_message, self::RESOURCE_NOT_FOUND );
	}

	/**
	 * Deletes a nonce.
	 *
	 * ## OPTIONS
	 *
	 * <nonce>
	 * : The nonce.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus delete_nonce <nonce>
	 */
	public function delete_nonce( $args, $assoc_args ) {
		$nonce           = $args[0];
		$result          = wppus_delete_nonce( $nonce );
		$error_message   = 'Unable to delete nonce';
		$success_message = 'Nonce deleted';

		$this->process_result( $result, $success_message, $error_message, self::RESOURCE_NOT_FOUND );
	}

	/**
	 * Browse licenses.
	 *
	 * ## OPTIONS
	 *
	 * <browse_query>
	 * : The browse query, as JSON
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus browse_licenses <browse_query>
	 */
	public function browse_licenses( $args, $assoc_args ) {
		$result          = wppus_browse_licenses( $args[0] );
		$error_message   = 'Unable to browse licenses';
		$success_message = $result;

		$this->process_result( $result, $success_message, $error_message, self::DEFAULT_ERROR, 'error' );
	}

	/**
	 * Read a license by ID or key.
	 *
	 * ## OPTIONS
	 *
	 * <license_key_or_id>
	 * : The license key or ID.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus read_license <license_key_or_id>
	 */
	public function read_license( $args, $assoc_args ) {
		$license_data = array();

		if ( is_numeric( $args[0] ) ) {
			$license_data['id'] = $args[0];
		} else {
			$license_data['license_key'] = $args[0];
		}

		$result          = wppus_read_license( $license_data );
		$error_message   = 'Unable to read license';
		$success_message = $result;

		$this->process_result( $result, $success_message, $error_message, self::RESOURCE_NOT_FOUND );
	}

	/**
	 * Add a license.
	 *
	 * ## OPTIONS
	 *
	 * <license_data>
	 * : The license data, as JSON - see `$params` for the License API action `add` in the License API documentation for details.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus add_license <license_data>
	 */
	public function add_license( $args, $assoc_args ) {
		$result        = wppus_add_license( $args[0] );
		$error_message = 'Unable to add the license';

		$this->process_result( $result, $result, $error_message, self::DEFAULT_ERROR, 'error' );
	}

	/**
	 * Edit a license.
	 *
	 * ## OPTIONS
	 *
	 * <license_data>
	 * : The license data, as JSON - see `$params` for the License API action `edit` in the License API documentation for details.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus edit_license <license_data>
	 */
	public function edit_license( $args, $assoc_args ) {
		$result        = wppus_edit_license( $args[0] );
		$error_message = 'Unable to edit the license';

		$this->process_result( $result, $result, $error_message, self::DEFAULT_ERROR, 'error' );
	}

	/**
	 * Delete a license.
	 *
	 * ## OPTIONS
	 *
	 * <license_key_or_id>
	 * : The license key or ID.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus delete_license <license_key_or_id>
	 */
	public function delete_license( $args, $assoc_args ) {
		$license_data = array();

		if ( is_numeric( $args[0] ) ) {
			$license_data['id'] = $args[0];
		} else {
			$license_data['license_key'] = $args[0];
		}

		$result        = wppus_delete_license( $license_data );
		$error_message = 'Unable to delete the license';

		$this->process_result( $result, $result, $error_message, self::DEFAULT_ERROR, 'error' );
	}

	/**
	 * Check a license.
	 *
	 * ## OPTIONS
	 *
	 * <license_key_or_id>
	 * : The license key or ID.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus check_license <license_key_or_id>
	 */
	public function check_license( $args, $assoc_args ) {
		$license_data = array();

		if ( is_numeric( $args[0] ) ) {
			$license_data['id'] = $args[0];
		} else {
			$license_data['license_key'] = $args[0];
		}

		$result        = wppus_check_license( $license_data );
		$error_message = 'Unable to check the license';

		$this->process_result( $result, $result, $error_message, self::DEFAULT_ERROR, 'error' );
	}

	/**
	 * Activate a license for a domain.
	 *
	 * ## OPTIONS
	 *
	 * <license_key>
	 * : The license key.
	 *
	 * <domain>
	 * : The domain.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus activate_license <license_key> <domain>
	 */
	public function activate_license( $args, $assoc_args ) {
		$license_data = array(
			'license_key' => $args[0],
			'domain'      => $args[1],
		);

		$result        = wppus_activate_license( $license_data );
		$error_message = 'Unable to activate the license';

		$this->process_result( $result, $result, $error_message, self::DEFAULT_ERROR, 'error' );
	}

	/**
	 * Deactivate a license for a domain.
	 *
	 * ## OPTIONS
	 *
	 * <license_key>
	 * : The license key.
	 *
	 * <domain>
	 * : The domain.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus deactivate_license <license_key> <domain>
	 */
	public function deactivate_license( $args, $assoc_args ) {
		$license_data = array(
			'license_key' => $args[0],
			'domain'      => $args[1],
		);

		$result        = wppus_deactivate_license( $license_data );
		$error_message = 'Unable to deactivate the license';

		$this->process_result( $result, $result, $error_message, self::DEFAULT_ERROR, 'error' );
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function cleanup( $method ) {
		$method          = 'wppus_force_cleanup_' . $method;
		$result          = $method();
		$success_message = 'OK';
		$error_message   = 'Cleanup failed';

		$this->process_result( $result, $success_message, $error_message );
	}

	protected function process_result( $result, $success_message, $error_message, $error_code = 1, $error_level = 'warning' ) {

		if ( $result instanceof WP_Error ) {
			$this->output(
				array(
					'level'  => 'error',
					'output' => $result->get_error_message(),
				)
			);
		} else {

			if ( empty( $success_message ) ) {
				$success_message = $result;
			}

			if ( empty( $error_message ) ) {
				$error_message = 'Undefined error';
			}

			$message = $result ? $success_message : $error_message;
			$level   = $result ? 'success' : $error_level;

			$this->output(
				array(
					'level'  => $level,
					'output' => $message,
				)
			);

			if ( 'warning' === $level ) {
				$this->output(
					array(
						'level'  => 'halt',
						'output' => $error_code,
					)
				);
			}
		}
	}

	protected function output( $message ) {

		if ( is_string( $message ) ) {
			WP_CLI::log( $message );
		} elseif ( is_array( $message ) ) {

			if (
				! isset( $message['level'] ) ||
				! in_array( $message['level'], self::LOG_METHODS, true )
			) {
				$message['level'] = 'log';
			}

			if (
				'halt' === $message['level'] &&
				(
					! isset( $message['output'] ) ||
					! is_int( $message['output'] )
				)
			) {
				$message['output'] = 255;
			} elseif ( ! isset( $message['output'] ) ) {
				$message['output'] = print_r( $message, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}

			if (
				'error_multi_line' === $message['level'] &&
				! is_array( $message['output'] )
			) {
				$message['level'] = 'log';
			}

			if (
				'error_multi_line' !== $message['level'] &&
				! is_string( $message['output'] ) &&
				! is_int( $message['output'] )
			) {
				$message['output'] = print_r( $message['output'], true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}

			WP_CLI::{$message['level']}( $message['output'] );
		} else {
			WP_CLI::log( print_r( $message, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}
	}
}
