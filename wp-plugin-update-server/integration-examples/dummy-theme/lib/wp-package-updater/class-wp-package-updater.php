<?php
/**
 * WP Package Updater
 * Plugins and themes update library to enable with WP Plugin Update Server
 *
 * @author Alexandre Froger
 * @version 1.4.0
 * @see https://github.com/froger-me/wp-package-updater
 * @copyright Alexandre Froger - https://www.froger.me
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* ================================================================================================ */
/*                                     WP Package Updater                                           */
/* ================================================================================================ */

/**
* Copy/paste this section to your main plugin file or theme's functions.php and uncomment the sections below
* where appropriate to enable updates with WP Plugin Update Server.
*
* WARNING - READ FIRST:
* Before deploying the plugin or theme, make sure to change the following value
* - https://your-update-server.com  => The URL of the server where WP Plugin Update Server is installed.
* - $prefix_updater                 => Change this variable's name with your plugin or theme prefix
**/

/** Uncomment for plugin updates **/
// require_once plugin_dir_path( __FILE__ ) . 'lib/wp-package-updater/class-wp-package-updater.php';

/** Enable plugin updates with license check **/
// $prefix_updater = new WP_Package_Updater(
// 	'https://your-update-server.com',
// 	wp_normalize_path( __FILE__ ),
// 	wp_normalize_path( plugin_dir_path( __FILE__ ) ),
// 	true
// );

/** Enable plugin updates without license check **/
// $prefix_updater = new WP_Package_Updater(
// 	'https://your-update-server.com',
// 	wp_normalize_path( __FILE__ ),
// 	wp_normalize_path( plugin_dir_path( __FILE__ ) ),
// 	false // Can be omitted, false by default
// );

/** Uncomment for theme updates **/
// require_once get_stylesheet_directory() . '/lib/wp-package-updater/class-wp-package-updater.php';

/** Enable theme updates with license check **/
// $prefix_updater = new WP_Package_Updater(
// 	'https://your-update-server.com',
// 	wp_normalize_path( __FILE__ ),
// 	get_stylesheet_directory(),
// 	true
// );

/** Enable theme updates without license check **/
// $prefix_updater = new WP_Package_Updater(
// 	'https://your-update-server.com',
// 	wp_normalize_path( __FILE__ ),
// 	get_stylesheet_directory(),
// 	false // Can be omitted, false by default
// );

/* ================================================================================================ */

if ( ! class_exists( 'WP_Package_Updater' ) ) {

	class WP_Package_Updater {

		const VERSION = '1.0.2';

		private $license_server_url;
		private $package_slug;
		private $update_server_url;
		private $package_path;
		private $package_url;
		private $update_checker;
		private $type;
		private $use_license;

		public function __construct(
			$update_server_url,
			$package_file_path,
			$package_path,
			$use_license = false
		) {
			$this->package_path = trailingslashit( $package_path );

			$this->set_type();

			$package_path_parts = explode( '/', $package_path );

			if ( 'Plugin' === $this->type ) {
				$package_slug = $package_path_parts[ count( $package_path_parts ) - 2 ];
			} elseif ( 'Theme' === $this->type ) {
				$package_slug = $package_path_parts[ count( $package_path_parts ) - 1 ];
			}

			$package_file_path_parts = explode( '/', $package_file_path );
			$package_id_parts        = array_slice( $package_file_path_parts, -2, 2 );
			$package_id              = implode( '/', $package_id_parts );

			$this->package_id        = $package_id;
			$this->update_server_url = trailingslashit( $update_server_url ) . 'wppus-update-api/';
			$this->package_slug      = $package_slug;
			$this->use_license       = $use_license;

			if ( ! class_exists( 'Puc_v4_Factory' ) ) {
				require $this->package_path . 'lib/plugin-update-checker/plugin-update-checker.php';
			}

			$metadata_url  = trailingslashit( $this->update_server_url ) . '?action=get_metadata&package_id=';
			$metadata_url .= rawurlencode( $this->package_slug );

			$this->update_checker = Puc_v4_Factory::buildUpdateChecker( $metadata_url, $package_file_path );

			$this->set_type();

			if ( 'Plugin' === $this->type ) {
				$this->package_url = plugin_dir_url( $package_file_path );
			} elseif ( 'Theme' === $this->type ) {
				$this->package_url = trailingslashit( get_theme_root_uri() ) . $package_slug;
			}

			$this->update_checker->addQueryArgFilter( array( $this, 'filter_update_checks' ) );

			if ( $this->use_license ) {
				$this->license_server_url = trailingslashit( $update_server_url ) . 'wppus-license-api/';

				$this->update_checker->addResultFilter( array( $this, 'set_license_error_notice_content' ) );

				if ( 'Plugin' === $this->type ) {
					add_action( 'after_plugin_row_' . $this->package_id, array( $this, 'print_license_under_plugin' ), 10, 3 );
				} elseif ( 'Theme' === $this->type ) {
					add_action( 'admin_menu', array( $this, 'setup_theme_admin_menus' ), 10, 0 );

					add_filter( 'custom_menu_order', array( $this, 'alter_admin_appearence_submenu_order' ), 10, 1 );
				}

				add_action( 'wp_ajax_wppu_' . $this->package_id . '_activate_license', array( $this, 'activate_license' ), 10, 0 );
				add_action( 'wp_ajax_wppu_' . $this->package_id . '_deactivate_license', array( $this, 'deactivate_license' ), 10, 0 );
				add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ), 99, 1 );
				add_action( 'admin_notices', array( $this, 'show_license_error_notice' ), 10, 0 );
				add_action( 'init', array( $this, 'load_textdomain' ), 10, 0 );
			}
		}

		public function load_textdomain() {
			$i10n_path = trailingslashit( basename( $this->package_path ) ) . 'lib/wp-update-migrate/languages';

			if ( 'Plugin' === $this->type ) {
				load_plugin_textdomain( 'wp-package-updater', false, $i10n_path );
			} else {
				load_theme_textdomain( 'wp-update-migrate', $i10n_path );
			}
		}

		public function setup_theme_admin_menus() {
			add_submenu_page(
				'themes.php',
				'Theme License',
				'Theme License',
				'manage_options',
				'theme-license',
				array( $this, 'theme_license_settings' )
			);
		}

		public function alter_admin_appearence_submenu_order( $menu_ord ) {
			global $submenu;

			$theme_menu     = $submenu['themes.php'];
			$reordered_menu = array();
			$first_key      = 0;
			$license_menu   = null;

			foreach ( $theme_menu as $key => $menu ) {

				if ( 'themes.php' === $menu[2] ) {
					$reordered_menu[ $key ] = $menu;
					$first_key              = $key;
				} elseif ( 'theme-license' === $menu[2] ) {
					$license_menu = $menu;
				} else {
					$reordered_menu[ $key + 1 ] = $menu;
				}
			}

			$reordered_menu[ $first_key + 1 ] = $license_menu;

			ksort( $reordered_menu );

			$submenu['themes.php'] = $reordered_menu; // @codingStandardsIgnoreLine

			return $menu_ord;
		}

		public function theme_license_settings() {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Sorry, you are not allowed to access this page.' ) ); // @codingStandardsIgnoreLine
			}

			$this->print_license_form_theme_page();

		}

		public function add_admin_scripts( $hook ) {
			$debug = (bool) ( constant( 'WP_DEBUG' ) );

			$condition = 'plugins.php' === $hook;
			$condition = $condition || 'appearance_page_theme-license' === $hook;
			$condition = $condition || 'appearance_page_parent-theme-license' === $hook;
			$condition = $condition && ! wp_script_is( 'wp-package-updater-script' );

			if ( $condition ) {
				$js_ext = ( $debug ) ? '.js' : '.min.js';
				$ver_js = filemtime( $this->package_path . 'lib/wp-package-updater/js/main' . $js_ext );
				$params = array(
					'action_prefix' => 'wppu_' . $this->package_id,
					'ajax_url'      => admin_url( 'admin-ajax.php' ),
				);

				wp_enqueue_script( 'wp-package-updater-script', $this->package_url . '/lib/wp-package-updater/js/main' . $js_ext, array( 'jquery' ), $ver_js, true );
				wp_localize_script( 'wp-package-updater-script', 'WP_PackageUpdater', $params );
			}
		}

		public function filter_update_checks( $query_args ) {

			if ( $this->use_license ) {
				$license           = get_option( 'license_key_' . $this->package_slug );
				$license_signature = get_option( 'license_signature_' . $this->package_slug );

				if ( $license ) {
					$query_args['update_license_key']       = rawurlencode( $license );
					$query_args['update_license_signature'] = rawurlencode( $license_signature );
				}
			}

			$query_args['update_type'] = $this->type;

			return $query_args;
		}

		public function print_license_form_theme_page() {
			$theme = wp_get_theme();
			$title = __( 'Theme License - ', 'wp-package-updater' ) . $theme->get( 'Name' );
			$form  = $this->get_license_form();

			ob_start();

			require_once $this->package_path . 'lib/wp-package-updater/templates/theme-page-license.php';

			echo ob_get_clean(); // @codingStandardsIgnoreLine			
		}

		public function print_license_under_plugin( $plugin_file = null, $plugin_data = null, $status = null ) {
			$form = $this->get_license_form();

			ob_start();

			require_once $this->package_path . 'lib/wp-package-updater/templates/plugin-page-license-row.php';

			echo ob_get_clean(); // @codingStandardsIgnoreLine
		}

		public function activate_license() {
			$license_data = $this->do_query_license( 'activate' );

			if ( isset( $license_data->package_slug, $license_data->license_key ) ) {
				update_option( 'license_key_' . $license_data->package_slug, $license_data->license_key );

				if ( isset( $license_data->license_signature ) ) {
					update_option( 'license_signature_' . $license_data->package_slug, $license_data->license_signature );
				} else {
					delete_option( 'license_signature_' . $license_data->package_slug );
				}
			} else {
				$error = new WP_Error( 'License', $license_data->message );

				if ( $license_data->clear_key ) {
					delete_option( 'license_signature_' . $this->package_slug );
					delete_option( 'license_key_' . $this->package_slug );
				}

				wp_send_json_error( $error );
			}

			// @todo remove in 2.0
			if ( 'Theme' === $this->type ) {
				delete_option( 'license_signature_' . $license_data->package_slug . '/functions.php' );
				delete_option( 'license_key_' . $license_data->package_slug . '/functions.php' );
			} else {
				delete_option( 'license_signature_' . $license_data->package_slug . '/' . $license_data->package_slug . '.php' );
				delete_option( 'license_key_' . $license_data->package_slug . '/' . $license_data->package_slug . '.php' );
			}

			delete_option( 'wppu_' . $this->package_slug . '_license_error' );
			wp_send_json_success( $license_data );
		}

		public function deactivate_license() {
			$license_data = $this->do_query_license( 'deactivate' );

			if ( isset( $license_data->package_slug, $license_data->license_key ) ) {
				update_option( 'license_key_' . $license_data->package_slug, '' );

				if ( isset( $license_data->license_signature ) ) {
					update_option( 'license_signature_' . $license_data->package_slug, '' );
				} else {
					delete_option( 'license_signature_' . $license_data->package_slug );
				}
			} else {
				$error = new WP_Error( 'License', $license_data->message );

				if ( $license_data->clear_key ) {
					delete_option( 'license_signature_' . $this->package_slug );
					delete_option( 'license_key_' . $this->package_slug );
				}

				wp_send_json_error( $error );
			}

			// @todo remove in 2.0
			if ( 'Theme' === $this->type ) {
				delete_option( 'license_signature_' . $license_data->package_slug . '/functions.php' );
				delete_option( 'license_key_' . $license_data->package_slug . '/functions.php' );
			} else {
				delete_option( 'license_signature_' . $license_data->package_slug . '/' . $license_data->package_slug . '.php' );
				delete_option( 'license_key_' . $license_data->package_slug . '/' . $license_data->package_slug . '.php' );
			}

			wp_send_json_success( $license_data );
		}

		public function set_license_error_notice_content( $package_info, $result ) {

			if ( isset( $package_info->license_error ) && ! empty( $package_info->license_error ) ) {

				$license_data = $this->handle_license_errors( $package_info->license_error );

				update_option( 'wppu_' . $this->package_slug . '_license_error', $package_info->name . ': ' . $license_data->message );
			} else {
				delete_option( 'wppu_' . $this->package_slug . '_license_error' );
			}

			return $package_info;
		}

		public function show_license_error_notice() {
			$error = get_option( 'wppu_' . $this->package_slug . '_license_error' );

			if ( $error ) {
				$class = 'license-error-' . $this->package_slug . ' notice notice-error is-dismissible';

				printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $error ); // @codingStandardsIgnoreLine
			}
		}

		protected function do_query_license( $query_type ) {

			if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'license_nonce' ) ) {
				$error = new WP_Error( 'License', 'Unauthorised access.' );

				wp_send_json_error( $error );
			}

			$license_key        = $_REQUEST['license_key'];
			$this->package_slug = $_REQUEST['package_slug'];

			if ( empty( $license_key ) ) {
				$error = new WP_Error( 'License', 'A license key is required.' );

				wp_send_json_error( $error );
			}

			$api_params = array(
				'action'          => $query_type,
				'license_key'     => $license_key,
				'allowed_domains' => $_SERVER['SERVER_NAME'],
				'package_slug'    => rawurlencode( $this->package_slug ),
			);

			$query    = esc_url_raw( add_query_arg( $api_params, $this->license_server_url ) );
			$response = wp_remote_get( $query, array(
				'timeout'   => 20,
				'sslverify' => true,
			) );

			if ( is_wp_error( $response ) ) {
				$license_data            = new stdClass();
				$license_data->clear_key = true;
				$license_data->message   = $response->get_error_message();

				return $license_data;
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( JSON_ERROR_NONE !== json_last_error() ) {
				$license_data          = new stdClass();
				$license_data->message = __( 'Unexpected Error! The query to retrieve the license data returned a malformed response.', 'wp-package-updater' );

				return $license_data;
			}

			if ( ! isset( $license_data->id ) ) {
				$license_data = $this->handle_license_errors( $license_data, $query_type );
			}

			return $license_data;
		}

		protected function handle_license_errors( $license_data, $query_type = null ) {
			$license_data->clear_key = false;

			if ( 'activate' === $query_type ) {

				if ( isset( $license_data->allowed_domains ) ) {
					$license_data->message = __( 'The license is already in use for this domain.', 'wp-package-updater' );
				} elseif ( isset( $license_data->max_allowed_domains ) ) {
					$license_data->clear_key = true;
					$license_data->message   = __( 'The license has reached the maximum number of activations and cannot be activated for this domain.', 'wp-package-updater' );
				}
			} elseif ( 'deactivate' === $query_type ) {

				if ( isset( $license_data->allowed_domains ) ) {
					$license_data->clear_key = true;
					$license_data->message   = __( 'The license is already inactive for this domain.', 'wp-package-updater' );
				}
			}

			if (
				isset( $license_data->status ) &&
				'expired' === $license_data->status
			) {
				if ( isset( $license_data->date_expiry ) ) {
					$license_data->message = sprintf(
						// translators: the license expiry date
						__( 'The license expired on %s and needs to be renewed to be updated.', 'wp-package-updater' ),
						date_i18n( get_option( 'date_format' ), $license_data->date_expiry )
					);
				} else {
					$license_data->message = __( 'The license expired and needs to be renewed to be updated.', 'wp-package-updater' );
				}
			} elseif (
				isset( $license_data->status ) &&
				'blocked' === $license_data->status
			) {
				$license_data->message = __( 'The license is blocked and cannot be updated anymore. Please use another license key.', 'wp-package-updater' );
			} elseif (
				isset( $license_data->status ) &&
				'pending' === $license_data->status
			) {
				$license_data->clear_key = true;
				$license_data->message   = __( 'The license has not been activated and its status is stil pending. Please try again or use another license key.', 'wp-package-updater' );
			} elseif (
				isset( $license_data->status ) &&
				'invalid' === $license_data->status
			) {
				$license_data->clear_key = true;
				$license_data->message   = __( 'The provided license key is invalid. Please use another license key.', 'wp-package-updater' );
			} elseif ( isset( $license_data->license_key ) ) {
				$license_data->clear_key = true;
				$license_data->message   = __( 'The provided license key does not appear to be valid. Please use another license key.', 'wp-package-updater' );
			} elseif ( 1 === count( (array) $license_data ) ) {

				if ( 'Plugin' === $this->type ) {
					$license_data->message = __( 'An active license is required to update the plugin. Please provide a valid license key in Plugins > Installed Plugins.', 'wp-package-updater' );
				} else {
					$license_data->message = __( 'An active license is required to update the theme. Please provide a valid license key in Appearence > Theme License.', 'wp-package-updater' );
				}
			} elseif ( ! isset( $license_data->message ) || empty( $license_data->message ) ) {
				$license_data->clear_key = true;

				if ( 'Plugin' === $this->type ) {
					$license_data->message = __( 'An unexpected error has occured. Please try again. If the problem persists, please contact the author of the plugin.', 'wp-package-updater' );
				} else {
					$license_data->message = __( 'An unexpected error has occured. Please try again. If the problem persists, please contact the author of the theme.', 'wp-package-updater' );
				}
			}

			return $license_data;
		}

		protected function get_license_form() {

			// @todo remove in 2.0
			if ( get_option( 'license_key_' . $this->package_id ) ) {
				update_option( 'license_key_' . $this->package_slug, get_option( 'license_key_' . $this->package_id ), true );
				delete_option( 'license_key_' . $this->package_id );
			}

			$license      = get_option( 'license_key_' . $this->package_slug );
			$package_id   = $this->package_id;
			$package_slug = $this->package_slug;
			$show_license = ( ! empty( $license ) );

			ob_start();

			require_once $this->package_path . 'lib/wp-package-updater/templates/license-form.php';

			return ob_get_clean();
		}

		protected static function is_plugin_file( $absolute_path ) {
			$plugin_dir    = wp_normalize_path( WP_PLUGIN_DIR );
			$mu_plugin_dir = wp_normalize_path( WPMU_PLUGIN_DIR );

			if ( ( 0 === strpos( $absolute_path, $plugin_dir ) ) || ( 0 === strpos( $absolute_path, $mu_plugin_dir ) ) ) {

				return true;
			}

			if ( ! is_file( $absolute_path ) ) {
				return false;
			}

			if ( function_exists( 'get_file_data' ) ) {
				$headers = get_file_data( $absolute_path, array( 'Name' => 'Plugin Name' ), 'plugin' );

				return ! empty( $headers['Name'] );
			}

			return false;
		}

		protected static function get_theme_directory_name( $absolute_path ) {

			if ( is_file( $absolute_path ) ) {
				$absolute_path = dirname( $absolute_path );
			}

			if ( file_exists( $absolute_path . '/style.css' ) ) {

				return basename( $absolute_path );
			}

			return null;
		}

		protected function set_type() {
			$theme_directory = self::get_theme_directory_name( $this->package_path );

			if ( self::is_plugin_file( $this->package_path ) ) {
				$this->type = 'Plugin';
			} elseif ( null !== $theme_directory ) {
				$this->type = 'Theme';
			} else {
				throw new RuntimeException(
					sprintf(
						'The package updater cannot determine if "%s" is a plugin or a theme. ',
						htmlentities( $this->package_path )
					)
				);
			}
		}
	}
}
