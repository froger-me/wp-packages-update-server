<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_CLI extends WP_CLI_Command {
	protected const RESOURCE_NOT_FOUND = 3;
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
			$this->output(
				array(
					'level'  => 'error',
					'output' => 'Invalid package type',
				)
			);
			return;
		}

		$result = wppus_check_remote_package_update( $slug, $type );

		if ( $result instanceof WP_Error ) {
			$this->output(
				array(
					'level'  => 'error',
					'output' => $result->get_error_message(),
				)
			);

			return;
		} else {
			$message = $result ? 'Update available' : 'No update needed';
			$level   = 'success';

			if ( null === $result ) {
				$message = 'Unknown package slug';
				$level   = 'warning';
			}

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
						'output' => self::RESOURCE_NOT_FOUND,
					)
				);
			}

			return;
		}
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
			$this->output(
				array(
					'level'  => 'error',
					'output' => 'Invalid package type',
				)
			);
			return;
		}

		$result = wppus_download_remote_package( $slug, $type, true );

		if ( $result instanceof WP_Error ) {
			$this->output(
				array(
					'level'  => 'error',
					'output' => $result->get_error_message(),
				)
			);

			return;
		} else {
			$message = $result ? 'Package downloaded' : 'Unable to download package';
			$level   = $result ? 'success' : 'warning';

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
						'output' => self::RESOURCE_NOT_FOUND,
					)
				);
			}

			return;
		}
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
		$slug   = $args[0];
		$result = wppus_delete_package( $slug );

		if ( $result instanceof WP_Error ) {
			$this->output(
				array(
					'level'  => 'error',
					'output' => $result->get_error_message(),
				)
			);

			return;
		} else {
			$message = $result ? 'Package deleted' : 'Unable to delete package';
			$level   = $result ? 'success' : 'warning';

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
						'output' => self::RESOURCE_NOT_FOUND,
					)
				);
			}

			return;
		}
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
		$slug   = $args[0];
		$result = wppus_get_package_info( $slug );

		if ( $result instanceof WP_Error ) {
			$this->output(
				array(
					'level'  => 'error',
					'output' => $result->get_error_message(),
				)
			);

			return;
		} else {
			$message = $result ? $result : 'Unable to get package info';
			$level   = $result ? 'success' : 'warning';

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
						'output' => self::RESOURCE_NOT_FOUND,
					)
				);
			}

			return;
		}
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

		$result = wppus_create_nonce(
			$assoc_args['true_nonce'],
			$assoc_args['expiry_length'],
			$assoc_args['data'],
			$assoc_args['return_type'],
			$assoc_args['store']
		);

		if ( $result instanceof WP_Error ) {
			$this->output(
				array(
					'level'  => 'error',
					'output' => $result->get_error_message(),
				)
			);

			return;
		} else {
			$message = $result ? $result : 'Unable to create nonce';
			$level   = $result ? 'success' : 'error';

			$this->output(
				array(
					'level'  => $level,
					'output' => $message,
				)
			);

			return;
		}
	}

	/**
	 * Clears nonces.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wppus clear_nonces
	 */
	public function clear_nonces() {
		$result = wppus_clear_nonces();

		if ( $result instanceof WP_Error ) {
			$this->output(
				array(
					'level'  => 'error',
					'output' => $result->get_error_message(),
				)
			);

			return;
		} else {
			$message = $result ? $result : 'Unable to clear nonces';
			$level   = $result ? 'success' : 'error';

			$this->output(
				array(
					'level'  => $level,
					'output' => $message,
				)
			);

			return;
		}
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	/* phpcs:disable
	Commands to implement:
	function wppus_browse_licenses( $browse_query )
	function wppus_read_license( $license_data )
	function wppus_add_license( $license_data )
	function wppus_edit_license( $license_data )
	function wppus_delete_license( $license_data )
	function wppus_check_license( $license_data )
	function wppus_activate_license( $license_data )
	function wppus_deactivate_license( $license_data )
	function wppus_create_nonce(
		$true_nonce = true,
		$expiry_length = WPPUS_Nonce::DEFAULT_EXPIRY_LENGTH,
		$data = array(),
		$return_type = WPPUS_Nonce::NONCE_ONLY,
		$store = true
	)
	function wppus_get_nonce_expiry( $nonce )
	function wppus_get_nonce_data( $nonce )
	function wppus_delete_nonce( $value )
	function wppus_build_nonce_api_signature( $api_key_id, $api_key, $timestamp, $payload )
	phpcs:enable
	*/

	protected function cleanup( $method ) {
		$method = 'wppus_force_cleanup_' . $method;

		if ( $method() ) {
			$this->output(
				array(
					'level'  => 'success',
					'output' => 'OK',
				)
			);
		} else {
			$this->output(
				array(
					'level'  => 'warning',
					'output' => 'Cleanup failed',
				)
			);
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
