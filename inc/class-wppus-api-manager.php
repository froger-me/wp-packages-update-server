<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_API_Manager {

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 20, 0 );
			add_filter( 'wppus_admin_tab_links', array( $this, 'wppus_admin_tab_links' ), 20, 1 );
			add_filter( 'wppus_admin_tab_states', array( $this, 'wppus_admin_tab_states' ), 20, 2 );
		}
	}

	public function admin_menu() {
		$function   = array( $this, 'plugin_page' );
		$page_title = __( 'WP Packages Update Server - API & Webhooks', 'wppus' );
		$menu_title = __( 'API & Webhooks', 'wppus' );
		$menu_slug  = 'wppus-page-api';

		add_submenu_page( 'wppus-page', $page_title, $menu_title, 'manage_options', $menu_slug, $function );
	}

	public function plugin_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
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
				$skip      = false;

				if ( ! $skip && isset( $option_info['condition'] ) ) {

					if ( 'boolean' === $option_info['condition'] ) {
						$condition            = true;
						$option_info['value'] = ( $option_info['value'] );
					}

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
					}

					// @todo doc
					$condition = apply_filters(
						'wppus_api_option_update',
						$condition,
						$option_name,
						$option_info,
						$options
					);
				}

				if (
					! $skip &&
					isset( $option_info['dependency'] ) &&
					! $options[ $option_info['dependency'] ]['value']
				) {
					$skip      = true;
					$condition = false;
				}

				if ( ! $skip && $condition ) {
					update_option( $option_name, $option_info['value'] );
				} elseif ( ! $skip ) {
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

		// @todo doc
		do_action( 'wppus_api_options_updated', $errors );

		return $result;
	}

	protected function get_submitted_options() {

		return apply_filters(
			'wppus_submitted_api_config',
			array(
				'wppus_package_private_api_auth_key'     => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_package_private_api_auth_key', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Private API Authentication Key', 'wppus' ),
					'failure_display_message' => __( 'Not a valid string', 'wppus' ),
				),
				'wppus_package_private_api_ip_whitelist' => array(
					'value'     => filter_input( INPUT_POST, 'wppus_package_private_api_ip_whitelist', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'condition' => 'ip-list',
				),
			)
		);
	}
}
