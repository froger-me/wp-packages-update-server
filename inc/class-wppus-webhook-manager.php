<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Webhook_Manager {

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {
			add_action( 'wppus_template_remote_source_manager_option_before_recurring_check', array( $this, 'wppus_template_remote_source_manager_option_before_recurring_check' ), 10, 0 );

			add_filter( 'wppus_admin_scripts', array( $this, 'wppus_admin_scripts' ), 10, 1 );
			add_filter( 'wppus_submitted_remote_sources_config', array( $this, 'wppus_submitted_remote_sources_config' ), 10, 1 );
			add_filter( 'wppus_submitted_api_config', array( $this, 'wppus_submitted_api_config' ), 10, 1 );
			add_filter( 'wppus_remote_source_option_update', array( $this, 'wppus_remote_source_option_update' ), 10, 3 );
			add_filter( 'wppus_page_wppus_scripts_l10n', array( $this, 'wppus_page_wppus_scripts_l10n' ), 10, 1 );
			add_filter( 'wppus_use_recurring_schedule', array( $this, 'wppus_use_recurring_schedule' ), 10, 1 );
			add_filter( 'wppus_need_reschedule_remote_check_recurring_events', array( $this, 'wppus_need_reschedule_remote_check_recurring_events' ), 10, 1 );
		}
	}

	public static function activate() {

		if (
			! get_option( 'wppus_remote_repository_webhook_secret' ) ||
			'repository_webhook_secret' === get_option( 'wppus_remote_repository_webhook_secret' )
		) {
			update_option( 'wppus_remote_repository_webhook_secret', bin2hex( openssl_random_pseudo_bytes( 16 ) ) );
		}
	}

	public static function deactivate() {}

	public static function uninstall() {}

	public function wppus_admin_scripts( $scripts ) {
		$scripts['webhook'] = array(
			'path' => WPPUS_PLUGIN_PATH . 'js/admin/webhook' . wppus_assets_suffix() . '.js',
			'uri'  => WPPUS_PLUGIN_URL . 'js/admin/webhook' . wppus_assets_suffix() . '.js',
			'deps' => array( 'jquery' ),
		);

		return $scripts;
	}

	public function wppus_page_wppus_scripts_l10n( $l10n ) {

		if (
			get_option( 'wppus_remote_repository_use_webhooks' ) &&
			get_option( 'wppus_use_remote_repository' )
		) {
			$l10n['deletePackagesConfirm'][1] = __( 'Packages with a Remote Repository will be added again automatically whenever a client asks for updates, or when its Webhook is called.', 'wppus' );
		}

		return $l10n;
	}

	public function wppus_use_recurring_schedule( $use_recurring_schedule ) {
		return $use_recurring_schedule && ! get_option( 'wppus_remote_repository_use_webhooks' );
	}

	public function wppus_submitted_remote_sources_config( $config ) {
		$config = array_merge(
			$config,
			array(
				'wppus_remote_repository_use_webhooks'   => array(
					'value'        => filter_input( INPUT_POST, 'wppus_remote_repository_use_webhooks', FILTER_VALIDATE_BOOLEAN ),
					'display_name' => __( 'Use Webhooks', 'wppus' ),
					'condition'    => 'boolean',
				),
				'wppus_remote_repository_check_delay'    => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_remote_repository_check_delay', FILTER_UNSAFE_RAW ),
					'display_name'            => __( 'Remote download delay', 'wppus' ),
					'failure_display_message' => __( 'Not a valid option', 'wppus' ),
					'condition'               => 'positive number',
				),
				'wppus_remote_repository_webhook_secret' => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_remote_repository_webhook_secret', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Remote repository Webhook Secret', 'wppus' ),
					'failure_display_message' => __( 'Not a valid string', 'wppus' ),
					'condition'               => 'non-empty',
				),
			)
		);

		return $config;
	}

	public function wppus_remote_source_option_update( $condition, $option_name, $option_info ) {

		if ( 'wppus_remote_repository_use_webhooks' === $option_name ) {
			wp_cache_set(
				'wppus_remote_repository_use_webhooks',
				array(
					'new' => $option_info['value'],
					'old' => get_option( 'wppus_remote_repository_use_webhooks' ),
				),
				'wppus'
			);
		}

		if ( 'non-empty' === $option_info['condition'] ) {
			$condition = ! empty( $option_info['value'] );
		}

		if ( 'positive number' === $option_info['condition'] ) {
			$condition = is_numeric( $option_info['value'] ) && intval( $option_info['value'] ) >= 0;
		}

		return $condition;
	}

	public function wppus_need_reschedule_remote_check_recurring_events( $need_reschedule ) {
		$states = wp_cache_get( 'wppus_remote_repository_use_webhooks', 'wppus' );

		if ( is_array( $states ) && $states['old'] && ! $states['new'] ) {
			$need_reschedule = true;
		}

		return $need_reschedule;
	}

	public function wppus_submitted_api_config( $config ) {

		$config = array_merge(
			$config,
			array(
				'wppus_webhooks' => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_webhooks', FILTER_UNSAFE_RAW ),
					'display_name'            => __( 'Webhooks', 'wppus' ),
					'failure_display_message' => __( 'Not a valid payload', 'wppus' ),
					'condition'               => 'webhooks',
				),
			)
		);

		return $config;
	}

	public function wppus_api_option_update( $condition, $option_name, $option_info ) {

		if ( 'webhooks' === $option_info['condition'] ) {
			$inputs = json_decode( $option_info['value'], true );

			if ( empty( $option_info['value'] ) || json_last_error() ) {
				$option_info['value'] = '{}';
			} else {
				$filtered = array();

				foreach ( $inputs as $url => $values ) {
					$url             = filter_var( $url, FILTER_SANITIZE_URL );
					$events          = filter_var(
						isset( $values['events'] ) ? $values['events'] : array(),
						FILTER_SANITIZE_FULL_SPECIAL_CHARS,
						FILTER_REQUIRE_ARRAY
					);
					$secret          = filter_var(
						isset( $values['secret'] ) ? $values['secret'] : false,
						FILTER_SANITIZE_FULL_SPECIAL_CHARS
					);
					$license_api_key = filter_var(
						isset( $values['secret'] ) ? $values['secret'] : false,
						FILTER_SANITIZE_FULL_SPECIAL_CHARS
					);

					if ( ! $url || empty( $events ) || ! $secret ) {
						$filtered = new stdClass();

						break;
					}

					$filtered[ $url ] = array(
						'secret'        => $secret,
						'events'        => $events,
						'licenseAPIKey' => $license_api_key,
					);
				}

				$option_info['value'] = wp_json_encode(
					$filtered,
					JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
				);
			}
		}

		return $condition;
	}

	public function wppus_template_remote_source_manager_option_before_recurring_check() {
		wppus_get_admin_template(
			'remote-webhook-options.php',
			array(
				'use_webhooks' => get_option( 'wppus_remote_repository_use_webhooks' ),
			)
		);
	}
}
