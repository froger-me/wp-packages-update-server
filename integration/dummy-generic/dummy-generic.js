#!/usr/bin/env node

const wppusApi = require('./wppus-api');

wppusApi.on('ready', function (api) {

    const commands = {

        // ### CHECKING THE PACKAGE STATUS ###

        status: async function () {

            if (true === api.is_installed()) {
                console.log("Status: Installed");
            } else if (false === api.is_installed()) {
                console.log("Status: Not installed");
            } else {
                console.log("Status: Unknown");
            }
        },

        // ### INSTALLING THE PACKAGE ###

        install: async function (licenseKey) {
            // If the command is "install", the script is not installed, and the license key is not empty
            if (false === api.is_installed() && '' !== licenseKey) {
                // Install the script
                api.install(licenseKey);

                console.log("Installed");
            } else {
                console.log("Failed to install");
            }
        },


        // ### UNINSTALLING THE PACKAGE ###

        uninstall: async function () {
            // If the command is "uninstall" and the script is installed
            if (true === api.is_installed()) {
                // Uninstall the script
                api.uninstall();

                console.log("Uninstalled");
            } else {
                console.log("Failed to uninstall");
            }
        },

        // ### ACTIVATING THE LICENSE ###

        activate: async function () {
            // If the command is "activate", the script is installed, and the license key is not empty
            if (true === api.is_installed()) {
                // Activate the license
                await api.activate_license();

                console.log("Activated");
            } else {
                console.log("The package is not installed");
            }
        },

        // ### DEACTIVATING THE LICENSE ###

        deactivate: async function () {
            // If the command is "deactivate" and the script is installed
            if (true === api.is_installed()) {
                // Deactivate the license
                api.deactivate_license();

                console.log("Deactivated");
            } else {
                console.log("The package is not installed");
            }
        },

        // ### GETTING UPDATE INFORMATION ###

        get_update_info: async function () {
            // If the command is "get_update_info" and the script is installed
            if (true === api.is_installed()) {
                // Get the update information
                const info = await api.get_update_info();
                // Get the current version
                const version = api.get_version();
                // Get the remote version
                const newVersion = info.version;

                console.log("");
                console.log(`current ${version} vs. remote ${newVersion}`);
                console.log("");

                if (newVersion > version) {
                    console.log("---------");
                    console.log("");
                    console.log("Update available !!! Run the \"update\" command!");
                    console.log("");
                }

                console.log("---------");
                console.log("");
                console.log(info);
            } else {
                console.log("The package is not installed");
            }
        },

        // ### UPDATING THE PACKAGE ###

        update: async function () {
            // If the command is "update" and the script is installed
            if (true === api.is_installed()) {
                // Get the update information
                await api.update();

                console.log("Updated");
                console.log("");
                console.log(await api.get_update_info());
                console.log("");
            } else {
                console.log("The package is not installed");
            }
        },

        // ### USAGE ###

        usage: function () {
            console.log("Usage: ./dummy-generic.js [command] [arguments]");
            console.log("Commands:");
            console.log("  install [license] - install the package");
            console.log("  uninstall - uninstall the package");
            console.log("  activate - activate the license");
            console.log("  deactivate - deactivate the license");
            console.log("  get_update_info - output information about the remote package update");
            console.log("  update - update the package if available");
            console.log("  status - output the package status");
            console.log("Note: this package assumes it needs a license.");
        }
    };

    // ### MAIN ###

    (async function () {
        const command = process.argv[2] || '';
        const license = process.argv[3] || '';

        if (typeof commands[command] === 'function') {

            if (command === 'install') {
                await commands[command](license);
            } else {
                await commands[command]();
            }
        } else {
            commands.usage();
        }

        process.exit();
    })();
});