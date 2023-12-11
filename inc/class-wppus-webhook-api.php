<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Webhook_API {
	protected static $doing_update_api_request = null;
	protected static $instance;
	protected static $config;

	protected $webhooks;

	public function __construct( $init_hooks = false ) {
		$this->webhooks = json_decode( get_option( 'wppus_webhooks', '{}' ), true );

		if ( $init_hooks && get_option( 'wppus_remote_repository_use_webhooks' ) ) {

			if ( ! self::is_doing_api_request() ) {
				add_action( 'init', array( $this, 'add_endpoints' ), 10, 0 );
			}

			add_action( 'parse_request', array( $this, 'parse_request' ), -99, 0 );
			add_action( 'wppus_webhook_invalid_request', array( $this, 'wppus_webhook_invalid_request' ), 10, 0 );

			add_filter( 'query_vars', array( $this, 'query_vars' ), -99, 1 );
			add_filter( 'wppus_webhook_process_request', array( $this, 'wppus_webhook_process_request' ), 10, 2 );
		}

		add_action( 'wppus_webhook', array( $this, 'fire_webhook' ), 10, 4 );
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	// WordPress hooks ---------------------------------------------

	public function add_endpoints() {
		add_rewrite_rule( '^wppus-webhook$', 'index.php?__wppus_webhook=1&', 'top' );
		add_rewrite_rule( '^wppus-webhook/(plugin|theme)/(.+)?$', 'index.php?type=$matches[1]&package_id=$matches[2]&__wppus_webhook=1&', 'top' );
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wppus_webhook'] ) ) {
			$this->handle_api_request();

			exit;
		}
	}

	public function query_vars( $query_vars ) {
		$query_vars = array_merge(
			$query_vars,
			array(
				'__wppus_webhook',
				'package_id',
				'type',
			)
		);

		return $query_vars;
	}

	public function wppus_webhook_invalid_request() {

		if ( ! isset( $_SERVER['SERVER_PROTOCOL'] ) || '' === $_SERVER['SERVER_PROTOCOL'] ) {
			$protocol = 'HTTP/1.1';
		} else {
			$protocol = $_SERVER['SERVER_PROTOCOL'];
		}

		header( $protocol . ' 401 Unauthorized' );

		wppus_get_template(
			'error-page.php',
			array(
				'title'   => __( '401 Unauthorized', 'wppus' ),
				'heading' => __( '401 Unauthorized', 'wppus' ),
				'message' => __( 'Invalid signature', 'wppus' ),
			)
		);

		exit( -1 );
	}

	public function wppus_webhook_process_request( $process, $payload ) {
		$payload = json_decode( $payload, true );

		if ( ! $payload ) {
			return false;
		}

		$branch = false;
		$config = self::get_config();

		if (
			( isset( $payload['object_kind'] ) && 'push' === $payload['object_kind'] ) ||
			( isset( $_SERVER['X_GITHUB_EVENT'] ) && 'push' === $_SERVER['X_GITHUB_EVENT'] )
		) {
			$branch = str_replace( 'refs/heads/', '', $payload['ref'] );
		} elseif ( isset( $payload['push'], $payload['push']['changes'] ) ) {
			$branch = str_replace(
				'refs/heads/',
				'',
				$payload['push']['changes'][0]['new']['name']
			);
		}

		$process = $branch === $config['repository_branch'];

		return $process;
	}

	// Misc. -------------------------------------------------------

	public static function is_doing_api_request() {

		if ( null === self::$doing_update_api_request ) {
			self::$doing_update_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], 'wppus-webhook' ) );
		}

		return self::$doing_update_api_request;
	}

	public static function get_config() {

		if ( ! self::$config ) {
			$config = array(
				'use_webhooks'           => get_option( 'wppus_remote_repository_use_webhooks' ),
				'repository_branch'      => get_option( 'wppus_remote_repository_branch', 'master' ),
				'repository_check_delay' => intval( get_option( 'wppus_remote_repository_check_delay', 0 ) ),
				'webhook_secret'         => get_option( 'wppus_remote_repository_webhook_secret' ),
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

			self::$config = $config;
		}

		return apply_filters( 'wppus_webhook_config', self::$config );
	}

	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function schedule_webhook( $payload, $event_type ) {

		if ( empty( $this->webhooks ) ) {
			return;
		}

		if ( ! isset( $payload['event'], $payload['content'] ) ) {
			return new WP_Error(
				__METHOD__,
				__( 'The webhook payload must contain an event string and a content.', 'wppus' )
			);
		}

		$payload['origin']    = get_bloginfo( 'url' );
		$payload['timestamp'] = time();

		foreach ( $this->webhooks as $url => $info ) {
			$fire = false;

			if (
				isset( $info['secret'], $info['events'] ) &&
				! empty( $info['events'] ) &&
				is_array( $info['events'] )
			) {

				if ( in_array( $event_type, $info['events'], true ) ) {
					$fire = true;
				} else {

					foreach ( $info['events'] as $event ) {

						if ( $event === $payload['event'] && 0 === strpos( $event, $event_type ) ) {
							$fire = true;

							break;
						}
					}
				}
			}

			if ( apply_filters( 'wppus_webhook_fire', $fire, $payload, $url, $info ) ) {
				$body = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
				$hook = 'wppus_webhook';

				if ( ! wp_next_scheduled( 'wppus_webhook', array( $url, $info, $body, current_action() ) ) ) {
					$params    = array( $url, $info['secret'], $body, current_action() );
					$timestamp = time();

					wp_schedule_single_event( $timestamp, $hook, $params );
				}
			}
		}
	}

	public function fire_webhook( $url, $secret, $body, $action ) {
		return wp_remote_post(
			$url,
			array(
				'method'   => 'POST',
				'blocking' => false,
				'headers'  => array(
					'X-WPPUS-Action'        => $action,
					'X-WPPUS-Signature'     => 'sha1=' . hash_hmac( 'sha1', $body, $secret ),
					'X-WPPUS-Signature-256' => 'sha256=' . hash_hmac( 'sha256', $body, $secret ),
				),
				'body'     => $body,
			)
		);
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function handle_remote_test() {
		$sign       = $_SERVER['HTTP_X_WPPUS_SIGNATURE_256'];
		$sign_parts = explode( '=', $sign );
		$sign       = 2 === count( $sign_parts ) ? end( $sign_parts ) : false;
		$algo       = ( $sign ) ? reset( $sign_parts ) : false;
		$payload    = ( $sign ) ? filter_input_array(
			INPUT_POST,
			array(
				'test'   => FILTER_VALIDATE_INT,
				'source' => FILTER_SANITIZE_URL,
			)
		) : false;
		$valid      = false;

		if (
			$payload &&
			1 === intval( $payload['test'] ) &&
			! empty( $this->webhooks )
		) {
			$source   = $payload['source'];
			$webhooks = array_filter(
				$this->webhooks,
				function ( $key ) use ( $source ) {
					return 0 === strpos( $key, $source );
				},
				ARRAY_FILTER_USE_KEY
			);

			if ( ! empty( $webhooks ) ) {

				foreach ( $webhooks as $webhook ) {
					$secret = $webhook['secret'];
					$body   = wp_json_encode( $payload, JSON_NUMERIC_CHECK );
					$valid  = hash_equals( hash_hmac( $algo, $body, $secret ), $sign );

					if ( $valid ) {
						break;
					}
				}
			}
		}

		wp_send_json( $valid, $valid ? 200 : 403 );
	}

	protected function handle_api_request() {
		global $wp, $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';

			WP_Filesystem();
		}

		if ( isset( $_SERVER['HTTP_X_WPPUS_SIGNATURE_256'] ) ) {
			$this->handle_remote_test();
		}

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
			$package_directory = WPPUS_Data_Manager::get_data_dir( 'packages' );
			$package_exists    = false;

			if ( $wp_filesystem->is_dir( $package_directory ) ) {
				$package_path   = trailingslashit( $package_directory ) . $package_id . '.zip';
				$package_exists = $wp_filesystem->exists( $package_path );
			}

			$payload        = $wp_filesystem->get_contents( 'php://input' );
			$package_exists = apply_filters(
				'wppus_webhook_package_exists',
				$package_exists,
				$payload,
				$package_id,
				$type,
				$config
			);
			$process        = apply_filters(
				'wppus_webhook_process_request',
				true,
				$payload,
				$package_id,
				$type,
				$package_exists,
				$config
			);

			if ( $process ) {
				do_action(
					'wppus_webhook_before_processing_request',
					$payload,
					$package_id,
					$type,
					$package_exists,
					$config
				);

				$hook = 'wppus_check_remote_' . $package_id;

				wp_unschedule_hook( $hook );
				do_action( 'wppus_cleared_check_remote_schedule', $package_id, $hook );

				if ( $package_exists ) {

					if ( ! wp_next_scheduled( $hook, array( $package_id, $type, true ) ) ) {
						$params    = array( $package_id, $type, true );
						$delay     = apply_filters( 'wppus_check_remote_delay', $delay, $package_id );
						$timestamp = ( $delay ) ? time() + ( abs( intval( $delay ) ) * MINUTE_IN_SECONDS ) : time();
						$result    = wp_schedule_single_event( $timestamp, $hook, $params );

						do_action( 'wppus_scheduled_check_remote_event', $result, $package_id, $timestamp, false, $hook, $params );
					}
				} else {
					$api = WPPUS_Update_API::get_instance();

					$api->download_remote_package( $package_id, $type );
				}

				do_action(
					'wppus_webhook_after_processing_request',
					$payload,
					$package_id,
					$type,
					$package_exists,
					$config
				);
			}
		} else {
			php_log( 'Invalid request signature' );
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

			if ( isset( $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ) ) {
				$sign = $_SERVER['HTTP_X_HUB_SIGNATURE_256'];
			} elseif ( isset( $_SERVER['HTTP_X_HUB_SIGNATURE'] ) ) {
				$sign = $_SERVER['HTTP_X_HUB_SIGNATURE'];
			}

			$sign = apply_filters( 'wppus_webhook_signature', $sign, $config );

			if ( $sign ) {
				$sign_parts = explode( '=', $sign );
				$sign       = 2 === count( $sign_parts ) ? end( $sign_parts ) : false;
				$algo       = ( $sign ) ? reset( $sign_parts ) : false;
				$payload    = ( $sign ) ? $wp_filesystem->get_contents( 'php://input' ) : false;
				$valid      = $sign && hash_equals( hash_hmac( $algo, $payload, $secret ), $sign );
			}
		}

		return apply_filters( 'wppus_webhook_validate_request', $valid, $sign, $config );
	}
}
