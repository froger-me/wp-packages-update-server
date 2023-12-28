<?php
/**
* Run as little as possible of the WordPress core with WP Packages Update Server actions and filters.
* Effect:
* - keep only a selection of plugins (@see $wppus_always_active_plugins below)
* - prevent inclusion of themes functions.php (parent and child)
* - remove all core actions and filters that haven't been fired yet
*
* Place this file in a wp-content/mu-plugin folder (after editing if needed) and it will be loaded automatically.
* Use the following variables in the plugins you kept active for customization purposes:
* - @see global $wppus_doing_api_request
* - @see global $wppus_doing_update_api_request
* - @see global $wppus_doing_license_api_request
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wppus_doing_update_api_request;
global $wppus_doing_license_api_request;
global $wppus_doing_api_request;
global $wppus_always_active_plugins;

if ( ! $wppus_always_active_plugins ) {
	$wppus_always_active_plugins = array(
		// Edit with your plugin IDs here to keep them active during update checks.
		// 'my-plugin-slug/my-plugin-file.php',
		// 'my-other-plugin-slug/my-other-plugin-file.php',
		'wp-packages-update-server/wp-packages-update-server.php',
	);
}

$url_parts                       = explode(
	DIRECTORY_SEPARATOR,
	ltrim( wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' )
);
$wppus_doing_update_api_request  = (
	'wp-update-server' === reset( $url_parts ) ||
	'wppus-update-api' === reset( $url_parts )
);
$wppus_doing_license_api_request = ( 'wppus-license-api' === reset( $url_parts ) );
$wppus_doing_api_request         = $wppus_doing_license_api_request || $wppus_doing_update_api_request;

if ( $wppus_doing_api_request ) {
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

	add_filter( 'option_active_plugins', 'wppus_unset_plugins', 99, 1 );
	add_filter( 'template_directory', 'wppus_bypass_themes_functions', 99, 0 );
	add_filter( 'stylesheet_directory', 'wppus_bypass_themes_functions', 99, 0 );
	add_filter( 'enable_loading_advanced_cache_dropin', 'wppus_bypass_cache', 99, 0 );
}

function wppus_unset_plugins( $plugins ) {
	global $wppus_always_active_plugins;

	foreach ( $plugins as $key => $plugin ) {

		if ( ! in_array( $plugin, $wppus_always_active_plugins, true ) ) {
			unset( $plugins[ $key ] );
		}
	}

	return $plugins;
}

function wppus_bypass_cache() {
	return false;
}

function wppus_bypass_themes_functions() {
	return __DIR__;
}
