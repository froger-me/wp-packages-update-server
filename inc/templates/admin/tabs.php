<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div id="wppus-header">
	<nav class="nav-tab-wrapper">
		<?php
		foreach ( $links as $tab_id => $tab_link ) {
			if ( ! empty( $states[ $tab_id ] ) ) {
				printf(
					'<a href="%s" class="nav-tab nav-tab-active">%s</a>',
					esc_url( $tab_link[0] ),
					wp_kses_post( $tab_link[1] )
				);
			} else {
				printf(
					'<a href="%s" class="nav-tab">%s</a>',
					esc_url( $tab_link[0] ),
					wp_kses_post( $tab_link[1] )
				);
			}
		}
		?>
	</nav>
	<?php
		do_action( 'wppus_tab_header', $state, $states );
	?>
</div>
