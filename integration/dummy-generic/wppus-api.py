# pylint: disable=C0103
"""
### EXAMPLE INTEGRATION WITH WP PACKAGES UPDATE SERVER ###

DO NOT USE THIS FILE AS IT IS IN PRODUCTION !!!
It is just a collection of basic functions and snippets, and they do not
perform the necessary checks to ensure data integrity ; they assume that all
the requests are successful, and do not check paths or permissions.
They also assume that the package necessitates a license key.

replace https://server.domain.tld/ with the URL of the server where
WP Packages Update Server is installed in wppus.json
"""

import os
import sys
import json
import subprocess
import urllib.parse
import urllib.request
import urllib.error
import zipfile
import shutil
import tempfile
import requests

conf = "wppus.json"

# get the config from wppus.json in a variable
with open(os.path.join(os.path.dirname(__file__), conf), encoding="utf-8") as config_file:
    wppus_config = json.load(config_file)

# define the url of the server
url = wppus_config["server"]
# define the package name
package_name = os.path.basename(os.path.dirname(__file__))
# define the package script
package_script = os.path.join(os.path.dirname(__file__), os.path.basename(__file__))
# define the current script name
script_name = os.path.basename(__file__)
# define the current version of the package from the wppus.json file
version = wppus_config["packageData"]["Version"]

# define license_key from the wppus.json file - check if it exists to avoid errors
if "licenseKey" in wppus_config:
    license_key = wppus_config["licenseKey"]
else:
    license_key = ""

# define license_signature from the wppus.json file - check if it exists to avoid errors
if "licenseSignature" in wppus_config:
    license_signature = wppus_config["licenseSignature"]
else:
    license_signature = ""

# define the domain
if sys.platform == "darwin":
    # macOS
    command = ["ioreg", "-rd1", "-c", "IOPlatformExpertDevice"]
    domain = subprocess.check_output(command).decode("utf-8")
elif sys.platform == "linux":
    # Ubuntu
    domain = subprocess.check_output(["cat", "/var/lib/dbus/machine-id"]).decode("utf-8").strip()


def install():
    """
    ### INSTALLING THE PACKAGE ###
    """
    # add the license key to wppus.json
    wppus_config["licenseKey"] = license_key

    # add a file '.installed' in current directory
    with open(os.path.join(os.path.dirname(__file__), ".installed"), "w", encoding="utf-8") as _:
        pass

def uninstall():
    """
    ### UNINSTALLING THE PACKAGE ###
    """
    # remove the license key from wppus.json
    wppus_config.pop("licenseKey", None)
    # remove the license signature from wppus.json
    wppus_config.pop("licenseSignature", None)

    # remove the file '.installed' from current directory
    os.remove(os.path.join(os.path.dirname(__file__), ".installed"))


def is_installed():
    """
    ### CHECKING IF THE PACKAGE IS INSTALLED ###
    """
    # check if the file '.installed exists in current directory
    if os.path.isfile(os.path.join(os.path.dirname(__file__), ".installed")):

        # return true
        return True

    # return false
    return False


def _send_api_request(endpoint, args):
    """
    ### SENDING AN API REQUEST ###
    """
    # build the request url
    full_url = url.rstrip('/') + "/" + endpoint + "/?" + "&".join(args)

    # set headers
    headers = {
        "user-agent": "curl",
        "accept": "*/*"
    }

    # make the request
    response = requests.get(full_url, headers=headers, timeout=20, verify=True)

    # return the response
    return response.text

def _check_for_updates():
    """
    ### CHECKING FOR UPDATES ###
    """
    # build the request url
    endpoint = "wppus-update-api"
    args = [
        "action=get_metadata",
        "package_id=" + urllib.parse.quote(package_name),
        "installed_version=" + urllib.parse.quote(version),
        "license_key=" + urllib.parse.quote(license_key),
        "license_signature=" + urllib.parse.quote(license_signature),
        "update_type=Generic"
    ]
    # make the request
    response = _send_api_request(endpoint, args)

    # return the response
    return response


def activate_license():
    """
    ### ACTIVATING A LICENSE ###
    """
    # build the request url
    endpoint = "wppus-license-api"
    args = [
        "action=activate",
        "license_key=" + urllib.parse.quote(license_key),
        "allowed_domains=" + urllib.parse.quote(domain),
        "package_slug=" + urllib.parse.quote(package_name)
    ]
    # make the request
    response = _send_api_request(endpoint, args)
    # get the signature from the response
    signature = urllib.parse.unquote(json.loads(response)["license_signature"])
    # add the license signature to wppus.json
    wppus_config["licenseSignature"] = signature

    with open(os.path.join(os.path.dirname(__file__), conf), "w", encoding="utf-8") as f:
        json.dump(wppus_config, f, indent=4)

def deactivate_license():
    """
    ### DEACTIVATING A LICENSE ###
    """
    # build the request url
    endpoint = "wppus-license-api"
    args = [
        "action=deactivate",
        "license_key=" + urllib.parse.quote(license_key),
        "allowed_domains=" + urllib.parse.quote(domain),
        "package_slug=" + urllib.parse.quote(package_name)
    ]
    # make the request
    _send_api_request(endpoint, args)
    # remove the license signature from wppus.json
    wppus_config.pop("licenseSignature", None)

    with open(os.path.join(os.path.dirname(__file__), conf), "w", encoding="utf-8") as f:
        json.dump(wppus_config, f, indent=4)

def _download_update():
    """
    ### DOWNLOADING THE PACKAGE ###
    """
    # build the request url
    endpoint = "wppus-update-api"
    args = [
        "action=get_download_url",
        "package_id=" + urllib.parse.quote(package_name),
        "installed_version=" + urllib.parse.quote(version),
        "license_key=" + urllib.parse.quote(license_key),
        "license_signature=" + urllib.parse.quote(license_signature),
        "update_type=Generic"
    ]
    # make the request
    response = _send_api_request(endpoint, args)
    # get the download url from the response
    _url = urllib.parse.unquote(json.loads(response)["download_url"])
    # set the path to the downloaded file
    output_file = os.path.join(tempfile.gettempdir(), package_name + ".zip")

    # make the request
    urllib.request.urlretrieve(_url, output_file)

    # return the path to the downloaded file
    return output_file

def get_version():
    """
    ### GETTING THE PACKAGE VERSION ###
    """
    # return the current version of the package
    return version

def update():
    """
    ### UPDATING THE PACKAGE ###
    """
    # check for updates
    response = _check_for_updates()
    # get the version from the response
    new_version = json.loads(response)["version"]

    if new_version > version:
        # download the update
        output_file = _download_update()

        # extract the zip in /tmp/$(package_name)
        with zipfile.ZipFile(output_file, "r") as zip_file:
            zip_file.extractall(os.path.join(tempfile.gettempdir(), package_name))

        if os.path.isdir(os.path.join(tempfile.gettempdir(), package_name)):
            # get the permissions of the current script
            octal_mode = oct(os.stat(package_script).st_mode)[-4:]

             # set the permissions of the new main scripts to the permissions of the
            # current script
            for file in os.listdir(os.path.join(tempfile.gettempdir(), package_name)):

                # check if the file starts with the package name
                if file.startswith(package_name):
                    path = os.path.join(tempfile.gettempdir(), package_name, file)

                    os.chmod(path, int(octal_mode, 8))

            # delete all files in the current directory, except for update scripts
            for file in os.listdir(os.path.dirname(__file__)):

                # check if the file does not start with `wppus`, or is .json
                if not file.startswith("wppus") or file.endswith(".json"):
                    os.remove(os.path.join(os.path.dirname(__file__), file))

            # move the updated package files to the current directory ; the
            # updated package is in charge of overriding the update scripts
            # with new ones after update (may be contained in a subdirectory)
            for file in os.listdir(os.path.join(tempfile.gettempdir(), package_name)):

                # check if the file does not start with `wppus`, or is .json
                if not file.startswith("wppus") or file.endswith(".json"):
                    src = os.path.join(tempfile.gettempdir(), package_name, file)
                    dest = os.path.join(os.path.dirname(__file__), file)

                    if os.path.exists(dest):
                        os.remove(dest)

                    shutil.move(src, dest)

            # add the license key to wppus.json
            wppus_config["licenseKey"] = license_key
            # add the license signature to wppus.json
            wppus_config["licenseSignature"] = license_signature

            with open(os.path.join(os.path.dirname(__file__), conf), "w", encoding="utf-8") as f:
                json.dump(wppus_config, f, indent=4)

            # remove the directory
            for file in os.listdir(os.path.join(tempfile.gettempdir(), package_name)):
                os.remove(os.path.join(tempfile.gettempdir(), package_name, file))

            os.rmdir(os.path.join(tempfile.gettempdir(), package_name))

        # remove the zip
        os.remove(output_file)

def get_update_info():
    """
    ### GETTING THE PACKAGE INFO ###
    """
    response = _check_for_updates()
    # get the update information - json parsed, knowing it may contain url encoded characters
    return json.loads(urllib.parse.unquote(response))
