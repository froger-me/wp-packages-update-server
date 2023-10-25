<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<div class="wrap wppus-wrap">
	<h1><?php esc_html_e( 'WP Plugin Update Server', 'wppus' ); ?></h1>
	<h2 class="nav-tab-wrapper">
		<a href="admin.php?page=wppus-page" class="nav-tab">
			<span class='dashicons dashicons-welcome-view-site'></span> <?php esc_html_e( 'Overview', 'wppus' ); ?>
		</a>
		<a href="admin.php?page=wppus-page-remote-sources" class="nav-tab">
			<span class='dashicons dashicons-networking'></span> <?php esc_html_e( 'Remote Sources', 'wppus' ); ?>
		</a>
		<a href="admin.php?page=wppus-page-licenses" class="nav-tab">
			<span class='dashicons dashicons-admin-network'></span> <?php esc_html_e( 'Licenses', 'wppus' ); ?>
		</a>
		<a href="admin.php?page=wppus-page-help" class="nav-tab nav-tab-active">
			<span class='dashicons dashicons-editor-help'></span> <?php esc_html_e( 'Help', 'wppus' ); ?>
		</a>
	</h2>
	<h2><?php esc_html_e( 'Provide updates with WP Plugin Update Server - packages requirements', 'wppus' ); ?></h2>
	<p>
		<?php _e( 'To link your packages to WP Plugin Update Server, and maybe to prevent webmasters from getting updates of your plugins and themes unless they have a license, your plugins and themes need to include some extra code. It is a simple matter of adding a few lines in the main plugin file (for plugins) or in the <code>functions.php</code> file (for themes), and provide the necessary libraries in a <code>lib</code> directory at the root of the package - see <a target="_blank" href="https://github.com/froger-me/wp-package-updater">WP Package Updater</a> for more information.', 'wppus' ); ?><?php // @codingStandardsIgnoreLine ?>
	</p>
	<p>
		<?php echo sprintf( __( 'See <code>%s</code> for an example of plugin, and <code>%s</code> for an example of theme. They are fully functionnal and can be used to test all the features of the server with a test client installation of WordPress.', 'wppus' ), WPPUS_PLUGIN_PATH . 'integration-examples/dummy-plugin', WPPUS_PLUGIN_PATH . 'integration-examples/dummy-theme' ); ?><?php // @codingStandardsIgnoreLine ?>
	</p>
	<p>
		<?php echo sprintf( __( 'Unless "Use remote repository service" is checked in "Remote Sources", you need to manually upload the packages zip archives (and subsequent updates) in <code>%s</code>. Packages need to be valid WordPress plugin or theme packages, and in the case of a plugin the main plugin file must have the same name as the zip archive. For example, the main plugin file in <code>package-name.zip</code> would be <code>package-name.php</code>.', 'wppus' ), WPPUS_Data_Manager::get_data_dir( 'packages' ) ); ?><?php // @codingStandardsIgnoreLine ?>
	</p>
	<hr>
	<h2><?php esc_html_e( 'Requests optimisation', 'wppus' ); ?></h2>
	<p>
		<?php _e( 'When the remote clients where your plugins and themes are installed send a request to check for updates, download a package or check or change license status, the current server\'s WordPress installation is loaded, with its own plugins and themes. This is not optimised if left untouched because unnecessary action and filter hooks that execute before <code>parse_request</code> action hook are also triggered, even though the request is not designed to produce any on-screen output or further computation.', 'wppus' ); ?><?php // @codingStandardsIgnoreLine ?>
	</p>
	<p>
		<?php echo sprintf( __( 'To solve this, the file <code>%s</code> has been automatically copied to <code>%s</code>. This effectively creates a Must Use Plugin running before everything else and preventing themes and other plugins from being executed when an update request or a license API request is received by WP Plugin Update Server.' ), WPPUS_PLUGIN_PATH . 'optimisation/wppus-endpoint-optimiser.php', dirname( dirname( WPPUS_PLUGIN_PATH ) ) . '/mu-plugins/wppus-endpoint-optimiser.php' ); ?><?php // @codingStandardsIgnoreLine ?>
	</p>
	<p>
		<?php echo sprintf( __( 'You may edit the variable <code>$wppus_always_active_plugins</code> of the MU Plugin file to allow some plugins to run anyway, or set the <code>$wppus_bypass_themes</code> to <code>false</code> to allow <code>functions.php</code> files to be included, for example to hook into WP Plugin Server actions and filters. If the MU Plugin is in use and a new version is available, it will be backed-up to <code>%s</code> when updating WP Plugin Update Server and it will automatically be replaced with its new version. If necessary, make sure to report any previous customization from the backup to the new file.' ), dirname( dirname( WPPUS_PLUGIN_PATH ) ) . '/mu-plugins/wppus-endpoint-optimiser.php.backup' ); ?><?php // @codingStandardsIgnoreLine ?>
	</p>
	<p>
		<?php _e( 'The MU Plugin also provides the global variable <code>$wppus_doing_update_api_request</code> and <code>$wppus_doing_license_api_request</code> that can be tested when adding hooks and filters would you choose to keep some plugins active with <code>$wppus_always_active_plugins</code> or keep <code>functions.php</code> from themes included with <code>$wppus_bypass_themes</code> set to <code>false</code>.' ); ?><?php // @codingStandardsIgnoreLine ?>
	</p>
	<hr>
	<h2><?php esc_html_e( 'More help...', 'wppus' ); ?></h2>
	<p>
		<?php _e( 'The full documentation can be found <a target="_blank" href="https://github.com/froger-me/wp-plugin-update-server/blob/master/README.md">here</a>, with more details for developers on how to integrate WP Plugin Server with their own plugins and themes.', 'wppus' ); ?><?php // @codingStandardsIgnoreLine ?>
	</p>
	<p>
		<?php _e( 'After reading the documentation, for more help on how to use WP Plugin Update Server, please <a target="_blank" href="https://github.com/froger-me/wp-plugin-update-server/issues">open an issue on Github</a> or contact <a href="mailto:wppus-help@froger.me">wppus-help@froger.me</a>.', 'wppus' ); ?><?php // @codingStandardsIgnoreLine ?>
	</p>
	<p>
		<?php _e( 'Depending on the nature of the request, a fee may apply.'); ?><?php // @codingStandardsIgnoreLine ?>
	</p>
</div>
