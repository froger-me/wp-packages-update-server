#!/bin/bash

### CHECKING THE PACKAGE STATUS ###

if [ "$1" == "status" ] && [ "$(bash "$(dirname "$0")/wppus-api.sh" is_installed)" == "false" ]; then
    echo "Status: Not installed"
    # halt the script
    exit 0
elif [ "$1" == "status" ] && [ "$(bash "$(dirname "$0")/wppus-api.sh" is_installed)" == "true" ]; then
    echo "Status: Installed"
    # halt the script
    exit 0
elif [ "$1" == "status" ]; then
    echo "Status: Unknown"
    # halt the script
    exit 1
fi

### INSTALLING THE PACKAGE ###

if [ "$1" == "install" ] && [ "$(bash "$(dirname "$0")/wppus-api.sh" is_installed)" == "false" ] && [ "$2" != "" ]; then
    bash "$(dirname "$0")/wppus-api.sh" install $2
    echo "Installed"
    # halt the script
    exit 0
elif [ "$1" == "install" ]; then
    echo "Failed to install"
    # halt the script
    exit 1
fi

### UNINSTALLING THE PACKAGE ###

if [ "$1" == "uninstall" ] && [ "$(bash "$(dirname "$0")/wppus-api.sh" is_installed)" == "true" ]; then
    # uninstall the package
    bash "$(dirname "$0")/wppus-api.sh" uninstall
    echo "Uninstalled"
    # halt the script
    exit 0
elif [ "$1" == "uninstall" ]; then
    echo "Nothing to uninstall"
    # halt the script
    exit 1
fi

### ACTIVATING THE LICENSE ###

if [ "$1" == "activate" ] && [ "$(bash "$(dirname "$0")/wppus-api.sh" is_installed)" == "true" ]; then
    # activate the license
    bash "$(dirname "$0")/wppus-api.sh" activate_license
    echo "Activated"
    # halt the script
    exit 0
elif [ "$1" == "activate" ]; then
    echo "The package is not installed"
    # halt the script
    exit 1
fi

### DEACTIVATING THE LICENSE ###

if [ "$1" == "deactivate" ] && [ "$(bash "$(dirname "$0")/wppus-api.sh" is_installed)" == "true" ]; then
    # activate the license
    bash "$(dirname "$0")/wppus-api.sh" deactivate_license
    echo "Deactivated"
    # halt the script
    exit 0
elif [ "$1" == "deactivate" ]; then
    echo "The package is not installed"
    # halt the script
    exit 1
fi

### GETTING UPDATE INFORMATION ###

if [ "$1" == "get_update_info" ] && [ "$(bash "$(dirname "$0")/wppus-api.sh" is_installed)" == "true" ]; then
    # get the update information
    info=$(bash "$(dirname "$0")/wppus-api.sh" get_update_info)
    version=$(bash "$(dirname "$0")/wppus-api.sh" get_version)
    new_version=$(echo -n "$info" | jq -r '.version')

    echo ""
    # get the current version
    echo "current $version vs. remote $new_version"

    if [ "$(printf '%s\n' "$new_version" "$version" | sort -V | tail -n1)" != "$version" ]; then
        echo ""
        echo "---------"
        echo ""
        echo "Update available !!! Run the \"update\" command!"
    fi
    echo ""
    echo "---------"
    echo ""
    # pretty print the response
    echo "$info" | jq
    echo ""
    # halt the script
    exit 0
elif [ "$1" == "get_update_info" ]; then
    echo "The package is not installed"
    # halt the script
    exit 1
fi

### UPDATING THE PACKAGE ###

if [ "$1" == "update" ] && [ "$(bash "$(dirname "$0")/wppus-api.sh" is_installed)" == "true" ]; then
    # update the package
    bash "$(dirname "$0")/wppus-api.sh" check_for_updates
    echo "Updated"
    echo ""
    bash "$(dirname "$0")/wppus-api.sh" get_update_info
    # halt the script
    exit 0
elif [ "$1" == "update" ]; then
    echo "The package is not installed"
    # halt the script
    exit 1
fi

### USAGE ###

echo "Usage: bash \"$(dirname "$0")/wppus-api.sh\" [command] [arguments]"
echo "Commands:"
echo "  install [license] - install the package"
echo "  uninstall - uninstall the package"
echo "  activate - activate the license"
echo "  deactivate - deactivate the license"
echo "  get_update_info - output information about the remote package update"
echo "  update - update the package if available"
# halt the script
exit 1
