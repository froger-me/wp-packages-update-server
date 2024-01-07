<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<tr>
	<?php
	$actions = array();

	$query_string = '?page=%s&action=%s&packages=%s&linknonce=%s';
	$args         = array(
		$_REQUEST['page'], // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'download',
		$record_key,
		wp_create_nonce( 'linknonce' ),
	);

	if ( isset( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$args[]        = $_REQUEST['s']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$query_string .= '&s=%s';
	}

	$args[]              = __( 'Download', 'wppus' );
	$actions['download'] = vsprintf( '<a href="' . $query_string . '">%s</a>', $args );

	$args[1]                    = 'delete';
	$args[ count( $args ) - 1 ] = __( 'Delete' );
	$actions['delete']          = vsprintf( '<a href="' . $query_string . '">%s</a>', $args );

	$actions = apply_filters( 'wppus_packages_table_row_actions', $actions, $args, $query_string, $record_key );
	$actions = $table->row_actions( $actions );
	?>
	<?php foreach ( $columns as $column_name => $column_display_name ) : ?>
		<?php
		$key   = str_replace( 'col_', '', $column_name );
		$class = $column_name . ' column-' . $column_name;
		$style = '';

		if ( in_array( $column_name, $hidden, true ) ) {
			$style = 'display:none;';
		}

		$attributes = $class . $style;
		?>
		<?php if ( 'cb' === $column_name ) : ?>
			<th scope="row" class="check-column">
				<input type="checkbox" name="packages[]" id="cb-select-<?php echo esc_attr( $record_key ); ?>" value="<?php echo esc_attr( $record_key ); ?>" />
			</th>
		<?php else : ?>
			<td class="<?php echo esc_attr( $class ); ?>" style="<?php echo esc_attr( $style ); ?>">
				<?php if ( 'col_name' === $column_name ) : ?>
					<?php echo esc_html( $record[ $key ] ); ?>
					<?php echo $actions; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php elseif ( 'col_version' === $column_name ) : ?>
					<?php echo esc_html( $record[ $key ] ); ?>
				<?php elseif ( 'col_type' === $column_name ) : ?>
					<?php if ( 'theme' === $record[ $key ] ) : ?>
						<?php esc_html_e( 'Theme', 'wppus' ); ?>
					<?php elseif ( 'plugin' === $record[ $key ] ) : ?>
						<?php esc_html_e( 'Plugin', 'wppus' ); ?>
					<?php elseif ( 'generic' === $record[ $key ] ) : ?>
						<?php esc_html_e( 'Generic', 'wppus' ); ?>
					<?php endif; ?>
				<?php elseif ( 'col_file_name' === $column_name ) : ?>
					<?php echo esc_html( $record[ $key ] ); ?>
				<?php elseif ( 'col_file_size' === $column_name ) : ?>
					<?php echo esc_html( size_format( $record[ $key ] ) ); ?>
				<?php elseif ( 'col_file_last_modified' === $column_name ) : ?>
					<?php
					echo esc_html(
						wp_date(
							get_option( 'date_format' ) . ' - H:i:s',
							$record[ $key ],
							new DateTimeZone( wp_timezone_string() )
						)
					);
					?>
				<?php else : ?>
					<?php do_action( 'wppus_packages_table_cell', $column_name, $record, $record_key ); ?>
				<?php endif; ?>
			</td>
		<?php endif; ?>
	<?php endforeach; ?>
</tr>
