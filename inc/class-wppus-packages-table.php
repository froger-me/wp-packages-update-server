<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Packages_Table extends WP_List_Table {

	public $bulk_action_error;
	public $nonce_action;

	protected $rows;
	protected $package_manager;

	public function __construct( $package_manager ) {
		parent::__construct(
			array(
				'singular' => 'wppus-packages-table',
				'plural'   => 'wppus-packages-table',
				'ajax'     => false,
			)
		);

		$this->nonce_action    = 'bulk-wppus-packages-table';
		$this->package_manager = $package_manager;
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	// Overrides ---------------------------------------------------

	public function get_columns() {
		$columns = apply_filters(
			'wppus_packages_table_columns',
			array(
				'cb'                     => '<input type="checkbox" />',
				'col_name'               => __( 'Package Name', 'wppus' ),
				'col_version'            => __( 'Version', 'wppus' ),
				'col_type'               => __( 'Type', 'wppus' ),
				'col_file_name'          => __( 'File Name', 'wppus' ),
				'col_file_size'          => __( 'Size', 'wppus' ),
				'col_file_last_modified' => __( 'Last Modified ', 'wppus' ),
			)
		);

		return $columns;
	}

	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	public function get_sortable_columns() {
		$columns = apply_filters(
			'wppus_packages_table_sortable_columns',
			array(
				'col_name'               => array( 'name', false ),
				'col_version'            => array( 'version', false ),
				'col_type'               => array( 'type', false ),
				'col_file_name'          => array( 'file_name', false ),
				'col_file_size'          => array( 'file_size', false ),
				'col_file_last_modified' => array( 'file_last_modified', false ),
			)
		);

		return $columns;
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

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'total_pages' => $total_pages,
				'per_page'    => $per_page,
			)
		);

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

			foreach ( $records as $record_key => $record ) {
				wppus_get_admin_template(
					'packages-table-row.php',
					array(
						'table'      => $table,
						'columns'    => $columns,
						'hidden'     => $hidden,
						'records'    => $records,
						'record_key' => $record_key,
						'record'     => $record,
					)
				);
			}
		}
	}

	// Misc. -------------------------------------------------------

	public function set_rows( $rows ) {
		$this->rows = $rows;
	}

	public function uasort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'name'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	// Overrides ---------------------------------------------------

	protected function extra_tablenav( $which ) {

		if ( 'top' === $which ) {

			if ( 'max_file_size_exceeded' === $this->bulk_action_error ) {
				$class   = 'notice notice-error';
				$message = __( 'Download: Archive max size exceeded - try to adjust it in the settings below.', 'wppus' );

				printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$this->bulk_action_error = '';
			}
		} elseif ( 'bottom' === $which ) {
			print '<div class="alignleft actions bulkactions"><input id="post-query-submit" type="submit" name="wppus_delete_all_packages" value="' . esc_html( __( 'Delete All Packages', 'wppus' ) ) . '" class="button wppus-delete-all-packages"></div>';
		}
	}

	protected function get_table_classes() {
		$mode = get_user_setting( 'posts_list_mode', 'list' );

		$mode_class = esc_attr( 'table-view-' . $mode );

		return array( 'widefat', 'striped', $mode_class, $this->_args['plural'] );
	}

	protected function get_bulk_actions() {
		$actions = apply_filters(
			'wppus_packages_table_bulk_actions',
			array(
				'delete'   => __( 'Delete' ),
				'download' => __( 'Download', 'wppus' ),
			)
		);

		return $actions;
	}
}
