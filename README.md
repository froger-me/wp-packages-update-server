# WP Update Plugin Server - Run your own update server for plugins and themes

* [General description](#user-content-general-description)
	* [Overview](#user-content-overview)
	* [Special Thanks](#user-content-special-thanks)
	* [Screenshots](#user-content-screenshots)
* [Settings](#user-content-settings)
	* [General Settings](#user-content-general)
	* [Packages licensing](#user-content-packages-licensing)
	* [Packages remote source](#user-content-packages-remote-source)
* [Help](#user-content-help)
	* [Requirements to add update checker to plugins and themes (and possibly provide license support)](#user-content-requirements-to-add-update-checker-to-plugins-and-themes-and-possibly-provide-license-support)
	* [Requests optimisation](#user-content-requests-optimisation)
	* [Remote license server integration](#user-content-remote-license-server-integration)
	* [More Help...](#user-content-more-help)

## General Description

WP Update Plugin Server allows developers to provide updates for plugins and themes not hosted on wordpress.org. It optionally integrates with [Software License Manager](https://wordpress.org/plugins/software-license-manager/) for license checking. It is also useful to provide updates for plugins or themes not compliant with the GPLv2 (or later).
Plugins may be either uploaded directly, or hosted in a remote repository, public or private. It supports Bitbucket, Github and Gitlab, as well as self-hosted installations of Gitlab.

### Special Thanks
A warm thank you to [Yahnis Elsts](https://github.com/YahnisElsts), the author of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) and [WP Update Server](https://github.com/YahnisElsts/wp-update-server) libraries, without whom the creation of this plugin would not have been possible.  
Authorisation to use these libraries freely provided relevant licenses are included has been graciously granted [here](https://github.com/YahnisElsts/wp-update-server/issues/37#issuecomment-386814776).

### Overview

This plugin adds the following major features to WordPress:

* **WP Update Plugin Server admin page:** to manage the list of packages and configure the plugin.
* **Package management:** to manage update packages, showing a listing with Package Name, Version, Type, File Name, Size, Last Modified and License Status ; includes bulk operations to delete, download and change the license status, and the ability to delete all the packages.
* **Add Packages:** Upload update packages from your local machine to the server, or download them to the server from a remote repository.
* **General settings:** for archive files download size, cache, and logs, with force clean.
* **Packages licensing:** Prevent plugins and themes installed on remote WordPress installation from being updated without a valid license. Check the validity of licenses using Software License Manager, with an extra signature for stronger security. Possibility to use a remote installation of Software License Manager running on a separate WordPress installation.
* **Packages remote source:** host the packages on a remote repository. WP WUpdate Plugin Server acts as a proxy and checks for packages updates regulary and downloads them automatically when a new version is available. Supports Bitbucket, Github and Gitlab, as well as self-hosted installations of Gitlab.

To connect their plugins or themes and WP WUpdate Plugin Server, developers can find integration examples in `wp-plugin-update-server/integration-examples`:
* **Dummy Plugin:** a folder `dummy-plugin` with a simple, empty plugin that includes the necessary code in the `dummy-plugin.php` main plugin file and the necessary libraries in a `lib` folder.
* **Dummy Theme:** a folder `dummy-theme` with a simple, empty child theme of Twenty Seventeen that includes the necessary code in the `functions.php` file and the necessary libraries in a `lib` folder.
* **Remote Software License Manager:** a file `remote-slm.php` demonstrating how a remote installation of Software License Manager can be put in place, with a little bit of extra code.

In addition, a [Must Use Plugin](https://codex.wordpress.org/Must_Use_Plugins) developers can add to the WordPress installation running WP Update Plugin Server is available in `wp-plugin-update-server/optimisation/wppus-endpoint-optimizer.php`.  
It allows to bypass all plugins execution when checking for updates (or keep some with a global whitelist in an array `$wppus_always_active_plugins`).  
It also provides a global variable `$wppus_doing_update_api_request` to test in themes and control if filters and actions should be added/removed.

### Screenshots

#### General tab

<img src="https://froger.me/wp-content/uploads/2018/08/wppus-general-1.png" alt="General tab 1" width="100%">
<img src="https://froger.me/wp-content/uploads/2018/08/wppus-general-2.png" alt="General tab 2" width="100%">
<img src="https://froger.me/wp-content/uploads/2018/08/wppus-general-3.png" alt="General tab 3" width="100%">

#### Packages licensing tab

<img src="https://froger.me/wp-content/uploads/2018/08/wppus-license-1.png" alt="Packages licensing tab 1" width="100%"> 
<img src="https://froger.me/wp-content/uploads/2018/08/wppus-license-2.png" alt="Packages licensing tab 2" width="100%"> 

#### Packages remote source tab

<img src="https://froger.me/wp-content/uploads/2018/08/wppus-remote-1.png" alt="Packages remote source tab 1" width="100%"> 
<img src="https://froger.me/wp-content/uploads/2018/08/wppus-remote-2.png" alt="Packages remote source tab 2" width="100%"> 

#### Client - updates available

<img src="https://froger.me/wp-content/uploads/2018/08/wppus-client-1-e1534058639284.png" alt="Client - updates available 1" width="100%">
<img src="https://froger.me/wp-content/uploads/2018/08/wppus-client-2.png" alt="Client - updates available 2" width="100%">

## Settings

The following settings can be accessed on the WP Update Plugin Server admin page.

### General

This tab allows administrators to:
- View the list of packages currently available in WP Update Plugin Server, with Package Name, Version, Type (Plugin or Theme), File Name, Size, Last Modified and License Status 
- Download a package
- Toggle between "Require License" and "Do not Require License" for a package
- Delete a package
- Apply bulk actions on the list of packages
- Add a package (either by uploading it directly, or by pulling it forcefully from a configured remote repository)

In addition, the following settings are available:

Name                                | Type   | Description                                                                                                                  
----------------------------------- |:------:| ------------------------------------------------------------------------------------------------------------------------------
Archive max size (in MB)            | number | Maximum file size when uploading or downloading packages.
Cache max size (in MB)              | number | Maximum size in MB for the `wp-content/plugins/wp-plugin-update-server/cache` directory. If the size of the directory grows larger, its content will be deleted at next cron run (checked hourly). The size indicated in the "Force Clean" button is the real current size.
Logs max size (in MB)               | number | Maximum size in MB for the `wp-content/plugins/wp-plugin-update-server/logs` directory. If the size of the directory grows larger, its content will be deleted at next cron run (checked hourly). The size indicated in the "Force Clean" button is the real current size.

### Packages licensing

Name                                                 | Type      | Description                                                                                                                                                                                                                                                                            
---------------------------------------------------- |:---------:| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Software License Manager integration                 | checkbox  | Enables this server manages to deliver license-enabled plugins and themes using Software License Manager licenses.<br/>**It affects all the packages with a "Requires License" license status delivered by this installation of WP Plugin Update Server. Settings of the "Packages licensing" section will be saved only if this option is checked.**
HMAC Key                                             | text      | Ideally a random string, used to authenticate license signatures.<br/>**WARNING: Changing this value will invalidate all the licence signatures for current remote installations.**<br/>It is possible to grant a grace period and let webmasters deactivate and re-activate their license(s) by unchecking "Check License signature?".
Encryption Key                                       | text      | Ideally a random string, used to encrypt license signatures.<br/>**WARNING: Changing this value will invalidate all the licence signatures for current remote installations.**<br/>It is possile to grant a grace period and let webmasters deactivate and re-activate their license(s) by unchecking "Check License signature?".
License server URL (Software License Manager plugin) | text      | The URL of the server where Software License Manager plugin is installed. Must include the protocol ("http://" or "https://").<br/>If using a remote install of Software License Manager plugin, the validity of the server URL will be checked regularly by checking the existance of a transient `wppus_valid_license_server`.<br/>If using a remote install of Software License Manager plugin, see `wp-content/plugins/wp-plugin-update-server/integration-examples/remote-slm.php`.
Check License signature?                             | checkbox  | Check signatures - can be deactivated if the HMAC Key or the Encryption Key has been recently changed and remote installations have active licenses.<br/>Typically, all webmasters would have to deactivate and re-activate their license(s) to re-build their signatures, and this could take time ; it allows to grant a grace period during which license checking is less strict to avoid conflicts.

### Packages remote source

Name                                  | Type      | Description                                                                                                                                                                                                                    
------------------------------------- |:---------:|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Use remote repository service         | checkbox  | Enables this server to download plugins and themes from a remote repository before delivering updates.<br/>Supports Bitbucket, Github and Gitlab.<br/>If left unchecked, zip packages need to be manually uploaded to `wp-content/plugins/wp-plugin-update-server/packages`.<br/>**It affects all the packages delivered by this installation of WP Plugin Update Server if they have a corresponding repository in the remote repository service.**<br/>**Settings of the "Packages remote source" section will be saved only if this option is checked.**
Remote repository service URL         | text      | The URL of the remote repository service where packages are hosted.<br/>Must follow the following pattern: `https://repository-service.tld/username` where `https://repository-service.tld` may be a self-hosted instance of Gitlab.<br/>Each package repository URL must follow the following pattern: `https://repository-service.tld/username/package-name/` ; the package files must be located at the root of the repository, and in the case of plugins the main plugin file must follow the pattern `package-name.php`.
Self-hosted remote repository service | checkbox  | Check this only if the remote repository service is a self-hosted instance of Gitlab.
Packages branch name                  | text      | The branch to download when getting remote packages from the remote repository service.
Remote repository service credentials | text      | Credentials for non-publicly accessible repositories.<br/>In the case of Github and Gitlab, an access token (`token`).<br/>In the case of Bitbucket, the Consumer key and secret separated by a pipe (`consumer_key|consumer_secret`). IMPORTANT: when creating the consumer, "This is a private consumer" must be checked.	
Remote update check frequency         | select    | How often WP Plugin Update Server will poll each remote repository for package updates - checking too often may slow down the server (recommended "Once Daily").

## Help

The following can also be found under the "Help" tab of the WP Update Plugin Server admin page.  

### Requirements to add update checker to plugins and themes (and possibly provide license support)

To link your packages to WP Plugin Update Server, and maybe to prevent webmasters from getting updates of your plugins and themes unless they have a license, your plugins and themes need to include some extra code. It is a simple matter of adding a few lines in the main plugin file (for plugins) or in the functions.php file (for themes), and provide the necessary libraries in a lib directory at the root of the package.  

An example of plugin is available in `wp-content/plugins/wp-plugin-update-server/integration-examples/dummy-plugin`, and an example of theme is available in `wp-content/plugins/wp-plugin-update-server/integration-examples/dummy-theme`.  

Unless "Use remote repository service" is checked in "Packages remote source", you need to manually upload the packages zip archives (and subsequent updates) in `wp-content/plugins/wp-plugin-update-server/packages`. Packages need to be valid WordPress plugin or theme packages, and in the case of a plugin the main plugin file must have the same name as the zip archive. For example, the main plugin file in `package-name.zip` would be `package-name.php`.  

When adding package licenses in Software License Manager, each license must have its "Product Reference" field set to `package-name/package-name.php` for a plugin, or `package-name/functions.php` for a theme.  

### Requests optimisation

When the remote clients where your plugins and themes are installed send a request to check for updates or download a package, this server's WordPress installation is loaded, with its own plugins and themes. This is not optimised because unnecessary action and filter hooks that execute before `parse_request` action hook are also triggered, even though the request is not designed to produce any output.

To solve this for plugins, you can place `wp-content/plugins/wp-plugin-update-server/optimisation/wppus-endpoint-optimiser.php` in `wp-content/mu-plugins/wppus-endpoint-optimiser.php`. This will effectively create a [Must Use Plugin](https://codex.wordpress.org/Must_Use_Plugins) that runs before everything else and prevents other plugins from being executed when a request is received by WP Plugin Update Server.

You may edit the variable `$wppus_always_active_plugins` of the MU Plugin file to allow some plugins to run anyway.

**IMPORTANT - This MU Plugin does not prevent theme hooks registered before `parse_request` action hook from being fired.**  
To solve this for themes, a few code changes are necessary.  
The MU Plugin provides a global variable `$wppus_doing_update_api_request` that can be tested when adding hooks and filters:

- Use the global variable in a **main theme's `functions.php` to test if current theme's hooks should be added.**
- Use the global variable in a **child theme's `functions.php` to remove action and filter hooks from the parent theme AND test if current theme's hooks should be added.**

### Remote license server integration

WP Plugin Update Server can work with Software License Manager running on a separate installation of WordPress.  
WP Plugin Update Server uses an extra parameter `license_signature` containing license information, in particular the registered domain, encrypted with Open SSL for extra security when checking licenses.  
When running on the same installation, a filter `slm_ap_response_args` is added, but it cannot run if Software License Manager is installed remotely ; this means the remote installation needs to take care of adding and running this filter.

An example of filter implementation is available in `wp-content/plugins/wp-plugin-update-server/integration-examples/remote-slm.php` for you to add in the code base of the remote WordPress installation running the Software License Manager plugin. You may add your code in a theme's `functions.php` file or build an extra plugin around it.

### More help...

For more help on how to use WP Plugin Update Server, please open an issue on Github or contact wppus-help@froger.me.  
Depending on the nature of the request, a fee may apply.