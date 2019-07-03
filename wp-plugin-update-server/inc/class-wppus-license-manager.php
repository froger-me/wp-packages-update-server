<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_License_Manager {

	protected $licences_table;
	protected $message = '';
	protected $errors  = array();
	protected $scheduler;
	protected $license_server;

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {
			$use_licenses = get_option( 'wppus_use_licenses', false );

			$this->scheduler = new WPPUS_Scheduler();

			if ( $use_licenses ) {
				$this->license_server = new WPPUS_License_Server();

				add_action( 'init', array( $this->scheduler, 'register_license_events' ), 10, 0 );
				add_action( 'init', array( $this->scheduler, 'register_license_schedules' ), 10, 0 );
			}

			add_action( 'admin_init', array( $this, 'init_request' ), 10, 0 );
			add_action( 'admin_menu', array( $this, 'plugin_options_menu' ), 11, 0 );
			add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ), 5, 1 );
			add_action( 'load-wp-plugin-update-server_page_wppus-page-licenses', array( $this, 'add_page_options' ), 10, 0 );

			add_filter( 'set-screen-option', array( $this, 'set_page_options' ), 10, 3 );
		}
	}

	public static function clear_schedules() {
		$scheduler = new WPPUS_Scheduler();

		return $scheduler->clear_license_schedules();
	}

	public static function register_schedules() {
		$scheduler = new WPPUS_Scheduler();
		$frequency = apply_filters( 'wppus_schedule_license_frequency', 'hourly' );

		return $scheduler->register_license_schedules( $frequency );
	}

	public static function expire_licenses() {
		$license_server = new WPPUS_License_Server();

		$license_server->switch_expired_licenses_status();
	}

	public function init_request() {

		if ( is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() ) {
			$this->licences_table = new WPPUS_Licenses_Table();
			$redirect             = false;

			$condition = ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], $this->licences_table->nonce_action ) );
			$condition = $condition || ( isset( $_REQUEST['linknonce'] ) && wp_verify_nonce( $_REQUEST['linknonce'], 'linknonce' ) );
			$condition = $condition || ( isset( $_REQUEST['wppus_license_form_nonce'] ) && wp_verify_nonce( $_REQUEST['wppus_license_form_nonce'], 'wppus_license_form_nonce' ) );

			if ( $condition ) {
				$page                = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : false;
				$license_data        = isset( $_REQUEST['license_data'] ) ? $_REQUEST['license_data'] : false;
				$delete_all_licenses = isset( $_REQUEST['wppus_delete_all_licenses'] ) ? true : false;
				$license_data        = isset( $_REQUEST['wppus_license_values'] ) ? $_REQUEST['wppus_license_values'] : $license_data;
				$action              = isset( $_REQUEST['wppus_license_action'] ) ? $_REQUEST['wppus_license_action'] : false;

				if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {  // @codingStandardsIgnoreLine
					$action = $_REQUEST['action'];
				} elseif ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {  // @codingStandardsIgnoreLine
					$action = $_REQUEST['action2'];
				}

				if ( 'wppus-page-licenses' === $page ) {
					$redirect = false;

					if ( $license_data && in_array( $action, WPPUS_License_Server::$license_statuses, true ) ) {
						$this->change_license_statuses_bulk( $action, $license_data );
					}

					if ( false !== $license_data && 'delete' === $action ) {
						$this->delete_license_bulk( $license_data );
					}

					if ( $license_data && 'update' === $action ) {
						$this->update_license( $license_data );
					}

					if ( $license_data && 'create' === $action ) {
						$this->create_license( $license_data );
					}

					if ( $delete_all_licenses ) {
						$this->delete_all_licenses();
					}
				}
			}
		}
	}

	public function add_admin_scripts( $hook ) {
		$debug = (bool) ( constant( 'WP_DEBUG' ) );

		if ( 'wp-plugin-update-server_page_wppus-page-licenses' === $hook ) {
			wp_enqueue_script( 'wp-plugin-update-server-validate-script', WPPUS_PLUGIN_URL . 'js/admin/jquery.validate.min.js', array( 'jquery' ), false, true );
			wp_enqueue_script( 'jquery-ui-datepicker' );

			$css_ext = ( $debug ) ? '.css' : '.min.css';
			$ver_css = filemtime( WPPUS_PLUGIN_PATH . 'css/admin/jquery-ui' . $css_ext );

			wp_enqueue_style( 'wppus-admin-jquery-ui', WPPUS_PLUGIN_URL . 'css/admin/jquery-ui' . $css_ext, array(), $ver_css );
		}
	}

	public function add_page_options() {
		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Licenses per page', 'wppus' ),
			'default' => 10,
			'option'  => 'licenses_per_page',
		);

		add_screen_option( $option, $args );
	}

	public function set_page_options( $status, $option, $value ) {

		return $value;
	}

	public function plugin_options_menu() {
		$function    = array( $this, 'plugin_page_license_settings' );
		$page_title  = __( 'WP Plugin Update Server - Licenses', 'wppus' );
		$menu_title  = __( 'Licenses', 'wppus' );
		$menu_slug   = 'wppus-page-licenses';
		$parent_slug = 'wppus-page';
		$capability  = 'manage_options';

		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	public function plugin_page_license_settings() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.' ) ); // @codingStandardsIgnoreLine
		}
		$result         = $this->plugin_options_handler();
		$licences_table = $this->licences_table;

		if ( ! $result ) {

			if ( ! empty( $this->errors ) ) {
				$result = $this->errors;
			} else {
				$result = $this->message;
			}
		}

		$licences_table->prepare_items();
		ob_start();

		require_once WPPUS_PLUGIN_PATH . 'inc/templates/admin/plugin-licenses-page.php';

		echo ob_get_clean(); // @codingStandardsIgnoreLine
	}

	protected function plugin_options_handler() {
		$errors = array();
		$result = false;

		if ( isset( $_REQUEST['wppus_plugin_options_handler_nonce'] ) &&
			wp_verify_nonce( $_REQUEST['wppus_plugin_options_handler_nonce'], 'wppus_plugin_options' ) ) {
			$result  = __( 'WP Plugin Update Server license options successfully updated.', 'wppus' );
			$options = $this->get_submitted_options();

			foreach ( $options as $option_name => $option_info ) {
				$condition = $option_info['value'];

				if ( isset( $option_info['condition'] ) ) {

					if ( 'boolean' === $option_info['condition'] ) {
						$condition            = true;
						$option_info['value'] = ( $option_info['value'] );
					}
				}

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
		} elseif ( isset( $_REQUEST['wppus_plugin_options_handler_nonce'] ) && ! wp_verify_nonce( $_REQUEST['wppus_plugin_options_handler_nonce'], 'wppus_plugin_options' ) ) {
			$errors['general'] = __( 'There was an error validating the form. It may be outdated. Please reload the page.', 'wppus' );
		}

		if ( ! empty( $errors ) ) {
			$result       = false;
			$this->errors = $errors;
		}

		return $result;
	}

	protected function get_submitted_options() {

		return apply_filters( 'wppus_submitted_licenses_config', array(
			'wppus_use_licenses'                 => array(
				'value'        => filter_input( INPUT_POST, 'wppus_use_licenses', FILTER_VALIDATE_BOOLEAN ),
				'display_name' => __( 'Enable Package Licenses', 'wppus' ),
				'condition'    => 'boolean',
			),
			'wppus_license_private_api_auth_key' => array(
				'value'                   => filter_input( INPUT_POST, 'wppus_license_private_api_auth_key', FILTER_SANITIZE_STRING ),
				'display_name'            => __( 'Private API Authentication Key', 'wppus' ),
				'failure_display_message' => __( 'Not a valid string', 'wppus' ),
			),
			'wppus_license_hmac_key'             => array(
				'value'                   => filter_input( INPUT_POST, 'wppus_license_hmac_key', FILTER_SANITIZE_STRING ),
				'display_name'            => __( 'Signatures HMAC Key', 'wppus' ),
				'failure_display_message' => __( 'Not a valid string', 'wppus' ),
			),
			'wppus_license_crypto_key'           => array(
				'value'                   => filter_input( INPUT_POST, 'wppus_license_crypto_key', FILTER_SANITIZE_STRING ),
				'display_name'            => __( 'Signatures Encryption Key', 'wppus' ),
				'failure_display_message' => __( 'Not a valid string', 'wppus' ),
			),
			'wppus_license_check_signature'      => array(
				'value'        => filter_input( INPUT_POST, 'wppus_license_check_signature', FILTER_VALIDATE_BOOLEAN ),
				'display_name' => __( 'Check License signature?', 'wppus' ),
				'condition'    => 'boolean',
			),
		) );
	}

	protected function change_license_statuses_bulk( $status, $license_data ) {
		$license_data           = is_array( $license_data ) ? $license_data : array( $license_data );
		$applicable_license_ids = array();
		$license_ids            = array();

		foreach ( $license_data as $data ) {
			$license_info = json_decode( wp_unslash( $data ) );
			$include      = false;

			if ( in_array( $status, WPPUS_License_Server::$license_statuses, true ) ) {

				if ( 'blocked' === $status || 'expired' === $status ) {
					$include = true;
				} elseif ( '0000-00-00' !== $license_info->date_expiry ) {
					$include = current_time( 'timestamp' ) < mysql2date( 'U', $license_info->date_expiry );
				} else {
					$include = true;
				}

				if ( ! is_numeric( $license_info->id ) ) {
					$include = false;
				}
			}

			if ( $status !== $license_info->status && $include ) {
				$applicable_license_ids[] = $license_info->id;
			}

			$license_ids[] = $license_info->id;
		}

		if ( ! in_array( $status, WPPUS_License_Server::$license_statuses, true ) ) {
			$this->errors[] = __( 'Operation failed: an unexpected error occured (invalid license status).', 'wppus' );

			return;
		}

		if ( ! empty( $applicable_license_ids ) ) {
			$this->license_server->update_licenses_status( $status, $applicable_license_ids );

			$this->message = __( 'Status of the selected licenses updated successfully where applicable - IDs of updated licenses: ', 'wppus' ) . implode( ', ', $applicable_license_ids );
		} else {
			$this->errors[] = __( 'Operation failed: all the selected licenses have passed their expiry date, or already have the selected status - IDs: ', 'wppus' ) . implode( ', ', $license_ids );
		}
	}

	protected function delete_license_bulk( $license_ids ) {
		$license_ids = is_array( $license_ids ) ? $license_ids : array( $license_ids );

		foreach ( $license_ids as $key => $data ) {

			if ( ! is_numeric( $data ) ) {
				$license = json_decode( wp_unslash( $data ), true );

				if ( isset( $license['id'] ) ) {
					$license_ids[ $key ] = $license['id'];
				} else {
					unset( $license_ids[ $key ] );
				}
			}
		}

		$this->license_server->purge_licenses( $license_ids );

		$this->message = __( 'Selected licenses deleted - IDs: ', 'wppus' ) . implode( ', ', $license_ids );

		return $license_ids;
	}

	protected function update_license( $license_data ) {
		$payload = json_decode( wp_unslash( $license_data ), true );
		$license = $this->license_server->edit_license( $payload );

		if ( is_object( $license ) ) {
			$this->message = __( 'License edited successfully.', 'wppus' );
		} else {
			$this->errors   = array_merge( $this->errors, $license );
			$this->errors[] = __( 'Failed to update the license record in the database.', 'wppus' );
			$this->errors[] = __( 'License update failed.', 'wppus' );
		}
	}

	protected function create_license( $license_data ) {
		$payload = json_decode( wp_unslash( $license_data ), true );
		$license = $this->license_server->add_license( $payload );

		if ( is_object( $license ) ) {
			$this->message = __( 'License added successfully.', 'wppus' );
		} else {
			$this->errors   = array_merge( $this->errors, $license );
			$this->errors[] = __( 'Failed to insert the license record in the database.', 'wppus' );
			$this->errors[] = __( 'License creation failed.', 'wppus' );
		}
	}

	protected function delete_all_licenses() {
		$this->license_server->purge_licenses();

		$this->message = __( 'All the licenses have been deleted.', 'wppus' );
	}

}
