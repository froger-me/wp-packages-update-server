<?php

use PhpS3\PhpS3;
use PhpS3\PhpS3Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Cloud_Storage_Manager {
	protected static $instance;
	protected static $config;
	protected static $cloud_storage;

	public function __construct( $init_hooks = false ) {
		require_once WPPUS_PLUGIN_PATH . 'lib/PhpS3/PhpS3.php';

		$config = self::get_config();

		if ( ! self::$cloud_storage instanceof PhpS3 ) {
			self::$cloud_storage = new PhpS3(
				$config['access_key'],
				$config['secret_key'],
				true,
				$config['endpoint'],
				$config['region'],
			);

			self::$cloud_storage->setExceptions();
		}

		if ( $init_hooks ) {

			if ( ! wppus_is_doing_api_request() ) {
				add_action( 'wp_ajax_wppus_cloud_storage_test', array( $this, 'cloud_storage_test' ), 10, 0 );
			}
		}
	}

	public static function get_config() {

		if ( ! self::$config ) {
			$config = array(
				'access_key'   => get_option( 'wppus_cloud_storage_access_key' ),
				'secret_key'   => get_option( 'wppus_cloud_storage_secret_key' ),
				'endpoint'     => get_option( 'wppus_cloud_storage_endpoint' ),
				'storage_unit' => get_option( 'wppus_cloud_storage_unit' ),
				'region'       => get_option( 'wppus_cloud_storage_region' ),
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

	public function cloud_storage_test() {
		$result = array();

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'wppus_plugin_options' ) ) {
			$data = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY );

			if ( $data ) {
				$access_key   = $data['wppus_cloud_storage_access_key'];
				$secret_key   = $data['wppus_cloud_storage_secret_key'];
				$endpoint     = $data['wppus_cloud_storage_endpoint'];
				$storage_unit = $data['wppus_cloud_storage_unit'];
				$region       = $data['wppus_cloud_storage_region'];

				self::$cloud_storage->setAuth( $access_key, $secret_key );
				self::$cloud_storage->setEndpoint( $endpoint );
				self::$cloud_storage->setRegion( $region );

				try {
					$storage_units = self::$cloud_storage->listBuckets();

					if ( ! in_array( $storage_unit, $storage_units, true ) ) {
						$result = new WP_Error(
							__METHOD__,
							__( 'Error - Storage Unit not found', 'wppus' )
						);
					} else {
						$result[] = __( 'Cloud Storage service was reached sucessfully.', 'wppus' );

						if ( ! $this->virtual_folder_exists( 'wppus-plugins' ) ) {
							$created  = $this->create_virtual_folder( 'wppus-plugins' );
							$result[] = $created ?
								sprintf(
									// translators: %s is the virtual folder
									esc_html__( 'Virtual folder "%s" was created successfully.', 'wppus' ),
									'wppus-themes',
								) :
								sprintf(
									// translators: %s is the virtual folder
									esc_html__( 'WARNING: Unable to create Virtual folder "%s". The Cloud Storage feature may not work as expected. Try to create it manually and test again.', 'wppus' ),
									'wppus-themes',
								);
						} else {
							$result[] = sprintf(
								// translators: %s is the virtual folder
								esc_html__( 'Virtual folder "%s" found.', 'wppus' ),
								'wppus-plugins',
							);
						}

						if ( ! $this->virtual_folder_exists( 'wppus-themes' ) ) {
							$created  = $this->create_virtual_folder( 'wppus-themes' );
							$result[] = $created ?
								sprintf(
									// translators: %s is the virtual folder
									esc_html__( 'Virtual folder "%s" was created successfully.', 'wppus' ),
									'wppus-themes',
								) :
								sprintf(
									// translators: %s is the virtual folder
									esc_html__( 'WARNING: Unable to create Virtual folder "%s". The Cloud Storage feature may not work as expected. Try to create it manually and test again.', 'wppus' ),
									'wppus-themes',
								);
						} else {
							$result[] = sprintf(
								// translators: %s is the virtual folder
								esc_html__( 'Virtual folder "%s" found.', 'wppus' ),
								'wppus-themes',
							);
						}
					}
				} catch ( PhpS3Exception $e ) {
					$result = new WP_Error(
						__METHOD__ . ' => PhpS3Exception',
						$e->getMessage()
					);

					$result->add( __METHOD__ . ' => LF', '' );
					$result->add( __METHOD__, __( 'An error occured when attempting to communicate with the Cloud Storage service provider. Please check all the settings and try again.', 'wppus' ) );
				}
			} else {
				$result = new WP_Error(
					__METHOD__,
					__( 'Error - Received invalid data ; please reload the page and try again.', 'wppus' )
				);
			}
		}

		if ( ! is_wp_error( $result ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	protected function virtual_folder_exists( $name ) {
		$config = self::get_config();

		return self::$cloud_storage->getObjectInfo(
			$config['storage_unit'],
			trailingslashit( $name )
		);
	}

	protected function create_virtual_folder( $name ) {
		$config = self::get_config();

		return self::$cloud_storage->putObject(
			trailingslashit( $name ),
			$config['storage_unit'],
			trailingslashit( $name )
		);
	}
}
