<?php
require WPPUS_PLUGIN_PATH . '/lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5p3\Vcs\GitHubApi;
use YahnisElsts\PluginUpdateChecker\v5p3\Vcs\GitLabApi;
use YahnisElsts\PluginUpdateChecker\v5p3\Vcs\BitBucketApi;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class WPPUS_Update_Server extends Wpup_UpdateServer {

	const LOCK_REMOTE_UPDATE_SEC = 10;

	protected $server_directory;
	protected $use_remote_repository;
	protected $repository_service_url;
	protected $repository_branch;
	protected $repository_credentials;
	protected $repository_service_self_hosted;
	protected $update_checker;
	protected $type;

	public function __construct(
		$use_remote_repository,
		$server_url,
		$server_directory = null,
		$repository_service_url = null,
		$repository_branch = 'master',
		$repository_credentials = null,
		$repository_service_self_hosted = false,
	) {
		parent::__construct( $server_url, untrailingslashit( $server_directory ) );

		$this->use_remote_repository          = $use_remote_repository;
		$this->server_directory               = $server_directory;
		$this->repository_service_self_hosted = $repository_service_self_hosted;
		$this->repository_service_url         = $repository_service_url;
		$this->repository_branch              = $repository_branch;
		$this->repository_credentials         = $repository_credentials;
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	// Misc. -------------------------------------------------------

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

						do_action(
							'wppus_saved_remote_package_to_local',
							$local_ready,
							$info['type'],
							$safe_slug
						);
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
		$type = $type ? ucfirst( $type ) : false;

		if ( 'Plugin' === $type || 'Theme' === $type ) {
			$this->type = $type;
		}
	}

	public function check_remote_package_update( $slug ) {
		do_action( 'wppus_check_remote_update', $slug );

		$needs_update  = true;
		$local_package = $this->findPackage( $slug );

		if ( $local_package instanceof Wpup_Package ) {
			$package_path = $local_package->getFileName();
			$local_meta   = WshWordPressPackageParser::parsePackage( $package_path, true );
			$local_meta   = apply_filters(
				'wppus_check_remote_package_update_local_meta',
				$local_meta,
				$local_package,
				$slug
			);

			if ( ! $local_meta ) {
				$needs_update = apply_filters(
					'wppus_check_remote_package_update_no_local_meta_needs_update',
					$needs_update,
					$local_package,
					$slug
				);

				return $needs_update;
			}

			$local_info = array(
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
					$needs_update = version_compare( $remote_info['version'], $local_info['version'], '>' );
				} else {
					php_log(
						$remote_info,
						'Invalid value $remote_info for package of type '
						. $this->type . ' and slug ' . $slug
					);
				}
			}
		}

		do_action( 'wppus_checked_remote_package_update', $needs_update, $this->type, $slug );

		return $needs_update;
	}

	public function remove_package( $slug ) {
		WP_Filesystem();

		global $wp_filesystem;

		$package_path = trailingslashit( $this->packageDirectory ) . $slug . '.zip'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$result       = false;
		$type         = false;
		$cache_key    = false;

		if ( $wp_filesystem->is_file( $package_path ) ) {
			$cache_key = 'metadata-b64-' . $slug . '-'
				. md5(
					$package_path . '|'
					. filesize( $package_path ) . '|'
					. filemtime( $package_path )
				);

			$parsed_info = WshWordPressPackageParser::parsePackage( $package_path, true );
			$type        = ucfirst( $parsed_info['type'] );
			$result      = $wp_filesystem->delete( $package_path );
		}

		$result = apply_filters( 'wppus_remove_package_result', $result, $type, $slug );

		if ( $result && $cache_key ) {

			if ( ! $this->cache ) {
				$this->cache = new Wpup_FileCache( WPPUS_Data_Manager::get_data_dir( 'cache' ) );
			}

			$this->cache->clear( $cache_key );
		}

		do_action( 'wppus_removed_package', $result, $type, $slug );

		return $result;
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	// Overrides ---------------------------------------------------

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
			! wppus_validate_nonce( $request->token )
		) {
			$message = __( 'The download URL token has expired.', 'wppus' );

			$this->exitWithError( $message, 403 );
		}
	}

	protected function generateDownloadUrl( Wpup_Package $package ) {
		$query = array(
			'action'     => 'download',
			'token'      => wppus_create_nonce(),
			'package_id' => $package->slug,
		);

		return self::addQueryArg( $query, $this->serverUrl ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	protected function actionDownload( Wpup_Request $request ) {
		do_action( 'wppus_update_server_action_download', $request );

		$handled = apply_filters( 'wppus_update_server_action_download_handled', false, $request );

		if ( ! $handled ) {
			parent::actionDownload( $request );
		}
	}

	protected function findPackage( $slug, $check_remote = true ) {
		WP_Filesystem();

		global $wp_filesystem;

		$safe_slug            = preg_replace( '@[^a-z0-9\-_\.,+!]@i', '', $slug );
		$filename             = trailingslashit( $this->packageDirectory ) . $safe_slug . '.zip'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$save_remote_to_local = apply_filters(
			'wppus_save_remote_to_local',
			! $wp_filesystem->is_file( $filename ) || ! $wp_filesystem->is_readable( $filename ),
			$safe_slug,
			$filename,
			$check_remote
		);

		if ( $save_remote_to_local ) {
			$re_check_local = false;

			if ( $this->use_remote_repository && $this->repository_service_url ) {

				if ( $check_remote ) {
					$re_check_local = $this->save_remote_package_to_local( $safe_slug );
				}
			}

			if ( $re_check_local ) {
				return $this->findPackage( $slug, false );
			} else {
				return null;
			}
		}

		$package = false;

		try {
			$cached_value = null;

			if ( $this->cache ) {

				if ( $wp_filesystem->is_file( $filename ) && $wp_filesystem->is_readable( $filename ) ) {
					$cache_key    = 'metadata-b64-' . $safe_slug . '-'
						. md5( $filename . '|' . filesize( $filename ) . '|' . filemtime( $filename ) );
					$cached_value = $this->cache->get( $cache_key );
				}
			} else {
				$this->cache = new Wpup_FileCache( WPPUS_Data_Manager::get_data_dir( 'cache' ) );
			}

			if ( ! $cached_value ) {
				do_action( 'wppus_find_package_no_cache', $safe_slug, $filename, $this->cache );
			}

			$package = Wpup_Package_Extended::fromArchive( $filename, $safe_slug, $this->cache );
		} catch ( Exception $e ) {
			php_log( 'Corrupt archive ' . $filename . ' ; will not be displayed or delivered' );

			$log  = 'Exception caught: ' . $e->getMessage() . "\n";
			$log .= 'File: ' . $e->getFile() . "\n";
			$log .= 'Line: ' . $e->getLine() . "\n";

			php_log( $log );
		}

		return $package;
	}

	protected function actionGetMetadata( Wpup_Request $request ) {
		$meta                         = $request->package->getMetadata();
		$meta['download_url']         = $this->generateDownloadUrl( $request->package );
		$meta                         = $this->filterMetadata( $meta, $request );
		$meta['request_time_elapsed'] = sprintf( '%.3f', microtime( true ) - $this->startTime ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$this->outputAsJson( $meta );

		exit;
	}

	// Misc. -------------------------------------------------------

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
					$this->packageDirectory // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				);
			} elseif ( 'Theme' === $this->type ) {
				$this->update_checker = new Proxuc_Vcs_ThemeUpdateChecker(
					new GitLabApi( trailingslashit( $this->repository_service_url ) . $slug ),
					$slug,
					$slug,
					$this->packageDirectory // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				);
			}
		} else {
			$this->update_checker = Proxuc_Factory::buildUpdateChecker(
				trailingslashit( $this->repository_service_url ) . $slug,
				$slug,
				$slug,
				$this->type,
				$this->packageDirectory // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
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
			wp_delete_file( $local_filename );
			php_log( $response, 'Invalid value for $response' );

			return $response;
		}

		if ( 200 !== absint( wp_remote_retrieve_response_code( $response ) ) ) {
			wp_delete_file( $local_filename );

			return new WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
		}

		$content_md5 = wp_remote_retrieve_header( $response, 'content-md5' );

		if ( $content_md5 ) {
			$md5_check = verify_file_md5( $local_filename, $content_md5 );

			if ( is_wp_error( $md5_check ) ) {
				wp_delete_file( $local_filename );
				php_log( $md5_check, 'Invalid value for $md5_check' );

				return $md5_check;
			}
		}

		return $local_filename;
	}
}
