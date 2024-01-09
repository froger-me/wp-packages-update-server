#!/bin/bash

### EXAMPLE INTEGRATION WITH WP PACKAGES UPDATE SERVER ###

# DO NOT USE THIS FILE AS IT IS IN PRODUCTION !!!
# It is just a collection of basic functions and snippets, and they do not
# perform the necessary checks to ensure data integrity ; they assume that all
# the requests are successful, and do not check paths or permissions.
# They also assume that the package necessitates a license key, stored in an
# environment variable WPPUS_GENERIC_PACKAGE_LICENSE

# replace https://server.domain.tld/ with the URL of the server where
# WP Packages Update Server is installed
url=$(jq -r '.server' "$(cd "$(dirname "$0")"; pwd -P)/wppus.json")

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
version=$(jq -r '.packageData.Version' "$(cd "$(dirname "$0")"; pwd -P)/wppus.json")
# define license_key from the wppus.json file
license_key=$(jq -r '.licenseKey' "$(cd "$(dirname "$0")"; pwd -P)/wppus.json")
# define license_signature from the wppus.json file
license_signature=$(jq -r '.licenseSignature' "$(cd "$(dirname "$0")"; pwd -P)/wppus.json")
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

### INSTALLING THE PACKAGE ###

function install() {
    # add the license key to wppus.json
    jq '.licenseKey = "'"$1"'"' "$(cd "$(dirname "$0")"; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")"; pwd -P)/wppus.json"

    if [ "$debug" == "true" ]; then
        # return early
        return
    fi

    # define the new cron job
    local cron_job="0 */12 * * * $script_path check_for_updates"

    # check if the cron job already exists
    if ! crontab -l 2>/dev/null | grep -q "$script_path check_for_updates"; then
        # add the new cron job
        (crontab -l 2>/dev/null; echo "$cron_job") | crontab -
    fi
}

### UNINSTALLING THE PACKAGE ###

function uninstall() {
    local license=$license_key
    # remove the license key from wppus.json
    jq '.licenseKey = ""' "$(cd "$(dirname "$0")"; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")"; pwd -P)/wppus.json"
    # remove the license signature from wppus.json
    jq '.licenseSignature = ""' "$(cd "$(dirname "$0")"; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")"; pwd -P)/wppus.json"
    license_signature=""

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

### CHECKING IF THE PACKAGE IS INSTALLED ###

function is_installed() {

    # check if the WPPUS_GENERIC_PACKAGE_LICENSE environment variable is set
    if [ -z "$license_key" ]; then
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
    if crontab -l 2>/dev/null | grep -q "$script_path"; then
        # return true
        echo "true"
    else
        # return false
        echo "false"
    fi
}

### SENDNG AN API REQUEST ###

function send_api_request() {
    # build the request url
    local IFS='&'
    local full_url=$(printf "%s/%s/?%s" "$url" "$1" "${*:2}")
    # make the request
    local response=$(curl -s "$full_url")

    # return the response
    echo "$response"
}

## ENCODING AND DECODING FOR URLS ##

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
    local url_encoded="${1}"
    printf '%b' "${url_encoded//%/\\x}"
}

### CHECKING FOR UPDATES ###

function check_for_updates() {
    # build the request url
    local endpoint="wppus-update-api"
    local args=(
        "action=get_metadata"
        "package_id=$(urlencode "$package_name")"
        "installed_version=$(urlencode "$version")"
        "license_key=$(urlencode "$license_key")"
        "license_signature=$(urlencode "$license_signature")"
        "update_type=Generic"
    )
    # make the request
    local response=$(send_api_request "$endpoint" "${args[@]}")
    # return the response
    echo "$response"
}

### ACTIVATING A LICENSE ###

function activate_license() {
    # build the request url
    local endpoint="wppus-license-api"
    local args=(
        "action=activate"
        "license_key=$(urlencode "$license_key")"
        "allowed_domains=$(urlencode "$domain")"
        "package_slug=$(urlencode "$package_name")"
    )
    # make the request
    local response=$(send_api_request "$endpoint" "${args[@]}")
    # get the signature from the response
    local signature=$(urldecode $(echo -n "$response" | jq -r '.license_signature'))
    # add the license signature to wppus.json
    jq '.licenseSignature = "'"$signature"'"' "$(cd "$(dirname "$0")"; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")"; pwd -P)/wppus.json"
    license_signature=$signature
}

### DEACTIVATING A LICENSE ###

function deactivate_license() {
    # build the request url
    local endpoint="wppus-license-api"
    local args=(
        "action=deactivate"
        "license_key=$(urlencode "$license_key")"
        "allowed_domains=$(urlencode "$domain")"
        "package_slug=$(urlencode "$package_name")"
    )
    # make the request
    local response=$(send_api_request "$endpoint" "${args[@]}")
    # remove the license signature from wppus.json
    jq '.licenseSignature = ""' "$(cd "$(dirname "$0")"; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")"; pwd -P)/wppus.json"
    license_signature=""
}

### DOWNLOADING THE PACKAGE ###

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

### MAIN SCRIPT ###

# check if the script was called with the argument "get_version"
if [ "$1" == "get_version" ]; then
    # return the version
    echo "$version"
    # halt the script
    exit 0
fi

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

        # add the license key to wppus.json
        jq '.licenseKey = "'"$1"'"' "$(cd "$(dirname "$0")"; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")"; pwd -P)/wppus.json"
        # add the license signature to wppus.json
        jq '.licenseSignature = "'"$signature"'"' "$(cd "$(dirname "$0")"; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")"; pwd -P)/wppus.json"

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
    # get the update information
    echo "$(check_for_updates)"
    # halt the script
    exit 0
fi

