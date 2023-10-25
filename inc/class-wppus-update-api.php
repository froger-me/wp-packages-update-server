<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Update_API {
	protected $update_server;
	protected $scheduler;

	protected static $doing_update_api_request = null;

	public function __construct( $init_hooks = false ) {
		$this->scheduler = new WPPUS_Scheduler();

		if ( $init_hooks ) {

			if ( ! self::is_doing_api_request() ) {
				add_action( 'init', array( $this, 'add_endpoints' ), 10, 0 );
			}
			add_action( 'parse_request', array( $this, 'parse_request' ), -99, 0 );

			add_filter( 'query_vars', array( $this, 'addquery_variables' ), -99, 1 );
		}
	}

	public static function is_doing_api_request() {

		if ( null === self::$doing_update_api_request ) {
			$deprecated                     = ( false !== strpos( $_SERVER['REQUEST_URI'], 'wp-update-server' ) );
			self::$doing_update_api_request = $deprecated || ( false !== strpos( $_SERVER['REQUEST_URI'], 'wppus-update-api' ) );
		}

		return self::$doing_update_api_request;
	}

	public static function get_config() {
		$config = array();

		$config['use_remote_repository']          = get_option( 'wppus_use_remote_repository', false );
		$config['server_directory']               = WPPUS_Data_Manager::get_data_dir();
		$config['use_licenses']                   = get_option( 'wppus_use_licenses', false );
		$config['repository_service_url']         = get_option( 'wppus_remote_repository_url' );
		$config['repository_branch']              = get_option( 'wppus_remote_repository_branch', 'master' );
		$config['repository_credentials']         = explode( '|', get_option( 'wppus_remote_repository_credentials' ) );
		$config['repository_service_self_hosted'] = get_option( 'wppus_remote_repository_self_hosted', false );
		$config['repository_check_frequency']     = get_option( 'wppus_remote_repository_check_frequency', 'daily' );

		$is_valid_schedule = in_array( $config['repository_check_frequency'], array_keys( wp_get_schedules() ) ); // @codingStandardsIgnoreLine

		if ( ! $is_valid_schedule ) {
			$config['repository_check_frequency'] = 'daily';

			update_option( 'wppus_remote_repository_check_frequency', 'daily' );
		}

		if ( 1 < count( $config['repository_credentials'] ) ) {
			$config['repository_credentials'] = array(
				'consumer_key'    => reset( $config['repository_credentials'] ),
				'consumer_secret' => end( $config['repository_credentials'] ),
			);
		} else {
			$config['repository_credentials'] = reset( $config['repository_credentials'] );
		}

		return apply_filters( 'wppus_update_api_config', $config );
	}

	public static function maybe_download_remote_update( $slug, $type = null ) {
		$update_server = self::get_wppus_update_server( $slug, $type );

		if ( self::check_remote_update( $slug, $type, $update_server ) ) {

			return self::download_remote_update( $slug, $type, $update_server );
		}

		return false;
	}

	public static function check_remote_update( $slug, $type, $update_server = null ) {

		if ( null === $update_server ) {
			$update_server = self::get_wppus_update_server( $slug, $type );
		}

		return $update_server->check_remote_package_update( $slug );
	}

	public static function download_remote_update( $slug, $type, $update_server = null ) {

		if ( null === $update_server ) {
			$update_server = self::get_wppus_update_server( $slug, $type );
		}

		return $update_server->save_remote_package_to_local( $slug );
	}

	public function add_endpoints() {
		add_rewrite_rule( '^wp-update-server/*$', 'index.php?$matches[1]&__wppus_update_api=1&deprecated=1&', 'top' ); // @todo remove in 2.0
		add_rewrite_rule( '^wppus-update-api/*$', 'index.php?$matches[1]&__wppus_update_api=1&', 'top' );
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wppus_update_api'] ) ) {

			// @todo remove in 2.0
			if ( ! isset( $wp->query_vars['action'] ) && isset( $wp->query_vars['update_action'] ) ) {
				$wp->query_vars['action'] = $wp->query_vars['update_action'];

				unset( $wp->query_vars['update_action'] );
			}

			$this->handle_api_request();
		}
	}

	public function addquery_variables( $query_variables ) {
		$query_variables = array_merge( $query_variables, array(
			'__wppus_update_api',
			'update_action', // @todo remove in 2.0
			'action',
			'token',
			'package_id',
			'plugin_id', // @todo remove in 2.0
			'deprecated', // @todo remove in 2.0
			'update_secret_key',
			'update_license_key',
			'update_license_signature',
			'update_type',
		) );

		return $query_variables;
	}

	protected static function get_wppus_update_server( $slug, $type ) {
		$config        = self::get_config();
		$update_server = new WPPUS_Update_Server(
			$config['use_remote_repository'],
			home_url( '/wp-update-server/' ),
			new WPPUS_Scheduler(),
			$config['server_directory'],
			$config['repository_service_url'],
			$config['repository_branch'],
			$config['repository_credentials'],
			$config['repository_service_self_hosted'],
			$config['repository_check_frequency']
		);

		$update_server->set_type( $type );

		return apply_filters( 'wppus_update_server', $update_server, $config, null );
	}

	protected function handle_api_request() {
		global $wp;

		$package_id = isset( $wp->query_vars['package_id'] ) ? trim( rawurldecode( $wp->query_vars['package_id'] ) ) : null;

		// @todo remove in 2.0
		if ( ! $package_id ) {
			$package_id = isset( $wp->query_vars['plugin_id'] ) ? trim( rawurldecode( $wp->query_vars['plugin_id'] ) ) : null;
		}

		$type = isset( $wp->query_vars['update_type'] ) ? trim( $wp->query_vars['update_type'] ) : null;

		// @todo remove in 2.0
		if ( 'Plugin' === $type ) {
			$package_id_parts = explode( '/', $package_id );
			$package_id       = reset( $package_id_parts );
		} elseif ( 'Theme' === $type ) {
			$package_id = str_replace( '/functions.php', '', $package_id );
		}

		$action            = isset( $wp->query_vars['action'] ) ? trim( $wp->query_vars['action'] ) : null;
		$token             = isset( $wp->query_vars['token'] ) ? trim( $wp->query_vars['token'] ) : null;
		$secret_key        = isset( $wp->query_vars['update_secret_key'] ) ? trim( $wp->query_vars['update_secret_key'] ) : null;
		$license_key       = isset( $wp->query_vars['update_license_key'] ) ? trim( $wp->query_vars['update_license_key'] ) : null;
		$license_signature = isset( $wp->query_vars['update_license_signature'] ) ? trim( $wp->query_vars['update_license_signature'] ) : null;
		$request_params    = apply_filters( 'wppus_handle_update_request_params', array_merge( $_GET, array( // @codingStandardsIgnoreLine
			'action'            => $action,
			'token'             => $token,
			'slug'              => $package_id,
			'secret_key'        => $secret_key,
			'license_key'       => $license_key,
			'license_signature' => $license_signature,
			'type'              => $type,
		) ) );

		$this->init_server( $package_id );
		do_action( 'wppus_before_handle_update_request', $request_params );
		$this->update_server->handleRequest( $request_params );

	}

	protected function init_server( $slug ) {
		$package_use_license = false;
		$config              = self::get_config();

		if ( $config['use_licenses'] ) {
			$licensed_package_slugs = apply_filters(
				'wppus_licensed_package_slugs',
				get_option( 'wppus_licensed_package_slugs', array() )
			);

			if ( in_array( $slug, $licensed_package_slugs, true ) ) {
				$package_use_license = true;
			}
		}

		$server_class_name = 'WPPUS_Update_Server';

		if ( $package_use_license && $config['use_licenses'] ) {
			require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-license-update-server.php';

			$server_class_name = 'WPPUS_License_Update_Server';
		}

		$this->update_server = new $server_class_name(
			$config['use_remote_repository'],
			home_url( '/wp-update-server/' ),
			$this->scheduler,
			$config['server_directory'],
			$config['repository_service_url'],
			$config['repository_branch'],
			$config['repository_credentials'],
			$config['repository_service_self_hosted'],
			$config['repository_check_frequency']
		);

		$this->update_server = apply_filters( 'wppus_update_server', $this->update_server, $config, $slug, $package_use_license );
	}
}
