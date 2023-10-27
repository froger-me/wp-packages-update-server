<?php
require WPPUS_PLUGIN_PATH . '/lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5p1\Vcs\GitHubApi;
use YahnisElsts\PluginUpdateChecker\v5p1\Vcs\GitLabApi;
use YahnisElsts\PluginUpdateChecker\v5p1\Vcs\BitBucketApi;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class WPPUS_Update_Server extends Wpup_UpdateServer {

	const LOCK_REMOTE_UPDATE_SEC = 10;

	protected $server_directory;
	protected $use_remote_repository;
	protected $repository_service_url;
	protected $repository_branch;
	protected $repository_credentials;
	protected $repository_service_self_hosted;
	protected $repository_check_frequency;
	protected $update_checker;
	protected $type;
	protected $package_file_loader = array( 'Wpup_Package_Extended', 'fromArchive' );
	protected $scheduler;

	public function __construct(
		$use_remote_repository,
		$server_url,
		$scheduler,
		$server_directory = null,
		$repository_service_url = null,
		$repository_branch = 'master',
		$repository_credentials = null,
		$repository_service_self_hosted = false,
		$repository_check_frequency = 'daily'
	) {
		parent::__construct( $server_url, untrailingslashit( $server_directory ) );

		$this->use_remote_repository          = $use_remote_repository;
		$this->server_directory               = $server_directory;
		$this->repository_service_self_hosted = $repository_service_self_hosted;
		$this->repository_service_url         = $repository_service_url;
		$this->repository_branch              = $repository_branch;
		$this->repository_credentials         = $repository_credentials;
		$this->repository_check_frequency     = $repository_check_frequency;
		$this->scheduler                      = $scheduler;
	}

	public static function unlock_update_from_remote( $slug ) {
		$locks = get_option( 'wppus_update_from_remote_locks' );

		if ( ! is_array( $locks ) ) {
			update_option( 'wppus_update_from_remote_locks', array() );
		} elseif ( array_key_exists( $slug, $locks ) ) {
			unset( $locks[ $slug ] );

			update_option( 'wppus_update_from_remote_locks', $locks );
		}
	}

	public static function lock_update_from_remote( $slug ) {
		$locks = get_option( 'wppus_update_from_remote_locks' );

		if ( ! is_array( $locks ) ) {
			update_option( 'wppus_update_from_remote_locks', array( $slug => time() + self::LOCK_REMOTE_UPDATE_SEC ) );
		} elseif ( ! array_key_exists( $slug, $locks ) ) {
			$locks[ $slug ] = time() + self::LOCK_REMOTE_UPDATE_SEC;

			update_option( 'wppus_update_from_remote_locks', $locks );
		}
	}

	public static function is_update_from_remote_locked( $slug ) {
		$locks     = get_option( 'wppus_update_from_remote_locks' );
		$is_locked = is_array( $locks ) && array_key_exists( $slug, $locks ) && $locks[ $slug ] >= time();

		return $is_locked;
	}

	public function save_remote_package_to_local( $safe_slug ) {
		$local_ready = false;

		if ( ! self::is_update_from_remote_locked( $safe_slug ) ) {
			self::lock_update_from_remote( $safe_slug );
			$this->init_update_checker( $safe_slug );

			if ( $this->update_checker ) {

				try {
					$info = $this->update_checker->requestInfo();

					if ( $info && ! is_wp_error( $info ) ) {
						require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-zip-package-manager.php';

						$this->remove_package( $safe_slug );

						$package = $this->download_remote_package( $info['download_url'] );

						do_action( 'wppus_downloaded_remote_package', $package, $info['type'], $safe_slug );

						$package_manager = new WPPUS_Zip_Package_Manager(
							$safe_slug,
							$package,
							WPPUS_Data_Manager::get_data_dir( 'tmp' ),
							WPPUS_Data_Manager::get_data_dir( 'packages' )
						);
						$local_ready     = $package_manager->clean_package();

						do_action( 'wppus_saved_remote_package_to_local', $local_ready, $info['type'], $safe_slug );
					} else {
						error_log( __METHOD__ . ' invalid value for $info: ' . print_r( $info, true ) ); // @codingStandardsIgnoreLine
					}
				} catch ( Exception $e ) {
					self::unlock_update_from_remote( $safe_slug );

					throw $e;
				}
			}

			self::unlock_update_from_remote( $safe_slug );
		}

		return $local_ready;
	}

	public function set_type( $type ) {
		$type = ucfirst( $type );

		if ( 'Plugin' === $type || 'Theme' === $type ) {
			$this->type = $type;
		}
	}

	public function check_remote_package_update( $slug ) {
		$has_update    = true;
		$local_package = $this->findPackage( $slug );

		if ( $local_package instanceof Wpup_Package ) {
			$package_path = $local_package->getFileName();
			$local_meta   = WshWordPressPackageParser::parsePackage( $package_path, true );
			$local_info   = array(
				'type'         => $local_meta['type'],
				'version'      => $local_meta['header']['Version'],
				'main_file'    => $local_meta['pluginFile'],
				'download_url' => '',
			);

			$this->type = ucfirst( $local_info['type'] );

			if ( 'Plugin' === $this->type || 'Theme' === $this->type ) {
				$this->init_update_checker( $slug );

				$remote_info = $this->update_checker->requestInfo();

				if ( $remote_info && ! is_wp_error( $remote_info ) ) {
					$has_update = version_compare( $remote_info['version'], $local_info['version'], '>' );
				} else {
					error_log(  __METHOD__ . ' invalid value for $remote_info: ' .  print_r( $remote_info, true ) ); // @codingStandardsIgnoreLine
				}
			}
		}

		do_action( 'wppus_checked_remote_package_update', $has_update, $this->type, $slug );

		return $has_update;
	}

	public function remove_package( $slug ) {
		WP_Filesystem();

		global $wp_filesystem;

		$package_path = trailingslashit( $this->packageDirectory ) . $slug . '.zip'; // @codingStandardsIgnoreLine

		if ( $wp_filesystem->is_file( $package_path ) ) {
			$parsed_info = WshWordPressPackageParser::parsePackage( $package_path, true );
			$type        = ucfirst( $parsed_info['type'] );
			$result      = $wp_filesystem->delete( $package_path );

			do_action( 'wppus_deleted_package', $result, $type, $slug );

			return $result;
		}

		return false;
	}

	protected function initRequest( $query = null, $headers = null ) {
		$request = parent::initRequest( $query, $headers );

		if ( $request->param( 'type' ) ) {
			$request->type = $request->param( 'type' );
			$this->type    = ucfirst( $request->type );
		}

		$request->token = $request->param( 'token' );

		return $request;
	}

	protected function checkAuthorization( $request ) {
		parent::checkAuthorization( $request );

		if (
			'download' === $request->action &&
			get_option( 'wppus_package_download_url_token' ) !== $request->token
		) {
			$message = __( 'The download URL token has expired.', 'wppus' );

			$this->exitWithError( $message, 403 );
		}
	}

	protected function generateDownloadUrl( Wpup_Package $package ) {
		$query = array(
			'action'     => 'download',
			'token'      => get_option( 'wppus_package_download_url_token' ),
			'package_id' => $package->slug,
		);

		return self::addQueryArg( $query, $this->serverUrl ); // @codingStandardsIgnoreLine
	}

	protected function findPackage( $slug, $check_remote = true ) {
		WP_Filesystem();

		global $wp_filesystem;

		$safe_slug = preg_replace( '@[^a-z0-9\-_\.,+!]@i', '', $slug );
		$filename  = trailingslashit( $this->packageDirectory ) . $safe_slug . '.zip'; // @codingStandardsIgnoreLine

		if ( ! $wp_filesystem->is_file( $filename ) || ! $wp_filesystem->is_readable( $filename ) ) {
			$re_check_local = false;

			if ( $this->use_remote_repository && $this->repository_service_url ) {

				if ( $check_remote ) {
					$re_check_local = $this->save_remote_package_to_local( $safe_slug );
				}
			} else {
				$this->scheduler->clear_remote_check_schedule( $safe_slug );
			}

			if ( $re_check_local ) {

				return $this->findPackage( $slug, false );
			} else {

				return null;
			}
		}

		if (
			! get_option( 'wppus_remote_repository_use_webhooks', false ) &&
			$this->use_remote_repository &&
			$this->repository_service_url
		) {
			$this->scheduler->register_remote_check_recurring_event( $safe_slug, $this->repository_check_frequency );
		}

		$package = false;

		try {
			$package = call_user_func( $this->package_file_loader, $filename, $slug, $this->cache );
		} catch ( Exception $e ) {
			error_log( __METHOD__ . ' corrupt archive ' . $filename . ' ; will not be displayed or delivered'); // @codingStandardsIgnoreLine

			$error_log  = 'Exception caught: ' . $e->getMessage() . "\n";
			$error_log .= 'File: ' . $e->getFile() . "\n";
			$error_log .= 'Line: ' . $e->getLine() . "\n";

			error_log( $error_log ); // @codingStandardsIgnoreLine
		}

		return $package;
	}

	protected function actionGetMetadata( Wpup_Request $request ) {
		$meta                         = $request->package->getMetadata();
		$meta['download_url']         = $this->generateDownloadUrl( $request->package );
		$meta                         = $this->filterMetadata( $meta, $request );
		$meta['request_time_elapsed'] = sprintf( '%.3f', microtime( true ) - $this->startTime ); // @codingStandardsIgnoreLine

		$this->outputAsJson( $meta );

		exit;
	}

	protected function init_update_checker( $slug ) {

		if ( $this->update_checker ) {

			return;
		}

		require_once WPPUS_PLUGIN_PATH . 'lib/proxy-update-checker/proxy-update-checker.php';

		if ( $this->repository_service_self_hosted ) {

			if ( 'Plugin' === $this->type ) {
				$this->update_checker = new Proxuc_Vcs_PluginUpdateChecker(
					new GitLabApi( trailingslashit( $this->repository_service_url ) . $slug ),
					$slug,
					$slug,
					$this->packageDirectory // @codingStandardsIgnoreLine
				);
			} elseif ( 'Theme' === $this->type ) {
				$this->update_checker = new Proxuc_Vcs_ThemeUpdateChecker(
					new GitLabApi( trailingslashit( $this->repository_service_url ) . $slug ),
					$slug,
					$slug,
					$this->packageDirectory // @codingStandardsIgnoreLine
				);
			}
		} else {
			$this->update_checker = Proxuc_Factory::buildUpdateChecker(
				trailingslashit( $this->repository_service_url ) . $slug,
				$slug,
				$slug,
				$this->type,
				$this->packageDirectory // @codingStandardsIgnoreLine
			);
		}

		if ( $this->update_checker ) {

			if ( $this->repository_credentials ) {
				$this->update_checker->setAuthentication( $this->repository_credentials );
			}

			if ( $this->repository_branch ) {
				$this->update_checker->setBranch( $this->repository_branch );
			}
		}

		$this->update_checker = apply_filters(
			'wppus_update_checker',
			$this->update_checker,
			$slug,
			$this->type,
			$this->repository_service_url,
			$this->repository_branch,
			$this->repository_credentials,
			$this->repository_service_self_hosted
		);
	}

	protected function download_remote_package( $url, $timeout = 300 ) {

		if ( ! $url ) {

			return new WP_Error( 'http_no_url', __( 'Invalid URL provided.', 'wppus' ) );
		}

		$local_filename = wp_tempnam( $url );

		if ( ! $local_filename ) {

			return new WP_Error( 'http_no_file', __( 'Could not create temporary file.', 'wppus' ) );
		}

		$params = array(
			'timeout'  => $timeout,
			'stream'   => true,
			'filename' => $local_filename,
		);

		if ( is_string( $this->repository_credentials ) ) {
			$params['headers'] = array(
				'Authorization' => 'token ' . $this->repository_credentials,
			);
		}

		$response = wp_safe_remote_get( $url, $params );

		if ( is_wp_error( $response ) ) {
			unlink( $local_filename );
			error_log(  __METHOD__ . ' invalid value for $response: ' .  print_r( $response, true ) ); // @codingStandardsIgnoreLine

			return $response;
		}

		if ( 200 !== absint( wp_remote_retrieve_response_code( $response ) ) ) {
			unlink( $local_filename );

			return new WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
		}

		$content_md5 = wp_remote_retrieve_header( $response, 'content-md5' );

		if ( $content_md5 ) {
			$md5_check = verify_file_md5( $local_filename, $content_md5 );

			if ( is_wp_error( $md5_check ) ) {
				unlink( $local_filename );
				error_log(  __METHOD__ . ' invalid value for $md5_check: ' .  print_r( $md5_check, true ) ); // @codingStandardsIgnoreLine

				return $md5_check;
			}
		}

		return $local_filename;
	}

}