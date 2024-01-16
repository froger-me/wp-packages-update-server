<?php
/**
* Run as little as possible of the WordPress core with WP Packages Update Server actions and filters.
* Effect:
* - keep only a selection of plugins (@see wppus_mu_always_active_plugins filter below)
* - prevent inclusion of themes functions.php (parent and child)
* - remove all core actions and filters that haven't been fired yet
*
* Place this file in a wp-content/mu-plugin folder and it will be loaded automatically.
*
* Use the following filters in your own MU plugin for customization purposes:
* - @see wppus_mu_always_active_plugins - filter the plugins to be kept active during WPPUS API calls
* - @see wppus_mu_doing_api_request - determine if the current request is a WPPUS API call
* - @see wppus_mu_require - filter the files to be required before WPPUS API calls are handled
*
* The following action is also available in your own MU plugin to completely alter WPPUS behaviour:
* - @see wppus_mu_init - fire this action after handling your own initialization of WPPUS (bypass the default)
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function wppus_muplugins_loaded() {
	$wppus_always_active_plugins = apply_filters(
		'wppus_mu_always_active_plugins',
		array(
			// Add your own MU plugin and subscribe to this filter to add your plugin IDs here
			// to keep them active during update checks.
			// 'my-plugin-slug/my-plugin-file.php',
			// 'my-other-plugin-slug/my-other-plugin-file.php',
			'wp-packages-update-server/wp-packages-update-server.php',
		)
	);

	$url_parts               = explode(
		DIRECTORY_SEPARATOR,
		ltrim( wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' )
	);
	$frag                    = reset( $url_parts );
	$wppus_doing_api_request = (
		'wppus-license-api' === $frag ||
		'wppus-nonce' === $frag ||
		'wppus-token' === $frag ||
		'wppus-update-api' === $frag ||
		'wppus-webhook-api' === $frag
	);

	if ( apply_filters( 'wppus_mu_doing_api_request', $wppus_doing_api_request ) ) {
		$hooks = array(
			'registered_taxonomy',
			'wp_register_sidebar_widget',
			'registered_post_type',
			'auth_cookie_malformed',
			'auth_cookie_valid',
			'widgets_init',
			'wp_default_scripts',
			'option_siteurl',
			'option_home',
			'option_active_plugins',
			'query',
			'option_blog_charset',
			'plugins_loaded',
			'sanitize_comment_cookies',
			'template_directory',
			'stylesheet_directory',
			'determine_current_user',
			'set_current_user',
			'user_has_cap',
			'init',
			'option_category_base',
			'option_tag_base',
			'heartbeat_settings',
			'locale',
			'wp_loaded',
			'query_vars',
			'request',
			'parse_request',
			'shutdown',
		);

		foreach ( $hooks as $hook ) {
			remove_all_filters( $hook );
		}

		add_filter(
			'option_active_plugins',
			function ( $plugins ) use ( $wppus_always_active_plugins ) {

				foreach ( $plugins as $key => $plugin ) {

					if ( ! in_array( $plugin, $wppus_always_active_plugins, true ) ) {
						unset( $plugins[ $key ] );
					}
				}

				return $plugins;
			},
			PHP_INT_MAX - 100,
			1
		);

		add_filter( 'template_directory', fn() => __DIR__, PHP_INT_MAX - 100, 0 );
		add_filter( 'stylesheet_directory', fn() => __DIR__, PHP_INT_MAX - 100, 0 );
		add_filter( 'enable_loading_advanced_cache_dropin', fn() => false, PHP_INT_MAX - 100, 0 );
	}
}
add_action( 'muplugins_loaded', 'wppus_muplugins_loaded', 0 );
