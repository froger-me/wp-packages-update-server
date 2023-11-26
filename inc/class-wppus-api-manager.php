<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_API_Manager {

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 20, 0 );

			add_filter( 'wppus_admin_scripts', array( $this, 'wppus_admin_scripts' ), 10, 1 );
			add_filter( 'wppus_admin_styles', array( $this, 'wppus_admin_styles' ), 10, 1 );
			add_filter( 'wppus_admin_tab_links', array( $this, 'wppus_admin_tab_links' ), 20, 1 );
			add_filter( 'wppus_admin_tab_states', array( $this, 'wppus_admin_tab_states' ), 20, 2 );
			add_filter( 'wppus_page_wppus_scripts_l10n', array( $this, 'wppus_page_wppus_scripts_l10n' ), 20, 2 );
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	// WordPress hooks ---------------------------------------------

	public function wppus_admin_styles( $styles ) {
		$styles['api'] = array(
			'path' => WPPUS_PLUGIN_PATH . 'css/admin/api' . wppus_assets_suffix() . '.css',
			'uri'  => WPPUS_PLUGIN_URL . 'css/admin/api' . wppus_assets_suffix() . '.css',
		);

		return $styles;
	}

	public function wppus_admin_scripts( $scripts ) {
		$scripts['api'] = array(
			'path' => WPPUS_PLUGIN_PATH . 'js/admin/api' . wppus_assets_suffix() . '.js',
			'uri'  => WPPUS_PLUGIN_URL . 'js/admin/api' . wppus_assets_suffix() . '.js',
			'deps' => array( 'jquery' ),
		);

		return $scripts;
	}

	public function wppus_page_wppus_scripts_l10n( $l10n ) {
		$l10n['deleteApiKeyConfirm']         = array(
			__( 'You are about to delete an API key.', 'wppus' ),
			__( 'If you proceed, the remote systems using it will not be able to access the API anymore.', 'wppus' ),
			"\n",
			__( 'Are you sure you want to do this?', 'wppus' ),
		);
		$l10n['deleteApiWebhookConfirm']     = array(
			__( 'You are about to delete a Webhook.', 'wppus' ),
			__( 'If you proceed, the remote URL will not receive the configured events anymore.', 'wppus' ),
			"\n",
			__( 'Are you sure you want to do this?', 'wppus' ),
		);
		$l10n['actionApiCountSingular']      = array(
			__( '1 action', 'wppus' ),
		);
		$l10n['actionApiCountSingularOther'] = array(
			__( '1 action (all records)', 'wppus' ),
		);
		$l10n['actionApiCountPlural']        = array(
			// translators: %d is the number of actions
			__( '%d actions', 'wppus' ),
		);
		$l10n['actionApiCountPluralOther']   = array(
			// translators: %d is the number of actions
			__( '%d actions (all records)', 'wppus' ),
		);
		$l10n['actionApiCountAll']           = array(
			__( 'All actions', 'wppus' ),
		);
		$l10n['actionApiCountAllOther']      = array(
			__( 'All actions (all records)', 'wppus' ),
		);
		$l10n['eventApiCountAll']            = array(
			__( 'All events', 'wppus' ),
		);
		$l10n['eventApiCountAllType']        = array(
			// translators: %s is the type of events
			__( 'All %s events', 'wppus' ),
		);
		$l10n['eventApiCountTypeSingular']   = array(
			// translators: %s is the type of event
			__( '1 %s event', 'wppus' ),
		);
		$l10n['eventApiCountTypePlural']     = array(
			// translators: %1$d is the number of events, %s is the type of events
			__( '%1$d %2$s events', 'wppus' ),
		);
		$l10n['eventApiTypePackage']         = array(
			_x( 'package', 'WPPUS webhook event type', 'wppus' ),
		);
		$l10n['eventApiTypeLicense']         = array(
			_x( 'license', 'WPPUS webhook event type', 'wppus' ),
		);
		$l10n['apiSumSep']                   = array(
			// translators: the separator between summaries ; example: All package events, 3 license events
			_x( ', ', 'WPPUS separator between API summaries', 'wppus' ),
		);

		return $l10n;
	}

	public function admin_menu() {
		$function   = array( $this, 'plugin_page' );
		$page_title = __( 'WP Packages Update Server - API & Webhooks', 'wppus' );
		$menu_title = __( 'API & Webhooks', 'wppus' );
		$menu_slug  = 'wppus-page-api';

		add_submenu_page( 'wppus-page', $page_title, $menu_title, 'manage_options', $menu_slug, $function );
	}

	public function wppus_admin_tab_links( $links ) {
		$links['api'] = array(
			admin_url( 'admin.php?page=wppus-page-api' ),
			"<span class='dashicons dashicons-rest-api'></span> " . __( 'API & Webhooks', 'wppus' ),
		);

		return $links;
	}

	public function wppus_admin_tab_states( $states, $page ) {
		$states['api'] = 'wppus-page-api' === $page;

		return $states;
	}

	// Misc. -------------------------------------------------------

	public function plugin_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$result = $this->plugin_options_handler();

		wppus_get_admin_template(
			'plugin-api-page.php',
			array(
				'result'              => $result,
				'license_api_actions' => apply_filters(
					'wppus_api_license_actions',
					array()
				),
				'package_api_actions' => apply_filters(
					'wppus_api_package_actions',
					array()
				),
				'webhook_events'      => apply_filters(
					'wppus_api_webhook_events',
					array(
						'package' => array(
							'label'  => __( 'Package events', 'wppus' ),
							'events' => array(),
						),
						'license' => array(
							'label'  => __( 'License events', 'wppus' ),
							'events' => array(),
						),
					)
				),
			)
		);
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function plugin_options_handler() {
		$errors = array();
		$result = '';

		if (
			isset( $_REQUEST['wppus_plugin_options_handler_nonce'] ) &&
			wp_verify_nonce( $_REQUEST['wppus_plugin_options_handler_nonce'], 'wppus_plugin_options' )
		) {
			$result  = __( 'WP Packages Update Server options successfully updated', 'wppus' );
			$options = $this->get_submitted_options();

			foreach ( $options as $option_name => $option_info ) {
				$condition = $option_info['value'];

				if ( isset( $option_info['condition'] ) ) {

					if ( 'ip-list' === $option_info['condition'] ) {
						$condition = true;

						if ( ! empty( $option_info['value'] ) ) {
							$option_info['value'] = array_filter( array_map( 'trim', explode( "\n", $option_info['value'] ) ) );
							$option_info['value'] = array_unique(
								array_map(
									function ( $ip ) {
										return preg_match( '/\//', $ip ) ? $ip : $ip . '/32';
									},
									$option_info['value']
								)
							);
						} else {
							$option_info['value'] = array();
						}
					} elseif ( 'api-keys' === $option_info['condition'] ) {
						$inputs = json_decode( $option_info['value'], true );

						if ( empty( $option_info['value'] ) || json_last_error() ) {
							$option_info['value'] = '{}';
						} else {
							$filtered = array();

							foreach ( $inputs as $id => $values ) {
								$id = filter_var( $id, FILTER_SANITIZE_FULL_SPECIAL_CHARS );

								if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $id ) ) {
									$id = false;
								}

								$access = filter_var(
									isset( $values['access'] ) ? $values['access'] : array(),
									FILTER_SANITIZE_FULL_SPECIAL_CHARS,
									FILTER_REQUIRE_ARRAY
								);
								$key    = filter_var(
									isset( $values['key'] ) ? $values['key'] : false,
									FILTER_SANITIZE_FULL_SPECIAL_CHARS
								);

								if ( ! $id || empty( $access ) || ! $key ) {
									$filtered = new stdClass();

									break;
								}

								$filtered[ $id ] = array(
									'key'    => $key,
									'access' => $access,
								);
							}

							$option_info['value'] = wp_json_encode(
								$filtered,
								JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
							);
						}
					}
				}

				$condition = apply_filters(
					'wppus_api_option_update',
					$condition,
					$option_name,
					$option_info,
					$options
				);

				if ( $condition ) {
					update_option( $option_name, $option_info['value'] );
				} else {
					$errors[ $option_name ] = sprintf(
						// translators: %1$s is the option display name, %2$s is the condition for update
						__( 'Option %1$s was not updated. Reason: %2$s', 'wppus' ),
						$option_info['display_name'],
						$option_info['failure_display_message']
					);
				}
			}
		} elseif (
			isset( $_REQUEST['wppus_plugin_options_handler_nonce'] ) &&
			! wp_verify_nonce( $_REQUEST['wppus_plugin_options_handler_nonce'], 'wppus_plugin_options' )
		) {
			$errors['general'] = __( 'There was an error validating the form. It may be outdated. Please reload the page.', 'wppus' );
		}

		if ( ! empty( $errors ) ) {
			$result = $errors;
		}

		do_action( 'wppus_api_options_updated', $errors );

		return $result;
	}

	protected function get_submitted_options() {
		return apply_filters(
			'wppus_submitted_api_config',
			array(
				'wppus_package_private_api_keys'         => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_package_private_api_keys', FILTER_UNSAFE_RAW ),
					'display_name'            => __( 'Package API Authentication Keys', 'wppus' ),
					'failure_display_message' => __( 'Not a valid payload', 'wppus' ),
					'condition'               => 'api-keys',
				),
				'wppus_package_private_api_ip_whitelist' => array(
					'value'     => filter_input( INPUT_POST, 'wppus_package_private_api_ip_whitelist', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'condition' => 'ip-list',
				),
				'wppus_license_private_api_keys'         => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_license_private_api_keys', FILTER_UNSAFE_RAW ),
					'display_name'            => __( 'Private API Authentication Key', 'wppus' ),
					'failure_display_message' => __( 'Not a valid string', 'wppus' ),
					'condition'               => 'api-keys',
				),
				'wppus_license_private_api_ip_whitelist' => array(
					'value'     => filter_input( INPUT_POST, 'wppus_license_private_api_ip_whitelist', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'condition' => 'ip-list',
				),
			)
		);
	}
}
