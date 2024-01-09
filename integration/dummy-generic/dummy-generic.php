<?php

require_once __DIR__ . '/wppus-api.php';

### MAIN ###

( function () {
	$command = $argv[1] ?? '';
	$license = $argv[2] ?? '';

	if ( function_exists( 'dummy_generic_' . $command ) ) {

		if ( 'install' === $command ) {
			$command( $license );
		} else {
			$command();
		}
	} else {
		dummy_generic_usage();
	}

	exit;
} )();

### CHECKING THE PACKAGE STATUS ###

function dummy_generic_status() {

	if ( true === WPPUS_API::is_installed() ) {
		echo "Status: Installed\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} elseif ( false === WPPUS_API::is_installed() ) {
		echo "Status: Not installed\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo "Status: Unknown\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

### INSTALLING THE PACKAGE ###

function dummy_generic_install( $license_key ) {
	// If the command is "install", the script is not installed, and the license key is not empty
	if ( ! WPPUS_API::is_installed() && ! empty( $license_key ) ) {
		// Install the script
		WPPUS_API::install( $license_key );

		echo "Installed\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo "Failed to install\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

### UNINSTALLING THE PACKAGE ###

function dummy_generic_uninstall() {
	// If the command is "uninstall" and the script is installed
	if ( WPPUS_API::is_installed() ) {
		// Uninstall the script
		WPPUS_API::uninstall();

		echo "Uninstalled\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo "Failed to uninstall\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

### ACTIVATING THE LICENSE ###

function dummy_generic_activate( $license_key ) {
	// If the command is "activate", the script is installed, and the license key is not empty
	if ( WPPUS_API::is_installed() && ! empty( $license_key ) ) {
		// Activate the license
		WPPUS_API::activate( $license_key );

		echo "Activated\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo "The package is not installed\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

### DEACTIVATING THE LICENSE ###

function dummy_generic_deactivate() {
	// If the command is "deactivate" and the script is installed
	if ( WPPUS_API::is_installed() ) {
		// Deactivate the license
		WPPUS_API::deactivate();

		echo "Deactivated\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo "The package is not installed\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

### GETTING UPDATE INFORMATION ###

function dummy_generic_get_update_info() {
	// If the command is "get_update_info" and the script is installed
	if ( WPPUS_API::is_installed() ) {
		// Get the update information
		$info = WPPUS_API::get_update_info();
		// Get the current version
		$version = WPPUS_API::get_version();
		// Get the remote version
		$new_version = $info['version'];

		echo "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		// Get the current version
		echo "current $version vs. remote $new_version\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( version_compare( $new_version, $version, '>' ) ) {
			echo "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "---------\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "Update available !!! Run the \"update\" command!\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "---------\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		// Pretty print the response
		echo json_encode( $info, JSON_PRETTY_PRINT ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.json_encode_json_encode
		echo "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo "The package is not installed\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

### UPDATING THE PACKAGE ###

function dummy_generic_update() {
	// If the command is "update" and the script is installed
	if ( WPPUS_API::is_installed() ) {
		// Get the update information
		WPPUS_API::check_for_updates();

		echo "Updated\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo json_encode( WPPUS_API::get_update_info() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.json_encode_json_encode
	} else {
		echo "The package is not installed\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

### USAGE ###

function dummy_generic_usage() {
	echo "Usage: bash \"$(dirname \"$0\")/wppus-api.sh\" [command] [arguments]\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "Commands:\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "  install [license] - install the package\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "  uninstall - uninstall the package\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "  activate - activate the license\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "  deactivate - deactivate the license\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "  get_update_info - output information about the remote package update\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "  update - update the package if available\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
