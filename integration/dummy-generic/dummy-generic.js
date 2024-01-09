const wppusApi = require('./wppus-api');
const commands = {

    // ### CHECKING THE PACKAGE STATUS ###

    status: function () {

        if (true === wppusApi.isInstalled()) {
            console.log("Status: Installed");
        } else if (false === wppusApi.isInstalled()) {
            console.log("Status: Not installed");
        } else {
            console.log("Status: Unknown");
        }
    },

    // ### INSTALLING THE PACKAGE ###

    install: function (licenseKey) {
        // If the command is "install", the script is not installed, and the license key is not empty
        if (false === wppusApi.isInstalled() && '' !== licenseKey) {
            // Install the script
            wppusApi.install(licenseKey);

            console.log("Installed");
        } else {
            console.log("Failed to install");
        }
    },


    // ### UNINSTALLING THE PACKAGE ###

    uninstall: function () {
        // If the command is "uninstall" and the script is installed
        if (true === wppusApi.isInstalled()) {
            // Uninstall the script
            wppusApi.uninstall();

            console.log("Uninstalled");
        } else {
            console.log("Failed to uninstall");
        }
    },

    // ### ACTIVATING THE LICENSE ###

    activate: function () {
        // If the command is "activate", the script is installed, and the license key is not empty
        if (true === wppusApi.isInstalled()) {
            // Activate the license
            wppusApi.activate();

            console.log("Activated");
        } else {
            console.log("The package is not installed");
        }
    },

    // ### DEACTIVATING THE LICENSE ###

    deactivate: function () {
        // If the command is "deactivate" and the script is installed
        if (true === wppusApi.isInstalled()) {
            // Deactivate the license
            wppusApi.deactivate();

            console.log("Deactivated");
        } else {
            console.log("The package is not installed");
        }
    },

    // ### GETTING UPDATE INFORMATION ###

    get_update_info: function () {
        // If the command is "get_update_info" and the script is installed
        if (true === wppusApi.isInstalled()) {
            // Get the update information
            const info = wppusApi.getUpdateInfo();
            // Get the current version
            const version = wppusApi.getVersion();
            // Get the remote version
            const newVersion = info.version;

            console.log(`current ${version} vs. remote ${newVersion}`);

            if (newVersion > version) {
                console.log("Update available !!! Run the \"update\" command!");
            }

            console.log("---------");
            console.log(info);
        } else {
            console.log("The package is not installed");
        }
    },

    // ### UPDATING THE PACKAGE ###

    update: function () {
        // If the command is "update" and the script is installed
        if (true === wppusApi.isInstalled()) {
            // Get the update information
            wppusApi.update();

            console.log("Updated");
            console.log(wppusApi.getUpdateInfo());
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

(function () {
    const command = process.argv[2] || '';
    const license = process.argv[3] || '';

    if (typeof commands[command] === 'function') {

        if (command === 'install') {
            commands[command](license);
        } else {
            commands[command]();
        }
    } else {
        commands.usage();
    }

    process.exit();
})();