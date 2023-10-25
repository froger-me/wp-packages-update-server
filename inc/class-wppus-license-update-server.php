<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_License_Update_Server extends WPPUS_Update_Server {

	protected $license_key;
	protected $license_signature;

	public function __construct(
		$use_remote_repository,
		$server_url,
		$scheduler,
		$server_directory,
		$repository_service_url,
		$repository_branch,
		$repository_credentials,
		$repository_service_self_hosted,
		$repository_check_frequency
		) {
		parent::__construct(
			$use_remote_repository,
			$server_url,
			$scheduler,
			$server_directory,
			$repository_service_url,
			$repository_branch,
			$repository_credentials,
			$repository_service_self_hosted,
			$repository_check_frequency
		);

		$this->repository_service_url = $repository_service_url;
	}

	protected function initRequest( $query = null, $headers = null ) {
		$request = parent::initRequest( $query, $headers );
		$license = null;

		if ( $request->param( 'license_key' ) ) {
			$result = $this->verifyLicenseExists(
				$request->slug,
				$request->param( 'license_key' )
			);

			$request->license_key       = $request->param( 'license_key' );
			$request->license_signature = $request->param( 'license_signature' );
			$request->license           = $result;

			$this->license_key       = $request->license_key;
			$this->license_signature = $request->license_signature;
		}

		return $request;
	}

	protected function filterMetadata( $meta, $request ) {
		$meta              = parent::filterMetadata( $meta, $request );
		$license           = $request->license;
		$license_signature = $request->license_signature;

		if ( null !== $license ) {
			$meta['license'] = $this->prepareLicenseForOutput( $license );
		}

		if ( apply_filters( 'wppus_license_valid', $this->isLicenseValid( $license, $license_signature ), $license, $license_signature ) ) {
			$args                 = array(
				'update_license_key'       => $request->license_key,
				'update_license_signature' => $request->license_signature,
			);
			$meta['download_url'] = self::addQueryArg( $args, $meta['download_url'] );
		} else {
			unset( $meta['download_url'] );
			unset( $meta['license'] );

			$meta['license_error'] = $this->get_license_error_message( $license );
		}

		return $meta;
	}

	protected function checkAuthorization( $request ) {
		parent::checkAuthorization( $request );

		$license           = $request->license;
		$license_signature = $request->license_signature;

		if (
			'download' === $request->action &&
			! ( apply_filters( 'wppus_license_valid', $this->isLicenseValid( $license, $license_signature ), $license, $license_signature ) )
		) {
			$message = $this->get_license_error_message( $license );

			$this->exitWithError( $message, 403 );
		}
	}

	protected function generateDownloadUrl( Wpup_Package $package ) {
		$query = array(
			'action'                   => 'download',
			'token'                    => get_option( 'wppus_package_download_url_token' ),
			'package_id'               => $package->slug,
			'update_license_key'       => $this->license_key,
			'update_license_signature' => $this->license_signature,
		);

		return self::addQueryArg( $query, $this->serverUrl ); // @codingStandardsIgnoreLine
	}

	protected function get_license_error_message( $license ) {
		// @todo remove in 2.0
		$deprecated = ( false !== strpos( $_SERVER['REQUEST_URI'], 'wp-update-server' ) );

		if ( ! $license ) {
			$error = (object) array();
			// @todo remove in 2.0
			$error = $deprecated ? __( 'An active license key is required to download or update this package.', 'wppus' ) : $error;

			return $error;
		}

		if ( ! is_object( $license ) ) {
			$error = (object) array(
				'license_key' => $this->license_key,
			);
			// @todo remove in 2.0
			$error = $deprecated ? __( 'The provided license could not be found.', 'wppus' ) : $error;

			return $error;
		}

		switch ( $license->status ) {
			case 'blocked':
				$error = (object) array(
					'status' => 'blocked',
				);
				// @todo remove in 2.0
				$error = $deprecated ? __( 'The license has been blocked.', 'wppus' ) : $error;

				return $error;
			case 'expired':
				$error = (object) array(
					'status'      => 'expired',
					'date_expiry' => $license->date_expiry,
				);
				// @todo remove in 2.0
				$error = $deprecated ? sprintf( __( 'The license has expired on %s', 'wppus' ), $license->date_expiry ) : $error; // @codingStandardsIgnoreLine

				return $error;
			case 'pending':
				$error = (object) array(
					'status' => 'pending',
				);
				// @todo remove in 2.0
				$error = $deprecated ? __( 'The license is pending activation.', 'wppus' ) : $error;

				return $error;
			default:
				$error = (object) array(
					'status' => 'invalid',
				);
				// @todo remove in 2.0
				$error = $deprecated ? __( 'Invalid license key. Please contact the author for help or buy a license.', 'wppus' ) : $error;

				return $error;
		}
	}

	protected function verifyLicenseExists( $slug, $license_key ) {
		$license_server = new WPPUS_License_Server();
		$payload        = array( 'license_key' => $license_key );
		$result         = $license_server->read_license( $payload );

		// @todo remove in 2.0
		if ( ! is_object( $result ) ) {
			$result['result']     = 'error';
			$result['message']    = 'Invalid license information.';
			$result['error_code'] = 60;
		} else {
			$name_parts                 = explode( ' ', $result->owner_name );
			$first_name                 = array_shift( $name_parts );
			$last_name                  = implode( ' ', $name_parts );
			$result->result             = 'success';
			$result->first_name         = $first_name;
			$result->last_name          = $last_name;
			$result->registered_domains = $result->allowed_domains;
			$result->message            = 'License key details retrieved.';
			$result->product_ref        = ( 'theme' === $result->package_type ) ? $result->package_slug . '/functions.php' : $result->package_slug . '/' . $result->package_slug . '.php';
		}

		return $result;
	}

	protected function prepareLicenseForOutput( $license ) {
		$output = json_decode( wp_json_encode( $license ), true );

		return $output;
	}

	protected function isLicenseValid( $license, $license_signature ) {
		$valid = false;

		if ( $license && ! is_wp_error( $license ) && 'activated' === $license->status ) {
			$config = WPPUS_License_API::get_config();

			if ( ! $config['licenses_check_signature'] ) {
				$valid = $this->license_key === $license->license_key;
			} else {
				$license_server = new WPPUS_License_Server();

				$valid = $this->license_key === $license->license_key;
				$valid = $valid && $license_server->is_signature_valid( $license, $license_signature );
			}
		}

		return $valid;
	}

}
