<?php

use YahnisElsts\PluginUpdateChecker\v5p1\OAuthSignature;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Remote_Sources_Manager {

	protected $scheduler;

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {
			$this->scheduler = new WPPUS_Scheduler();

			if ( get_option( 'wppus_use_remote_repository' ) ) {
				add_action( 'init', array( $this->scheduler, 'register_remote_check_scheduled_hooks' ), 10, 0 );
			} else {
				add_action( 'init', array( $this->scheduler, 'clear_remote_check_scheduled_hooks' ), 10, 0 );
			}

			add_action( 'wp_ajax_wppus_force_clean', array( $this, 'force_clean' ), 10, 0 );
			add_action( 'wp_ajax_wppus_force_register', array( $this, 'force_register' ), 10, 0 );
			add_action( 'wp_ajax_wppus_remote_repository_test', array( $this, 'remote_repository_test' ), 10, 0 );
			add_action( 'admin_menu', array( $this, 'plugin_options_menu' ), 11, 0 );
		}
	}

	public static function clear_schedules() {
		$scheduler = new WPPUS_Scheduler();

		return $scheduler->clear_remote_check_scheduled_hooks();
	}

	public static function register_schedules() {
		$scheduler = new WPPUS_Scheduler();
		$result    = false;

		// @todo doc
		if ( apply_filters( 'wppus_use_recurring_schedule', true ) ) {
			$frequency = get_option( 'wppus_remote_repository_check_frequency', 'daily' );
			$result    = $scheduler->reschedule_remote_check_recurring_events( $frequency );
		}

		return $result;
	}

	public function plugin_options_menu() {
		$function    = array( $this, 'plugin_packages_remote_source_page' );
		$page_title  = __( 'WP Plugin Update Server - Remote Sources', 'wppus' );
		$menu_title  = __( 'Remote Sources', 'wppus' );
		$menu_slug   = 'wppus-page-remote-sources';
		$parent_slug = 'wppus-page';
		$capability  = 'manage_options';

		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	public function plugin_packages_remote_source_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$registered_schedules = wp_get_schedules();
		$schedules            = array();

		foreach ( $registered_schedules as $key => $schedule ) {
			$schedules[ $schedule['display'] ] = array(
				'slug' => $key,
			);
		}

		wppus_get_admin_template(
			'plugin-remote-sources-page.php',
			array(
				'updated'              => $this->plugin_options_handler(),
				'action_error'         => '',
				'registered_schedules' => $registered_schedules,
				'schedules'            => $schedules,
				'hide_check_frequency' => ! apply_filters(
					'wppus_use_recurring_schedule',
					true
				),
				'packages_dir'         => WPPUS_Data_Manager::get_data_dir( 'packages' ),
			)
		);
	}

	public function force_register() {
		$result = false;
		$type   = false;

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'wppus_plugin_options' ) ) {
			$type = filter_input( INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

			if ( 'schedules' === $type ) {
				$result = self::register_schedules();
			}
		}

		if ( $result && $type ) {
			wp_send_json_success();
		} elseif ( 'schedules' === $type ) {
			$error = new WP_Error(
				__METHOD__,
				__( 'Error - check the packages directory is readable and not empty', 'wppus' )
			);

			wp_send_json_error( $error );
		} else {
			$error = new WP_Error(
				__METHOD__,
				__( 'Error - an unknown error has occured', 'wppus' ) . ': type = "' . $type . '" ; result = "' . $result . '"'
			);

			wp_send_json_error( $error );
		}
	}

	public function force_clean() {
		$result = false;
		$type   = false;

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'wppus_plugin_options' ) ) {
			$type = filter_input( INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

			if ( 'schedules' === $type ) {
				$result = self::clear_schedules();
			}
		}

		if ( $result && $type ) {
			wp_send_json_success();
		} elseif ( 'schedules' === $type ) {
			$error = new WP_Error(
				__METHOD__,
				__( 'Error - check the packages directory is readable and not empty', 'wppus' )
			);

			wp_send_json_error( $error );
		}
	}

	public function remote_repository_test() {
		$result = array();

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'wppus_plugin_options' ) ) {
			$data = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY );

			if ( $data ) {
				$url         = $data['wppus_remote_repository_url'];
				$self_hosted = $data['wppus_remote_repository_self_hosted'];
				$credentials = $data['wppus_remote_repository_credentials'];
				$options     = array();
				$service     = false;
				$host        = wp_parse_url( $url, PHP_URL_HOST );
				$path        = wp_parse_url( $url, PHP_URL_PATH );
				$user_name   = false;

				if ( 'true' === $self_hosted ) {
					$service = 'GitLab';
				} else {
					$services = array(
						'github.com'    => 'GitHub',
						'bitbucket.org' => 'BitBucket',
						'gitlab.com'    => 'GitLab',
					);

					if ( isset( $services[ $host ] ) ) {
						$service = $services[ $host ];
					}
				}

				if ( 'BitBucket' === $service ) {
					wp_send_json_error(
						new WP_Error(
							__METHOD__,
							__( 'Error - Test Remote Repository Access is not supported for Bitbucket. Please save your settings and try to prime a package in the Overview page.', 'wppus' )
						)
					);
				}

				if ( preg_match( '@^/?(?P<username>[^/]+?)/?$@', $path, $matches ) ) {
					$user_name = $matches['username'];

					if ( 'GitHub' === $service ) {
						$url                = 'https://api.github.com/user';
						$options            = array( 'timeout' => 3 );
						$options['headers'] = array(
							'Authorization' => 'Basic '
								. base64_encode( $user_name . ':' . $credentials ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
						);
					} elseif ( 'GitLab' === $service ) {
						$options = array( 'timeout' => 3 );
						$scheme  = wp_parse_url( $url, PHP_URL_SCHEME );
						$url     = sprintf(
							'%1$s://%2$s/api/v4/groups/%3$s/?private_token=%4$s',
							$scheme,
							$host,
							$user_name,
							$credentials
						);
					}
				}

				$response = wp_remote_get( $url, $options );

				if ( is_wp_error( $response ) ) {
					$result = $response;
				} else {
					$code = wp_remote_retrieve_response_code( $response );
					$body = wp_remote_retrieve_body( $response );

					if ( 200 === $code ) {
						$condition = false;

						if ( 'GitHub' === $service ) {
							$body      = json_decode( $body, true );
							$condition = trailingslashit(
								$data['wppus_remote_repository_url']
							) === trailingslashit(
								$body['html_url']
							);
						}

						if ( 'GitLab' === $service ) {
							$body      = json_decode( $body, true );
							$condition = $user_name === $body['path'];
						}

						if ( $condition ) {
							$result[] = __( 'Remote Repository Service was reached sucessfully.', 'wppus' );
						} else {
							$result = new WP_Error(
								__METHOD__,
								__( 'Error - Please check the provided Remote Repository Service URL.', 'wppus' )
							);
						}
					} else {
						$result = new WP_Error(
							__METHOD__,
							__( 'Error - Please check the provided Remote Repository Service Credentials.', 'wppus' )
						);
					}
				}
			} else {
				$result = new WP_Error(
					__METHOD__,
					__( 'Error - Received invalid data ; please reload the page and try again.', 'wppus' )
				);
			}
		}

		if ( ! is_wp_error( $result ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	protected function plugin_options_handler() {
		$errors = array();
		$result = '';
		$original_wppus_remote_repository_check_frequency = get_option( 'wppus_remote_repository_check_frequency', 'daily' );
		$new_wppus_remote_repository_check_frequency      = null;
		$original_wppus_use_remote_repository             = get_option( 'wppus_use_remote_repository' );
		$new_wppus_use_remote_repository                  = null;

		if (
			isset( $_REQUEST['wppus_plugin_options_handler_nonce'] ) &&
			wp_verify_nonce( $_REQUEST['wppus_plugin_options_handler_nonce'], 'wppus_plugin_options' )
		) {
			$result  = __( 'WP Plugin Update Server options successfully updated', 'wppus' );
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

					if ( 'known frequency' === $option_info['condition'] ) {
						$schedules      = wp_get_schedules();
						$schedule_slugs = array_keys( $schedules );
						$condition      = $condition && in_array( $option_info['value'], $schedule_slugs, true );
					}

					if ( 'service_url' === $condition ) {
						$repo_regex = '@^/?([^/]+?)/([^/#?&]+?)/?$@';
						$path       = wp_parse_url( $option_info['value'], PHP_URL_PATH );
						$condition  = (bool) preg_match( $repo_regex, $path );
					}

					// @todo doc
					$condition = apply_filters(
						'wppus_remote_source_option_update',
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

					if ( 'wppus_remote_repository_check_frequency' === $option_name ) {
						$new_wppus_remote_repository_check_frequency = $option_info['value'];
					}

					if ( 'wppus_use_remote_repository' === $option_name ) {
						$new_wppus_use_remote_repository = $option_info['value'];
					}
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

		if ( apply_filters( 'wppus_use_recurring_schedule', true ) ) {

			if (
				null !== $new_wppus_use_remote_repository &&
				$new_wppus_use_remote_repository !== $original_wppus_use_remote_repository
			) {

				if ( ! $original_wppus_use_remote_repository && $new_wppus_use_remote_repository ) {
					$this->scheduler->reschedule_remote_check_recurring_events(
						get_option( 'wppus_remote_repository_check_frequency', 'daily' )
					);
				} elseif (
					$original_wppus_use_remote_repository &&
					! $new_wppus_use_remote_repository
				) {
					$this->scheduler->clear_remote_check_scheduled_hooks();
				}
			}

			if (
				null !== $new_wppus_remote_repository_check_frequency &&
				$new_wppus_remote_repository_check_frequency !== $original_wppus_remote_repository_check_frequency
			) {
				$this->scheduler->reschedule_remote_check_recurring_events(
					$new_wppus_remote_repository_check_frequency
				);
			}
		} else {
			$this->scheduler->clear_remote_check_scheduled_hooks();
			set_transient( 'wppus_flush', 1, 60 );
		}

		// @todo doc
		do_action( 'wppus_remote_sources_options_updated', $errors );

		return $result;
	}

	protected function get_submitted_options() {

		return apply_filters(
			'wppus_submitted_remote_sources_config',
			array(
				'wppus_use_remote_repository'             => array(
					'value'        => filter_input( INPUT_POST, 'wppus_use_remote_repository', FILTER_VALIDATE_BOOLEAN ),
					'display_name' => __( 'Use a Remote Repository Service', 'wppus' ),
					'condition'    => 'boolean',
				),
				'wppus_remote_repository_url'             => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_remote_repository_url', FILTER_VALIDATE_URL ),
					'display_name'            => __( 'Remote Repository Service URL', 'wppus' ),
					'failure_display_message' => __( 'Not a valid URL', 'wppus' ),
					'dependency'              => 'wppus_use_remote_repository',
					'condition'               => 'service_url',
				),
				'wppus_remote_repository_self_hosted'     => array(
					'value'        => filter_input( INPUT_POST, 'wppus_remote_repository_self_hosted', FILTER_VALIDATE_BOOLEAN ),
					'display_name' => __( 'Self-hosted Remote Repository Service', 'wppus' ),
					'condition'    => 'boolean',
				),
				'wppus_remote_repository_branch'          => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_remote_repository_branch', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Packages Branch Name', 'wppus' ),
					'failure_display_message' => __( 'Not a valid string', 'wppus' ),
				),
				'wppus_remote_repository_credentials'     => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_remote_repository_credentials', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Remote Repository Service Credentials', 'wppus' ),
					'failure_display_message' => __( 'Not a valid string', 'wppus' ),
				),
				'wppus_remote_repository_check_frequency' => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_remote_repository_check_frequency', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Remote Update Check Frequency', 'wppus' ),
					'failure_display_message' => __( 'Not a valid option', 'wppus' ),
					'condition'               => 'known frequency',
				),
				'wppus_package_private_api_auth_key'      => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_package_private_api_auth_key', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Private API Authentication Key', 'wppus' ),
					'failure_display_message' => __( 'Not a valid string', 'wppus' ),
				),
				'wppus_package_private_api_ip_whitelist'  => array(
					'value'     => filter_input( INPUT_POST, 'wppus_package_private_api_ip_whitelist', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'condition' => 'ip-list',
				),
			)
		);
	}
}
