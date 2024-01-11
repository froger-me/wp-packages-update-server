#!/usr/bin/env python3
# pylint: disable=C0103
"""
Dummy Generic
"""
import importlib
import sys
import json

wppus_api = importlib.import_module("wppus-api")

def status():
    """
    ### CHECKING THE PACKAGE STATUS ###
    """
    if wppus_api.is_installed():
        print("Status: Installed")
    elif not wppus_api.is_installed():
        print("Status: Not installed")
    else:
        print("Status: Unknown")

def install(license_key):
    """
    ### INSTALLING THE PACKAGE ###
    """
    # If the command is "install", the script is not installed, and the license key is not empty
    if not wppus_api.is_installed() and license_key:
        # Install the script
        wppus_api.install(license_key)

        print("Installed")
    else:
        print("Failed to install")

def uninstall():
    """
    ### UNINSTALLING THE PACKAGE ###
    """
    # If the command is "uninstall" and the script is installed
    if wppus_api.is_installed():
        # Uninstall the script
        wppus_api.uninstall()

        print("Uninstalled")
    else:
        print("Failed to uninstall")

def activate():
    """
    ### ACTIVATING THE LICENSE ###
    """
    # If the command is "activate", the script is installed, and the license key is not empty
    if wppus_api.is_installed() :
        # Activate the license
        wppus_api.activate_license()

        print("Activated")
    else:
        print("The package is not installed")

def deactivate():
    """
    ### DEACTIVATING THE LICENSE ###
    """
    # If the command is "deactivate" and the script is installed
    if wppus_api.is_installed():
        # Deactivate the license
        wppus_api.deactivate_license()

        print("Deactivated")
    else:
        print("The package is not installed")

def get_update_info():
    """
    ### GETTING UPDATE INFORMATION ###
    """
    # If the command is "get_update_info" and the script is installed
    if wppus_api.is_installed():
        # Get the update information
        info = wppus_api.get_update_info()
        # Get the current version
        version = wppus_api.get_version()
        # Get the remote version
        new_version = info["version"]

        print("")
        # Get the current version
        print("current " + version + " vs. remote " + new_version)
        if new_version > version:
            print("")
            print("---------")
            print("")
            print("Update available !!! Run the \"update\" command!")
        print("")
        print("---------")
        print("")
        # Pretty print the response
        print(json.dumps(wppus_api.get_update_info(), indent=4).replace("\\/", "/"))
        print("")
    else:
        print("The package is not installed")

def update():
    """
    ### UPDATING THE PACKAGE ###
    """
    # If the command is "update" and the script is installed
    if wppus_api.is_installed():
        # Get the update information
        wppus_api.update()

        print("Updated")
        print("")
        # Pretty print the response
        print(json.dumps(wppus_api.get_update_info(), indent=4).replace("\\/", "/"))
        print("")
    else:
        print("The package is not installed")

def usage():
    """
    ### USAGE ###
    """
    print("Usage: ./dummy-generic.py [command] [arguments]")
    print("Commands:")
    print("  install [license] - install the package")
    print("  uninstall - uninstall the package")
    print("  activate - activate the license")
    print("  deactivate - deactivate the license")
    print("  get_update_info - output information about the remote package update")
    print("  update - update the package if available")
    print("  status - output the package status")
    print("Note: this package assumes it needs a license.")

def main():
    """
    ### MAIN ###
    """
    funcs = globals()

    if len(sys.argv) > 1 and len(sys.argv) <= 3:
        command = sys.argv[1]
        license_key = ''

        if len(sys.argv) == 3:
            license_key = sys.argv[2]

        if command in funcs:

            if command == "install":
                funcs["install"](license_key)
            else:
                funcs[command]()
        else:
            funcs["usage"]()
    else:
        funcs["usage"]()

    sys.exit()

if __name__ == "__main__":
    main()
