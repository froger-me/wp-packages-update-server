#!/bin/bash

### EXAMPLE INTEGRATION WITH WP PACKAGES UPDATE SERVER ###

# DO NOT USE THIS FILE AS IT IS IN PRODUCTION !!!
# It is just a collection of basic functions and snippets, and they do not
# perform the necessary checks to ensure data integrity ; they assume that all
# the requests are successful, and do not check paths or permissions.
# They also assume that the package necessitates a license key.

# replace https://server.domain.tld/ with the URL of the server where
# WP Packages Update Server is installed in wppus.json

# define the url of the server
url=$(jq -r '.server' "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json")
# define the package name
package_name="$(basename "$(cd "$(dirname "$0")" || exit; pwd -P)")"
# define the package script
package_script="$(cd "$(dirname "$0")" || exit; pwd -P)/$(basename "$(cd "$(dirname "$0")" || exit; pwd -P)").sh"
# define the current version of the package from the wppus.json file
version=$(jq -r '.packageData.Version' "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json")
# define license_key from the wppus.json file
license_key=$(jq -r '.licenseKey' "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json")
# define license_signature from the wppus.json file
license_signature=$(jq -r '.licenseSignature' "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json")

# define the domain
if [[ "$(uname)" == "Darwin" ]]; then
    # macOS
    domain=$(ioreg -rd1 -c IOPlatformExpertDevice | awk -F'"' '/IOPlatformUUID/{print $4}' | tr -d '\n')
elif [[ "$(uname)" == "Linux" ]]; then
    # Ubuntu
    domain=$(tr -d '\n' < /var/lib/dbus/machine-id)
fi

### INSTALLING THE PACKAGE ###

function install() {
    # add the license key to wppus.json
    jq --indent 4 '.licenseKey = "'"$1"'"' "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json"
    # add a file '.installed' in current directory
    touch "$(cd "$(dirname "$0")" || exit; pwd -P)/.installed"
}

### UNINSTALLING THE PACKAGE ###

function uninstall() {
    license_signature=""

    # remove the license key from wppus.json
    jq --indent 4 '.licenseKey = ""' "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json"
    # remove the license signature from wppus.json
    jq --indent 4 '.licenseSignature = ""' "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json"
    # remove the file '.installed' from current directory
    rm "$(cd "$(dirname "$0")" || exit; pwd -P)/.installed"
}

### CHECKING IF THE PACKAGE IS INSTALLED ###

function is_installed() {

    # check if the file '.installed exists in current directory
    if [ -f "$(cd "$(dirname "$0")" || exit; pwd -P)/.installed" ]; then

        # return true
        echo "true"
    else

        # return false
        echo "false"
    fi
}

### SENDING AN API REQUEST ###

function send_api_request() {
    local full_url
    local response
    local IFS='&'
    # build the request url
    full_url=$(printf "%s/%s/?%s" "$url" "$1" "${*:2}")
    # make the request
    response=$(curl -sL "$full_url")

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
    local response
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
    response=$(send_api_request "$endpoint" "${args[@]}")

    # return the response
    echo "$response"
}

### ACTIVATING A LICENSE ###

function activate_license() {
    local response
    local signature
    # build the request url
    local endpoint="wppus-license-api"
    local args=(
        "action=activate"
        "license_key=$(urlencode "$license_key")"
        "allowed_domains=$(urlencode "$domain")"
        "package_slug=$(urlencode "$package_name")"
    )
    # make the request
    response=$(send_api_request "$endpoint" "${args[@]}")
    # get the signature from the response
    signature="$(urldecode "$(echo -n "$response" | jq -r '.license_signature')")"

    # add the license signature to wppus.json
    jq --indent 4 '.licenseSignature = "'"$signature"'"' "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json"

    license_signature=$signature
}

### DEACTIVATING A LICENSE ###

function deactivate_license() {
    # # build the request url
    local endpoint="wppus-license-api"
    local args=(
        "action=deactivate"
        "license_key=$(urlencode "$license_key")"
        "allowed_domains=$(urlencode "$domain")"
        "package_slug=$(urlencode "$package_name")"
    )

    # # make the request
    send_api_request "$endpoint" "${args[@]}" > /dev/null
    # remove the license signature from wppus.json
    jq --indent 4 'del(.licenseSignature)' "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json"

    license_signature=""
}

### DOWNLOADING THE PACKAGE ###

function download_update() {
    local download_url
    # get the download url from the response in $1
    download_url="$(echo -n "$1" | jq -r '.download_url')"
    # set the path to the downloaded file
    local output_file="/tmp/$package_name.zip"

    # make the request
    curl -sS -L -o "$output_file" "$download_url"

    # return the path to the downloaded file
    echo "$output_file"
}

### MAIN SCRIPT ###

# check if the script was called with the argument "get_version"
if [ "$1" == "get_version" ]; then
    # return the version
    echo "$version"

    exit 0
fi

# check if the script was called with the argument "install"
if [ "$1" == "install" ]; then
    # install the package
    install "$2"

    exit 0
fi

# check if the script was called with the argument "uninstall"
if [ "$1" == "uninstall" ]; then
    # uninstall the package
    uninstall

    exit 0
fi

# check if the script was called with the argument "is_installed"
if [ "$1" == "is_installed" ]; then
    # check if the package is installed
    is_installed

    exit 0
fi

# check if the script was called with the argument "update"
if [ "$1" == "update" ]; then
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
        unzip -q -o "$output_file" -d /tmp

        if [ -d "/tmp/$package_name" ]; then

            # get the permissions of the current script
            if [[ "$OSTYPE" == "linux-gnu"* ]]; then
                OCTAL_MODE=$(stat -c '%a' "$package_script")
            elif [[ "$OSTYPE" == "darwin"* ]]; then
                OCTAL_MODE=$(stat -f '%p' "$package_script" | cut -c 4-6)
            fi

            # set the permissions of the new main scripts to the permissions of the
            # current script
            for file in "/tmp/$package_name"/*; do

                # check if the file starts with the package name
                if [[ "$file" == "/tmp/$package_name/$package_name".* ]]; then
                    chmod "$OCTAL_MODE" "$file"
                fi
            done

            # delete all files in the current directory, except for update scripts
            for file in "$(cd "$(dirname "$0")" || exit; pwd -P)"/*; do

                # check if the file does not start with `wppus`, or is .json
                if [[ ! "$file" == "$(cd "$(dirname "$0")" || exit; pwd -P)"/wppus* ]] || [[ "$file" == *.json ]]; then
                    rm -rf "$file"
                fi
            done

            # move the updated package files to the current directory ; the
            # updated package is in charge of overriding the update scripts
            # with new ones after update (may be contained in a subdirectory)
            for file in "/tmp/$package_name"/*; do

                # check if the file does not start with `wppus`, or is .json
                if [[ ! "$file" == /tmp/"$package_name"/wppus* ]] || [[ "$file" == *.json ]]; then
                    mv "$file" "$(dirname "$0")"
                fi
            done

            # remove the directory
            rm -rf /tmp/"$package_name"
        fi

        # add the license key to wppus.json
        jq --indent 4 '.licenseKey = "'"$license_key"'"' "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json"
        # add the license signature to wppus.json
        jq --indent 4 '.licenseSignature = "'"$license_signature"'"' "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json" > tmp.json && mv tmp.json "$(cd "$(dirname "$0")" || exit; pwd -P)/wppus.json"
        # remove the zip
        rm "$output_file"

        exit 0
    fi

    exit 0
fi

# check if the script was called with the argument "activate_license"
if [ "$1" == "activate_license" ]; then
    # activate the license
    activate_license

    exit 0
fi

# check if the script was called with the argument "deactivate_license"
if [ "$1" == "deactivate_license" ]; then
    # deactivate the license
    deactivate_license

    exit 0
fi

# check if the script was called with the argument "get_update"
if [ "$1" == "get_update_info" ]; then
    # get the update information
    check_for_updates

    exit 0
fi

