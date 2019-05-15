<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<tr>
	<?php foreach ( $columns as $column_name => $column_display_name ) : ?>
		<?php
		$key     = str_replace( 'col_', '', $column_name );
		$class   = $column_name . ' column-' . $column_name;
		$style   = '';
		$actions = '';

		if ( in_array( $column_name, $hidden ) ) { //@codingStandardsIgnoreLine
			$style = 'display:none;';
		}

		if ( 'license_key' === $key ) {
			$actions = array(
				'edit'   => sprintf( '<a href="#">%s</a>', __( 'Edit', 'wppus' ) ),
				'delete' => sprintf( '<a href="?page=%s&action=%s&license_data=%s&linknonce=%s">%s</a>',
					$_REQUEST['page'], //@codingStandardsIgnoreLine
					'delete',
					$record['id'],
					wp_create_nonce( 'linknonce' ),
					__( 'Delete' )
				),
			);
			$actions = $table->row_actions( $actions );
		}
		$attributes = $class . $style;
		?>
		<?php if ( 'cb' === $column_name ) : ?>
			<th scope="row" class="check-column">
				<input type="checkbox" name="license_data[]" id="cb-select-<?php echo esc_attr( $record_key ); ?>" value="<?php echo esc_attr( $bulk_value ); ?>" />
			</th>
		<?php else : ?>
			<td class="<?php echo esc_attr( $class ); ?>" style="<?php echo esc_attr( $style ); ?>">
				<?php if ( 'col_id' === $column_name ) : ?>
					<?php echo esc_html( $record[ $key ] ); ?>
				<?php elseif ( 'col_license_key' === $column_name ) : ?>
					<?php echo esc_html( $record[ $key ] ); ?>
					<?php echo $actions; ?><?php //@codingStandardsIgnoreLine ?>
				<?php elseif ( 'col_status' === $column_name ) : ?>
					<?php echo esc_html( ucfirst( $record[ $key ] ) ); ?>
				<?php elseif ( 'col_package_type' === $column_name ) : ?>
					<?php echo esc_html( ucfirst( $record[ $key ] ) ); ?>
				<?php elseif ( 'col_package_slug' === $column_name ) : ?>
					<?php echo esc_html( $record[ $key ] ); ?>
				<?php elseif ( 'col_email' === $column_name ) : ?>
					<?php echo esc_html( $record[ $key ] ); ?>
				<?php elseif ( 'col_date_created' === $column_name ) : ?>
					<?php echo esc_html( date_i18n( get_option( 'date_format' ), mysql2date( 'U', $record[ $key ] ) ) ); ?>
				<?php elseif ( 'col_date_expiry' === $column_name ) : ?>
					<?php if ( '0000-00-00' === $record[ $key ] ) : ?>
						<?php esc_html_e( 'N/A', 'wppus' ); ?>
					<?php else : ?>
						<?php echo esc_html( date_i18n( get_option( 'date_format' ), mysql2date( 'U', $record[ $key ] ) ) ); ?>
					<?php endif; ?>
				<?php endif; ?>
			</td>
		<?php endif; ?>
	<?php endforeach; ?>
</tr>
