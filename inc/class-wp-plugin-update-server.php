<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Plugin_Update_Server {
	protected $update_server;
	protected $hmac_key;
	protected $crypto_key;
	protected $license_check_signature;
	protected $use_licenses;
	protected $scheduler;

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {

			if ( ! self::is_doing_api_request() ) {
				add_action( 'init', array( $this, 'register_activation_notices' ), 99, 0 );
				add_action( 'init', array( $this, 'maybe_flush' ), 99, 0 );
			}

			add_action( 'init', array( $this, 'load_textdomain' ), 10, 0 );
		}
	}

	public static function uninstall() {
		require_once WPPUS_PLUGIN_PATH . 'uninstall.php';
	}

	public static function deactivate() {
		flush_rewrite_rules();

		WPPUS_Update_Manager::clear_schedules();
		WPPUS_Remote_Sources_Manager::clear_schedules();
		WPPUS_License_Manager::clear_schedules();
		WPPUS_Data_Manager::clear_schedules();
	}

	public static function activate() {

		if ( ! version_compare( phpversion(), '7.0', '>=' ) ) {
			$error_message  = __( 'PHP version 7.0 or higher is required. Current version: ', 'wppus' );
			$error_message .= phpversion();

			die( $error_message ); // @codingStandardsIgnoreLine
		}

		if ( ! class_exists( 'ZipArchive' ) ) {
			$error_message  = __( 'The <a href="">zip</a> PHP extension is required by WP Plugin Update Server. Please check your server configuration.', 'wppus' );
		}

		if ( ! get_option( 'wppus_plugin_version' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$plugin_data = get_plugin_data( WPPUS_PLUGIN_FILE );
			$version     = $plugin_data['Version'];

			update_option( 'wppus_plugin_version', $version );
		}

		$result = self::maybe_create_or_upgrade_db();

		if ( ! $result ) {
			// translators: %1$s is the path to the plugin's data directory
			$error_message = sprintf(
				__( 'Failed to create the necessary database table(s).', 'wppus' ),
				WPPUS_Data_Manager::get_data_dir()
			);

			die( $error_message ); // @codingStandardsIgnoreLine
		}

		set_transient( 'wppus_flush', 1, 60 );

		$result = WPPUS_Data_Manager::maybe_setup_directories();

		if ( ! $result ) {
			$error_message = sprintf(
				// translators: %1$s is the path to the plugin's data directory
				__( 'Permission errors creating <code>%1$s</code> - could not setup the data directory. Please check the parent directory is writable.', 'wppus' ),
				WPPUS_Data_Manager::get_data_dir()
			);

			die( $error_message ); // @codingStandardsIgnoreLine
		}

		$result = self::maybe_setup_mu_plugin();

		if ( $result ) {
			set_transient( 'wppus_activated_mu_success', 1, 60 );
		} else {
			set_transient( 'wppus_activated_mu_failure', 1, 60 );
		}

		WPPUS_Update_Manager::register_schedules();
		WPPUS_Remote_Sources_Manager::register_schedules();
		WPPUS_License_Manager::register_schedules();
		WPPUS_Data_Manager::register_schedules();
	}

	public static function is_doing_api_request( $type = false ) {

		if ( ! $type ) {

			return WPPUS_Update_API::is_doing_api_request() || WPPUS_License_API::is_doing_api_request();
		}

		if ( 'license' === $type ) {

			return WPPUS_License_API::is_doing_api_request();
		}

		if ( 'update' === $type ) {

			return WPPUS_Update_API::is_doing_api_request();
		}
	}

	public static function setup_mu_plugin_failure_notice() {
		$class = 'notice notice-error';
		// translators: %1$s is the path to the mu-plugins directory, %2$s is the path of the source MU Plugin
		$message = sprintf( __( 'Permission errors for <code>%1$s</code> - could not setup the endpoint optimizer MU Plugin. You may create the directory if necessary and manually copy <code>%2$s</code> in it (recommended).', 'wppus' ),
			trailingslashit( wp_normalize_path( WPMU_PLUGIN_DIR ) ),
			wp_normalize_path( WPPUS_PLUGIN_PATH . 'optimisation/wppus-endpoint-optimizer.php' )
		);

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); // @codingStandardsIgnoreLine
	}

	public static function setup_mu_plugin_success_notice() {
		$class = 'notice notice-info is-dismissible';
		// translators: %1$s is the path to the mu-plugin
		$message = sprintf( __( 'An endpoint optimizer MU Plugin has been confirmed to be installed in <code>%1$s</code>.', 'wppus' ),
			trailingslashit( wp_normalize_path( WPMU_PLUGIN_DIR ) ) . 'wppus-endpoint-optimizer.php'
		);

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); // @codingStandardsIgnoreLine
	}

	public static function maybe_create_or_upgrade_db() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if ( ! get_option( 'wppus_license_private_api_auth_key' ) ) {
			update_option( 'wppus_license_private_api_auth_key', bin2hex( openssl_random_pseudo_bytes( 12 ) ) );
		}

		if ( ! get_option( 'wppus_license_hmac_key' ) ) {
			update_option( 'wppus_license_hmac_key', bin2hex( openssl_random_pseudo_bytes( 8 ) ) );
		}

		if ( ! get_option( 'wppus_license_crypto_key' ) ) {
			update_option( 'wppus_license_crypto_key', bin2hex( openssl_random_pseudo_bytes( 8 ) ) );
		}

		if ( ! get_option( 'wppus_package_download_url_token' ) ) {
			update_option( 'wppus_package_download_url_token', bin2hex( openssl_random_pseudo_bytes( 8 ) ), true );
		}

		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}

		$table_name = $wpdb->prefix . 'wppus_licenses';
		$sql        = 'CREATE TABLE ' . $table_name . " (
			id int(12) NOT NULL auto_increment,
			license_key varchar(255) NOT NULL,
			max_allowed_domains int(12) NOT NULL,
			allowed_domains longtext NOT NULL,
			status ENUM('pending', 'activated', 'deactivated', 'blocked', 'expired') NOT NULL DEFAULT 'pending',         
			owner_name varchar(255) NOT NULL default '',
			email varchar(64) NOT NULL,
			company_name varchar(100) NOT NULL default '',
			txn_id varchar(64) NOT NULL default '',
			date_created date NOT NULL DEFAULT '0000-00-00',
			date_renewed date NOT NULL DEFAULT '0000-00-00',
			date_expiry date NOT NULL DEFAULT '0000-00-00',
			package_slug varchar(255) NOT NULL default '',
			package_type varchar(8) NOT NULL default '',
			PRIMARY KEY  (id),
			KEY licence_key (license_key)
			)" . $charset_collate . ';';

		dbDelta( $sql );

		$table_name = $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "wppus_licenses'" );

		if ( $wpdb->prefix . 'wppus_licenses' !== $table_name ) {
			return false;
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

		if ( get_transient( 'wppus_activated_mu_failure' ) ) {
			delete_transient( 'wppus_activated_mu_failure' );
			add_action( 'admin_notices', array( 'WP_Plugin_Update_Server', 'setup_mu_plugin_failure_notice' ), 10, 0 );
		}

		if ( get_transient( 'wppus_activated_mu_success' ) ) {
			delete_transient( 'wppus_activated_mu_success' );
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

}
