#!/bin/bash
version="1.4.14"

# get_version function
function get_version() {
    echo "$version"
}

if [ "$1" == "get_version" ]; then
    # install the package
    get_version
    # halt the script
    exit 0
fi

### INSTALLING THE PACKAGE IF NECESSARY ###

if [ "$(bash "$(dirname "$0")/wppus-api.sh" is_installed)" == "false" ]; then
    bash "$(dirname "$0")/wppus-api.sh" install
fi

### PACKAGE EXECUTION ###
echo "Hello"