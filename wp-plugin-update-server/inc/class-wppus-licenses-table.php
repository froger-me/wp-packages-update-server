<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Licenses_Table extends WP_List_Table {

	public $bulk_action_error;
	public $nonce_action;

	protected $rows;

	public function __construct() {
		parent::__construct( array(
			'singular' => 'wppus-licenses-table',
			'plural'   => 'wppus-licenses-table',
			'ajax'     => false,
		) );

		$this->nonce_action = 'bulk-wppus-licenses-table';
	}

	public function set_rows( $rows ) {
		$this->rows = $rows;
	}

	public function get_columns() {

		return array(
			'cb'               => '<input type="checkbox" />',
			'col_license_key'  => __( 'License Key', 'wppus' ),
			'col_email'        => __( 'Registered Email', 'wppus' ),
			'col_status'       => __( 'Status', 'wppus' ),
			'col_package_type' => __( 'Package Type', 'wppus' ),
			'col_package_slug' => __( 'Package Slug', 'wppus' ),
			'col_date_created' => __( 'Creation Date', 'wppus' ),
			'col_date_expiry'  => __( 'Expiry Date', 'wppus' ),
			'col_id'           => __( 'ID', 'wppus' ),
		);
	}

	public function column_default( $item, $column_name ) {

		return $item[ $column_name ];
	}

	public function get_sortable_columns() {

		return array(
			'col_id'           => array( 'id', false ),
			'col_status'       => array( 'status', false ),
			'col_package_type' => array( 'package_type', false ),
			'col_package_slug' => array( 'package_slug', false ),
			'col_email'        => array( 'email', false ),
			'col_date_created' => array( 'date_created', false ),
			'col_date_expiry'  => array( 'date_expiry', false ),
		);
	}

	public function prepare_items() {
		global $wpdb;

		$search     = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : false; // @codingStandardsIgnoreLine
		$where      = false;
		$where_args = false;

		if ( $search ) {
			$where      = " WHERE
				id = %d OR
				license_key = %s OR
				allowed_domains LIKE '%%%s%%' OR
				status = %s OR
				owner_name LIKE '%%%s%%' OR
				email LIKE '%%%s%%' OR
				company_name LIKE '%%%s%%' OR
				txn_id = %s OR
				package_slug = %s OR
				package_type = %s";
			$where_args = array(
				absint( $search ),
				$search,
				$search,
				strtolower( $search ),
				$search,
				$search,
				$search,
				$search,
				str_replace( '_', '-', sanitize_title_with_dashes( $search ) ),
				strtolower( $search ),
			);
		}

		$sql = "SELECT COUNT(id) FROM {$wpdb->prefix}wppus_licenses";

		if ( $search ) {
			$sql        .= $where;
			$total_items = $wpdb->get_var( $wpdb->prepare( $sql, $where_args ) ); // @codingStandardsIgnoreLine
		} else {
			$total_items = $wpdb->get_var( $sql ); // @codingStandardsIgnoreLine
		}

		$offset   = 0;
		$per_page = $this->get_items_per_page( 'licenses_per_page', 10 );
		$paged    = filter_input( INPUT_GET, 'paged', FILTER_VALIDATE_INT );
		$order_by = 'date_created';
		$order    = 'desc';

		if ( isset( $_REQUEST['orderby'] ) ) { // @codingStandardsIgnoreLine

			if ( in_array( 'col_' . $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ), true ) ) { // @codingStandardsIgnoreLine
				$order_by = $_REQUEST['orderby']; // @codingStandardsIgnoreLine
			}
		}

		if ( isset( $_REQUEST['order'] ) ) { // @codingStandardsIgnoreLine

			if ( in_array( $_REQUEST['order'], array( 'asc', 'desc' ), true ) ) { // @codingStandardsIgnoreLine
				$order = $_REQUEST['order']; // @codingStandardsIgnoreLine
			}
		}

		if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
			$paged = 1;
		}

		$total_pages = ceil( $total_items / $per_page );

		if ( ! empty( $paged ) && ! empty( $per_page ) ) {
			$offset = ( $paged - 1 ) * $per_page;
		}

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page'    => $per_page,
		) );

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->process_bulk_action();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$args = array(
			$per_page,
			$offset,
		);
		$sql  = "SELECT * 
			FROM {$wpdb->prefix}wppus_licenses";

		if ( $search ) {
			$sql .= $where;

			array_push( $where_args, $per_page, $offset );

			$args = $where_args;
		}

		$sql  .= " ORDER BY $order_by $order LIMIT %d OFFSET %d";
		$query = $wpdb->prepare( $sql, $args ); // @codingStandardsIgnoreLine
		error_log($query);
		$items = $wpdb->get_results( $query, ARRAY_A ); // @codingStandardsIgnoreLine

		foreach ( $items as $index => $item ) {
			$items[ $index ]['allowed_domains'] = maybe_unserialize( $item['allowed_domains'] );
		}

		$this->items = $items;
	}


	public function display_rows() {
		$records = $this->items;
		$table   = $this;

		list( $columns, $hidden ) = $this->get_column_info();

		if ( ! empty( $records ) ) {

			foreach ( $records as $record_key => $record ) {

				$bulk_value = wp_json_encode( $record );

				ob_start();

				require WPPUS_PLUGIN_PATH . 'inc/templates/admin/licenses-table-row.php';

				echo ob_get_clean(); // @codingStandardsIgnoreLine
			}
		}
	}

	protected function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );
		$i            = 0;

		if ( ! $action_count ) {
			return '';
		}

		$out = '<div class="' . ( $always_visible ? 'row-actions visible open-panel' : 'row-actions open-panel' ) . '">';
		foreach ( $actions as $action => $link ) {
			++$i;
			( $i === $action_count ) ? $sep = '' : $sep = ' | ';

			$out .= "<span class='$action'>$link$sep</span>";
		}
		$out .= '</div>';

		$out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details' ) . '</span></button>';

		return $out;
	}

	protected function extra_tablenav( $which ) {

		if ( 'top' === $which ) {

			if ( 'max_file_size_exceeded' === $this->bulk_action_error ) {
				$class   = 'notice notice-error';
				$message = __( 'Download: Archive max size exceeded - try to adjust it in the settings below.', 'wppus' );

				printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );// @codingStandardsIgnoreLine
				$this->bulk_action_error = '';
			}
		} elseif ( 'bottom' === $which ) {
			print '<div class="alignleft actions bulkactions"><input id="post-query-submit" type="submit" name="wppus_delete_all_licenses" value="' . esc_html( __( 'Delete All Licenses', 'wppus' ) ) . '" class="button wppus-delete-all-licenses"><input id="add_license_trigger" type="button" value="' . esc_html( __( 'Add License', 'wppus' ) ) . '" class="button button-primary open-panel"></div>';
		}
	}

	protected function get_bulk_actions() {
		$actions = array(
			'pending'     => __( 'Set to Pending', 'wppus' ),
			'activated'   => __( 'Activate', 'wppus' ),
			'deactivated' => __( 'Deactivate', 'wppus' ),
			'blocked'     => __( 'Block', 'wppus' ),
			'expired'     => __( 'Expire', 'wppus' ),
			'delete'      => __( 'Delete', 'wppus' ),
		);

		return $actions;
	}

}
