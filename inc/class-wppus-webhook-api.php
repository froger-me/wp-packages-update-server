<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Webhook_API {
	protected $update_server;
	protected $scheduler;

	protected static $doing_update_api_request = null;

	public function __construct( $init_hooks = false ) {
		$this->scheduler = new WPPUS_Scheduler();

		if ( $init_hooks && get_option( 'wppus_remote_repository_use_webhooks', false ) ) {

			if ( ! self::is_doing_api_request() ) {
				add_action( 'init', array( $this, 'add_endpoints' ), 10, 0 );
			}

			add_action( 'parse_request', array( $this, 'parse_request' ), -99, 0 );

			add_filter( 'query_vars', array( $this, 'addquery_variables' ), -99, 1 );
		}
	}

	public static function is_doing_api_request() {

		if ( null === self::$doing_update_api_request ) {
			self::$doing_update_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], 'wppus-webhook' ) );
		}

		return self::$doing_update_api_request;
	}

	public static function get_config() {
		$config = array(
			'use_webhooks'                   => get_option( 'wppus_remote_repository_use_webhooks', false ),
			'use_remote_repository'          => get_option( 'wppus_use_remote_repository', false ),
			'server_directory'               => WPPUS_Data_Manager::get_data_dir(),
			'use_licenses'                   => get_option( 'wppus_use_licenses', false ),
			'repository_service_url'         => get_option( 'wppus_remote_repository_url' ),
			'repository_branch'              => get_option( 'wppus_remote_repository_branch', 'master' ),
			'repository_credentials'         => explode( '|', get_option( 'wppus_remote_repository_credentials' ) ),
			'repository_service_self_hosted' => get_option( 'wppus_remote_repository_self_hosted', false ),
			'repository_check_delay'         => get_option( 'repository_check_delay', 0 ),
			'webhook_secret'                 => get_option( 'wppus_remote_repository_webhook_secret' ),
		);

		if (
			! is_numeric( $config['repository_check_delay'] ) &&
			0 <= intval( $config['repository_check_delay'] )
		) {
			$config['repository_check_delay'] = 0;

			update_option( 'wppus_remote_repository_check_delay', 0 );
		}

		if ( empty( $config['repository_check_delay'] ) ) {
			$config['repository_check_delay'] = bin2hex( openssl_random_pseudo_bytes( 16 ) );

			update_option( 'wppus_remote_repository_webhook_secret', $config['repository_check_delay'] );
		}

		if ( 1 < count( $config['repository_credentials'] ) ) {
			$config['repository_credentials'] = array(
				'consumer_key'    => reset( $config['repository_credentials'] ),
				'consumer_secret' => end( $config['repository_credentials'] ),
			);
		} else {
			$config['repository_credentials'] = reset( $config['repository_credentials'] );
		}

		return apply_filters( 'wppus_webhook_config', $config );
	}

	public function add_endpoints() {
		add_rewrite_rule( '^wppus-webhook/*$', 'index.php?$matches[1]&__wppus_webhook=1&', 'top' );
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wppus_webhook'] ) ) {
			$this->handle_api_request();
		}
	}

	public function addquery_variables( $query_variables ) {
		$query_variables = array_merge( $query_variables, array( '__wppus_webhook' ) );

		return $query_variables;
	}

	protected function handle_api_request() {
		global $wp;

		$package_id        = isset( $wp->query_vars['package_id'] ) ? trim( rawurldecode( $wp->query_vars['package_id'] ) ) : null;
		$type              = isset( $wp->query_vars['update_type'] ) ? trim( $wp->query_vars['update_type'] ) : null;
		$action            = isset( $wp->query_vars['action'] ) ? trim( $wp->query_vars['action'] ) : null;
		$token             = isset( $wp->query_vars['token'] ) ? trim( $wp->query_vars['token'] ) : null;
		$secret_key        = isset( $wp->query_vars['update_secret_key'] ) ? trim( $wp->query_vars['update_secret_key'] ) : null;
		$license_key       = isset( $wp->query_vars['update_license_key'] ) ? trim( $wp->query_vars['update_license_key'] ) : null;
		$license_signature = isset( $wp->query_vars['update_license_signature'] ) ? trim( $wp->query_vars['update_license_signature'] ) : null;
		$request_params    = apply_filters(
			'wppus_handle_update_request_params',
			array_merge(
				$_GET, // @codingStandardsIgnoreLine
				array(
					'action'            => $action,
					'token'             => $token,
					'slug'              => $package_id,
					'secret_key'        => $secret_key,
					'license_key'       => $license_key,
					'license_signature' => $license_signature,
					'type'              => $type,
				)
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

		$this->update_server = apply_filters( 'wppus_update_server', $this->update_server, $config, $slug, $package_use_license );
	}
}
