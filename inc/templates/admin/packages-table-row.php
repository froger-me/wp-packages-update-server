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

		if ( 'name' === $key ) {
			$actions = array();

			$query_string = '?page=%s&action=%s&packages=%s&linknonce=%s';
			$args         = array(
				$_REQUEST['page'], //@codingStandardsIgnoreLine
				'download',
				$record_key,
				wp_create_nonce( 'linknonce' ),
			);

			if ( isset( $_REQUEST['s'] ) ) { //@codingStandardsIgnoreLine
				$args[]        = $_REQUEST['s']; //@codingStandardsIgnoreLine
				$query_string .= '&s=%s';
			}

			$args[]              = __( 'Download', 'wppus' );
			$actions['download'] = vsprintf( '<a href="' . $query_string . '">%s</a>', $args );

			$args[1]                    = 'delete';
			$args[ count( $args ) - 1 ] = __( 'Delete' );
			$actions['delete']          = vsprintf( '<a href="' . $query_string . '">%s</a>', $args );

			if ( $show_license_info ) {
				$args[1]                    = $license_action;
				$args[ count( $args ) - 1 ] = $license_action_text;
				$actions['change_license']  = vsprintf( '<a href="' . $query_string . '">%s</a>', $args );
			}

			$actions = $table->row_actions( $actions );
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
					<?php echo $actions; ?><?php //@codingStandardsIgnoreLine ?>
				<?php elseif ( 'col_version' === $column_name ) : ?>
					<?php echo esc_html( $record[ $key ] ); ?>
				<?php elseif ( 'col_type' === $column_name ) : ?>
					<?php echo esc_html( $record[ $key ] ); ?>
				<?php elseif ( 'col_file_name' === $column_name ) : ?>
					<?php echo esc_html( $record[ $key ] ); ?>
				<?php elseif ( 'col_file_size' === $column_name ) : ?>
					<?php echo esc_html( size_format( $record[ $key ] ) ); ?>
				<?php elseif ( 'col_file_last_modified' === $column_name ) : ?>
					<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' - H:i:s', $record[ $key ] ) ); ?>
				<?php elseif ( 'col_use_license' === $column_name ) : ?>
					<?php echo esc_html( $use_license_text ); ?>
				<?php endif; ?>
			</td>
		<?php endif; ?>
	<?php endforeach; ?>
</tr>
