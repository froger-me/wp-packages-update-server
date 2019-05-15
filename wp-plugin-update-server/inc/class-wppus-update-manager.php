<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'WPPUS_MB_TO_B' ) ) {
	define( 'WPPUS_MB_TO_B', 1000000 );
}

if ( ! defined( 'WPPUS_DEFAULT_LOGS_MAX_SIZE' ) ) {
	define( 'WPPUS_DEFAULT_LOGS_MAX_SIZE', 10 );
}

if ( ! defined( 'WPPUS_DEFAULT_CACHE_MAX_SIZE' ) ) {
	define( 'WPPUS_DEFAULT_CACHE_MAX_SIZE', 100 );
}

class WPPUS_Update_Manager {

	const WPPUS_DEFAULT_LOGS_MAX_SIZE    = 10;
	const WPPUS_DEFAULT_CACHE_MAX_SIZE   = 100;
	const WPPUS_DEFAULT_ARCHIVE_MAX_SIZE = 20;

	public static $filesystem_clean_types = array(
		'cache',
		'logs',
	);

	protected $packages_table;
	protected $scheduler;

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {
			$parts     = explode( DIRECTORY_SEPARATOR, untrailingslashit( WPPUS_PLUGIN_PATH ) );
			$plugin_id = end( $parts ) . '/wp-plugin-update-server.php';

			$this->scheduler = new WPPUS_Scheduler();

			add_action( 'init', array( $this->scheduler, 'register_renew_download_url_token_event' ), 10, 0 );
			add_action( 'init', array( $this->scheduler, 'register_renew_download_url_token_schedule' ), 10, 0 );
			add_action( 'admin_init', array( $this, 'init_request' ), 10, 0 );
			add_action( 'admin_menu', array( $this, 'plugin_options_menu_main' ), 10, 0 );
			add_action( 'admin_menu', array( $this, 'plugin_options_menu_help' ), 99, 0 );
			add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ), 10, 1 );
			add_action( 'wp_ajax_wppus_force_clean', array( $this, 'force_clean' ), 10, 0 );
			add_action( 'wp_ajax_wppus_prime_package_from_remote', array( $this, 'prime_package_from_remote' ), 10, 0 );
			add_action( 'wp_ajax_wppus_manual_package_upload', array( $this, 'manual_package_upload' ), 10, 0 );
			add_action( 'load-toplevel_page_wppus-page', array( $this, 'add_page_options' ), 10, 0 );

			add_filter( 'set-screen-option', array( $this, 'set_page_options' ), 10, 3 );
			add_filter( 'plugin_action_links_' . $plugin_id, array( $this, 'add_action_links' ), 10, 1 );
		}
	}

	public static function clear_schedules() {
		$scheduler = new WPPUS_Scheduler();

		return $scheduler->clear_renew_download_url_token_schedule();
	}

	public static function register_schedules() {
		$scheduler = new WPPUS_Scheduler();

		return $scheduler->register_renew_download_url_token_schedule();
	}

	public static function renew_download_url_token() {
		update_option( 'wppus_package_download_url_token', bin2hex( openssl_random_pseudo_bytes( 8 ) ), true );
	}

	public function init_request() {

		if ( is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() ) {
			$this->packages_table = new WPPUS_Packages_Table( $this );
			$redirect             = false;

			$condition = ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], $this->packages_table->nonce_action ) );
			$condition = $condition || ( isset( $_REQUEST['linknonce'] ) && wp_verify_nonce( $_REQUEST['linknonce'], 'linknonce' ) );

			if ( $condition ) {
				$page                = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : false;
				$packages            = isset( $_REQUEST['packages'] ) ? $_REQUEST['packages'] : false;
				$delete_all_packages = isset( $_REQUEST['wppus_delete_all_packages'] ) ? true : false;
				$action              = false;

				if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {  // @codingStandardsIgnoreLine
					$action = $_REQUEST['action'];
				} elseif ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {  // @codingStandardsIgnoreLine
					$action = $_REQUEST['action2'];
				}

				if ( 'wppus-page' === $page ) {
					// $redirect = admin_url( 'admin.php?page=wppus-page' );

					if ( $packages && 'download' === $action ) {
						$error    = $this->download_packages_bulk( $packages );
						$redirect = false;
						if ( $error ) {
							$this->packages_table->bulk_action_error = $error;
						}
					}

					if ( $packages && 'delete' === $action ) {
						$this->delete_packages_bulk( $packages );
					}

					if ( $packages && 'enable_license' === $action ) {
						$this->change_packages_license_status_bulk( $packages, true );
					}

					if ( $packages && 'disable_license' === $action ) {
						$this->change_packages_license_status_bulk( $packages, false );
					}

					if ( $delete_all_packages ) {
						$this->delete_packages_bulk();
					}
				}
			}

			$this->packages_table->licensed_package_slugs = get_option( 'wppus_licensed_package_slugs', array() );
			$this->packages_table->show_license_info      = get_option( 'wppus_use_licenses', false );

			// if ( $redirect ) {

			// 	if ( isset( $_REQUEST['s'] ) ) {
			// 		$redirect = add_query_arg( 's', $_REQUEST['s'], $redirect );
			// 	}

			// 	wp_redirect( $redirect );
			// }
		}
	}

	public function add_admin_scripts( $hook ) {
		$debug = (bool) ( constant( 'WP_DEBUG' ) );

		if ( false !== strpos( $hook, 'page_wppus' ) ) {
			$js_ext = ( $debug ) ? '.js' : '.min.js';
			$ver_js = filemtime( WPPUS_PLUGIN_PATH . 'js/admin/main' . $js_ext );
			$params = array(
				'ajax_url'              => admin_url( 'admin-ajax.php' ),
				'invalidFileFormat'     => __( 'Error: invalid file format.', 'wppus' ),
				'invalidFileSize'       => __( 'Error: invalid file size.', 'wppus' ),
				'invalidFileName'       => __( 'Error: invalid file name.', 'wppus' ),
				'invalidFile'           => __( 'Error: invalid file', 'wppus' ),
				'deleteRecord'          => __( 'Are you sure you want to delete this record?', 'wppus' ),
				'deleteLicensesConfirm' => __( "You are about to delete all the licenses from this server.\nAll the records will be permanently deleted.\nPackages requiring these licenses will not be able to get a successful response from this server.\n\nAre you sure you want to do this?", 'wppus' ),
			);

			if ( get_option( 'wppus_use_remote_repository' ) ) {
				$params['deletePackagesConfirm'] = __( "You are about to delete all the packages from this server.\nPackages with a remote repository will be added again automatically whenever a client asks for updates.\nAll packages manually uploaded without counterpart in a remote repository will be permanently deleted.\nLicense status will need to be re-applied manually for all packages.\n\nAre you sure you want to do this?", 'wppus' );
			} else {
				$params['deletePackagesConfirm'] = __( "You are about to delete all the packages from this server.\nAll packages will be permanently deleted.\nLicense status will need to be re-applied manually for all packages.\n\nAre you sure you want to do this?", 'wppus' );
			}
			wp_enqueue_script( 'wp-plugin-update-server-script', WPPUS_PLUGIN_URL . 'js/admin/main' . $js_ext, array( 'jquery' ), $ver_js, true );
			wp_localize_script( 'wp-plugin-update-server-script', 'Wppus', $params );

			$css_ext = ( $debug ) ? '.css' : '.min.css';
			$ver_css = filemtime( WPPUS_PLUGIN_PATH . 'css/admin/main' . $css_ext );

			wp_enqueue_style( 'wppus-admin-main', WPPUS_PLUGIN_URL . 'css/admin/main' . $css_ext, array(), $ver_css );
		}
	}

	public function add_action_links( $links ) {
		$link = array(
			'<a href="' . admin_url( 'admin.php?page=wppus-page' ) . '">' . __( 'Packages overview', 'wppus' ) . '</a>',
			'<a href="' . admin_url( 'admin.php?page=wppus-page-help' ) . '">' . __( 'Help', 'wppus' ) . '</a>',
		);

		return array_merge( $links, $link );
	}

	public function plugin_options_menu_main() {
		$page_title  = __( 'WP Plugin Update Server', 'wppus' );
		$menu_title  = $page_title;
		$capability  = 'manage_options';
		$menu_slug   = 'wppus-page';
		$parent_slug = $menu_slug;
		$function    = array( $this, 'plugin_main_page' );
		$icon        = 'data:image/svg+xml;base64,PHN2ZyBpZD0iTGF5ZXJfMSIgZGF0YS1uYW1lPSJMYXllciAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNy44NSAxNS4zMSI+PGRlZnM+PHN0eWxlPi5jbHMtMXtmaWxsOiNhNGE0YTQ7fS5jbHMtMntmaWxsOiNhMGE1YWE7fTwvc3R5bGU+PC9kZWZzPjx0aXRsZT5VbnRpdGxlZC0xPC90aXRsZT48cGF0aCBjbGFzcz0iY2xzLTEiIGQ9Ik0xMCwxMy41NGMyLjIzLDAsNC40NiwwLDYuNjksMCwuNjksMCwxLS4xNSwxLS45MSwwLTIuMzUsMC00LjcxLDAtNy4wNiwwLS42NC0uMi0uODctLjg0LS44NS0xLjEzLDAtMi4yNiwwLTMuMzksMC0uNDQsMC0uNjgtLjExLS42OC0uNjJzLjIzLS42My42OC0uNjJjMS40MSwwLDIuODEsMCw0LjIyLDAsLjgyLDAsMS4yMS40MywxLjIsMS4yNywwLDIuOTMsMCw1Ljg3LDAsOC44LDAsMS0uMjksMS4yNC0xLjI4LDEuMjVxLTIuNywwLTUuNDEsMGMtLjU0LDAtLjg1LjA5LS44NS43NXMuMzUuNzMuODcuNzFjLjgyLDAsMS42NSwwLDIuNDgsMCwuNDgsMCwuNzQuMTguNzUuNjlzLS40LjUxLS43NS41MUg1LjJjLS4zNSwwLS43OC4xMS0uNzUtLjVzLjI4LS43MS43Ni0uN2MuODMsMCwxLjY1LDAsMi40OCwwLC41NCwwLC45NSwwLC45NC0uNzRzLS40OC0uNzEtMS0uNzFIMi41MWMtMS4yMiwwLTEuNS0uMjgtMS41LTEuNTFRMSw5LjE1LDEsNWMwLTEuMTQuMzQtMS40NiwxLjQ5LTEuNDdINi40NGMuNCwwLC43LDAsLjcxLjU3cy0uMjEuNjgtLjcuNjdjLTEuMTMsMC0yLjI2LDAtMy4zOSwwLS41NywwLS44My4xNy0uODIuNzhxMCwzLjYyLDAsNy4yNGMwLC42LjIxLjguOC43OUM1LjM2LDEzLjUyLDcuNjgsMTMuNTQsMTAsMTMuNTRaIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMSAtMi4xOSkiLz48cGF0aCBjbGFzcz0iY2xzLTIiIGQ9Ik0xMy4xLDkuMzhsLTIuNjIsMi41YS44MS44MSwwLDAsMS0xLjEyLDBMNi43NCw5LjM4YS43NC43NCwwLDAsMSwwLTEuMDguODIuODIsMCwwLDEsMS4xMywwTDkuMTMsOS41VjNhLjguOCwwLDAsMSwxLjU5LDBWOS41TDEyLDguM2EuODIuODIsMCwwLDEsMS4xMywwQS43NC43NCwwLDAsMSwxMy4xLDkuMzhaIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMSAtMi4xOSkiLz48L3N2Zz4=';

		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon );

		$menu_title = __( 'Overview', 'wppus' );

		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	public function plugin_options_menu_help() {
		$function    = array( $this, 'plugin_help_page' );
		$page_title  = __( 'WP Plugin Update Server - Help', 'wppus' );
		$menu_title  = __( 'Help', 'wppus' );
		$menu_slug   = 'wppus-page-help';
		$capability  = 'manage_options';
		$parent_slug = 'wppus-page';

		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	public function add_page_options() {
		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Packages per page', 'wppus' ),
			'default' => 10,
			'option'  => 'packages_per_page',
		);

		add_screen_option( $option, $args );
	}

	public function set_page_options( $status, $option, $value ) {

		return $value;
	}

	public function plugin_main_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.' ) ); // @codingStandardsIgnoreLine
		}

		$updated              = $this->plugin_options_handler();
		$action_error         = '';
		$cache_size           = 0;
		$logs_size            = 0;
		$package_rows         = array();
		$default_cache_size   = self::WPPUS_DEFAULT_LOGS_MAX_SIZE;
		$default_logs_size    = self::WPPUS_DEFAULT_CACHE_MAX_SIZE;
		$default_archive_size = self::WPPUS_DEFAULT_ARCHIVE_MAX_SIZE;
		$packages_table       = $this->packages_table;
		$cache_size           = self::get_dir_size_mb( 'cache' );
		$logs_size            = self::get_dir_size_mb( 'logs' );
		$package_rows         = $this->get_package_rows_data();

		$packages_table->set_rows( $package_rows );
		$packages_table->prepare_items();

		ob_start();

		require_once WPPUS_PLUGIN_PATH . 'inc/templates/admin/plugin-main-page.php';

		echo ob_get_clean(); // @codingStandardsIgnoreLine

	}

	public function plugin_help_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.' ) ); // @codingStandardsIgnoreLine
		}

		ob_start();

		require_once WPPUS_PLUGIN_PATH . 'inc/templates/admin/plugin-help-page.php';

		echo ob_get_clean(); // @codingStandardsIgnoreLine
	}

	public function force_clean() {
		$result = false;
		$type   = false;

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'wppus_plugin_options' ) ) {
			$type = filter_input( INPUT_POST, 'type', FILTER_SANITIZE_STRING );

			if ( in_array( $type, self::$filesystem_clean_types ) ) { // @codingStandardsIgnoreLine
				$result = WPPUS_Data_Manager::maybe_cleanup( $type, true );
			}
		}

		if ( $result && $type ) {
			wp_send_json_success( array( 'btnVal' => __( 'Force Clean', 'wppus' ) . ' (' . self::get_dir_size_mb( $type ) . ')' ) );
		} else {

			if ( in_array( $type, self::$filesystem_clean_types ) ) { // @codingStandardsIgnoreLine
				$error = new WP_Error(
					__METHOD__,
					__( 'Error - check the directory is writable', 'wppus' )
				);

				wp_send_json_error( $error );
			}
		}
	}

	public function prime_package_from_remote() {
		$result = false;
		$error  = false;
		$slug   = 'N/A';

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'wppus_plugin_options' ) ) {
			$slug = filter_input( INPUT_POST, 'slug', FILTER_SANITIZE_STRING );

			if ( $slug ) {
				Wppus_Update_Server::unlock_update_from_remote( $slug );

				$result = WPPUS_Update_API::download_remote_update( $slug, 'Theme' );

				if ( ! $result ) {
					Wppus_Update_Server::unlock_update_from_remote( $slug );

					$result = WPPUS_Update_API::download_remote_update( $slug, 'Plugin' );
				}
			} else {
				$error = new WP_Error(
					__METHOD__,
					__( 'Error - could not get remote package. Missing package slug - please reload the page and try again.', 'wppus' )
				);
			}
		} else {
			$error = new WP_Error(
				__METHOD__,
				__( 'Error - could not get remote package. The page has expired - please reload the page and try again.', 'wppus' )
			);
		}

		do_action( 'wppus_primed_package_from_remote', $result, $slug );

		if ( ! $error && $result ) {
			wp_send_json_success();
		} else {

			if ( ! $error ) {
				$error = new WP_Error(
					__METHOD__,
					__( 'Error - could not get remote package. Check if a repository with this slug exists and has a valid file structure.', 'wppus' )
				);
			}

			wp_send_json_error( $error );
		}
	}

	public function manual_package_upload() {
		$result      = false;
		$slug        = 'N/A';
		$parsed_info = false;
		$error_text  = __( 'Reload the page and try again.', 'wppus' );

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'wppus_plugin_options' ) ) {
			WP_Filesystem();

			global $wp_filesystem;

			if ( ! $wp_filesystem ) {

				return;
			}

			$package_info = isset( $_FILES['package'] ) ? $_FILES['package'] : false;
			$valid        = (bool) ( $package_info );

			if ( ! $valid ) {
				$error_text = __( 'Something very wrong happened.', 'wppus' );
			}

			$valid_archive_formats = array(
				'multipart/x-zip',
				'application/zip',
				'application/zip-compressed',
				'application/x-zip-compressed',
			);

			if ( $valid && ! in_array( $package_info['type'], $valid_archive_formats ) ) { // @codingStandardsIgnoreLine
				$valid      = false;
				$error_text = __( 'Make sure the uploaded file is a zip archive.', 'wppus' );
			}

			if ( $valid && 0 !== absint( $package_info['error'] ) ) {
				$valid = false;

				switch ( $package_info['error'] ) {
					case UPLOAD_ERR_INI_SIZE:
						$error_text = ( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.' );
						break;

					case UPLOAD_ERR_FORM_SIZE:
						$error_text = ( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.' );
						break;

					case UPLOAD_ERR_PARTIAL:
						$error_text = ( 'The uploaded file was only partially uploaded.' );
						break;

					case UPLOAD_ERR_NO_FILE:
						$error_text = ( 'No file was uploaded.' );
						break;

					case UPLOAD_ERR_NO_TMP_DIR:
						$error_text = ( 'Missing a temporary folder.' );
						break;

					case UPLOAD_ERR_CANT_WRITE:
						$error_text = ( 'Failed to write file to disk.' );
						break;

					case UPLOAD_ERR_EXTENSION:
						$error_text = ( 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.' );
						break;
				}
			}

			if ( $valid && 0 >= $package_info['size'] ) {
				$valid      = false;
				$error_text = __( 'Make sure the uploaded file is not empty.', 'wppus' );
			}

			if ( $valid ) {
				$parsed_info = WshWordPressPackageParser::parsePackage( $package_info['tmp_name'], true );
			}

			if ( $valid && ! $parsed_info ) {
				$valid      = false;
				$error_text = __( 'The uploaded package is not a valid WordPress package, or if it is a plugin, the main plugin file could not be found.', 'wppus' );
			}

			if ( $valid ) {
				$source      = $package_info['tmp_name'];
				$slug        = $package_info['name'];
				$type        = ucfirst( $parsed_info['type'] );
				$destination = WPPUS_Data_Manager::get_data_dir( 'packages' ) . $slug;

				Wppus_Update_Server::unlock_update_from_remote( $slug );

				$result = $wp_filesystem->move( $source, $destination, true );
			} else {
				$result = false;

				$wp_filesystem->delete( $package_info['tmp_name'] );
			}
		}

		do_action( 'wppus_did_manual_upload_package', $result, $type, $slug );

		if ( $result ) {
			wp_send_json_success();
		} else {
			$error = new WP_Error(
				__METHOD__,
				__( 'Error - could not upload the package. ', 'wppus' ) . "\n\n" . $error_text
			);

			wp_send_json_error( $error );
		}
	}

	public function delete_packages_bulk( $package_slugs = array() ) {
		$package_slugs         = is_array( $package_slugs ) ? $package_slugs : array( $package_slugs );
		$package_directory     = WPPUS_Data_Manager::get_data_dir( 'packages' );
		$package_paths         = glob( trailingslashit( $package_directory ) . '*.zip' );
		$package_names         = array();
		$deleted_package_slugs = array();
		$delete_all            = false;

		if ( ! empty( $package_paths ) ) {

			if ( empty( $package_slugs ) ) {
				$delete_all = true;
			}

			foreach ( $package_paths as $package_path ) {
				$package_name    = basename( $package_path );
				$package_names[] = $package_name;

				if ( $delete_all ) {
					$package_slugs[] = str_replace( '.zip', '', $package_name );
				}
			}
		} else {

			return;
		}

		$config = array(
			'use_remote_repository' => false,
			'server_directory'      => WPPUS_Data_Manager::get_data_dir(),
		);

		$update_server = new WPPUS_Update_Server(
			$config['use_remote_repository'],
			home_url( '/wp-update-server/' ),
			$this->scheduler,
			$config['server_directory']
		);

		$update_server = apply_filters( 'wppus_update_server', $update_server, $config, '', '' );

		foreach ( $package_slugs as $slug ) {
			$package_name = $slug . '.zip';

			if ( in_array( $package_name, $package_names, true ) ) {
				$update_server_class = get_class( $update_server );
				$result              = false;

				if ( ! $update_server_class::is_update_from_remote_locked( $slug ) ) {
					$update_server_class::lock_update_from_remote( $slug );

					$result = $update_server->remove_package( $slug );

					$update_server_class::unlock_update_from_remote( $slug );
				}

				if ( $result ) {
					$deleted_package_slugs[] = $slug;

					unset( $this->rows[ $slug ] );
				}
			}
		}

		if ( ! empty( $deleted_package_slugs ) ) {
			$this->change_packages_license_status_bulk( $deleted_package_slugs, false );
		}
	}

	public function download_packages_bulk( $package_slugs ) {
		WP_Filesystem();

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {

			return null;
		}

		$package_directory = WPPUS_Data_Manager::get_data_dir( 'packages' );
		$total_size        = 0;
		$max_archive_size  = get_option( 'wppus_archive_max_size', self::WPPUS_DEFAULT_ARCHIVE_MAX_SIZE );
		$package_slugs     = is_array( $package_slugs ) ? $package_slugs : array( $package_slugs );

		foreach ( $package_slugs as $package_slug ) {
			$total_size += filesize( trailingslashit( $package_directory ) . $package_slug . '.zip' );
		}

		if ( $max_archive_size < ( (float) ( $total_size / WPPUS_MB_TO_B ) ) ) {
			$this->packages_table->bulk_action_error = 'max_file_size_exceeded';

			return;
		}

		if ( 1 === count( $package_slugs ) ) {
			$archive_name = reset( $package_slugs );
			$archive_path = trailingslashit( $package_directory ) . $archive_name . '.zip';

			do_action( 'wppus_before_packages_download', $archive_name, $archive_path, $package_slugs );
			$this->trigger_packages_download( $archive_name, $archive_path );

			return;
		}

		$temp_directory = WPPUS_Data_Manager::get_data_dir( 'tmp' );
		$archive_name   = 'archive-' . current_time( 'timestamp' );
		$archive_path   = trailingslashit( $temp_directory ) . $archive_name . '.zip';

		$zip = new ZipArchive();

		if ( ! $zip->open( $archive_path, ZIPARCHIVE::CREATE ) ) {

			return false;
		}

		foreach ( $package_slugs as $package_slug ) {
			$file = trailingslashit( $package_directory ) . $package_slug . '.zip';

			$zip->addFromString( $package_slug . '.zip', $wp_filesystem->get_contents( $file ) );
		}

		$zip->close();

		do_action( 'wppus_before_packages_download', $archive_name, $archive_path, $package_slugs );
		$this->trigger_packages_download( $archive_name, $archive_path );
	}

	protected static function get_dir_size_mb( $type ) {
		$result = 'N/A';

		if ( ! WPPUS_Data_Manager::is_valid_data_dir( $type ) ) {

			return $result;
		}

		$directory  = WPPUS_Data_Manager::get_data_dir( $type );
		$total_size = 0;

		foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory ) ) as $file ) {
			$total_size += $file->getSize();
		}

		$size = (float) ( $total_size / WPPUS_MB_TO_B );

		if ( $size < 0.01 ) {
			$result = '< 0.01 MB';
		} else {
			$result = number_format( $size, 2, '.', '' ) . 'MB';
		}

		return $result;
	}

	public function trigger_packages_download( $archive_name, $archive_path ) {
		WP_Filesystem();

		global $wp_filesystem;

		if ( ! empty( $archive_path ) && ! empty( $archive_name ) ) {

			if ( ini_get( 'zlib.output_compression' ) ) {
				@ini_set( 'zlib.output_compression', 'Off' ); // @codingStandardsIgnoreLine
			}

			header( 'Content-Type: application/zip' );
			header( 'Content-Disposition: attachment; filename="' . $archive_name . '.zip"' );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Content-Length: ' . filesize( $archive_path ) );

			do_action( 'wppus_triggered_packages_download', $archive_name, $archive_path );

			echo $wp_filesystem->get_contents( $archive_path ); // @codingStandardsIgnoreLine

			exit;
		}
	}

	protected function plugin_options_handler() {
		$errors = array();
		$result = '';

		if ( isset( $_REQUEST['wppus_plugin_options_handler_nonce'] ) && wp_verify_nonce( $_REQUEST['wppus_plugin_options_handler_nonce'], 'wppus_plugin_options' ) ) {
			$result  = __( 'WP Plugin Update Server options successfully updated', 'wppus' );
			$options = $this->get_submitted_options();

			foreach ( $options as $option_name => $option_info ) {
				$condition = $option_info['value'];

				if ( isset( $option_info['condition'] ) ) {

					if ( 'number' === $option_info['condition'] ) {
						$condition = is_numeric( $option_info['value'] );
					}
				}

				if ( $condition ) {
					update_option( $option_name, $option_info['value'] );

					if ( 'wppus_remote_repository_check_frequency' === $option_name ) {
						$new_wppus_remote_repository_check_frequency = $option_info['value'];
					}

					if ( 'wppus_use_remote_repository' === $option_name ) {
						$new_wppus_use_remote_repository = $option_info['value'];
					}
				} else {
					$errors[ $option_name ] = sprintf(
						// translators: %1$s is the option display name, %2$s is the condition for update
						__( 'Option %1$s was not updated. Reason: %2$s', 'wppus' ),
						$option_info['display_name'],
						$option_info['failure_display_message']
					);
				}
			}
		} elseif ( isset( $_REQUEST['wppus_plugin_options_handler_nonce'] ) && ! wp_verify_nonce( $_REQUEST['wppus_plugin_options_handler_nonce'], 'wppus_plugin_options' ) ) {
			$errors['general'] = __( 'There was an error validating the form. It may be outdated. Please reload the page.', 'wppus' );
		}

		if ( ! empty( $errors ) ) {
			$result = $errors;
		}

		return $result;
	}

	protected function get_submitted_options() {

		return apply_filters( 'wppus_submitted_data_config', array(
			'wppus_cache_max_size'   => array(
				'value'                   => filter_input( INPUT_POST, 'wppus_cache_max_size', FILTER_VALIDATE_INT ),
				'display_name'            => __( 'Cache max size (in MB)', 'wppus' ),
				'failure_display_message' => __( 'Not a valid number', 'wppus' ),
				'condition'               => 'number',
			),
			'wppus_logs_max_size'    => array(
				'value'                   => filter_input( INPUT_POST, 'wppus_logs_max_size', FILTER_VALIDATE_INT ),
				'display_name'            => __( 'Logs max size (in MB)', 'wppus' ),
				'failure_display_message' => __( 'Not a valid number', 'wppus' ),
				'condition'               => 'number',
			),
			'wppus_archive_max_size' => array(
				'value'                   => filter_input( INPUT_POST, 'wppus_archive_max_size', FILTER_VALIDATE_INT ),
				'display_name'            => __( 'Archive max size (in MB)', 'wppus' ),
				'failure_display_message' => __( 'Not a valid number', 'wppus' ),
				'condition'               => 'number',
			),
		) );
	}

	protected function change_packages_license_status_bulk( $package_slugs, $add ) {
		$package_slugs          = is_array( $package_slugs ) ? $package_slugs : array( $package_slugs );
		$licensed_package_slugs = get_option( 'wppus_licensed_package_slugs', array() );
		$changed                = false;

		foreach ( $package_slugs as $package_slug ) {

			if ( $add && ! in_array( $package_slug, $licensed_package_slugs, true ) ) {
				$licensed_package_slugs[] = $package_slug;
				$changed                  = true;

				do_action( 'wppus_added_license_check', $package_slug );
			} elseif ( ! $add && in_array( $package_slug, $licensed_package_slugs, true ) ) {
				$key = array_search( $package_slug, $licensed_package_slugs, true );

				unset( $licensed_package_slugs[ $key ] );

				$changed = true;

				do_action( 'wppus_removed_license_check', $package_slug );
			}
		}

		if ( $changed ) {
			$licensed_package_slugs = array_values( $licensed_package_slugs );

			update_option( 'wppus_licensed_package_slugs', $licensed_package_slugs, true );
		}
	}

	protected function get_package_rows_data() {
		WP_Filesystem();

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {

			return;
		}

		$package_directory = WPPUS_Data_Manager::get_data_dir( 'packages' );
		$packages          = array();

		if ( $wp_filesystem->is_dir( $package_directory ) ) {
			$search        = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : false; // @codingStandardsIgnoreLine
			$package_paths = glob( trailingslashit( $package_directory ) . '*.zip' );

			if ( ! empty( $package_paths ) ) {

				foreach ( $package_paths as $package_path ) {
					$package = $this->get_package( $package_path );
					$meta    = $package->getMetadata();
					$include = true;

					if ( $search ) {

						if (
							false === strpos( strtolower( $meta['name'] ), strtolower( $search ) ) ||
							false === strpos( strtolower( $meta['slug'] ) . '.zip', strtolower( $search ) )
						) {
							$include = false;
						}
					}

					if ( $include ) {
						$packages[ $meta['slug'] ] = array(
							'name'               => $meta['name'],
							'version'            => $meta['version'],
							'type'               => isset( $meta['details_url'] ) ? __( 'Theme', 'wppus' ) : __( 'Plugin', 'wppus' ),
							'last_updated'       => $meta['last_updated'],
							'file_name'          => $meta['slug'] . '.zip',
							'file_path'          => $package_path,
							'file_size'          => $package->getFileSize(),
							'file_last_modified' => $package->getLastModified(),
						);
					}
				}
			}
		}

		return $packages;
	}

	protected function get_package( $path ) {

		return Wpup_Package::fromArchive( $path, null, new Wpup_FileCache( WPPUS_Data_Manager::get_data_dir( 'cache' ) ) );
	}

}
