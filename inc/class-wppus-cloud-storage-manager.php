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
				add_action( 'wppus_remote_sources_options_updated', array( $this, 'wppus_remote_sources_options_updated' ), 10, 0 );
			}

			add_action( 'wppus_saved_remote_package_to_local', array( $this, 'wppus_saved_remote_package_to_local' ), 10, 3 );
			add_action( 'wppus_checked_remote_package_update', array( $this, 'wppus_checked_remote_package_update' ), 10, 3 );
			add_action( 'wppus_find_package_no_cache', array( $this, 'wppus_find_package_no_cache' ), 10, 2 );

			apply_filters( 'wppus_save_remote_to_local', array( $this, 'wppus_save_remote_to_local' ), 10, 4 );
		}
	}

	public static function get_config() {

		if ( ! self::$config ) {
			$config = array(
				'use_cloud_storage' => get_option( 'wppus_use_cloud_storage' ),
				'access_key'        => get_option( 'wppus_cloud_storage_access_key' ),
				'secret_key'        => get_option( 'wppus_cloud_storage_secret_key' ),
				'endpoint'          => get_option( 'wppus_cloud_storage_endpoint' ),
				'storage_unit'      => get_option( 'wppus_cloud_storage_unit' ),
				'region'            => get_option( 'wppus_cloud_storage_region' ),
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
						$vir_dirs = array( 'plugins', 'themes' );
						$result[] = __( 'Cloud Storage service was reached sucessfully.', 'wppus' );

						foreach ( $vir_dirs as $vir_dir ) {

							if ( ! $this->virtual_folder_exists( 'wppus-' . $vir_dir ) ) {
								$created  = $this->create_virtual_folder( 'wppus-' . $vir_dir );
								$result[] = $created ?
									sprintf(
										// translators: %s is the virtual folder
										esc_html__( 'Virtual folder "%s" was created successfully.', 'wppus' ),
										'wppus-' . $vir_dir,
									) :
									sprintf(
										// translators: %s is the virtual folder
										esc_html__( 'WARNING: Unable to create Virtual folder "%s". The Cloud Storage feature may not work as expected. Try to create it manually and test again.', 'wppus' ),
										'wppus-' . $vir_dir,
									);
							} else {
								$result[] = sprintf(
									// translators: %s is the virtual folder
									esc_html__( 'Virtual folder "%s" found.', 'wppus' ),
									'wppus-' . $vir_dir,
								);
							}
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

	public function wppus_remote_sources_options_updated() {
		$config = self::get_config();

		if ( ! $config['use_cloud_storage'] ) {

			return;
		}

		try {
			$vir_dirs = array( 'plugins', 'themes' );

			foreach ( $vir_dirs as $vir_dir ) {

				if ( ! $this->virtual_folder_exists( 'wppus-' . $vir_dir ) ) {

					if ( ! $this->create_virtual_folder( 'wppus-' . $vir_dir ) ) {
						php_log(
							sprintf(
								// translators: %s is the virtual folder
								esc_html__( 'WARNING: Unable to create Virtual folder "%s". The Cloud Storage feature may not work as expected. Try to create it manually and test again.', 'wppus' ),
								'wppus-' . $vir_dir,
							)
						);
					}
				}
			}
		} catch ( PhpS3Exception $e ) {
			php_log( $e );
		}
	}

	public function wppus_saved_remote_package_to_local( $local_ready, $type, $slug ) {
		// We want to upload the package, and delete it
	}

	public function wppus_checked_remote_package_update( $has_update, $type, $slug ) {

	}

	public function wppus_save_remote_to_local( $save, $slug, $filename, $check_remote ) {
		// We want to set save to true if the package is not found in cloud storage
		return $save;
	}

	public function wppus_find_package_no_cache( $slug, $filename ) {
		// We want to create a local cache, using the file in cloud storage
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
