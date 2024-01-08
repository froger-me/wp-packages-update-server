<?php
/**
url="https://server.domain.tld"

# define the package name
package_name="$(basename "$(cd "$(dirname "$0")"; pwd -P)")"
# define the package script
package_script="$(cd "$(dirname "$0")"; pwd -P)/$(basename "$(cd "$(dirname "$0")"; pwd -P)").sh"
# define the current script name
script_name="$(basename $0)"
# define the current script path
script_path="$(cd "$(dirname "$0")"; pwd -P)/$package_name/$script_name"
# define the update zip archive name
zip_name="$package_name.zip"
# define the current version of the package from the wppus.json file
version=$(jq -r '.Version' "$(cd "$(dirname "$0")"; pwd -P)/wppus.json")
# define the domain
if [[ "$(uname)" == "Darwin" ]]; then
    # macOS
    domain=$(ioreg -rd1 -c IOPlatformExpertDevice | awk -F'"' '/IOPlatformUUID/{print $4}')
elif [[ "$(uname)" == "Linux" ]]; then
    # Ubuntu
    domain=$(cat /var/lib/dbus/machine-id)
fi
# debug mode - "true" will bypass cron setting
debug="false"

# source the bashrc file to initialize the environment variables
source ~/.bashrc

/*=== INSTALLING THE PACKAGE ===

function install() {
    # save the license in an environment variable
    echo "export WPPUS_GENERIC_PACKAGE_LICENSE=\"$1\"" >> ~/.bashrc
    # source the bashrc file to commit the changes
    source ~/.bashrc
    echo "Installed license $1" >&2

    if [ "$debug" == "true" ]; then
        # return early
        return
    fi

    # define the new cron job
    local cron_job="0 *\/12 * * * $script_path check_for_updates"

    # check if the cron job already exists
    if ! crontab -l 2>/dev/null | grep -q "$script_path check_for_updates"; then
        # add the new cron job
        (crontab -l 2>/dev/null; echo "$cron_job") | crontab -
    fi
}

/*=== UNINSTALLING THE PACKAGE ===

function uninstall() {
    local license=$WPPUS_GENERIC_PACKAGE_LICENSE
    # remove the license from the environment variable
    # WPPUS_GENERIC_PACKAGE_LICENSE
    sed -i '' '/WPPUS_GENERIC_PACKAGE_LICENSE/d' ~/.bashrc
    unset WPPUS_GENERIC_PACKAGE_LICENSE
    # remove the license signature from the environment variable
    # WPPUS_GENERIC_PACKAGE_SIGNATURE
    sed -i '' '/WPPUS_GENERIC_PACKAGE_SIGNATURE/d' ~/.bashrc
    unset WPPUS_GENERIC_PACKAGE_SIGNATURE
    # source the bashrc file to commit the changes
    source ~/.bashrc
    echo "Uninstalled license $license" >&2

    if [ "$debug" == "true" ]; then
        # return early
        return
    fi

    # check if the cron job exists
    if crontab -l 2>/dev/null | grep -q "$script_path check_for_updates"; then
        # remove the cron job
        (crontab -l | grep -v "$script_path check_for_updates") | crontab -
    fi
}

/*=== CHECKING IF THE PACKAGE IS INSTALLED ===

function is_installed() {

    # check if the WPPUS_GENERIC_PACKAGE_LICENSE environment variable is set
    if [ -z "$WPPUS_GENERIC_PACKAGE_LICENSE" ]; then
        # return false
        echo "false"
        # return early
        return
    fi

    if [ "$debug" == "true" ]; then
        # return true
        echo "true"
        # return early
        return
    fi

    # check if the cron job exists
    if crontab -l | grep -q "$script_path"; then
        # return true
        echo "true"
    else
        # return false
        echo "false"
    fi
}

/*=== SENDNG AN API REQUEST ===

function send_api_request() {
    # build the request url
    local IFS='&'
    local full_url=$(printf "%s/%s/?%s" "$url" "$1" "${*:2}")
    # make the request
    local response=$(curl -s "$full_url")
    # return the response
    echo "$response"
}

## ENOCODING AND DECODING FOR URLS ##

function urlencode() {
    local old_lc_collate=$LC_COLLATE
    LC_COLLATE=C

    local length="${#1}"
    for (( i = 0; i < length; i++ )); do
        local c="${1:$i:1}"
        case $c in
            [a-zA-Z0-9.~_-]) printf '%s' "$c" ;;
            *) printf '%%%02X' "'$c" ;;
        esac
    done

    LC_COLLATE=$old_lc_collate
}

function urldecode() {
    local url_encoded="${1//+/ }"
    printf '%b' "${url_encoded//%/\\x}"
}

/*=== CHECKING FOR UPDATES ===

function check_for_updates() {
    # get a previously acquired license signature from an environment variable
    # WPPUS_GENERIC_PACKAGE_SIGNATURE, and url encode it
    local signature=$(urlencode "$WPPUS_GENERIC_PACKAGE_SIGNATURE")
    # build the request url
    local endpoint="wppus-update-api"
    local args=(
        "action=get_metadata"
        "package_id=$package_name"
        "installed_version=1.4.13"
        "license_key=41ec1eba0f17d47f76827a33c7daab2c"
        "license_signature=$signature"
        "update_type=Generic"
    )
    # make the request
    local response=$(send_api_request "$endpoint" "${args[@]}")
    # return the response
    echo "$response"
}

/*=== ACTIVATING A LICENSE ===

function activate_license() {
    # get the license signature from an environment variable
    # WPPUS_GENERIC_PACKAGE_LICENSE
    local license=$WPPUS_GENERIC_PACKAGE_LICENSE
    # build the request url
    local endpoint="wppus-license-api"
    local args=(
        "action=activate"
        "license_key=$license"
        "allowed_domains=$domain"
        "package_slug=$package_name"
    )
    # make the request
    local response=$(send_api_request "$endpoint" "${args[@]}")
    # get the license signature from the response
    local license_signature=$(urldecode $(echo -n "$response" | jq -r '.license_signature'))
    # save the license signature in an environment variable
    # WPPUS_GENERIC_PACKAGE_SIGNATURE
    echo "export WPPUS_GENERIC_PACKAGE_SIGNATURE=\"$license_signature\"" >> ~/.bashrc
    source ~/.bashrc
}

/*=== DEACTIVATING A LICENSE ===

function deactivate_license() {
    # get the license signature from an environment variable
    # WPPUS_GENERIC_PACKAGE_LICENSE
    local license=$WPPUS_GENERIC_PACKAGE_LICENSE
    # build the request url
    local endpoint="wppus-license-api"
    local args=(
        "action=deactivate"
        "license_key=$license"
        "allowed_domains=$domain"
        "package_slug=$package_name"
    )
    # make the request
    local response=$(send_api_request "$endpoint" "${args[@]}")
    # remove the license signature from the environment variable
    # WPPUS_GENERIC_PACKAGE_SIGNATURE
    sed -i '' '/WPPUS_GENERIC_PACKAGE_SIGNATURE/d' ~/.bashrc
    source ~/.bashrc
}

/*=== DOWNLOADING THE PACKAGE ===

function download_update() {
    # get the download url from the response in $1
    local url=$(urldecode $(echo -n "$1" | jq -r '.download_url'))
    # set the path to the downloaded file in /tmp/dummy-generic.zip
    local output_file="/tmp/$zip_name"
    # make the request
    curl -sS -L -o $output_file "$url"
    # return the path to the downloaded file
    echo $output_file
}

# check if the script was called with the argument "install"
if [ "$1" == "install" ]; then
    # install the package
    echo $(install "$2")
    # halt the script
    exit 0
fi

# check if the script was called with the argument "uninstall"
if [ "$1" == "uninstall" ]; then
    # uninstall the package
    echo $(uninstall)
    # halt the script
    exit 0
fi

# check if the script was called with the argument "is_installed"
if [ "$1" == "is_installed" ]; then
    # check if the package is installed
    echo $(is_installed)
    # halt the script
    exit 0
fi

# check if the script was called with the argument "check_for_updates"
if [ "$1" == "check_for_updates" ]; then
    # check for updates
    response=$(check_for_updates)
    # get the version from the response
    new_version=$(echo -n "$response" | jq -r '.version')

    # compare the versions - if new_version is greater than version,
    # download the update
    if [ "$(printf '%s\n' "$new_version" "$version" | sort -V | tail -n1)" != "$version" ]; then
        # download the update
        output_file=$(download_update "$response")
        # extract the zip in /tmp/$(package_name)
        unzip -q -o $output_file -d /tmp

        # get the permissions of the old file
        if [[ "$OSTYPE" == "linux-gnu"* ]]; then
            OCTAL_MODE=$(stat -c '%a' "$package_script")
        elif [[ "$OSTYPE" == "darwin"* ]]; then
            OCTAL_MODE=$(stat -f '%p' "$package_script" | cut -c 4-6)
        fi
        # set the permissions of the new file to the permissions of the old file
        chmod $OCTAL_MODE /tmp/$package_name/$package_name.sh

        # move all the files except the update scripts (all languages) to the
        # current directory ; the updated main script is in charge of
        # overriding the update scripts by moving files around after update
        for file in /tmp/$package_name *; do
            if [[ ! -d "$file" ]] && [[ "${file%.*}" != "${script_name%.*}" ]]; then
                mv /tmp/$package_name/$file $(dirname "$0")
            fi
        done

        # remove the directory
        rm -rf /tmp/$package_name
        # remove the zip
        rm $output_file

        # halt the script
        exit 0
    fi

    # halt the script
    exit 0
fi

# check if the script was called with the argument "activate_license"
if [ "$1" == "activate_license" ]; then
    # activate the license
    echo $(activate_license)
    # halt the script
    exit 0
fi

# check if the script was called with the argument "deactivate_license"
if [ "$1" == "deactivate_license" ]; then
    # deactivate the license
    echo $(deactivate_license)
    # halt the script
    exit 0
fi

# check if the script was called with the argument "get_update"
if [ "$1" == "get_update_info" ]; then
    response=$(check_for_updates)

    # get the current version
    echo "current: $version"
    echo "vs."
    # get the version from the response
    echo "remote: $(echo -n "$response" | jq -r '.version')"
    echo ""
    echo "---------"
    echo ""
    # pretty print the response
    echo "$response" | jq
    # halt the script
    exit 0
fi

**/