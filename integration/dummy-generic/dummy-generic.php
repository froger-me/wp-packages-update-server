<?php

require_once dirname(__FILE__) . '/wppus-api.php';

$command = $argv[1] ?? '';
$license = $argv[2] ?? '';

switch ($command) {
    case 'install':
        if (!wppus_api_is_installed() && $license !== '') {
            wppus_api_install($license);
            echo "Installed\n";
            exit(0);
        } else {
            echo "Failed to install\n";
            exit(1);
        }
        break;

    case 'uninstall':
        if (wppus_api_is_installed()) {
            wppus_api_uninstall();
            echo "Uninstalled\n";
            exit(0);
        } else {
            echo "Nothing to uninstall\n";
            exit(1);
        }
        break;

    case 'activate':
        if (wppus_api_is_installed()) {
            wppus_api_activate();
            echo "Activated\n";
            exit(0);
        } else {
            echo "The package is not installed\n";
            exit(1);
        }
        break;

    case 'deactivate':
        if (wppus_api_is_installed()) {
            wppus_api_deactivate();
            echo "Deactivated\n";
            exit(0);
        } else {
            echo "The package is not installed\n";
            exit(1);
        }
        break;

    case 'get_update_info':
        if (wppus_api_is_installed()) {
            wppus_api_get_update_info();
            exit(0);
        } else {
            echo "The package is not installed\n";
            exit(1);
        }
        break;

    case 'update':
        if (wppus_api_is_installed()) {
            wppus_api_check_for_updates();
            echo "Updated\n";
            wppus_api_get_update_info();
            exit(0);
        } else {
            echo "The package is not installed\n";
            exit(1);
        }
        break;

    default:
        echo "Usage: php " . basename(__FILE__) . " [command] [arguments]\n";
        echo "Commands:\n";
        echo "  install [license] - install the package\n";
        echo "  uninstall - uninstall the package\n";
        echo "  activate - activate the license\n";
        echo "  deactivate - deactivate the license\n";
        echo "  get_update_info - output information about the remote package update\n";
        echo "  update - update the package if available\n";
        echo "Installing the package will register a cronjob that will check for updates every 12 hours, and will install the update if available.\n";
        exit(1);
}