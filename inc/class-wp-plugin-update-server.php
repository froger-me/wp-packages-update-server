<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Plugin_Update_Server {

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {

			if ( ! wppus_is_doing_api_request() ) {
				add_action( 'init', array( $this, 'register_activation_notices' ), 99, 0 );
				add_action( 'init', array( $this, 'maybe_flush' ), 99, 0 );
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
			}

			add_action( 'init', array( $this, 'load_textdomain' ), 10, 0 );
		}
	}

	public static function activate() {

		if ( ! version_compare( phpversion(), '7.4', '>=' ) ) {
			$error_message  = __( 'PHP version 7.4 or higher is required. Current version: ', 'wppus' );
			$error_message .= phpversion();

			die( $error_message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( ! class_exists( 'ZipArchive' ) ) {
			$error_message = __( 'The zip PHP extension is required by WP Plugin Update Server. Please check your server configuration.', 'wppus' );

			die( $error_message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( ! get_option( 'wppus_plugin_version' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$plugin_data = get_plugin_data( WPPUS_PLUGIN_FILE );
			$version     = $plugin_data['Version'];

			update_option( 'wppus_plugin_version', $version );
		}

		$result = self::maybe_create_or_upgrade_db();

		if ( ! $result ) {
			$error_message = __( 'Failed to create the necessary database table(s).', 'wppus' );

			die( $error_message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		set_transient( 'wppus_flush', 1, 60 );

		$result = WPPUS_Data_Manager::maybe_setup_directories();

		if ( ! $result ) {
			$error_message = sprintf(
				// translators: %1$s is the path to the plugin's data directory
				__( 'Permission errors creating %1$s - could not setup the data directory. Please check the parent directory is writable.', 'wppus' ),
				'<code>' . WPPUS_Data_Manager::get_data_dir() . '</code>'
			);

			die( $error_message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$result = self::maybe_setup_mu_plugin();

		if ( $result ) {
			setcookie( 'wppus_activated_mu_success', '1', 60, '/', COOKIE_DOMAIN );
		} else {
			setcookie( 'wppus_activated_mu_failure', '1', 60, '/', COOKIE_DOMAIN );
		}

		WPPUS_Remote_Sources_Manager::register_schedules();
		WPPUS_Data_Manager::register_schedules();
	}

	public static function deactivate() {
		flush_rewrite_rules();

		WPPUS_Remote_Sources_Manager::clear_schedules();
		WPPUS_Data_Manager::clear_schedules();
	}

	public static function uninstall() {
		require_once WPPUS_PLUGIN_PATH . 'uninstall.php';
	}

	public static function locate_template( $template_name, $load = false, $required_once = true ) {
		$name     = str_replace( 'templates/', '', $template_name );
		$paths    = array(
			'plugins/wppus/templates/' . $name,
			'plugins/wppus/' . $name,
			'wppus/templates/' . $name,
			'wppus/' . $name,
		);
		$template = locate_template( apply_filters( 'wppus_locate_template_paths', $paths ) );

		if ( empty( $template ) ) {
			$template = WPPUS_PLUGIN_PATH . 'inc/templates/' . $template_name;
		}

		$template = apply_filters(
			'wppus_locate_template',
			$template,
			$template_name,
			str_replace( $template_name, '', $template )
		);

		if ( $load && '' !== $template ) {
			load_template( $template, $required_once );
		}

		return $template;
	}

	public static function locate_admin_template( $template_name, $load = false, $required_once = true ) {
		$template = apply_filters(
			'wppus_locate_admin_template',
			WPPUS_PLUGIN_PATH . 'inc/templates/admin/' . $template_name,
			$template_name,
			str_replace( $template_name, '', WPPUS_PLUGIN_PATH . 'inc/templates/admin/' )
		);

		if ( $load && '' !== $template ) {
			load_template( $template, $required_once );
		}

		return $template;
	}

	public static function setup_mu_plugin_failure_notice() {
		$class   = 'notice notice-error';
		$message = sprintf(
			// translators: %1$s is the path to the mu-plugins directory, %2$s is the path of the source MU Plugin
			__( 'Permission errors for <code>%1$s</code> - could not setup the endpoint optimizer MU Plugin. You may create the directory if necessary and manually copy <code>%2$s</code> in it (recommended).', 'wppus' ),
			trailingslashit( wp_normalize_path( WPMU_PLUGIN_DIR ) ),
			wp_normalize_path( WPPUS_PLUGIN_PATH . 'optimisation/wppus-endpoint-optimizer.php' )
		);

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function setup_mu_plugin_success_notice() {
		$class   = 'notice notice-info is-dismissible';
		$message = sprintf(
			// translators: %1$s is the path to the mu-plugin
			__( 'An endpoint optimizer MU Plugin has been confirmed to be installed in <code>%1$s</code>.', 'wppus' ),
			trailingslashit( wp_normalize_path( WPMU_PLUGIN_DIR ) ) . 'wppus-endpoint-optimizer.php'
		);

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function maybe_create_or_upgrade_db() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if ( ! get_option( 'wppus_package_private_api_auth_key' ) ) {
			update_option( 'wppus_package_private_api_auth_key', bin2hex( openssl_random_pseudo_bytes( 16 ) ) );
		}

		return true;
	}

	public static function maybe_setup_mu_plugin() {
		global $wp_filesystem;

		$result        = true;
		$mu_plugin_dir = trailingslashit( wp_normalize_path( WPMU_PLUGIN_DIR ) );
		$mu_plugin     = $mu_plugin_dir . 'wppus-endpoint-optimizer.php';

		if ( ! $wp_filesystem->is_dir( $mu_plugin_dir ) ) {
			$result = $wp_filesystem->mkdir( $mu_plugin_dir );
		}

		if ( $result && ! $wp_filesystem->is_file( $mu_plugin ) ) {
			$source_mu_plugin = wp_normalize_path( WPPUS_PLUGIN_PATH . 'optimisation/wppus-endpoint-optimizer.php' );
			$result           = $wp_filesystem->copy( $source_mu_plugin, $mu_plugin );
		}

		return $result;
	}

	public function register_activation_notices() {

		if ( filter_input( INPUT_COOKIE, 'wppus_activated_mu_failure', FILTER_UNSAFE_RAW ) ) {
			setcookie( 'wppus_activated_mu_failure', '', time() - 3600, '/', COOKIE_DOMAIN );
			add_action( 'admin_notices', array( 'WP_Plugin_Update_Server', 'setup_mu_plugin_failure_notice' ), 10, 0 );
		}

		if ( filter_input( INPUT_COOKIE, 'wppus_activated_mu_success', FILTER_UNSAFE_RAW ) ) {
			setcookie( 'wppus_activated_mu_success', '', time() - 3600, '/', COOKIE_DOMAIN );
			add_action( 'admin_notices', array( 'WP_Plugin_Update_Server', 'setup_mu_plugin_success_notice' ), 10, 0 );
		}
	}

	public function maybe_flush() {

		if ( get_transient( 'wppus_flush' ) ) {
			delete_transient( 'wppus_flush' );
			flush_rewrite_rules();
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'wppus', false, 'wp-plugin-update-server/languages' );
	}

	public function admin_enqueue_scripts( $hook ) {
		$debug = (bool) ( constant( 'WP_DEBUG' ) );

		if ( false !== strpos( $hook, 'page_wppus' ) ) {
			$js_ext = ( $debug ) ? '.js' : '.min.js';
			$ver_js = filemtime( WPPUS_PLUGIN_PATH . 'js/admin/main' . $js_ext );
			$l10n   = array(
				'invalidFileFormat' => array( __( 'Error: invalid file format.', 'wppus' ) ),
				'invalidFileSize'   => array( __( 'Error: invalid file size.', 'wppus' ) ),
				'invalidFileName'   => array( __( 'Error: invalid file name.', 'wppus' ) ),
				'invalidFile'       => array( __( 'Error: invalid file', 'wppus' ) ),
				'deleteRecord'      => array( __( 'Are you sure you want to delete this record?', 'wppus' ) ),
			);
			$params = array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'debug'    => $debug,
			);

			if ( get_option( 'wppus_use_remote_repository' ) ) {
				$l10n['deletePackagesConfirm'] = array(
					__( 'You are about to delete all the packages from this server.', 'wppus' ),
					__( 'Packages with a Remote Repository will be added again automatically whenever a client asks for updates.', 'wppus' ),
					__( 'All packages manually uploaded without counterpart in a Remote Repository will be permanently deleted.', 'wppus' ),
					"\n",
					__( 'Are you sure you want to do this?', 'wppus' ),
				);
			} else {
				$l10n['deletePackagesConfirm'] = array(
					__( 'You are about to delete all the packages from this server.', 'wppus' ),
					__( 'All packages will be permanently deleted.\n\nAre you sure you want to do this?', 'wppus' ),
					"\n",
					__( 'Are you sure you want to do this?', 'wppus' ),
				);
			}

			// @todo doc
			$l10n = apply_filters( 'wppus_page_wppus_scripts_l10n', $l10n );

			foreach ( $l10n as $key => $values ) {
				$l10n[ $key ] = implode( "\n", $values );
			}

			wp_enqueue_script(
				'wp-plugin-update-server-script',
				WPPUS_PLUGIN_URL . 'js/admin/main' . $js_ext,
				array( 'jquery' ),
				$ver_js,
				true
			);
			wp_localize_script( 'wp-plugin-update-server-script', 'Wppus_l10n', $l10n );
			wp_add_inline_script(
				'wp-plugin-update-server-script',
				'var Wppus = ' . wp_json_encode( $params ),
				'before'
			);

			$css_ext = ( $debug ) ? '.css' : '.min.css';
			$ver_css = filemtime( WPPUS_PLUGIN_PATH . 'css/admin/main' . $css_ext );

			wp_enqueue_style( 'wppus-admin-main', WPPUS_PLUGIN_URL . 'css/admin/main' . $css_ext, array(), $ver_css );
		}
	}
}
