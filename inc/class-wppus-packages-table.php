<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Packages_Table extends WP_List_Table {

	public $bulk_action_error;
	public $licensed_package_slugs;
	public $show_license_info;
	public $nonce_action;

	protected $rows;
	protected $update_manager;

	public function __construct( $update_manager ) {
		parent::__construct( array(
			'singular' => 'wppus-packages-table',
			'plural'   => 'wppus-packages-table',
			'ajax'     => false,
		) );

		$this->nonce_action   = 'bulk-wppus-packages-table';
		$this->update_manager = $update_manager;
	}

	public function set_rows( $rows ) {
		$this->rows = $rows;
	}

	public function get_columns() {
		$columns = array(
			'cb'                     => '<input type="checkbox" />',
			'col_name'               => __( 'Package Name', 'wppus' ),
			'col_version'            => __( 'Version', 'wppus' ),
			'col_type'               => __( 'Type', 'wppus' ),
			'col_file_name'          => __( 'File Name', 'wppus' ),
			'col_file_size'          => __( 'Size', 'wppus' ),
			'col_file_last_modified' => __( 'Last Modified ', 'wppus' ),
		);

		if ( $this->show_license_info ) {
			$columns['col_use_license'] = __( 'License status', 'wppus' );
		}

		return $columns;
	}

	public function column_default( $item, $column_name ) {

		return $item[ $column_name ];
	}

	public function get_sortable_columns() {
		$columns = array(
			'col_name'               => array( 'name', false ),
			'col_version'            => array( 'version', false ),
			'col_type'               => array( 'type', false ),
			'col_file_name'          => array( 'file_name', false ),
			'col_file_size'          => array( 'file_size', false ),
			'col_file_last_modified' => array( 'file_last_modified', false ),
		);

		if ( $this->show_license_info ) {
			$columns['col_use_license'] = array( 'use_license', false );
		}

		return $columns;
	}

	public function uasort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'name'; // @codingStandardsIgnoreLine
		$order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc'; // @codingStandardsIgnoreLine
		$result  = 0;

		if ( 'version' === $orderby ) {
			$result = version_compare( $a[ $orderby ], $b[ $orderby ] );
		} elseif ( 'file_size' === $orderby ) {
			$result = $a[ $orderby ] - $b[ $orderby ];
		} elseif ( 'file_last_modified' === $orderby ) {
			$result = $a[ $orderby ] - $b[ $orderby ];
		} else {
			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		}

		return ( 'asc' === $order ) ? $result : -$result;
	}

	public function prepare_items() {
		$total_items = count( $this->rows );
		$offset      = 0;
		$per_page    = $this->get_items_per_page( 'packages_per_page', 10 );
		$paged       = filter_input( INPUT_GET, 'paged', FILTER_VALIDATE_INT );

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
		$this->items           = array_slice( $this->rows, $offset, $per_page );

		uasort( $this->items, array( &$this, 'uasort_reorder' ) );
	}


	public function display_rows() {
		$records = $this->items;
		$table   = $this;

		list( $columns, $hidden ) = $this->get_column_info();

		if ( ! empty( $records ) ) {
			$show_license_info = $this->show_license_info;

			foreach ( $records as $record_key => $record ) {

				if ( $show_license_info ) {
					$use_license         = in_array( $record_key, $this->licensed_package_slugs, true );
					$use_license_text    = ( $use_license ) ? __( 'Requires License', 'wppus' ) : __( 'Does not Require License', 'wppus' );
					$license_action      = ( ! $use_license ) ? 'enable_license' : 'disable_license';
					$license_action_text = ( ! $use_license ) ? __( 'Require License', 'wppus' ) : __( 'Do not Require License', 'wppus' );
				}

				ob_start();

				require WPPUS_PLUGIN_PATH . 'inc/templates/admin/packages-table-row.php';

				echo ob_get_clean(); // @codingStandardsIgnoreLine
			}
		}
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
			print '<div class="alignleft actions bulkactions"><input id="post-query-submit" type="submit" name="wppus_delete_all_packages" value="' . esc_html( __( 'Delete All Packages', 'wppus' ) ) . '" class="button wppus-delete-all-packages"></div>';
		}
	}

	protected function get_bulk_actions() {
		$actions = array(
			'delete'   => __( 'Delete' ),
			'download' => __( 'Download', 'wppus' ),
		);

		if ( $this->show_license_info ) {
			$actions['enable_license']  = __( 'Require License', 'wppus' );
			$actions['disable_license'] = __( 'Do not Require License', 'wppus' );
		}

		return $actions;
	}

}
