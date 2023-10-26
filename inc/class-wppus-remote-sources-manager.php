<?php

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

		if ( ! get_option( 'wppus_remote_repository_use_webhooks' ) ) {
			$frequency = get_option( 'wppus_remote_repository_check_frequency', 'daily' );

			$scheduler->reschedule_remote_check_recurring_events( $frequency );
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
			wp_die( __( 'Sorry, you are not allowed to access this page.' ) ); // @codingStandardsIgnoreLine
		}

		$updated              = $this->plugin_options_handler();
		$action_error         = '';
		$registered_schedules = wp_get_schedules();
		$schedules            = array();
		$use_webhooks         = get_option( 'wppus_remote_repository_use_webhooks', 0 );

		foreach ( $registered_schedules as $key => $schedule ) {
			$schedules[ $schedule['display'] ] = array(
				'slug' => $key,
			);
		}

		ob_start();

		require_once WPPUS_PLUGIN_PATH . 'inc/templates/admin/plugin-remote-sources-page.php';

		echo ob_get_clean(); // @codingStandardsIgnoreLine
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
		} else {
			if ( 'schedules' === $type ) {
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
		} else {

			if ( 'schedules' === $type ) {
				$error = new WP_Error(
					__METHOD__,
					__( 'Error - check the packages directory is readable and not empty', 'wppus' )
				);

				wp_send_json_error( $error );
			}
		}
	}

	protected function plugin_options_handler() {
		$errors = array();
		$result = '';
		$original_wppus_remote_repository_check_frequency = get_option( 'wppus_remote_repository_check_frequency', 'daily' );
		$new_wppus_remote_repository_check_frequency      = null;
		$original_wppus_use_remote_repository             = get_option( 'wppus_use_remote_repository' );
		$new_wppus_use_remote_repository                  = null;
		$original_wppus_remote_repository_use_webhooks    = get_option( 'wppus_remote_repository_use_webhooks' );
		$new_wppus_remote_repository_use_webhooks         = null;

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

					if ( 'positive number' === $option_info['condition'] ) {
						$condition = is_numeric( $option_info['value'] ) && intval( $option_info['value'] ) >= 0;
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
				}

				if (
					! $skip && isset( $option_info['dependency'] ) &&
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

		$frequency = get_option( 'wppus_remote_repository_check_frequency', 'daily' );

		if (
			! get_option( 'wppus_remote_repository_use_webhooks' ) &&
			null !== $new_wppus_use_remote_repository &&
			$new_wppus_use_remote_repository !== $original_wppus_use_remote_repository
		) {

			if ( ! $original_wppus_use_remote_repository && $new_wppus_use_remote_repository ) {
				$this->scheduler->reschedule_remote_check_recurring_events( $frequency );
			} elseif ( $original_wppus_use_remote_repository && ! $new_wppus_use_remote_repository ) {
				$this->scheduler->clear_remote_check_scheduled_hooks();
			}
		}

		if (
			! get_option( 'wppus_remote_repository_use_webhooks' ) &&
			null !== $new_wppus_remote_repository_check_frequency &&
			$new_wppus_remote_repository_check_frequency !== $original_wppus_remote_repository_check_frequency
		) {
			$this->scheduler->reschedule_remote_check_recurring_events(
				$new_wppus_remote_repository_check_frequency
			);
		}

		return $result;
	}

	protected function get_submitted_options() {

		return apply_filters(
			'wppus_submitted_remote_sources_config',
			array(
				'wppus_use_remote_repository'             => array(
					'value'        => filter_input( INPUT_POST, 'wppus_use_remote_repository', FILTER_VALIDATE_BOOLEAN ),
					'display_name' => __( 'Use remote repository service', 'wppus' ),
					'condition'    => 'boolean',
				),
				'wppus_remote_repository_url'             => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_remote_repository_url', FILTER_VALIDATE_URL ),
					'display_name'            => __( 'Remote repository service URL', 'wppus' ),
					'failure_display_message' => __( 'Not a valid URL', 'wppus' ),
					'dependency'              => 'wppus_use_remote_repository',
					'condition'               => 'service_url',
				),
				'wppus_remote_repository_self_hosted'     => array(
					'value'        => filter_input( INPUT_POST, 'wppus_remote_repository_self_hosted', FILTER_VALIDATE_BOOLEAN ),
					'display_name' => __( 'Self-hosted remote repository service', 'wppus' ),
					'condition'    => 'boolean',
				),
				'wppus_remote_repository_branch'          => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_remote_repository_branch', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Packages branch name', 'wppus' ),
					'failure_display_message' => __( 'Not a valid string', 'wppus' ),
				),
				'wppus_remote_repository_credentials'     => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_remote_repository_credentials', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Remote repository service credentials', 'wppus' ),
					'failure_display_message' => __( 'Not a valid string', 'wppus' ),
				),
				'wppus_remote_repository_check_frequency' => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_remote_repository_check_frequency', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Remote update check frequency', 'wppus' ),
					'failure_display_message' => __( 'Not a valid option', 'wppus' ),
					'condition'               => 'known frequency',
				),
				'wppus_remote_repository_use_webhooks'    => array(
					'value'        => filter_input( INPUT_POST, 'wppus_remote_repository_use_webhooks', FILTER_VALIDATE_BOOLEAN ),
					'display_name' => __( 'Use Webhooks', 'wppus' ),
					'condition'    => 'boolean',
				),
				'wppus_remote_repository_check_delay'     => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_remote_repository_check_delay', FILTER_UNSAFE_RAW ),
					'display_name'            => __( 'Remote update schedule', 'wppus' ),
					'failure_display_message' => __( 'Not a valid option', 'wppus' ),
					'condition'               => 'positive number',
				),
			)
		);
	}

}
