<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Update_API {
	protected $update_server;
	protected $scheduler;

	protected static $doing_update_api_request = null;
	protected static $instance;

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
			self::$doing_update_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], 'wppus-update-api' ) );
		}

		return self::$doing_update_api_request;
	}

	public static function get_config() {
		$config = array(
			'use_remote_repository'          => get_option( 'wppus_use_remote_repository', false ),
			'server_directory'               => WPPUS_Data_Manager::get_data_dir(),
			'use_licenses'                   => get_option( 'wppus_use_licenses', false ),
			'repository_service_url'         => get_option( 'wppus_remote_repository_url' ),
			'repository_branch'              => get_option( 'wppus_remote_repository_branch', 'master' ),
			'repository_credentials'         => explode( '|', get_option( 'wppus_remote_repository_credentials' ) ),
			'repository_service_self_hosted' => get_option( 'wppus_remote_repository_self_hosted', false ),
			'repository_check_frequency'     => get_option( 'wppus_remote_repository_check_frequency', 'daily' ),
		);

		$is_valid_schedule = in_array(
			strtolower( $config['repository_check_frequency'] ),
			array_keys( wp_get_schedules() ),
			true
		);

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

	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function check_remote_update( $slug, $type ) {

		if ( ! $this->update_server instanceof WPPUS_Update_Server ) {
			$this->init_server( $slug );
			$this->update_server->set_type( $type );
		}

		return $this->update_server->check_remote_package_update( $slug );
	}

	public function download_remote_package( $slug, $type, $force = false ) {
		$result = false;

		$this->init_server( $slug );
		$this->update_server->set_type( $type );

		if ( $force || $this->check_remote_update( $slug, $type ) ) {
			$result = $this->update_server->save_remote_package_to_local( $slug );
		}

		return $result;
	}

	public function add_endpoints() {
		add_rewrite_rule( '^wppus-update-api/*$', 'index.php?$matches[1]&__wppus_update_api=1&', 'top' );
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wppus_update_api'] ) ) {
			$this->handle_api_request();
		}
	}

	public function addquery_variables( $query_variables ) {
		$query_variables = array_merge(
			$query_variables,
			array(
				'__wppus_update_api',
				'action',
				'token',
				'package_id',
				'update_license_key',
				'update_license_signature',
				'update_type',
			)
		);

		return $query_variables;
	}

	protected function handle_api_request() {
		global $wp;

		$vars           = $wp->query_vars;
		$package_id     = isset( $vars['package_id'] ) ? trim( rawurldecode( $vars['package_id'] ) ) : null;
		$request_params = array(
			'action'            => isset( $vars['action'] ) ? trim( $vars['action'] ) : null,
			'token'             => isset( $vars['token'] ) ? trim( $vars['token'] ) : null,
			'slug'              => $package_id,
			'license_key'       => isset( $vars['update_license_key'] ) ? trim( $vars['update_license_key'] ) : null,
			'license_signature' => isset( $vars['update_license_signature'] ) ?
				trim( $vars['update_license_signature'] ) :
				null,
			'type'              => isset( $vars['update_type'] ) ? trim( $vars['update_type'] ) : null,
		);
		$request_params = apply_filters(
			'wppus_handle_update_request_params',
			array_merge(
				$_GET, // @codingStandardsIgnoreLine
				$request_params
			)
		);

		$this->init_server( $package_id );
		do_action( 'wppus_before_handle_update_request', $request_params );
		$this->update_server->handleRequest( $request_params );
	}

	protected function init_server( $slug ) {
		$package_use_license = false;
		$config              = self::get_config();
		$server_class_name   = 'WPPUS_Update_Server';

		if ( $config['use_licenses'] ) {
			$licensed_package_slugs = apply_filters(
				'wppus_licensed_package_slugs',
				get_option( 'wppus_licensed_package_slugs', array() )
			);

			if ( in_array( $slug, $licensed_package_slugs, true ) ) {
				$package_use_license = true;
			}
		}

		if ( $package_use_license && $config['use_licenses'] ) {
			require_once WPPUS_PLUGIN_PATH . 'inc/class-wppus-license-update-server.php';

			$server_class_name = 'WPPUS_License_Update_Server';
		}

		$this->update_server = new $server_class_name(
			$config['use_remote_repository'],
			home_url( '/wppus-update-api/' ),
			$this->scheduler,
			$config['server_directory'],
			$config['repository_service_url'],
			$config['repository_branch'],
			$config['repository_credentials'],
			$config['repository_service_self_hosted'],
			$config['repository_check_frequency']
		);
		$this->update_server = apply_filters(
			'wppus_update_server',
			$this->update_server,
			$config,
			$slug,
			$package_use_license
		);
	}
}
