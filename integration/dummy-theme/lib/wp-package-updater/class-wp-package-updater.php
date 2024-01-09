<?php
/**
 * WP Package Updater
 * Plugins and themes update library to enable with WP Packages Update Server
 *
 * @author Alexandre Froger
 * @version 2.0
 * @copyright Alexandre Froger - https://www.froger.me
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Since v5 all classes have been moved into namespaces
 */
use YahnisElsts\PluginUpdateChecker\v5p3\PucFactory;
use YahnisElsts\PluginUpdateChecker\v5p3\Plugin\UpdateChecker;

/* ================================================================================================ */
/*                                     WP Package Updater                                           */
/* ================================================================================================ */

/**
* Copy/paste this section to your main plugin file or theme's functions.php and uncomment the sections below
* where appropriate to enable updates with WP Packages Update Server.
*
* WARNING - READ FIRST:
*
* Before deploying the plugin or theme, make sure to change the following values in wppus.json:
* - server          => The URL of the server where WP Packages Update Server is installed ; required
* - requireLicense  => Whether the package requires a license ; true or false ; optional
*
* Also change $prefix_updater below - replace "prefix" in this variable's name with a unique prefix
*

/** Use for plugin updates **/
/* phpcs:ignore Squiz.PHP.CommentedOutCode.Found
require_once plugin_dir_path( __FILE__ ) . 'lib/wp-package-updater/class-wp-package-updater.php';

$prefix_updater = new WP_Package_Updater(
	wp_normalize_path( __FILE__ ),
	wp_normalize_path( plugin_dir_path( __FILE__ ) )
);
*/

/** Use for theme updates **/
/* phpcs:ignore Squiz.PHP.CommentedOutCode.Found
require_once plugin_dir_path( __FILE__ ) . 'lib/wp-package-updater/class-wp-package-updater.php';

$prefix_updater = new WP_Package_Updater(
	wp_normalize_path( __FILE__ ),
	get_stylesheet_directory()
);
*/

/* ================================================================================================ */

if ( ! class_exists( 'WP_Package_Updater' ) ) {

	class WP_Package_Updater {
		const VERSION = '2.0';

		private $license_server_url;
		private $package_slug;
		private $update_server_url;
		private $package_path;
		private $package_url;
		private $update_checker;
		private $type;
		private $use_license;
		private $package_id;
		private $json_options;

		public function __construct( $package_file_path, $package_path ) {
			global $wp_filesystem;

			if ( ! isset( $wp_filesystem ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';

				WP_Filesystem();
			}

			$this->package_path = trailingslashit( $package_path );

			if ( ! $wp_filesystem->exists( $package_path . '/wppus.json' ) ) {
				throw new RuntimeException(
					sprintf(
						'The package updater cannot find the wppus.json file in "%s". ',
						esc_html( htmlentities( $package_path ) )
					)
				);
			}

			$update_server_url = $this->get_option( 'server' );
			$use_license       = ! empty( $this->get_option( 'requireLicense' ) );

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

			if ( ! class_exists( 'PucFactory' ) ) {
				require $this->package_path . 'lib/plugin-update-checker/plugin-update-checker.php';
			}

			$metadata_url  = trailingslashit( $this->update_server_url ) . '?action=get_metadata&package_id=';
			$metadata_url .= rawurlencode( $this->package_slug );

			$this->update_checker = PucFactory::buildUpdateChecker( $metadata_url, $package_file_path );

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
					add_filter( 'wp_prepare_themes_for_js', array( $this, 'wp_prepare_themes_for_js' ), 10, 1 );
				}

				add_action( 'wp_ajax_wppu_' . $this->package_id . '_activate_license', array( $this, 'activate_license' ), 10, 0 );
				add_action( 'wp_ajax_wppu_' . $this->package_id . '_deactivate_license', array( $this, 'deactivate_license' ), 10, 0 );
				add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ), 99, 1 );
				add_action( 'admin_notices', array( $this, 'show_license_error_notice' ), 10, 0 );
				add_action( 'init', array( $this, 'load_textdomain' ), 10, 0 );
				add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 10, 2 );

				add_filter( 'upgrader_pre_install', array( $this, 'upgrader_pre_install' ), 10, 2 );
			}
		}

		/*******************************************************************
		 * Public methods
		 *******************************************************************/

		// WordPress hooks ---------------------------------------------

		public function wp_prepare_themes_for_js( $prepared_themes ) {

			if ( isset( $prepared_themes[ $this->package_slug ] ) ) {
				$prepared_themes[ $this->package_slug ]['description'] .= '<div>' . $this->get_license_form() . '</div>';
			}

			return $prepared_themes;
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

			$submenu['themes.php'] = $reordered_menu; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			return $menu_ord;
		}

		public function add_admin_scripts( $hook ) {
			$debug = (bool) ( constant( 'WP_DEBUG' ) );

			$condition = 'plugins.php' === $hook ||
				'themes.php' === $hook ||
				'appearance_page_theme-license' === $hook ||
				'appearance_page_parent-theme-license' === $hook &&
				! wp_script_is( 'wp-package-updater-script' );

			if ( $condition ) {
				$js_ext = ( $debug ) ? '.js' : '.min.js';
				$ver_js = filemtime( $this->package_path . 'lib/wp-package-updater/js/main' . $js_ext );
				$params = array(
					'action_prefix' => 'wppu_' . $this->package_id,
					'ajax_url'      => admin_url( 'admin-ajax.php' ),
				);

				wp_enqueue_script( 'wp-package-updater-script', $this->package_url . '/lib/wp-package-updater/js/main' . $js_ext, array( 'jquery' ), $ver_js, true );
				wp_localize_script( 'wp-package-updater-script', 'WP_PackageUpdater', $params );

				if ( ! wp_style_is( 'wp-package-updater-style' ) ) {
					wp_register_style( 'wp-package-updater-style', false, array(), '1.0' );
					wp_enqueue_style( 'wp-package-updater-style' );

					$css = '
						.appearance_page_theme-license .license-error.notice {
							display: none;
						}

						.appearance_page_theme-license .postbox {
							max-width: 500px;
							margin: 0 auto;
						}

						.appearance_page_theme-license .wrap-license label {
							margin-bottom: 10px;
						}

						.appearance_page_theme-license .wrap-license input[type="text"],
						.theme-about .wrap-license input[type="text"] {
							width: 100%;
						}

						.appearance_page_theme-license .inside {
							margin: 2em;
						}

						.license-change {
							display: flex;
							flex-direction: column;
							gap: 1em;
							margin: 2em;
						}

						.plugin-update .license-change {
							flex-direction: row;
							align-items: center;
							margin: 1em 0;
						}

						.license-message {
							font-weight: bold;
						}

						.current-license-error {
						    background: #a00;
							color: white;
							padding: 0.25em;
						}
					';

					wp_add_inline_style( 'wp-package-updater-style', $css );
				}
			}
		}

		public function filter_update_checks( $query_args ) {

			if ( $this->use_license ) {
				$license           = $this->get_option( 'licenseKey' );
				$license_signature = $this->get_option( 'licenseSignature' );

				if ( $license ) {
					$query_args['license_key']       = rawurlencode( $license );
					$query_args['license_signature'] = rawurlencode( $license_signature );
				}
			}

			$query_args['update_type'] = $this->type;

			return $query_args;
		}

		public function print_license_under_plugin( $plugin_file = null, $plugin_data = null, $status = null ) {
			$this->get_template(
				'plugin-page-license-row.php',
				array(
					'form'        => $this->get_license_form(),
					'plugin_file' => $plugin_file,
					'plugin_data' => $plugin_data,
					'status'      => $status,
				)
			);
		}

		public function activate_license() {
			$license_data = $this->do_query_license( 'activate' );

			if ( isset( $license_data->package_slug, $license_data->license_key ) ) {
				$this->update_option( 'licenseKey', $license_data->license_key );

				if ( isset( $license_data->license_signature ) ) {
					$this->update_option( 'licenseSignature', $license_data->license_signature );
				} else {
					$this->delete_option( 'licenseSignature' );
				}
			} else {
				$error = new WP_Error( 'License', $license_data->message );

				if ( property_exists( $license_data, 'clear_key' ) && $license_data->clear_key ) {
					$this->delete_option( 'licenseSignature' );
					$this->delete_option( 'licenseKey' );
				}

				wp_send_json_error( $error );
			}

			$this->delete_option( 'licenseError' );
			wp_send_json_success( $license_data );
		}

		public function deactivate_license() {
			$license_data = $this->do_query_license( 'deactivate' );

			if ( isset( $license_data->package_slug, $license_data->license_key ) ) {
				$this->update_option( 'licenseKey', '' );

				if ( isset( $license_data->license_signature ) ) {
					$this->update_option( 'licenseSignature', '' );
				} else {
					$this->delete_option( 'licenseSignature' );
				}
			} else {
				$error = new WP_Error( 'License', $license_data->message );

				if ( $license_data->clear_key ) {
					$this->delete_option( 'licenseSignature' );
					$this->delete_option( 'licenseKey' );
				}

				wp_send_json_error( $error );
			}

			wp_send_json_success( $license_data );
		}

		public function set_license_error_notice_content( $package_info, $result ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

			if ( isset( $package_info->license_error ) && ! empty( $package_info->license_error ) ) {
				$license_data = $this->handle_license_errors( $package_info->license_error );

				$this->update_option( 'licenseError', $package_info->name . ': ' . $license_data->message );
			} else {
				$this->delete_option( 'licenseError' );
			}

			return $package_info;
		}

		public function show_license_error_notice() {
			$error = $this->get_option( 'licenseError' );

			if ( $error ) {
				$class = 'license-error license-error-' . $this->package_slug . ' notice notice-error is-dismissible';

				printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $error ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		public function upgrader_process_complete( $upgrader_object, $options ) {

			if ( 'update' === $options['action'] ) {

				if ( 'plugin' === $options['type'] && isset( $options['plugins'] ) && is_array( $options['plugins'] ) ) {

					foreach ( $options['plugins'] as $plugin ) {

						if ( $plugin === $this->package_id ) {
							$this->restore_wppus_options();
						}
					}
				}

				if ( 'theme' === $options['type'] && isset( $options['themes'] ) && is_array( $options['themes'] ) ) {

					foreach ( $options['themes'] as $theme ) {

						if ( $theme === $this->package_slug ) {
							$this->restore_wppus_options();
						}
					}
				}
			}
		}

		public function upgrader_pre_install( $_true, $hook_extra ) {

			if ( isset( $hook_extra['plugin'] ) ) {
				$plugin_to_update = $hook_extra['plugin'];

				if ( $plugin_to_update === $this->package_id ) {
					$this->save_wppus_options();
				}
			}

			if ( isset( $hook_extra['theme'] ) ) {
				$theme_to_update = $hook_extra['theme'];

				if ( $theme_to_update === $this->package_slug ) {
					$this->save_wppus_options();
				}
			}

			return $_true;
		}

		// Misc. -------------------------------------------------------

		public function locate_template( $template_name, $load = false, $required_once = true ) {
			$template = apply_filters(
				'wppu_' . $this->package_id . '_locate_template',
				$this->package_path . 'lib/wp-package-updater/templates/' . $template_name,
				$template_name,
				str_replace( $template_name, '', $this->package_path . 'lib/wp-package-updater/templates/' )
			);

			if ( $load && '' !== $template ) {
				load_template( $template, $required_once );
			}

			return $template;
		}

		public function get_template( $template_name, $args = array(), $load = true, $required_once = false ) {
			$template_name = apply_filters( 'wppu_' . $this->package_id . '_get_template_name', $template_name, $args );
			$template_args = apply_filters( 'wppu_' . $this->package_id . '_get_template_args', $args, $template_name );

			if ( ! empty( $template_args ) ) {

				foreach ( $template_args as $key => $arg ) {
					$key = is_numeric( $key ) ? 'var_' . $key : $key;

					set_query_var( $key, $arg );
				}
			}

			return $this->locate_template( $template_name, $load, $required_once );
		}

		public function theme_license_settings() {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Sorry, you are not allowed to access this page.' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			$theme = wp_get_theme();
			$title = __( 'Theme License - ', 'wp-package-updater' ) . $theme->get( 'Name' );

			$this->get_template(
				'theme-page-license.php',
				array(
					'form'  => $this->get_license_form(),
					'title' => $title,
					'theme' => $theme,
				)
			);
		}

		/*******************************************************************
		 * Protected methods
		 *******************************************************************/

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

		protected function get_option( $option ) {
			global $wp_filesystem;

			if ( ! isset( $wp_filesystem ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';

				WP_Filesystem();
			}

			if ( ! isset( $this->json_options ) ) {
				$wppus_json = $wp_filesystem->get_contents( $this->package_path . 'wppus.json' );

				if ( $wppus_json ) {
					$this->json_options = json_decode( $wppus_json, true );
				}
			}

			if ( isset( $this->json_options[ $option ] ) ) {
				return $this->json_options[ $option ];
			}

			return '';
		}

		protected function update_option( $option, $value ) {
			global $wp_filesystem;

			if ( ! isset( $wp_filesystem ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';

				WP_Filesystem();
			}

			if ( ! isset( $this->json_options ) ) {
				$wppus_json = $wp_filesystem->get_contents( $this->package_path . 'wppus.json' );

				if ( $wppus_json ) {
					$this->json_options = json_decode( $wppus_json, true );
				}
			}

			$this->json_options[ $option ] = $value;
			$wppus_json                    = wp_json_encode(
				$this->json_options,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			);

			$wp_filesystem->put_contents( $this->package_path . 'wppus.json', $wppus_json, FS_CHMOD_FILE );
		}

		protected function delete_option( $option ) {
			global $wp_filesystem;

			if ( ! isset( $wp_filesystem ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';

				WP_Filesystem();
			}

			if ( ! isset( $this->json_options ) ) {
				$wppus_json = $wp_filesystem->get_contents( $this->package_path . 'wppus.json' );

				if ( $wppus_json ) {
					$this->json_options = json_decode( $wppus_json, true );
				}
			}

			$save = false;

			if ( isset( $this->json_options[ $option ] ) ) {
				$save = true;

				unset( $this->json_options[ $option ] );
			}

			if ( $save ) {
				$wppus_json = wp_json_encode(
					$this->json_options,
					JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
				);

				$wp_filesystem->put_contents( $this->package_path . 'wppus.json', $wppus_json, FS_CHMOD_FILE );
			}
		}

		protected function save_wppus_options() {
			global $wp_filesystem;

			if ( ! isset( $wp_filesystem ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';

				WP_Filesystem();
			}

			if ( ! isset( $this->json_options ) ) {
				$wppus_json = $wp_filesystem->get_contents( $this->package_path . 'wppus.json' );

				if ( $wppus_json ) {
					$this->json_options = json_decode( $wppus_json, true );
				}
			}

			update_option( 'wppus_' . $this->package_slug . '_options', $this->json_options );
		}

		protected function restore_wppus_options() {
			$wppus_options = get_option( 'wppus_' . $this->package_slug . '_options' );

			if ( $wppus_options ) {
				global $wp_filesystem;

				if ( ! isset( $wp_filesystem ) ) {
					include_once ABSPATH . 'wp-admin/includes/file.php';

					WP_Filesystem();
				}

				$server                  = $this->get_option( 'server' );
				$wppus_options['server'] = $server;
				$wppus_json              = wp_json_encode(
					$wppus_options,
					JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
				);

				$wp_filesystem->put_contents( $this->package_path . 'wppus.json', $wppus_json, FS_CHMOD_FILE );
				delete_option( 'wppus_' . $this->package_slug . '_options' );
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
			$response = wp_remote_get(
				$query,
				array(
					'timeout'   => 20,
					'sslverify' => true,
				)
			);

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
			$timezone                = new DateTimeZone( wp_timezone_string() );

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
				} elseif ( isset( $license_data->next_deactivate ) ) {
					$date = new DateTime( 'now', $timezone );

					$date->setTimestamp( intval( $license_data->next_deactivate ) );

					$license_data->message = sprintf(
						// translators: the next posible deactivation date
						__( 'The license may not be deactivated before %s.', 'wp-package-updater' ),
						$date->format( get_option( 'date_format' ) . ' H:i:s' )
					);
				}
			}

			if ( isset( $license_data->status ) && 'expired' === $license_data->status ) {

				if ( isset( $license_data->date_expiry ) ) {
					$date = new DateTime( 'now', $timezone );

					$date->setTimestamp( intval( $license_data->date_expiry ) );

					$license_data->message = sprintf(
						// translators: the license expiry date
						__( 'The license expired on %s and needs to be renewed to be updated.', 'wp-package-updater' ),
						$date->format( get_option( 'date_format' ) )
					);
				} else {
					$license_data->message = __( 'The license expired and needs to be renewed to be updated.', 'wp-package-updater' );
				}
			} elseif ( isset( $license_data->status ) && 'blocked' === $license_data->status ) {
				$license_data->message = __( 'The license is blocked and cannot be updated anymore. Please use another license key.', 'wp-package-updater' );
			} elseif ( isset( $license_data->status ) && 'pending' === $license_data->status ) {
				$license_data->clear_key = true;
				$license_data->message   = __( 'The license has not been activated and its status is still pending. Please try again or use another license key.', 'wp-package-updater' );
			} elseif ( isset( $license_data->status ) && 'invalid' === $license_data->status ) {
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
			$license = $this->get_option( 'licenseKey' );

			ob_start();

			$this->get_template(
				'license-form.php',
				array(
					'license'      => $license,
					'package_id'   => $this->package_id,
					'package_slug' => $this->package_slug,
					'show_license' => ( ! empty( $license ) ),
				)
			);

			return ob_get_clean();
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
						esc_html( htmlentities( $this->package_path ) )
					)
				);
			}
		}
	}
}
