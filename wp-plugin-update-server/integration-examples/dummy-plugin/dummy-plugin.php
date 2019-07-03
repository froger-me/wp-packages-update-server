<?php
/*
Plugin Name: Dummy Plugin
Plugin URI: https://froger.me/
Description: Empty plugin to demonstrate the WP Plugin Updater.
Version: 1.4
Author: Alexandre Froger
Author URI: https://froger.me/
Icon1x: https://raw.githubusercontent.com/froger-me/wp-plugin-update-server/master/examples/icon-128x128.png
Icon2x: https://raw.githubusercontent.com/froger-me/wp-plugin-update-server/master/examples/icon-256x256.png
BannerHigh: https://raw.githubusercontent.com/froger-me/wp-plugin-update-server/master/examples/banner-1544x500.png
BannerLow: https://raw.githubusercontent.com/froger-me/wp-plugin-update-server/master/examples/banner-722x250.png
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* ================================================================================================ */
/*                                  WP Plugin Update Server                                         */
/* ================================================================================================ */

/**
* Selectively uncomment the sections below to enable updates with WP Plugin Update Server.
*
* WARNING - READ FIRST:
* Before deploying the plugin or theme, make sure to change the following value
* - https://your-update-server.com  => The URL of the server where WP Plugin Update Server is installed
* - $prefix_updater                 => Replace "prefix" in this variable's name with a unique plugin prefix
*
* @see https://github.com/froger-me/wp-package-updater
**/

require_once plugin_dir_path( __FILE__ ) . 'lib/wp-package-updater/class-wp-package-updater.php';

/** Enable plugin updates with license check **/
// $prefix_updater = new WP_Package_Updater(
// 	'https://your-update-server.com',
// 	wp_normalize_path( __FILE__ ),
// 	wp_normalize_path( plugin_dir_path( __FILE__ ) ),
// 	true
// );

/** Enable plugin updates without license check **/
// $prefix_updater = new WP_Package_Updater(
// 	'https://your-update-server.com',
// 	wp_normalize_path( __FILE__ ),
// 	wp_normalize_path( plugin_dir_path( __FILE__ ) )
// );

/* ================================================================================================ */

function dummy_plugin_run() {}
add_action( 'plugins_loaded', 'dummy_plugin_run', 10, 0 );
