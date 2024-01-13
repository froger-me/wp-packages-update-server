<?php

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

function dummy_theme_enqueue_styles() {
	$parent_style = 'twentyseventeen-style';

	wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css', array(), filemtime( __FILE__ ) );
	wp_enqueue_style(
		'child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( $parent_style ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'dummy_theme_enqueue_styles' );
