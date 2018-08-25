# WP Update Plugin Server - Run your own update server for plugins and themes

* [General description](#user-content-general-description)
	* [Overview](#user-content-overview)
	* [Special Thanks](#user-content-special-thanks)
	* [Screenshots](#user-content-screenshots)
* [Settings](#user-content-settings)
	* [General Settings](#user-content-general-settings)
	* [Packages licensing](#user-content-packages-licensing)
	* [Packages remote source](#user-content-packages-remote-source)
* [Help](#user-content-help)
	* [Requirements to add update checker to plugins and themes (and possibly provide license support)](#user-content-requirements-to-add-update-checker-to-plugins-and-themes-and-possibly-provide-license-support)
	* [Requests optimisation](#user-content-requests-optimisation)
	* [Remote license server integration](#user-content-remote-license-server-integration)
	* [More Help...](#user-content-more-help)
* [Functions](#user-content-functions)
* [Hooks - actions & filters](#user-content-hooks---actions--filters)
	* [Actions](#user-content-actions)
	* [Filters](#user-content-filters)

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
* **Packages remote source:** host the packages on a remote repository. WP Plugin Update Server acts as a proxy and checks for packages updates regulary and downloads them automatically when a new version is available. Supports Bitbucket, Github and Gitlab, as well as self-hosted installations of Gitlab.

To connect their plugins or themes and WP Plugin Update Server, developers can find integration examples in `wp-plugin-update-server/integration-examples`:
* **Dummy Plugin:** a folder `dummy-plugin` with a simple, empty plugin that includes the necessary code in the `dummy-plugin.php` main plugin file and the necessary libraries in a `lib` folder.
* **Dummy Theme:** a folder `dummy-theme` with a simple, empty child theme of Twenty Seventeen that includes the necessary code in the `functions.php` file and the necessary libraries in a `lib` folder.
* **Remote Software License Manager** (discouraged) **:** a file `remote-slm.php` demonstrating how a remote installation of Software License Manager can be put in place, with a little bit of extra code.

In addition, a [Must Use Plugin](https://codex.wordpress.org/Must_Use_Plugins) developers can add to the WordPress installation running WP Update Plugin Server is available in `wp-plugin-update-server/optimisation/wppus-endpoint-optimizer.php`.  
It allows to bypass all plugins execution when checking for updates (or keep some with a global whitelist in an array `$wppus_always_active_plugins`).  
It also provides a global variable `$wppus_doing_update_api_request` to test in themes and control if filters and actions should be added/removed.

### Screenshots

Note: the screenshots are updated on a regular basis, but the actual interface may vary slightly.

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

### General settings

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

To link your packages to WP Plugin Update Server, and maybe to prevent webmasters from getting updates of your plugins and themes unless they have a license, your plugins and themes need to include some extra code. It is a simple matter of adding a few lines in the main plugin file (for plugins) or in the functions.php file (for themes), and provide the necessary libraries in a lib directory at the root of the package - see [WP Package Updater](https://github.com/froger-me/wp-package-updater) for more information.  

An example of plugin is available in `wp-content/plugins/wp-plugin-update-server/integration-examples/dummy-plugin`, and an example of theme is available in `wp-content/plugins/wp-plugin-update-server/integration-examples/dummy-theme`.  

Unless "Use remote repository service" is checked in "Packages remote source", you need to manually upload the packages zip archives (and subsequent updates) in `wp-content/wppus/packages`. Packages need to be valid WordPress plugin or theme packages, and in the case of a plugin the main plugin file must have the same name as the zip archive. For example, the main plugin file in `package-name.zip` would be `package-name.php`.  

When adding package licenses in Software License Manager, each license must have its "Product Reference" field set to `package-name/package-name.php` for a plugin, or `package-name/functions.php` for a theme.  

### Requests optimisation

When the remote clients where your plugins and themes are installed send a request to check for updates or download a package, this server's WordPress installation is loaded, with its own plugins and themes. This is not optimised because unnecessary action and filter hooks that execute before `parse_request` action hook also triggered, even though the request is not designed to produce any output or further computation.

To solve this for plugins, you can place `wp-content/plugins/wp-plugin-update-server/optimisation/wppus-endpoint-optimiser.php` in `wp-content/mu-plugins/wppus-endpoint-optimiser.php`. This will effectively create a [Must Use Plugin](https://codex.wordpress.org/Must_Use_Plugins) that runs before everything else and prevents other plugins from being executed when a request is received by WP Plugin Update Server.

You may edit the variable `$wppus_always_active_plugins` of the MU Plugin file to allow some plugins to run anyway.

**IMPORTANT - This MU Plugin does not prevent theme hooks registered before `parse_request` action hook from being fired.**  
To solve this for themes, a few code changes are necessary.  
The MU Plugin provides a global variable `$wppus_doing_update_api_request` that can be tested when adding hooks and filters:

- Use the global variable in a **main theme's `functions.php` to test if current theme's hooks should be added.**
- Use the global variable in a **child theme's `functions.php` to remove action and filter hooks from the parent theme AND test if current theme's hooks should be added.**

### Remote license server integration

WP Plugin Update Server can work with Software License Manager running on a separate installation of WordPress.  
WP Plugin Update Server uses an extra parameter license_signature containing license information, in particular the registered domain, encrypted with Open SSL for extra security when checking licenses.  
When running on the same installation, a filter `slm_ap_response_args` is added, but it cannot run if Software License Manager is installed remotely ; this means the remote installation needs to take care of adding and running this filter.  

**IMPORTANT - Software License Manager integration is planned to be dropped in v1.4 in favor of handling licenses directly in WP Plugin Update Server.** This is to avoid maintaining integration with a potentially unreliable third party. Upgrade path will be seamless likely only for local installations of Software License Manager, so it is recommended NOT to use this feature unless absolutely necessary. The license API provided in v1.4 will be fully compatible with calls made to a local installation of Software License Manager to ensure backward compatibility.  

If you really need to use a remote server integration, an example of filter implementation is available in `wp-content/plugins/wp-plugin-update-server/integration-examples/remote-slm.php` for you to add in the code base of the remote WordPress installation running the Software License Manager plugin. You may add your code in a theme's `functions.php` file or build an extra plugin around it.

### More help...

For more help on how to use WP Plugin Update Server, please open an issue on Github or contact wppus-help@froger.me.  
Depending on the nature of the request, a fee may apply.

## Functions

The functions listed below are made publicly available by the plugin for theme and plugin developers. They can be used after the action `plugins_loaded` has been fired, or in a `plugins_loaded` action with priority of `6` and above.  
Although the main classes can theoretically be instanciated without side effect if the `$hook_init` parameter is set to `false`, it is recommended to use only the following functions as there is no guarantee future updates won't introduce changes of behaviors.

```php
wppus_get_root_data_dir();
```

**Description**  
Get the path to the plugin's content directory.

**Return value**
> (string) the path to the plugin content's directory.
___

```php
wppus_get_packages_data_dir();
```

**Description**  
Get the path to the packages directory on the WordPress file system.

**Return value**
> (string) the path to the packages directory on the WordPress file system.
___

```php
wppus_get_logs_data_dir();
```

**Description**  
Get the path to the plugin's log directory.

**Return value**
> (string) the path to the plugin's log directory.
___

```php
wppus_is_doing_update_api_request();
```

**Description**  
Determine wether the current request is made by a client plugin or theme interacting with the plugin's API.

**Return value**
> (bool) true if the current request is a client plugin or theme interacting with the plugin's API, false otherwise.
___

```php
wppus_check_remote_plugin_update( string $package_slug );
```

**Description**  
Determine wether the remote plugin package is an updated version compared to one on the WordPress file system.

**Parameters**  
$package_slug
> (string) slug of the plugin package to check  

**Return value**
> (bool) true if the remote plugin package is an updated version, false otherwise. If the local package does not exist, returns true.
___

```php
wppus_check_remote_theme_update( string $package_slug );
```

**Description**  
Determine wether the remote theme package is an updated version compared to the one on the WordPress file system.

**Parameters**  
$package_slug
> (string) slug of the theme package to check   

**Return value**
> (bool) true if the remote theme package is an updated version, false otherwise. If the package does not exist on the WordPress file system, returns true.
___

```php
wppus_download_remote_plugin_to_local( string $package_slug );
```

**Description**  
Download a plugin package from the remote repository down to the package directory on the WordPress file system.

**Parameters**  
$package_slug
> (string) slug of the plugin package to download  

**Return value**
> (bool) true if the plugin package was successfully downloaded, false otherwise.
___

```php
wppus_download_remote_theme_to_local( string $package_slug );
```

**Description**  
Download a theme package from the remote repository down to the package directory on the WordPress file system.

**Parameters**  
$package_slug
> (string) slug of the theme package to download  

**Return value**
> (bool) true if the theme package was successfully downloaded, false otherwise.
___

```php
wppus_force_clean_up_cache();
```

**Description**  
Force clean up the `cache` plugin data.

**Return value**
> (bool) true in case of success, false otherwise
___

```php
wppus_force_clean_up_logs();
```

**Description**  
Force clean up the `logs` plugin data.

**Return value**
> (bool) true in case of success, false otherwise
___

```php
wppus_force_clean_up_tmp();
```

**Description**  
Force clean up the `tmp` plugin data.

**Return value**
> (bool) true in case of success, false otherwise
___

```php
wppus_get_local_package_path( string $package_slug );
```

**Description**  
Get the path of a plugin or theme package on the WordPress file system

**Parameters**  
$package_slug
> (string) slug of the package  

**Return value**
> (string) path of the package on the WordPress file system
___

```php
wppus_download_local_package( string $package_slug, string $package_path = null );
```

**Description**  
Start a download of a package from the WordPress file system and exits. 

**Parameters**  
$package_slug  
> (string) slug of the package  

$package_path  
> (string) path of the package on the WordPress file system - if `null`, will attempt to find it using `wppus_get_local_package_path( $package_slug )`   
___


## Hooks - actions & filters

WP Plugin Update Server gives developers the possibilty to customise its behavior with a series of custom actions and filters.  
**Warning**: the hooks below with the mention "Fired during client update API request" need to be used with caution. Although they may be triggered when using the functions above, these hooks will be called when client packages request for updates. Registering functions doing heavy computation to these hooks when client update API requests are handled can seriously degrade the server's performances.  

### Actions

```php
do_action( 'wppus_primed_package_from_remote', bool $result, string $slug );
```

**Description**  
Fired after an attempt to prime a package from a remote repository has been performed.  

**Parameters**  
$result  
> (bool) true the operation was successful, false otherwise  

$slug  
> (slug) slug of the package  
___

```php
do_action( 'wppus_did_manual_upload_package', bool $result, string $type, string $slug );
```

**Description**  
Fired after an attempt to upload a package manually has been performed.  

**Parameters**  
$result  
> (bool) true the operation was successful, false otherwise  

$type  
> (string) type of package - "Plugin" or "Theme"  

$slug  
> (slug) slug of the package  
___

```php
do_action( 'wppus_before_packages_download', string $archive_name, string $archive_path, array $package_slugs );
```

**Description**  
Fired before a packages download using the "Download" bulk action is triggered.  

**Parameters**  
$archive_name
> (string) the name of the archive containing the packages to be downloaded  

$archive_path
> (string) the path of the archive containing the packages to be downloaded  

$package_slugs
> (string) the slug of the packages to be downloaded  
___

```php
do_action( 'wppus_triggered_archive_download', string $archive_name, string $archive_path );
```

**Description**  
Fired after a download of an archive containing one or multiple packages has been triggered.  
Remark: at this point, headers have already been sent to the client of the request and registered actions should not produce any output.  

**Parameters**  
$archive_name
> (string) the name of the archive containing the packages to be downloaded  

$archive_path
> (string) the path of the archive containing the packages to be downloaded  
___

```php
do_action( 'wppus_added_license_check', string $package_slug );
```

**Description**  
Fired after a package was marked as "Requires License".  

**Parameters**  
> (string) the slug of the package
___

```php
do_action( 'wppus_removed_license_check', string $package_slug );
```

**Description**  
Fired after a package was marked as "Does not Require License".  

**Parameters**  
> (string) the slug of the package
___


```php
do_action( 'wppus_scheduled_check_remote_event', bool $result, string $slug, int $timestamp, string $frequency, string $hook, array $params );
```

**Description**  
Fired after a remote check event has been scheduled for a package.  
Fired during client update API request.  

**Parameters**  
$result  
> (bool) true if the event was scheduled, false otherwise  

$slug  
> (string) slug of the package for which the event was scheduled  

$timestamp  
> (int) timestamp for when to run the event the first time after it's been scheduled  

$frequency  
> (string) frequency at which the even would be ran  

$hook  
> (string) event hook to fire when the event is ran  

$params  
> (array) parameters passed to the actions registered to $hook when the event is ran  
___

```php
do_action( 'wppus_registered_check_remote_schedule', string $slug, string $scheduled_hook, string $action_hook );
```

**Description**  
Fired after a remote check action has been registered for a package.  
Fired during client update API request.  

**Parameters**  
$slug
> (string) the slug of the package for which an action has been registered  

$scheduled_hook
> (string) the event hook the action has been registered to (see `wppus_scheduled_check_remote_event` action)  

$action_hook
> (string) the action that has been registered  
___

```php
do_action( 'wppus_cleared_check_remote_schedule', string $slug, string $scheduled_hook, array $params );
```

**Description**  
Fired after a remote check schedule event has been unscheduled for a package.  
Fired during client update API request.  

**Parameters**  
$slug
> (string) the slug of the package for which a remote check event has been unscheduled  

$scheduled_hook
> (string) the remote check event hook that has been unscheduled  

$params
> (array) the parameters that were passed to the actions registered to the remote check event    
___

```php
do_action( 'wppus_scheduled_cleanup_event', bool $result, string $type, int $timestamp, string $frequency, string $hook, array $params );
```

**Description**  
Fired after a cleanup event has been scheduled for a type of plugin data.  

**Parameters**  
$result
> (bool) true if the event was scheduled, false otherwise  

$type
> (string) plugin data type for which the event was scheduled (`cache`, `logs`,or `tmp`)  

$timestamp
> (int) timestamp for when to run the event the first time after it's been scheduled  

$frequency
> (string) frequency at which the even would be ran  

$hook
> (string) event hook to fire when the event is ran  

$params
> (array) parameters passed to the actions registered to $hook when the event is ran  
___

```php
do_action( 'wppus_registered_cleanup_schedule', string $type, array $params );
```

**Description**  
Fired after a cleanup action has been registered for a type of plugin data.  

**Parameters**  
$type
> (string) plugin data type for which or which an action has been registered (`cache`, `logs`,or `tmp`)  

$params
> (array) the parameters passed to the registered cleanup action  
___

```php
do_action( 'wppus_cleared_cleanup_schedule', string $type, array $params );
```

**Description**  
Fired after a cleanup schedule event has been unscheduled for a type of plugin data.  

**Parameters**  
$slug
> (string) plugin data type for which a cleanup event has been unscheduled (`cache`, `logs`,or `tmp`)  

$params
> (array) the parameters that were passed to the actions registered to the cleanup event  
___

```php
do_action( 'wppus_did_cleanup', bool $result, string $type, int $size, bool $force );
```

**Description**  
Fired after cleanup was attempted for a type of plugin data.  

**Parameters**  
$result
> (bool) true if the clean up was successful, flase otherwise  

$type
> (string) type of data to clean up (`cache`, `logs`,or `tmp`)  

$size
> (int) the size of the data in before the clean up attempt (in bytes)  

$force
> (bool) true if it was a forced cleanup (without checking if the size was beyond the maximum set size), false otherwise  
___

```php
do_action( 'wppus_before_handle_request', array $request_params );
```

**Description**  
Fired before handling the request made by a client plugin or theme to the plugin's API.  
Fired during client update API request.  

**Parameters**
$request_params  
> (array) the parameters or the request to the API.
___

```php
do_action( 'wppus_downloaded_remote_package', mixed $package, string $type, string $slug );
```

**Description**  
Fired after an attempt to download a package from the remote repository service down to the WordPress file system has been performed.  
Fired during client update API request.  

**Parameters**  
$package
> (mixed) full path to the package temporary file in case of success, WP_Error object otherwise  

$type
> (string) type of the downloaded package - "Plugin" or "Theme"  

$slug
> (string) slug of the downloaded package  
___

```php
do_action( 'wppus_saved_remote_package_to_local', bool $result, string $type, string $slug );
```

**Description**  
Fired after an attempt to save a downloaded package on the WordPress file system hase been performed.  
Fired during client update API request.  

**Parameters**  
$result
> (bool) true in case of success, false otherwise  

$type
> (string) type of the saved package - "Plugin" or "Theme"  

$slug
> (string) slug of the saved package  
___

```php
do_action( 'wppus_checked_remote_package_update', bool $has_update, string $type, string $slug );
```

**Description**  
Fired after an update check on the remote repository has been performed for a package.  
Fired during client update API request.  

**Parameters**  
$has_update
> (bool) true is the package has updates on the remote repository, false otherwise  

$type
> (string) type of the package checked - "Plugin" or "Theme"  

$slug
> (string) slug of the package checked  
___

```php
do_action( 'wppus_deleted_package', bool $result, string $type, string $slug );
```

**Description**  
Fired after a package has been deleted from the WordPress file system.  
Fired during client update API request.  

**Parameters**  
$result
> (bool) true if the package has been deleted on the WordPress file system  

$type
> (string) type of the deleted package - "Plugin" or "Theme"  

$slug
> (string) slug of the deleted package  
___


### Filters

```php
apply_filters( 'wppus_submitted_config', array $config );
```

**Description**  
Filter the submitted plugin configuration values before saving them.  

**Parameters**  
$config
> (array) the submitted plugin configuration values
___

```php
apply_filters( 'wppus_schedule_cleanup_frequency', string $frequency, string $type );
```

**Description**  
Filter the cleanup frequency. 

**Parameters**  
$frequency
> (string) the frequency - default 'hourly'  

$type
> (string) plugin data type to be clened up (`cache`, `logs`,or `tmp`)  
___

```php
apply_filters( 'wppus_check_remote_frequency', string $frequency, string $slug );
```

**Description**  
Filter the package update remote check frequency set in the configuration.  
Fired during client update API request.  

**Parameters**  
$frequency
> (string) the frequency set in the configuration  

$slug
> (string) the slug of the package to check for updates  
___

```php
apply_filters( 'wppus_handle_request_params' , array $params );
```

**Description**  
Filter the parameters used to handle the request made by a client plugin or theme to the plugin's API.  
Fired during client update API request.  

**Parameters**  
$params
> (array) the parameters of the request to the API  
___

```php
apply_filters( 'wppus_config', array $config );
```

**Description**  
Filter the plugin configuration values before using it.  
Fired during client update API request.  

**Parameters**  
$config
> (array) the plugin configuration values  
___

```php
apply_filters( 'wppus_licensed_package_slugs', array $package_slugs );
```

**Description**  
Filter the slugs of packages requiring a license.  
Fired during client update API request.  

**Parameters**  
$package_slugs
> (array) the slugs of packages requiring a license  
___

```php
apply_filters( 'wppus_update_server', mixed $update_server, array $config, string $slug, bool $use_license_server );
```

**Description**  
Filter the Wppus_Update_Server object to use.  
Fired during client update API request.  

**Parameters**  
$update_server
> (mixed) the Wppus_Update_Server object  

$config
> (array) the configuration values passed to the Wppus_Update_Server object  

$slug
> (string) the slug of the package using the Wppus_Update_Server object  

$use_license_server
> (bool) true if the corresponding package needs a license, false otherwise  
___

```php
apply_filters( 'wppus_update_checker', mixed $update_checker, string $slug, string $type, string $package_file_name, string $repository_service_url, string $repository_branch, mixed $repository_credentials, bool $repository_service_self_hosted );
```

**Description**  
Filter the checker object used to perform remote checks and downloads.  
Fired during client update API request.  

**Parameters**  
$update_checker
> (mixed) the checker object  

$slug
> (string) the slug of the package using the checker object  

$type
> (string) the type of the package using the checker object - "Plugin" or "Theme" 

$package_file_name
> (string) the name of the main plugin file or "styles.css" for the package using the checker object  

$repository_service_url
> (string) URL of the repository service where the remote packages are located  

$repository_branch
> (string) the branch of the remote repository where the packages are located  

$repository_credentials
> (mixed) the credentials to access the remote repository where the packages are located  

$repository_service_self_hosted
> (bool) true if the remote repository is on a self-hosted repository service, false otherwiseark  
___
