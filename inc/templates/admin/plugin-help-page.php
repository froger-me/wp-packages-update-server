<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>
<div class="wrap wppus-wrap">
	<?php WP_Packages_Update_Server::get_instance()->display_settings_header( '' ); ?>
	<div class="help-content">
		<h2><?php esc_html_e( 'Provide updates with WP Packages Update Server - packages requirements', 'wppus' ); ?></h2>
		<p>
			<?php esc_html_e( 'To link your packages to WP Packages Update Server, and optionally to prevent webmasters from getting updates of your ppackages without a license, your packages need to include some extra code.', 'wppus' ); ?><br><br>
			<?php esc_html_e( 'For plugins, and themes, it is fairly straightforward:', 'wppus' ); ?>
		</p>
		<ul>
			<li>
			<?php
			printf(
				// translators: %1$s is <code>lib</code>, %2$s is <code>plugin-update-checker</code>, %3$s is <code>wp-update-checker</code>, %4$s is <code>dummy-[plugin|theme]</code>, %5$s is <code>wp-update-checker</code>, %6$s is <code>plugin-update-checker</code>
				esc_html__( 'Add a %1$s directory with the %2$s and %3$s libraries to the root of the package (provided in %4$s ; %5$s can be customized as you see fit, but %6$s should be left untouched).', 'wppus' ),
				'<code>lib</code>',
				'<code>plugin-update-checker</code>',
				'<code>wp-update-checker</code>',
				'<code>dummy-[plugin|theme]</code>',
				'<code>wp-update-checker</code>',
				'<code>plugin-update-checker</code>',
			);
			?>
			</li>
			<li>
				<?php
				printf(
					// translators: %1s is <code>functions.php</code>
					esc_html__( 'Add the following code to the main plugin file (for plugins) or in the %s file (for themes) :', 'wppus' ),
					'<code>functions.php</code>'
				);
				?>
<pre>/** Enable updates - note the  `$prefix_updater` variable: change `prefix` to a unique string for your package **/
require_once __DIR__ . '/lib/wp-package-updater/class-wp-package-updater.php';

$prefix_updater = new WP_Package_Updater(
	wp_normalize_path( __FILE__ ),
	0 === strpos( __DIR__, WP_PLUGIN_DIR ) ? wp_normalize_path( __DIR__ ) : get_stylesheet_directory()
);</pre>
			</li>
			<li>
				<?php
				printf(
					// translators: %1$s is <code>wppus.json</code>, %2$s is <code>"server"</code>
					esc_html__( 'Add a %1$s file at the root of the package with the following content - change the value of %2$s to your own (required), and select a value for %3$s (optional):', 'wppus' ),
					'<code>wppus.json</code>',
					'<code>"server"</code>',
					'<code>"requireLicense"</code>'
				);
				?>
				<pre>{
	"server": "https://server.domain.tld/",
	"requireLicense": true|false
}</pre>
				</li>
				<li>
				<?php esc_html_e( 'Connect WP Packages Update Server with your repository and prime your package, or manually upload your package to WP Packages Update Server.', 'wppus' ); ?>
			</li>
		</ul>
		<p>
			<?php
			esc_html_e( 'For generic packages, the steps involved entirely depend on the language used to write the package and the update process of the target platform.', 'wppus' );
			?>
			<br>
			<?php
			printf(
				// translators: %s is a link to the documentation
				esc_html__( 'You may refer to the documentation found %s.', 'wppus' ),
				'<a target="_blank" href="' . esc_url( 'https://github.com/froger-me/wp-packages-update-server/blob/main/integration/docs/generic.md' ) . '">' . esc_html__( 'here', 'wppus' ) . '</a>'
			);
			?>
		</p>
		<hr>
		<p>
			<?php
			printf(
				// translators: %1$s is <code>integration/dummy-plugin</code>, %2$s is <code>integration/dummy-theme</code>
				esc_html__( 'See %1$s for an example of plugin, and %2$ss for an example of theme. They are fully functionnal and can be used to test all the features of the server with a test client installation of WordPress.', 'wppus' ),
				'<code>' . esc_html( WPPUS_PLUGIN_PATH ) . 'integration/dummy-theme</code>',
				'<code>' . esc_html( WPPUS_PLUGIN_PATH ) . 'integration/dummy-plugin</code>',
			);
			?>
		</p>
		<p>
			<?php
			printf(
				// translators: %1$s is <code>integration/dummy-generic</code>, %2$s is `wppus-api.[sh|php|js|py]`
				esc_html__( 'See %1$s for examples of a generic package written in Bash, NodeJS, PHP with Curl, and Python. The API calls made by generic packages to the license API and Update API are the same as the WordPress packages. Unlike the upgrade library provided with plugins & themes, the code found in %2$s files is NOT ready for production environment and MUST be adapted.', 'wppus' ),
				'<code>' . esc_html( WPPUS_PLUGIN_PATH ) . 'integration/dummy-generic</code>',
				'<code>wppus-api.[sh|php|js|py]</code>'
			);
			?>
		</p>
		<p>
			<?php
			printf(
				// translators: %1$s is <code>packages_dir</code>, %2$s is <code>package-slug.zip</code>, %3$s is <code>package-slug.php</code>
				esc_html__( 'Unless "Use Remote Repository Service" is checked in "Remote Sources", you need to manually upload the packages zip archives (and subsequent updates) in %1$s. A package needs to a valid generic package, or a valid WordPress plugin or theme package, and in the case of a plugin the main plugin file must have the same name as the zip archive. For example, the main plugin file in %2$s would be %3$s.', 'wppus' ),
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
				esc_html__( "When the remote clients where your plugins, themes, or generic packages are installed send a request to check for updates, download a package or check or change license status, the current server's WordPress installation is loaded, with its own plugins and themes. This is not optimised if left untouched because unnecessary action and filter hooks that execute before %s action hook are also triggered, even though the request is not designed to produce any on-screen output or further computation.", 'wppus' ),
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
				esc_html__( 'The full documentation can be found %s, with more details for developers on how to integrate WP Plugin Server with their own plugins, themes, and generic packages.', 'wppus' ),
				'<a target="_blank" href="https://github.com/froger-me/wp-packages-update-server/blob/master/README.md">' . esc_html__( 'here', 'wppus' ) . '</a>',
			);
			?>
		</p>
		<p>
			<?php
			printf(
				// translators: %1$s is a link to opening an issue, %2$s is a contact email
				esc_html__( 'After reading the documentation, for more help on how to use WP Packages Update Server, please %1$s - bugfixes are welcome via pull requests, detailed bug reports with accurate pointers as to where and how they occur in the code will be addressed in a timely manner, and a fee will apply for any other request (if they are addressed). If and only if you found a security issue, please contact %2$s with full details for responsible disclosure.', 'wppus' ),
				'<a target="_blank" href="https://github.com/froger-me/wp-packages-update-server/issues">' . esc_html__( 'open an issue on Github', 'wppus' ) . '</a>',
				'<a href="mailto:wppus-help@froger.me">wppus-help@anyape.com</a>',
			);
			?>
		</p>
	</div>
</div>
