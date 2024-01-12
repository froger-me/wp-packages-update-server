<?php
/*
Plugin Name: Dummy Plugin
Plugin URI: https://froger.me/
Description: Empty plugin to demonstrate the WP Package Updater.
Version: 1.4.14
Author: Alexandre Froger
Author URI: https://froger.me/
Icon1x: https://raw.githubusercontent.com/froger-me/wp-packages-update-server/main/integration/assets/icon-128x128.png
Icon2x: https://raw.githubusercontent.com/froger-me/wp-packages-update-server/main/integration/assets/icon-256x256.png
BannerLow: https://raw.githubusercontent.com/froger-me/wp-packages-update-server/main/integration/assets/banner-772x250.png
BannerHigh: https://raw.githubusercontent.com/froger-me/wp-packages-update-server/main/integration/assets/banner-1544x500.png
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* ================================================================================================ */
/*                                  WP Packages Update Server                                         */
/* ================================================================================================ */

/**
* Uncomment the section below to enable updates with WP Packages Update Server.
*
* WARNING - READ FIRST:
*
* Before deploying the plugin or theme, make sure to change the following values in wppus.json:
* - server          => The URL of the server where WP Packages Update Server is installed ; required
* - requireLicense  => Whether the package requires a license ; true or false ; optional
*
* Also change $prefix_updater below - replace "prefix" in this variable's name with a unique prefix
*
**/

/** Enable updates **/
/* phpcs:ignore Squiz.PHP.CommentedOutCode.Found
require_once __DIR__ . '/lib/wp-package-updater/class-wp-package-updater.php';

$prefix_updater = new WP_Package_Updater(
	wp_normalize_path( __FILE__ ),
	0 === strpos( __DIR__, WP_PLUGIN_DIR ) ? wp_normalize_path( __DIR__ ) : get_stylesheet_directory()
);
*/

/* ================================================================================================ */

function dummy_plugin_run() {}
add_action( 'plugins_loaded', 'dummy_plugin_run', 10, 0 );
