# WP Plugin Update Server - Packages - Developer documentation
(Looking for the main documentation page instead? [See here](https://github.com/froger-me/wp-plugin-update-server/blob/master/README.md))  

WP Plugin Update Server offers a series of functions, actions and filters for developers to use in their own plugins and themes to modify the behavior of the plugin when managing packages.  

* [Functions](#user-content-functions)
	* [wppus_get_root_data_dir](#user-content-wppus_get_root_data_dir)
	* [wppus_get_packages_data_dir](#user-content-wppus_get_packages_data_dir)
	* [wppus_get_logs_data_dir](#user-content-wppus_get_logs_data_dir)
	* [wppus_is_doing_update_api_request](#user-content-wppus_is_doing_update_api_request)
	* [wppus_check_remote_plugin_update](#user-content-wppus_check_remote_plugin_update)
	* [wppus_check_remote_theme_update](#user-content-wppus_check_remote_theme_update)
	* [wppus_download_remote_plugin_to_local](#user-content-wppus_download_remote_plugin_to_local)
	* [wppus_download_remote_theme_to_local](#user-content-wppus_download_remote_theme_to_local)
	* [wppus_force_cleanup_cache](#user-content-wppus_force_cleanup_cache)
	* [wppus_force_cleanup_logs](#user-content-wppus_force_cleanup_logs)
	* [wppus_force_cleanup_tmp](#user-content-wppus_force_cleanup_tmp)
	* [wppus_get_local_package_path](#user-content-wppus_get_local_package_path)
	* [wppus_download_local_package](#user-content-wppus_download_local_package)
* [Actions](#user-content-actions)
	* [wppus_primed_package_from_remote](#user-content-wppus_primed_package_from_remote)
	* [wppus_did_manual_upload_package](#user-content-wppus_did_manual_upload_package)
	* [wppus_before_packages_download](#user-content-wppus_before_packages_download)
	* [wppus_triggered_package_download](#user-content-wppus_triggered_package_download)
	* [wppus_scheduled_check_remote_event](#user-content-wppus_scheduled_check_remote_event)
	* [wppus_registered_check_remote_schedule](#user-content-wppus_registered_check_remote_schedule)
	* [wppus_cleared_check_remote_schedule](#user-content-wppus_cleared_check_remote_schedule)
	* [wppus_scheduled_cleanup_event](#user-content-wppus_scheduled_cleanup_event)
	* [wppus_registered_cleanup_schedule](#user-content-wppus_registered_cleanup_schedule)
	* [wppus_cleared_cleanup_schedule](#user-content-wppus_cleared_cleanup_schedule)
	* [wppus_did_cleanup](#user-content-wppus_did_cleanup)
	* [wppus_before_handle_update_request](#user-content-wppus_before_handle_update_request)
	* [wppus_downloaded_remote_package](#user-content-wppus_downloaded_remote_package)
	* [wppus_saved_remote_package_to_local](#user-content-wppus_saved_remote_package_to_local)
	* [wppus_checked_remote_package_update](#user-content-wppus_checked_remote_package_update)
	* [wppus_deleted_package](#user-content-wppus_deleted_package)
	* [wppus_registered_renew_download_url_token_schedule](#user-content-wppus_registered_renew_download_url_token_schedule)
	* [wppus_cleared_renew_download_url_token_schedule](#user-content-wppus_cleared_renew_download_url_token_schedule)
	* [wppus_scheduled_renew_download_url_token_event](#user-content-wppus_scheduled_renew_download_url_token_event)
	* [wppus_before_zip](#user-content-wppus_before_zip)
* [Filters](#user-content-filters)
	* [wppus_submitted_data_config](#user-content-wppus_submitted_data_config)
	* [wppus_submitted_remote_sources_config](#user-content-wppus_submitted_remote_sources_config)
	* [wppus_schedule_cleanup_frequency](#user-content-wppus_schedule_cleanup_frequency)
	* [wppus_schedule_renew_download_url_token_frequency](#user-content-wppus_schedule_renew_download_url_token_frequency)
	* [wppus_check_remote_frequency](#user-content-wppus_check_remote_frequency)
	* [wppus_handle_update_request_params](#user-content-wppus_handle_update_request_params)
	* [wppus_update_api_config](#user-content-wppus_update_api_config)
	* [wppus_update_server](#user-content-wppus_update_server)
	* [wppus_update_checker](#user-content-wppus_update_checker)

___
## Functions

The functions listed below are made publicly available by the plugin for theme and plugin developers. They can be used after the action `plugins_loaded` has been fired, or in a `plugins_loaded` action (just make sure the priority is above `-99`).  
Although the main classes can theoretically be instanciated without side effect if the `$hook_init` parameter is set to `false`, it is recommended to use only the following functions as there is no guarantee future updates won't introduce changes of behaviors.

___
#### wppus_get_root_data_dir

```php
wppus_get_root_data_dir();
```

**Description**  
Get the path to the plugin's content directory.

**Return value**
> (string) the path to the plugin content's directory.


___
#### wppus_get_packages_data_dir

```php
wppus_get_packages_data_dir();
```

**Description**  
Get the path to the packages directory on the WordPress file system.

**Return value**
> (string) the path to the packages directory on the WordPress file system.


___
#### wppus_get_logs_data_dir

```php
wppus_get_logs_data_dir();
```

**Description**  
Get the path to the plugin's log directory.

**Return value**
> (string) the path to the plugin's log directory.


___
#### wppus_is_doing_update_api_request

```php
wppus_is_doing_update_api_request();
```

**Description**  
Determine wether the current request is made by a client plugin or theme interacting with the plugin's API.

**Return value**
> (bool) true if the current request is a client plugin or theme interacting with the plugin's API, false otherwise.


___
#### wppus_check_remote_plugin_update

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
#### wppus_check_remote_theme_update

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
#### wppus_download_remote_plugin_to_local

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
#### wppus_download_remote_theme_to_local

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
#### wppus_force_cleanup_cache

```php
wppus_force_cleanup_cache();
```

**Description**  
Force clean up the `cache` plugin data.

**Return value**
> (bool) true in case of success, false otherwise


___
#### wppus_force_cleanup_logs

```php
wppus_force_cleanup_logs();
```

**Description**  
Force clean up the `logs` plugin data.

**Return value**
> (bool) true in case of success, false otherwise


___
#### wppus_force_cleanup_tmp

```php
wppus_force_cleanup_tmp();
```

**Description**  
Force clean up the `tmp` plugin data.

**Return value**
> (bool) true in case of success, false otherwise


___
#### wppus_get_local_package_path

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
#### wppus_download_local_package

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
## Actions

WP Plugin Update Server gives developers the possibility to have their plugins react to some events with a series of custom actions.  
**Warning**: the actions below with the mention "Fired during client update API request" need to be used with caution. Although they may also be triggered when using the functions above, these actions will possibly be called when client packages request for updates. Registering functions doing heavy computation to these actions when client update API requests are handled can seriously degrade the server's performances.  

___
#### wppus_primed_package_from_remote

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
#### wppus_did_manual_upload_package

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
#### wppus_before_packages_download

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
#### wppus_triggered_package_download

```php
do_action( 'wppus_triggered_package_download', string $archive_name, string $archive_path );
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
#### wppus_scheduled_check_remote_event

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
> (string) frequency at which the event would be ran  

$hook  
> (string) event hook to fire when the event is ran  

$params  
> (array) parameters passed to the actions registered to $hook when the event is ran  

___
#### wppus_registered_check_remote_schedule

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
#### wppus_cleared_check_remote_schedule

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
#### wppus_scheduled_cleanup_event

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
> (string) frequency at which the event would be ran  

$hook
> (string) event hook to fire when the event is ran  

$params
> (array) parameters passed to the actions registered to $hook when the event is ran  

___
#### wppus_registered_cleanup_schedule

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
#### wppus_cleared_cleanup_schedule

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
#### wppus_did_cleanup

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
#### wppus_before_handle_update_request

```php
do_action( 'wppus_before_handle_update_request', array $request_params );
```

**Description**  
Fired before handling the request made by a client plugin or theme to the plugin's API.  
Fired during client update API request.  

**Parameters**
$request_params  
> (array) the parameters or the request to the API.

___
#### wppus_downloaded_remote_package

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
#### wppus_saved_remote_package_to_local

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
#### wppus_checked_remote_package_update

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
#### wppus_deleted_package

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
#### wppus_registered_renew_download_url_token_schedule


```php
do_action( 'wppus_registered_renew_download_url_token_schedule', array $scheduled_hook );
```

**Description**  
Fired after the renew download url action has been registered.  

**Parameters**  
$scheduled_hook
> (string) the renew dowload url token event hook that has been registered  

___
#### wppus_cleared_renew_download_url_token_schedule


```php
do_action( 'wppus_cleared_renew_download_url_token_schedule', array $scheduled_hook );
```

**Description**  
Fired after the renew download url token event has been unscheduled.   

**Parameters**  
$scheduled_hook
> (string) the renew dowload url token event hook that has been unscheduled  

___
#### wppus_scheduled_renew_download_url_token_event


```php
do_action( 'wppus_scheduled_renew_download_url_token_event', bool $result, int $timestamp, string $frequency, string $hook );
```

**Description**  
Fired after the renew download url token event has been scheduled.  

**Parameters**  
$result  
> (bool) true if the event was scheduled, false otherwise  

$timestamp  
> (int) timestamp for when to run the event the first time after it's been scheduled  

$frequency  
> (string) frequency at which the event would be ran  

$hook  
> (string) event hook to fire when the event is ran  

$params  
> (array) parameters passed to the actions registered to $hook when the event is ran  

___
#### wppus_before_zip


```php
do_action( 'wppus_before_remote_package_zip', (string) $package_slug, (string) $files_path, (string) $archive_path );
```

**Description**  
Fired before packing the files received from the remote repository. Can be used for extra files manipulation.  
Fired during client update API request.  

**Parameters**  
$package_slug
> (string) the slug of the package  

$files_path
> (string) the path of the directory where the package files are located  

$archive_path
> (string) the path where the package archive will be located after packing  

___
## Filters

WP Plugin Update Server gives developers the possibility to customise its behavior with a series of custom filters.  
**Warning**: the filters below with the mention "Fired during client update API request" need to be used with caution. Although they may be triggered when using the functions above, these filters will possibly be called when client packages request for updates. Registering functions doing heavy computation to these filters when client update API requests are handled can seriously degrade the server's performances.  

___
#### wppus_submitted_data_config

```php
apply_filters( 'wppus_submitted_data_config', array $config );
```

**Description**  
Filter the submitted plugin data configuration values before saving them.  

**Parameters**  
$config
> (array) the submitted plugin data configuration values

___
#### wppus_submitted_remote_sources_config

```php
apply_filters( 'wppus_submitted_remote_sources_config', array $config );
```

**Description**  
Filter the submitted remote sources configuration values before saving them.  

**Parameters**  
$config
> (array) the submitted remote sources configuration values

___
#### wppus_schedule_cleanup_frequency

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
#### wppus_schedule_renew_download_url_token_frequency

```php
apply_filters( 'wppus_schedule_renew_download_url_token_frequency', string $frequency );
```

**Description**  
Filter the renew download url token frequency. 

**Parameters**  
$frequency
> (string) the frequency - default 'daily'  

___
#### wppus_check_remote_frequency

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
#### wppus_handle_update_request_params

```php
apply_filters( 'wppus_handle_update_request_params' , array $params );
```

**Description**  
Filter the parameters used to handle the request made by a client plugin or theme to the plugin's API.  
Fired during client update API request.  

**Parameters**  
$params
> (array) the parameters of the request to the API  


___
#### wppus_update_api_config

```php
apply_filters( 'wppus_update_api_config', array $config );
```

**Description**  
Filter the update API configuration values before using them.  
Fired during client update API request.  

**Parameters**  
$config
> (array) the update api configuration values  


___
#### wppus_update_server

```php
apply_filters( 'wppus_update_server', mixed $update_server, array $config, string $slug, mixed $use_license );
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

$use_license
> (mixed) true if the corresponding package needs a license, false if it doesn't, null if irrelevant when the object is used locally  


___
#### wppus_update_checker

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
