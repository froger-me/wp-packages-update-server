<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<div class="wrap wppus-wrap">
	<?php WP_Packages_Update_Server::get_instance()->display_settings_header( '' ); ?>
	<h2><?php esc_html_e( 'Provide updates with WP Packages Update Server - packages requirements', 'wppus' ); ?></h2>
	<p>
		<?php
		printf(
			// translators: %1$s is <code>functions.php</code>, %2$s is <code>lib</code>
			esc_html__( 'To link your packages to WP Packages Update Server, and optionally to prevent webmasters from getting updates of your plugins and themes without a license, your plugins and themes need to include some extra code. It is a simple matter of adding a few lines in the main plugin file (for plugins) or in the %1$s file (for themes), and provide the necessary libraries in a %2$s directory at the root of the package.', 'wppus' ),
			'<code>functions.php</code>',
			'<code>lib</code>'
		);
		?>
	</p>
	<p>
		<?php
		printf(
			// translators: %1$s is <code>integration/dummy-plugin</code>, %2$s is <code>integration/dummy-theme</code>
			esc_html__( 'See %1$s for an example of plugin, and %2$ss for an example of theme. They are fully functionnal and can be used to test all the features of the server with a test client installation of WordPress.', 'wppus' ),
			'<code>' . esc_html( WPPUS_PLUGIN_PATH ) . 'integration/dummy-plugin</code>',
			'<code>' . esc_html( WPPUS_PLUGIN_PATH ) . 'integration/dummy-plugin</code>',
		);
		?>
	</p>
	<p>
		<?php
		printf(
			// translators: %1$s is <code>packages_dir</code>, %2$s is <code>package-slug.zip</code>, %3$s is <code>package-slug.php</code>
			esc_html__( 'Unless "Use Remote Repository Service" is checked in "Remote Sources", you need to manually upload the packages zip archives (and subsequent updates) in %1$s. Packages need to be valid WordPress plugin or theme packages, and in the case of a plugin the main plugin file must have the same name as the zip archive. For example, the main plugin file in %2$s would be %3$s.', 'wppus' ),
			'<code>' . esc_html( $packages_dir ) . '</code>',
			'<code>package-slug.zip</code>',
			'<code>package-slug.php</code>',
		);
		?>
	</p>
	<hr>
	<h2><?php esc_html_e( 'Requests optimisation', 'wppus' ); ?></h2>
	<p>
		<?php
		printf(
			// translators: %s is <code>parse_request</code>
			esc_html__( "When the remote clients where your plugins and themes are installed send a request to check for updates, download a package or check or change license status, the current server's WordPress installation is loaded, with its own plugins and themes. This is not optimised if left untouched because unnecessary action and filter hooks that execute before %s action hook are also triggered, even though the request is not designed to produce any on-screen output or further computation.", 'wppus' ),
			'<code>parse_request</code>',
		);
		?>
	</p>
	<p>
		<?php
		printf(
			// translators: %1$s is <code>optimisation/wppus-endpoint-optimiser.php</code>, %2$s is the MU Plugin's path
			esc_html__( 'To solve this, the file %1$s has been automatically copied to %2$s. This effectively creates a Must Use Plugin running before everything else and preventing themes and other plugins from being executed when an update request or a license API request is received by WP Packages Update Server.', 'wppus' ),
			'<code>' . esc_html( WPPUS_PLUGIN_PATH . 'optimisation/wppus-endpoint-optimiser.php' ) . '</code>',
			'<code>' . esc_html( dirname( dirname( WPPUS_PLUGIN_PATH ) ) . '/mu-plugins/wppus-endpoint-optimiser.php' ) . '</code>',
		);
		?>
	</p>
	<p>
		<?php
		printf(
			// translators: %1$s is <code>$wppus_always_active_plugins</code>, %2$s is <code>$wppus_bypass_themes</code>, %3$s is <code>false</code>, %4$s is <code>functions.php</code>, %5$s is <code>wppus-endpoint-optimiser.php.backup</code>
			esc_html__( 'See %1$s for an example of plugin, and %2$ss for an example of theme. They are fully functionnal and can be used to test all the features of the server with a test client installation of WordPress.', 'wppus' ),
			'<code>$wppus_always_active_plugins</code>',
			'<code>$wppus_bypass_themes</code>',
			'<code>false</code>',
			'<code>functions.php</code>',
			'<code>' . esc_html( dirname( dirname( WPPUS_PLUGIN_PATH ) ) . '/mu-plugins/wppus-endpoint-optimiser.php.backup' ) . '</code>',
		);
		?>
	</p>
	<p>
		<?php
		printf(
			// translators: %1$s is <code>$wppus_doing_update_api_request</code>, %2$s is <code>$wppus_doing_license_api_request</code>, %3$s is <code>$wppus_always_active_plugins</code>, %4$s is <code>functions.php</code>, %5$s is <code>$wppus_bypass_themes</code>, %5$s is <code>false</code>
			esc_html__( 'The MU Plugin also provides the global variable %1$s and %2$s that can be tested when adding hooks and filters would you choose to keep some plugins active with %3$s or keep %4$s from themes included with %5$s set to %6$s.', 'wppus' ),
			'<code>$wppus_doing_update_api_request</code>',
			'<code>$wppus_doing_license_api_request</code>',
			'<code>$wppus_always_active_plugins</code>',
			'<code>functions.php</code>',
			'<code>$wppus_bypass_themes</code>',
			'<code>false</code>',
		);
		?>
	</p>
	<hr>
	<h2><?php esc_html_e( 'More help...', 'wppus' ); ?></h2>
	<p>
		<?php
		printf(
			// translators: %s is a link to the documentation
			esc_html__( 'The full documentation can be found %s, with more details for developers on how to integrate WP Plugin Server with their own plugins and themes.', 'wppus' ),
			'<a target="_blank" href="https://github.com/froger-me/wp-packages-update-server/blob/master/README.md">' . esc_html__( 'here', 'wppus' ) . '</a>',
		);
		?>
	</p>
	<p>
		<?php
		printf(
			// translators: %1$s is a link to opening an issue, %2$s is a contact email
			esc_html__( 'After reading the documentation, for more help on how to use WP Packages Update Server, 	please %1$s - bugfixes are welcome via pull requests, detailed bug reports with accurate pointers as to where and how they occur in the code will be addressed in a timely manner, and a fee will apply for any other request if they are addressed. If and only if you found a security issue, please contact %2$s with full details for responsible disclosure.', 'wppus' ),
			'<a target="_blank" href="https://github.com/froger-me/wp-packages-update-server/issues">' . esc_html__( 'open an issue on Github', 'wppus' ) . '</a>',
			'<a href="mailto:wppus-help@froger.me">wppus-help@anyape.com</a>',
		);
		?>
	</p>
</div>
