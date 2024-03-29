<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_License_Manager {

	protected $licenses_table;
	protected $message = '';
	protected $errors  = array();
	protected $license_server;

	public function __construct( $init_hooks = false ) {

		if ( $init_hooks ) {
			$use_licenses = get_option( 'wppus_use_licenses' );

			if ( $use_licenses ) {
				$this->license_server = new WPPUS_License_Server();

				add_action( 'action_scheduler_init', array( $this, 'action_scheduler_init' ), 10, 0 );
				add_action( 'wppus_packages_table_cell', array( $this, 'wppus_packages_table_cell' ), 10, 4 );

				add_filter( 'wppus_packages_table_columns', array( $this, 'wppus_packages_table_columns' ), 10, 1 );
				add_filter( 'wppus_packages_table_sortable_columns', array( $this, 'wppus_packages_table_sortable_columns' ), 10, 1 );
				add_filter( 'wppus_packages_table_bulk_actions', array( $this, 'wppus_packages_table_bulk_actions' ), 10, 1 );
				add_filter( 'wppus_packages_table_row_actions', array( $this, 'wppus_packages_table_row_actions' ), 10, 4 );
			}

			add_filter( 'wppus_admin_scripts', array( $this, 'wppus_admin_scripts' ), 10, 1 );
			add_filter( 'wppus_admin_styles', array( $this, 'wppus_admin_styles' ), 10, 1 );
			add_action( 'admin_init', array( $this, 'admin_init' ), 10, 0 );
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 20, 0 );
			add_filter( 'wppus_admin_tab_links', array( $this, 'wppus_admin_tab_links' ), 20, 1 );
			add_filter( 'wppus_admin_tab_states', array( $this, 'wppus_admin_tab_states' ), 20, 2 );
			add_action( 'load-wp-packages-update-server_page_wppus-page-licenses', array( $this, 'add_page_options' ), 10, 0 );
			add_action( 'wppus_udpdate_manager_request_action', array( $this, 'wppus_udpdate_manager_request_action' ), 10, 2 );
			add_action( 'wppus_package_manager_deleted_packages_bulk', array( $this, 'wppus_package_manager_deleted_packages_bulk' ), 10, 1 );
			add_action( 'wppus_added_license_check', array( $this, 'wppus_license_check_action' ), 10, 1 );
			add_action( 'wppus_removed_license_check', array( $this, 'wppus_license_check_action' ), 10, 1 );

			add_filter( 'set-screen-option', array( $this, 'set_page_options' ), 10, 3 );
			add_filter( 'wppus_page_wppus_scripts_l10n', array( $this, 'wppus_page_wppus_scripts_l10n' ), 10, 1 );
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public function wppus_license_check_action( $package_slug ) {
		$event = ( false !== strpos( current_action(), 'added' ) ) ? 'license_require' : 'license_unrequire';

		switch ( $event ) {
			case 'license_require':
				// translators: %s is the package slug
				$format = esc_html__( 'The package `%1$s` was set to require a license on WPPUS' );
				break;
			case 'license_unrequire':
				// translators: %s is the package slug
				$format = esc_html__( 'The package `%1$s` was set to not require a license on WPPUS' );
				break;
			default:
				return;
		}

		$content     = wppus_get_package_info( $package_slug, false );
		$description = sprintf(
			$format,
			$package_slug
		);

		$payload = array(
			'event'       => $event,
			'description' => $description,
			'content'     => $content,
		);

		wppus_schedule_webhook( $payload, 'license' );
	}

	// WordPress hooks ---------------------------------------------

	public static function activate() {
		$result = self::maybe_create_or_upgrade_db();

		if ( ! $result ) {
			$error_message = __( 'Failed to create the necessary database table(s).', 'wppus' );

			die( $error_message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$manager   = new self();
		$frequency = apply_filters( 'wppus_schedule_license_frequency', 'hourly' );

		$manager->register_license_schedules( $frequency );
	}

	public static function deactivate() {
		as_unschedule_all_actions( 'wppus_expire_licenses' );
		do_action( 'wppus_cleared_license_schedule' );
	}

	public function action_scheduler_init() {
		$hook = 'wppus_expire_licenses';

		if ( ! as_has_scheduled_action( $hook ) ) {
			$frequency = apply_filters( 'wppus_schedule_license_frequency', 'daily' );
			$schedules = wp_get_schedules();
			$timestamp = strtotime( 'today noon' );
			$result    = as_schedule_recurring_action(
				$timestamp,
				$schedules[ $frequency ]['interval'],
				$hook
			);

			do_action( 'wppus_scheduled_license_event', $result, $timestamp, $frequency, $hook );
		}

		$this->register_license_schedules();
	}

	public function admin_init() {

		if ( is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() ) {
			$this->licenses_table = new WPPUS_Licenses_Table();

			if (
				(
					isset( $_REQUEST['_wpnonce'] ) &&
					wp_verify_nonce( $_REQUEST['_wpnonce'], $this->licenses_table->nonce_action )
				) ||
				(
					isset( $_REQUEST['linknonce'] ) &&
					wp_verify_nonce( $_REQUEST['linknonce'], 'linknonce' )
				) ||
				(
					isset( $_REQUEST['wppus_license_form_nonce'] ) &&
					wp_verify_nonce( $_REQUEST['wppus_license_form_nonce'], 'wppus_license_form_nonce' )
				)
			) {
				$page                = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : false;
				$license_data        = isset( $_REQUEST['license_data'] ) ? $_REQUEST['license_data'] : false;
				$delete_all_licenses = isset( $_REQUEST['wppus_delete_all_licenses'] ) ? true : false;
				$license_data        = isset( $_REQUEST['wppus_license_values'] ) ? $_REQUEST['wppus_license_values'] : $license_data;
				$action              = isset( $_REQUEST['wppus_license_action'] ) ? $_REQUEST['wppus_license_action'] : false;

				if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
					$action = $_REQUEST['action'];
				} elseif ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
					$action = $_REQUEST['action2'];
				}

				if ( 'wppus-page-licenses' === $page ) {

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

	public function wppus_udpdate_manager_request_action( $action, $packages ) {

		if ( $packages && 'enable_license' === $action ) {
			$this->change_packages_license_status_bulk( $packages, true );
		} elseif ( $packages && 'disable_license' === $action ) {
			$this->change_packages_license_status_bulk( $packages, false );
		}
	}

	public function wppus_package_manager_deleted_packages_bulk( $deleted_package_slugs ) {
		$this->change_packages_license_status_bulk( $deleted_package_slugs, false );
	}

	public function wppus_admin_styles( $styles ) {
		$styles['license'] = array(
			'path' => WPPUS_PLUGIN_PATH . 'css/admin/license' . wppus_assets_suffix() . '.css',
			'uri'  => WPPUS_PLUGIN_URL . 'css/admin/license' . wppus_assets_suffix() . '.css',
		);

		wp_enqueue_style( 'wp-codemirror' );

		return $styles;
	}

	public function wppus_admin_scripts( $scripts ) {
		$scripts['license'] = array(
			'path'   => WPPUS_PLUGIN_PATH . 'js/admin/license' . wppus_assets_suffix() . '.js',
			'uri'    => WPPUS_PLUGIN_URL . 'js/admin/license' . wppus_assets_suffix() . '.js',
			'deps'   => array( 'jquery', 'wppus-jq-validate-admin-script' ),
			'params' => array(
				'debug'       => (bool) ( constant( 'WP_DEBUG' ) ),
				'cm_settings' => wp_enqueue_code_editor( array( 'type' => 'text/json' ) ),
			),
		);

		wp_enqueue_script( 'wp-theme-plugin-editor' );

		$scripts['jq-validate'] = array(
			'path' => WPPUS_PLUGIN_PATH . 'js/admin/jquery.validate.min.js',
			'uri'  => WPPUS_PLUGIN_URL . 'js/admin/jquery.validate.min.js',
			'deps' => array( 'jquery' ),
		);

		return $scripts;
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

	public function wppus_page_wppus_scripts_l10n( $l10n ) {
		$l10n['deleteLicensesConfirm'] = array(
			__( 'You are about to delete all the licenses from this server.', 'wppus' ),
			__( 'All the records will be permanently deleted.', 'wppus' ),
			__( 'Packages requiring these licenses will not be able to get a successful response from this server.', 'wppus' ),
			"\n",
			__( 'Are you sure you want to do this?', 'wppus' ),
		);

		if ( 3 < count( $l10n['deletePackagesConfirm'] ) ) {
			array_splice( $l10n['deletePackagesConfirm'], -3, 0, __( 'License status will need to be re-applied manually for all packages.', 'wppus' ) );
		}

		return $l10n;
	}

	public function wppus_packages_table_columns( $columns ) {
		$columns['col_use_license'] = __( 'License status', 'wppus' );

		return $columns;
	}

	public function wppus_packages_table_sortable_columns( $columns ) {
		$columns['col_use_license'] = __( 'License status', 'wppus' );

		return $columns;
	}

	public function wppus_packages_table_bulk_actions( $actions ) {
		$actions['enable_license']  = __( 'Require License', 'wppus' );
		$actions['disable_license'] = __( 'Do not Require License', 'wppus' );

		return $actions;
	}

	public function wppus_packages_table_row_actions( $actions, $args, $query_string, $record_key ) {
		$use_license                = in_array( $record_key, get_option( 'wppus_licensed_package_slugs', array() ), true );
		$license_action             = ( ! $use_license ) ? 'enable_license' : 'disable_license';
		$license_action_text        = ( ! $use_license ) ? __( 'Require License', 'wppus' ) : __( 'Do not Require License', 'wppus' );
		$args[1]                    = $license_action;
		$args[ count( $args ) - 1 ] = $license_action_text;
		$actions['change_license']  = vsprintf( '<a href="' . $query_string . '">%s</a>', $args );

		return $actions;
	}

	public function wppus_packages_table_cell( $column_name, $record, $record_key ) {
		$use_license = in_array( $record_key, get_option( 'wppus_licensed_package_slugs', array() ), true );

		if ( 'col_use_license' === $column_name ) {
			echo esc_html( ( $use_license ) ? __( 'Requires License', 'wppus' ) : __( 'Does not Require License', 'wppus' ) );
		}
	}

	public function admin_menu() {
		$function    = array( $this, 'plugin_page' );
		$page_title  = __( 'WP Packages Update Server - Licenses', 'wppus' );
		$menu_title  = __( 'Licenses', 'wppus' );
		$menu_slug   = 'wppus-page-licenses';
		$parent_slug = 'wppus-page';
		$capability  = 'manage_options';

		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	public function wppus_admin_tab_links( $links ) {
		$links['licenses'] = array(
			admin_url( 'admin.php?page=wppus-page-licenses' ),
			"<span class='dashicons dashicons-admin-network'></span> " . __( 'Licenses', 'wppus' ),
		);

		return $links;
	}

	public function wppus_admin_tab_states( $states, $page ) {
		$states['licenses'] = 'wppus-page-licenses' === $page;

		return $states;
	}

	// Misc. -------------------------------------------------------

	public function expire_licenses() {
		$this->license_server->switch_expired_licenses_status();
	}

	public function register_license_schedules() {
		$scheduled_hook = array( $this, 'expire_licenses' );

		add_action( 'wppus_expire_licenses', $scheduled_hook, 10, 2 );
		do_action( 'wppus_registered_license_schedule', $scheduled_hook );
	}

	public function plugin_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$result         = $this->plugin_options_handler();
		$licenses_table = $this->licenses_table;

		if ( ! $result ) {

			if ( ! empty( $this->errors ) ) {
				$result = $this->errors;
			} else {
				$result = $this->message;
			}
		}

		$licenses_table->prepare_items();
		wppus_get_admin_template(
			'plugin-licenses-page.php',
			array(
				'licenses_table' => $licenses_table,
				'result'         => $result,
			)
		);
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected static function maybe_create_or_upgrade_db() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}

		$table_name = $wpdb->prefix . 'wppus_licenses';
		$sql        = 'CREATE TABLE ' . $table_name . " (
			id int(12) NOT NULL auto_increment,
			license_key varchar(255) NOT NULL,
			max_allowed_domains int(12) NOT NULL,
			allowed_domains longtext NOT NULL,
			status ENUM('pending', 'activated', 'deactivated', 'on-hold', 'blocked', 'expired') NOT NULL DEFAULT 'pending',
			owner_name varchar(255) NOT NULL default '',
			email varchar(64) NOT NULL,
			company_name varchar(100) NOT NULL default '',
			txn_id varchar(64) NOT NULL default '',
			date_created date NOT NULL DEFAULT '0000-00-00',
			date_renewed date NOT NULL DEFAULT '0000-00-00',
			date_expiry date NOT NULL DEFAULT '0000-00-00',
			package_slug varchar(255) NOT NULL default '',
			package_type varchar(8) NOT NULL default '',
			hmac_key varchar(64) NOT NULL,
			crypto_key varchar(64) NOT NULL,
			data longtext NOT NULL,
			PRIMARY KEY  (id),
			KEY license_key (license_key)
			)" . $charset_collate . ';';

		dbDelta( $sql );

		$table_name = $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "wppus_licenses'" );

		if ( $wpdb->prefix . 'wppus_licenses' !== $table_name ) {
			return false;
		}

		return true;
	}

	protected function change_packages_license_status_bulk( $package_slugs, $add ) {
		$package_slugs          = is_array( $package_slugs ) ? $package_slugs : array( $package_slugs );
		$licensed_package_slugs = get_option( 'wppus_licensed_package_slugs', array() );
		$changed                = false;

		foreach ( $package_slugs as $package_slug ) {

			if ( $add && ! in_array( $package_slug, $licensed_package_slugs, true ) ) {
				$licensed_package_slugs[] = $package_slug;
				$changed                  = true;

				do_action( 'wppus_added_license_check', $package_slug );
			} elseif ( ! $add && in_array( $package_slug, $licensed_package_slugs, true ) ) {
				$key     = array_search( $package_slug, $licensed_package_slugs, true );
				$changed = true;

				unset( $licensed_package_slugs[ $key ] );

				do_action( 'wppus_removed_license_check', $package_slug );
			}
		}

		if ( $changed ) {
			$licensed_package_slugs = array_values( $licensed_package_slugs );

			update_option( 'wppus_licensed_package_slugs', $licensed_package_slugs, true );
		}
	}

	protected function plugin_options_handler() {
		$errors = array();
		$result = false;

		if (
			isset( $_REQUEST['wppus_plugin_options_handler_nonce'] ) &&
			wp_verify_nonce( $_REQUEST['wppus_plugin_options_handler_nonce'], 'wppus_plugin_options' )
		) {
			$result  = __( 'WP Packages Update Server license options successfully updated.', 'wppus' );
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
		} elseif (
			isset( $_REQUEST['wppus_plugin_options_handler_nonce'] ) &&
			! wp_verify_nonce( $_REQUEST['wppus_plugin_options_handler_nonce'], 'wppus_plugin_options' )
		) {
			$errors['general'] = __( 'There was an error validating the form. It may be outdated. Please reload the page.', 'wppus' );
		}

		if ( ! empty( $errors ) ) {
			$result       = false;
			$this->errors = $errors;
		}

		return $result;
	}

	protected function get_submitted_options() {

		return apply_filters(
			'wppus_submitted_licenses_config',
			array(
				'wppus_use_licenses' => array(
					'value'        => filter_input( INPUT_POST, 'wppus_use_licenses', FILTER_VALIDATE_BOOLEAN ),
					'display_name' => __( 'Enable Package Licenses', 'wppus' ),
					'condition'    => 'boolean',
				),
			)
		);
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
					$timezone    = new DateTimeZone( wp_timezone_string() );
					$date_expiry = new DateTime( $license_info->date_expiry, $timezone );
					$include     = time() < $date_expiry->getTimestamp();
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
		$payload         = json_decode( wp_unslash( $license_data ), true );
		$payload['data'] = json_decode( wp_unslash( $payload['data'] ), true );
		$license         = wppus_edit_license( $payload );

		if ( is_object( $license ) ) {
			$this->message = __( 'License edited successfully.', 'wppus' );
		} else {
			$this->errors   = array_merge( $this->errors, $license );
			$this->errors[] = __( 'Failed to update the license record in the database.', 'wppus' );
			$this->errors[] = __( 'License update failed.', 'wppus' );
		}
	}

	protected function create_license( $license_data ) {
		$payload         = json_decode( wp_unslash( $license_data ), true );
		$payload['data'] = json_decode( wp_unslash( $payload['data'] ), true );
		$license         = wppus_add_license( $payload );

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
