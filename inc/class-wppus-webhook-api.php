<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Webhook_API {
	protected $update_server;
	protected $scheduler;

	protected static $doing_update_api_request = null;

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks && get_option( 'wppus_remote_repository_use_webhooks', false ) ) {

			if ( ! self::is_doing_api_request() ) {
				add_action( 'init', array( $this, 'add_endpoints' ), 10, 0 );
			}

			add_action( 'parse_request', array( $this, 'parse_request' ), -99, 0 );
			add_action( 'wppus_webhook_invalid_request', array( $this, 'handle_unauthorized_request' ), 10, 0 );

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
			'repository_check_delay'         => intval( get_option( 'wppus_remote_repository_check_delay', 0 ) ),
			'webhook_secret'                 => get_option( 'wppus_remote_repository_webhook_secret' ),
		);

		if (
			! is_numeric( $config['repository_check_delay'] ) &&
			0 <= intval( $config['repository_check_delay'] )
		) {
			$config['repository_check_delay'] = 0;

			update_option( 'wppus_remote_repository_check_delay', 0 );
		}

		if ( empty( $config['webhook_secret'] ) ) {
			$config['webhook_secret'] = bin2hex( openssl_random_pseudo_bytes( 16 ) );

			update_option( 'wppus_remote_repository_webhook_secret', $config['webhook_secret'] );
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
		add_rewrite_rule( '^wppus-webhook/(.+)/(.+)?$', 'index.php?type=$matches[1]&package_id=$matches[2]&__wppus_webhook=1&', 'top' );
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wppus_webhook'] ) ) {
			$this->handle_api_request();

			die();
		}
	}

	public function addquery_variables( $query_variables ) {
		$query_variables = array_merge( $query_variables, array( '__wppus_webhook', 'package_id', 'type' ) );

		return $query_variables;
	}

	public function handle_unauthorized_request() {

		if ( ! isset( $_SERVER['SERVER_PROTOCOL'] ) || '' === $_SERVER['SERVER_PROTOCOL'] ) {
			$protocol = 'HTTP/1.1';
		} else {
			$protocol = $_SERVER['SERVER_PROTOCOL'];
		}

		header( $protocol . ' 401 Unauthorized' );

		echo '
			<html>
				<head>
					<title>401 Unauthorized</title>
				</head>
				<body>
					<h1>401 Unauthorized</h1>
					<p>Invalid signature</p>
				</body>
			</html>
		';

		exit( -1 );
	}

	protected function handle_api_request() {
		global $wp, $wp_filesystem;

		$config = self::get_config();

		do_action( 'wppus_webhook_before_handling_request', $config );

		if ( $this->validate_request( $config ) ) {
			$package_id        = isset( $wp->query_vars['package_id'] ) ?
				trim( rawurldecode( $wp->query_vars['package_id'] ) ) :
				null;
			$type              = isset( $wp->query_vars['type'] ) ?
				trim( rawurldecode( $wp->query_vars['type'] ) ) :
				null;
			$delay             = $config['repository_check_delay'];
			$scheduler         = new WPPUS_Scheduler();
			$package_directory = WPPUS_Data_Manager::get_data_dir( 'packages' );
			$package_exists    = false;

			if ( $wp_filesystem->is_dir( $package_directory ) ) {
				$package_path   = trailingslashit( $package_directory ) . $package_id . '.zip';
				$package_exists = $wp_filesystem->exists( $package_path );
			}

			do_action(
				'wppus_webhook_before_processing_request',
				$package_id,
				$type,
				$package_exists,
				$config,
				$scheduler
			);

			if ( $package_exists && $delay ) {
				$scheduler->clear_remote_check_schedule( $package_id, $type, true );
				$scheduler->register_remote_check_single_event( $package_id, $type, $delay );
			} else {
				$scheduler->clear_remote_check_schedule( $package_id, $type, true );
				WPPUS_Update_API::maybe_download_remote_update( $package_id, $type, true );
			}

			do_action(
				'wppus_webhook_after_processing_request',
				$package_id,
				$type,
				$package_exists,
				$config,
				$scheduler
			);
		} else {
			error_log(  __METHOD__ . ' invalid request signature' ); // @codingStandardsIgnoreLine

			do_action( 'wppus_webhook_invalid_request', $config );
		}

		do_action( 'wppus_webhook_after_handling_request', $config );
	}

	protected function validate_request( $config ) {
		$valid  = false;
		$sign   = false;
		$secret = apply_filters( 'wppus_webhook_secret', $config['webhook_secret'], $config );

		if ( isset( $_SERVER['HTTP_X_GITLAB_TOKEN'] ) ) {
			$valid = $_SERVER['HTTP_X_GITLAB_TOKEN'] === $secret;
		} else {
			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';

				WP_Filesystem();
			}

			if ( isset( $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ) ) {
				$sign = $_SERVER['HTTP_X_HUB_SIGNATURE_256'];
			} elseif ( isset( $_SERVER['HTTP_X_HUB_SIGNATURE'] ) ) {
				$sign = $_SERVER['HTTP_X_HUB_SIGNATURE'];
			}

			$sign = apply_filters( 'wppus_webhook_signature', $sign, $config );

			if ( $sign ) {
				$sign_parts = explode( $sign, '=' );
				$sign       = 2 === count( $sign_parts ) ? end( $sign_parts ) : false;
				$algo       = ( $sign ) ? reset( $sign_parts ) : false;
				$payload    = ( $sign ) ? $wp_filesystem->get_contents( 'php://input' ) : false;
				$valid      = $sign && hash_equals( hash_hmac( $algo, $payload, $secret ), $sign );
			}
		}

		return apply_filters( 'wppus_webhook_validate_request', $valid, $sign, $config );
	}
}
