#!/bin/bash

### EXAMPLE INTEGRATION WITH WP PACKAGES UPDATE SERVER ###

# DO NOT USE THIS FILE AS IT IS !!!
# It is just a collection of basic functions and snippets, and they do not
# perform the necessary checks to ensure data integrity ; they assume that all
# the requests are successful, and do not check paths or permissions.
# They also assume that the package necessitates a license key, stored in an
# environment variable WPPUS_GENERIC_PACKAGE_LICENSE

# replace https://server.domain.tld/ with the URL of the server where
# WP Packages Update Server is installed
url="https://server.anyape.com"

# define the package name - it is the name of the directory where the current script is held
package_name="$(basename "$(cd "$(dirname "$0")"; pwd -P)")"
# define the package script
package_script="$(cd "$(dirname "$0")"; pwd -P)/$(basename "$(cd "$(dirname "$0")"; pwd -P)").sh"
# define the current script name
script_name="$(basename $0)"
# define the update zip archive name
zip_name="$package_name.zip"
# get the version of the package
version=$(bash $package_script get_version)
# debug mode - bypass cron setting
debug="true"

if [ "$debug" == "true" ]; then
    export WPPUS_GENERIC_PACKAGE_LICENSE="8e470622acc90358baa0c5e46198ecf9"
fi

### INSTALLING THE PACKAGE ###

function install() {

    if [ "$debug" == "true" ]; then
        export WPPUS_GENERIC_PACKAGE_LICENSE="8e470622acc90358baa0c5e46198ecf9"
        echo $WPPUS_GENERIC_PACKAGE_LICENSE
        # return early
        return
    fi

    # get the full path of the current script
    script_path="$(cd "$(dirname "$0")"; pwd -P)/$package_name/$script_name"

    # define the new cron job
    new_cron_job="0 */12 * * * $script_path check_for_updates"

    # check if the cron job already exists
    if ! crontab -l | grep -q "$new_cron_job"; then
        # add the new cron job
        (crontab -l 2>/dev/null; echo "$new_cron_job") | crontab -
    fi
}

### CHECKING IF THE PACKAGE IS INSTALLED ###

function is_installed() {

    if [ "$debug" == "true" ]; then
        # return true
        echo "true"
        # return early
        return
    fi

    # get the full path of the current script
    script_path="$(cd "$(dirname "$0")"; pwd -P)/$package_name/$script_name"

    # check if the cron job exists
    if crontab -l | grep -q "$script_path"; then
        # return true
        echo "true"
    else
        # return false
        echo "false"
    fi
}

### CHECKING FOR UPDATES ###

function check_for_updates() {
    # get a previously acquired license signature from an environment variable
    # WPPUS_GENERIC_PACKAGE_SIGNATURE, and url encode it
    signature=$(echo -n "$WPPUS_GENERIC_PACKAGE_SIGNATURE" | perl -MURI::Escape -ne 'print uri_escape($_)')
    # build the request url
    url="$url/wppus-update-api/"
    args=(
        "action=get_metadata"
        "package_id=$package_name"
        "installed_version=1.4.13"
        "license_key=41ec1eba0f17d47f76827a33c7daab2c"
        "license_signature=$signature"
        "update_type=Generic"
    )
    full_url="${url}?$(IFS=\& ; echo "${args[*]}")"
    # make the request
    response=$(curl -s -H "Accept: application/json" "$full_url")
    # return the response
    echo "$response"
}

### ACTIVATING A LICENSE ###

function activate_license() {
    # get the license signature from an environment variable
    # WPPUS_GENERIC_PACKAGE_LICENSE
    license=$WPPUS_GENERIC_PACKAGE_LICENSE
    # build the request url
    url="$url/wppus-license-api/"
    args=(
        "action=activate"
        "license_key=$license"
        "allowed_domains=$(cat /var/lib/dbus/machine-id)"
        "package_slug=$package_name"
    )
    full_url="${url}?$(IFS=\& ; echo "${args[*]}")"
    # make the request
    response=$(curl -s "$full_url")
    # get the license signature from the response
    license_signature=$(echo -n "$response" | perl -MURI::Escape -ne 'print uri_unescape($_)' | jq -r '.license_signature')
    # save the license signature in an environment variable
    # WPPUS_GENERIC_PACKAGE_SIGNATURE
    echo "export WPPUS_GENERIC_PACKAGE_SIGNATURE=$license_signature" >> ~/.bashrc
    source ~/.bashrc
}

### DEACTIVATING A LICENSE ###

function deactivate_license() {
    # get the license signature from an environment variable
    # WPPUS_GENERIC_PACKAGE_LICENSE
    license=$WPPUS_GENERIC_PACKAGE_LICENSE
    # build the request url
    url="$url/wppus-license-api/"
    args=(
        "action=deactivate"
        "license_key=$license"
        "allowed_domains=$(cat /var/lib/dbus/machine-id)"
        "package_slug=$package_name"
    )
    full_url="${url}?$(IFS=\& ; echo "${args[*]}")"
    # make the request
    response=$(curl -s "$full_url")
    # remove the license signature from the environment variable
    # WPPUS_GENERIC_PACKAGE_SIGNATURE
    sed -i '/WPPUS_GENERIC_PACKAGE_SIGNATURE/d' ~/.bashrc
    source ~/.bashrc
}

### DOWNLOADING THE PACKAGE ###

function download_update() {
    # get the download url from the response in $1
    url=$(echo -n "$1" | perl -MURI::Escape -ne 'print uri_unescape($_)' | jq -r '.download_url')
    # set the path to the downloaded file in /tmp/dummy-generic.zip
    output_file="/tmp/$(zip_name)"
    # make the request
    curl -o $output_file $url
    # return the path to the downloaded file
    echo $output_file
}

# check if the script was called with the argument "install"
if [ "$1" == "install" ]; then
    # install the package
    install
    # halt the script
    exit 0
fi

# check if the script was called with the argument "is_installed"
if [ "$1" == "is_installed" ]; then
    # check if the package is installed
    is_installed
    # halt the script
    exit 0
fi

# check if the script was called with the argument "check_for_updates"
if [ "$1" == "check_for_updates" ]; then
    # check for updates
    response=$(check_for_updates)
    # get the version from the response
    new_version=$(echo -n "$response" | perl -MURI::Escape -ne 'print uri_unescape($_)' | jq -r '.version')

    # compare the versions - if new_version is greater than version,
    # download the update
    if [ "$(printf '%s\n' "$new_version" "$version" | sort -V | head -n1)" != "$version" ]; then
        # download the update
        output_file=$(download_update "$response")
        # extract the zip in /tmp/$(package_name)
        unzip -o $output_file -d /tmp/$(package_name)
        # remove the zip
        rm $output_file
        # get the permissions of the old file
        OCTAL_MODE=$(stat -c '%a' $package_script)
        # set the permissions of the new file to the permissions of the old file
        chmod $OCTAL_MODE /tmp/$package_name/$package_script
        # move the new file to the old file
        mv /tmp/$package_name/$package_script $package_script
        # move the wppus.json file
        mv /tmp/$package_name/wppus.json wppus.json
        # remove the directory
        rm -rf $(dirname $output_file)
        # halt the script
        exit 0
    fi

    # halt the script
    exit 0
fi

# check if the script was called with the argument "activate_license"
if [ "$1" == "activate_license" ]; then
    # activate the license
    activate_license
    # halt the script
    exit 0
fi

# check if the script was called with the argument "deactivate_license"
if [ "$1" == "deactivate_license" ]; then
    # deactivate the license
    deactivate_license
    # halt the script
    exit 0
fi

