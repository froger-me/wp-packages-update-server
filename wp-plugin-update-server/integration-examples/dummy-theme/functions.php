<?php

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
* - Public API Authentication Key   => The Public API Authentication Key in the "Licenses" tab of WP Plugin Update Server
* - $prefix_updater                 => Replace "prefix" in this variable's name with a unique theme prefix
*
* @see https://github.com/froger-me/wp-package-updater
**/

require_once get_stylesheet_directory() . '/lib/wp-package-updater/class-wp-package-updater.php';

/** Enable theme updates with license check **/
// $prefix_updater = new WP_Package_Updater(
// 	'https://your-update-server.com',
// 	wp_normalize_path( __FILE__ ),
// 	get_stylesheet_directory(),
// 	'Public API Authentication Key',
// 	true
// );

/** Enable theme updates without license check **/
// $prefix_updater = new WP_Package_Updater(
// 	'https://your-update-server.com',
// 	wp_normalize_path( __FILE__ ),
// 	get_stylesheet_directory()
// );

/* ================================================================================================ */

function dummy_theme_enqueue_styles() {
	$parent_style = 'twentyseventeen-style';

	wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( $parent_style ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'dummy_theme_enqueue_styles' );
