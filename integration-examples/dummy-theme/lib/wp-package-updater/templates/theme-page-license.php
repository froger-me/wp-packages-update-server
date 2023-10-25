<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<div class="wrap">
	<div class="postbox hidden" style="min-width: 500px; position: absolute; top: 150px; left: 50%; transform: translate(-50%, 0);">
		<div class="inside" style="margin: 50px;">
			<h2><?php echo esc_html( $title ); ?></h2>
			<?php echo $form; ?><?php // @codingStandardsIgnoreLine ?>
		</div>
	</div>
</div>
