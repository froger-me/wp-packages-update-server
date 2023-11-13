# WP Packages Update Server - Run your own update server for plugins and themes

Plugin documentation:
* [General description](#user-content-general-description)
	* [Overview](#user-content-overview)
	* [Special Thanks](#user-content-special-thanks)
	* [Compatibility](#user-content-compatibility)
	* [Screenshots](#user-content-screenshots)
* [User Interface](#user-content-user-interface)
	* [Overview Tab](#user-content-overview-tab)
	* [Remote Sources Tab](#user-content-remote-sources-tab)
	* [Licenses Tab](#user-content-licenses-tab)
* [Performances](#user-content-performances)
	* [Benchmark](#user-content-benchmark)
	* [Update API](#user-content-update-api)
	* [Public License API](#user-content-public-license-api)
* [Help](#user-content-help)
	* [Provide updates with WP Packages Update Server - packages requirements](#user-content-provide-updates-with-wp-packages-update-server---packages-requirements)
	* [Requests optimisation](#user-content-requests-optimisation)
	* [More Help...](#user-content-more-help)

Developer documentation:
* [Packages](https://github.com/froger-me/wp-packages-update-server/blob/master/packages.md)
	* [Functions](https://github.com/froger-me/wp-packages-update-server/blob/master/packages.md#user-content-functions)
	* [Actions](https://github.com/froger-me/wp-packages-update-server/blob/master/packages.md#user-content-actions)
	* [Filters](https://github.com/froger-me/wp-packages-update-server/blob/master/packages.md#user-content-filters)
* [Licenses](https://github.com/froger-me/wp-packages-update-server/blob/master/licenses.md)
	* [API](https://github.com/froger-me/wp-packages-update-server/blob/master/licenses.md#user-content-api)
	* [Functions](https://github.com/froger-me/wp-packages-update-server/blob/master/licenses.md#user-content-functions)
	* [Actions](https://github.com/froger-me/wp-packages-update-server/blob/master/licenses.md#user-content-actions)
	* [Filters](https://github.com/froger-me/wp-packages-update-server/blob/master/licenses.md#user-content-filters)
* [Miscellaneous](#user-content-miscellaneous)
	* [Functions](#user-content-functions)
		* [php_log](#user-content-php_log)
		* [cidr_match](#user-content-cidr_match)
		* [wppus_is_doing_api_request](#user-content-wppus_is_doing_api_request)
		* [wppus_is_doing_webhook_api_request](#user-content-wppus_is_doing_webhook_api_request)
		* [wppus_init_nonce_auth](#user-content-wppus_init_nonce_auth)
		* [wppus_create_nonce](#user-content-wppus_create_nonce)
		* [wppus_get_nonce_expiry](#user-content-wppus_get_nonce_expiry)
		* [wppus_validate_nonce](#user-content-wppus_validate_nonce)
		* [wppus_delete_nonce](#user-content-wppus_delete_nonce)
		* [wppus_clear_nonces](#user-content-wppus_clear_nonces)
	* [Actions](#user-content-actions)
	* [Filters](#user-content-filters)

## General Description

WP Update Plugin Server allows developers to provide updates for plugins and themes packages not hosted on wordpress.org, and possibly control the updates with the application of a license on the client packages. It is also useful to provide updates for plugins or themes not compliant with the GPLv2 (or later).
Packages may be either uploaded directly, or hosted in a Remote Repository, public or private. It supports Bitbucket, Github and Gitlab, as well as self-hosted installations of Gitlab.

To install, clone this repository and copy the "wp-packages-update-server" directory into your plugin folder.

### Overview

This plugin adds the following major features to WordPress:

* **WP Update Plugin Server admin page:** to manage the list of packages and configure the plugin.
* **Package management:** to manage update packages, showing a listing with Package Name, Version, Type, File Name, Size, Last Modified and License Status ; includes bulk operations to delete, download and change the license status, and the ability to delete all the packages.
* **Add Packages:** Upload update packages from your local machine to the server, or download them to the server from a Remote Repository.
* **General settings:** for archive files download size, cache, and logs, with force clean.
* **Packages licensing:** Prevent plugins and themes installed on remote WordPress installation from being updated without a valid license. Licenses are generated automatically by default and the values are unguessable (it is recommended to keep the default). When checking the validity of licenses an extra license signature is also checked to prevent the use of a license on more than the configured allowed domains.
* **Packages remote source:** host the packages on a Remote Repository. WP Packages Update Server acts as a proxy and checks for packages updates regulary and downloads them automatically when a new version is available. Supports Bitbucket, Github and Gitlab, as well as self-hosted installations of Gitlab.

To connect their plugins or themes and WP Packages Update Server, developers can find integration examples in `wp-packages-update-server/integration-examples`:
* **Dummy Plugin:** a folder `dummy-plugin` with a simple, empty plugin that includes the necessary code in the `dummy-plugin.php` main plugin file and the necessary libraries in a `lib` folder.
* **Dummy Theme:** a folder `dummy-theme` with a simple, empty child theme of Twenty Seventeen that includes the necessary code in the `functions.php` file and the necessary libraries in a `lib` folder.

In addition, requests to the various APIs are optimised with a customisable [Must Use Plugin](https://codex.wordpress.org/Must_Use_Plugins) automatically added upon install of WP Update Plugin Server. The original file can be found in `wp-packages-update-server/optimisation/wppus-endpoint-optimizer.php`.  

### Special Thanks
A warm thank you to [Yahnis Elsts](https://github.com/YahnisElsts), the author of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) and [WP Update Server](https://github.com/YahnisElsts/wp-update-server) libraries, without whom the creation of this plugin would not have been possible.  
Authorisation to use these libraries freely provided relevant licenses are included has been graciously granted [here](https://github.com/YahnisElsts/wp-update-server/issues/37#issuecomment-386814776).

### Compatibility

* Tested with PHP 7.0.29 - may work with higher PHP versions for the most part (warnings may appear for PHP 7.3)
* WP Packages Update Server proper uses Plugin Update Checker Library 4.4 and WP Update Server Library 4.4
* Integration examples use Plugin Update Checker Library 4.9

**Pull requests to update the plugin's Proxy Update Checker Library to ensure compatibility with the latest versions of Plugin Update Checker and WP Update Server Library or to support other PHP versions are now accepted - this plugin is a personal project, receiving payment for its maintenance does not fit into my schedule, and I do not plan to spend extensive amount of time maintaining it except for obvious bugs.**

### Screenshots

Note: the screenshots are updated on a regular basis, but the actual interface may vary slightly.

#### Overview

<img src="https://ps.w.org/wp-packages-update-server/assets/screenshot-1.png" alt="Overview" width="100%">

#### Remote Sources

<img src="https://ps.w.org/wp-packages-update-server/assets/screenshot-2.png" alt="Remote Sources" width="100%">

#### Licenses

<img src="https://ps.w.org/wp-packages-update-server/assets/screenshot-3.png" alt="Licenses" width="100%">

#### Client - plugin screens

<img src="https://ps.w.org/wp-packages-update-server/assets/screenshot-4.png" alt="Plugins" width="100%">
<img src="https://ps.w.org/wp-packages-update-server/assets/screenshot-5.png" alt="Plugin Details" width="100%">

#### Client - theme screens

<img src="https://ps.w.org/wp-packages-update-server/assets/screenshot-6.png" alt="Themes" width="100%">
<img src="https://ps.w.org/wp-packages-update-server/assets/screenshot-7.png" alt="Theme Details" width="100%">
<img src="https://ps.w.org/wp-packages-update-server/assets/screenshot-8.png" alt="Theme License" width="100%">

#### Client - updates screen

<img src="https://ps.w.org/wp-packages-update-server/assets/screenshot-9.png" alt="Updates" width="100%">

## User Interface

Aside from a help page, WP Update Plugin Server provides a user interface to keep track of packages and licenses, with settings to configure the server.

### Overview Tab

This tab allows administrators to:
- View the list of packages currently available in WP Update Plugin Server, with Package Name, Version, Type (Plugin or Theme), File Name, Size, Last Modified and License Status (if enabled)
- Download a package
- Toggle between "Require License" and "Do not Require License" for a package when "Enable Package Licenses" is checked under the "License" tab
- Delete a package
- Apply bulk actions on the list of packages (download, delete, change license status of the package if licenses are enabled)
- Add a package (either by uploading it directly, or by priming it by pulling it from a configured Remote Repository)

In addition, the following settings are available:

Name                                | Type   | Description                                                                                                                  
----------------------------------- |:------:| ------------------------------------------------------------------------------------------------------------------------------
Archive max size (in MB)            | number | Maximum file size when uploading or downloading packages.
Cache max size (in MB)              | number | Maximum size in MB for the `wp-content/plugins/wp-packages-update-server/cache` directory. If the size of the directory grows larger, its content will be deleted at next cron run (checked hourly). The size indicated in the "Force Clean" button is the real current size.
Logs max size (in MB)               | number | Maximum size in MB for the `wp-content/plugins/wp-packages-update-server/logs` directory. If the size of the directory grows larger, its content will be deleted at next cron run (checked hourly). The size indicated in the "Force Clean" button is the real current size.

### Remote Sources Tab

If "Enable Package Licenses" is checked, this tab allows administrators to:
- Clear all the scheduled remote updates
- Reschedule all the remote updates

These actions are useful to forcefully alter the schedules (maintenance, tests, etc).  
In addition, the following settings are available:

Name                                  | Type      | Description                                                                                                                                                                                                                    
------------------------------------- |:---------:|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Use Remote Repository Service         | checkbox  | Enables this server to download plugins and themes from a Remote Repository before delivering updates.<br/>Supports Bitbucket, Github and Gitlab.<br/>If left unchecked, zip packages need to be manually uploaded to `wp-content/plugins/wp-packages-update-server/packages`.<br/>**It affects all the packages delivered by this installation of WP Packages Update Server if they have a corresponding repository in the Remote Repository Service.**<br/>**Settings of the "Packages remote source" section will be saved only if this option is checked.**
Remote Repository Service URL         | text      | The URL of the Remote Repository Service where packages are hosted.<br/>Must follow the following pattern: `https://repository-service.tld/username` where `https://repository-service.tld` may be a self-hosted instance of Gitlab.<br/>Each package repository URL must follow the following pattern: `https://repository-service.tld/username/package-name/` ; the package files must be located at the root of the repository, and in the case of plugins the main plugin file must follow the pattern `package-name.php`.
Self-hosted Remote Repository Service | checkbox  | Check this only if the Remote Repository Service is a self-hosted instance of Gitlab.
Packages branch name                  | text      | The branch to download when getting remote packages from the Remote Repository Service.
Remote Repository Service credentials | text      | Credentials for non-publicly accessible repositories.<br/>In the case of Github and Gitlab, an access token (`token`).<br/>In the case of Bitbucket, the Consumer key and secret separated by a pipe (`consumer_key|consumer_secret`). IMPORTANT: when creating the consumer, "This is a private consumer" must be checked.	
Remote update check frequency         | select    | How often WP Packages Update Server will poll each Remote Repository for package updates - checking too often may slow down the server (recommended "Once Daily").

### Licenses Tab

If "Enable Package Licenses" is checked, this tab allows administrators to:
- View the list of licenses currently stored by WP Update Plugin Server, with License Key, Registered Email, Status, Package Type (Plugin or Theme), Package Slug, Creation Date, Expiry Date, ID
- Add a license
- Edit a license
- Delete a license
- Apply bulk actions on the list of licenses (delete, change license status)

In addition, the following settings are available:

Name                                    | Type     | Description                                                                                                                                                                                                                                                                            
----------------------------------------|:--------:| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Enable Package Licenses                 | checkbox | Enables this server manages to deliver license-enabled plugins and themes using Software License Manager licenses.<br/>**It affects all the packages with a "Requires License" license status delivered by this installation of WP Packages Update Server. Settings of the "Packages licensing" section will be saved only if this option is checked.**
Private API Authentication Key          | text     | deally a random string - used to authenticate administration requests (create, update and delete).<br/>**WARNING: Keep this key secret, do not share it with customers!**
Signatures HMAC Key                     | text     | Ideally a random string, used to authenticate license signatures.<br/>**WARNING: Changing this value will invalidate all the licence signatures for current remote installations.**<br/>It is possible to grant a grace period and let webmasters deactivate and re-activate their license(s) by unchecking "Check License signature?".
Signatures Encryption Key               | text     | Ideally a random string, used to encrypt license signatures.<br/>**WARNING: Changing this value will invalidate all the licence signatures for current remote installations.**<br/>It is possile to grant a grace period and let webmasters deactivate and re-activate their license(s) by unchecking "Check License signature?".
Check License signature?                | checkbox | Check signatures - can be deactivated if the HMAC Key or the Encryption Key has been recently changed and remote installations have active licenses.<br/>Typically, all webmasters would have to deactivate and re-activate their license(s) to re-build their signatures, and this could take time ; it allows to grant a grace period during which license checking is less strict to avoid conflicts.

## Performances

Performances can be evaluated using the script `tests.php` located at the plugin's root. It is included only if the WordPress constants `WP_DEBUG` and `SAVEQUERIES` are truthy. Developers can edit the script freely by uncommenting relevant parts to  activate the desired tests.  

The performance insights below have been gathered on a cheap shared hosting server (less than $10 per month) with 256 MB of RAM, without any function hooked to WP Packages Update Server actions or filters, and with the MU Plugin endpoint optimizer active. Your Mileage May Vary depending on your server configuration and various optimisations you may add to your WordPress installation.  

The general conclusion is that calls to the APIs are lighter and faster than loading the vaste majority of WordPress homepages (which is the page likely to be visited the most on any website) and lighter than a WordPress ajax call (extra optimisations and aggressive caching not considered).

### Benchmark

Performances loading the frontpage of a fresh WordPress installation with `dummy-theme`, an empty static frontpage and no active plugin:  

```
--- Start load tests ---
Time elapsed: 0.129
Server memory used: 16.02 M / 256M
Total number of queries: 13
Total number of scripts: 194
--- End load tests ---
```

### Update API

Performances when a client is checking for updates (no license):

```
--- Start load tests ---
Time elapsed: 0.103
Total server memory used: 16.06 M / 256M
Total number of queries: 1
Total number of scripts: 173
Server memory used to run the plugin: 1.76 M / 256M
Number of queries executed by the plugin: 0
Number of included/required scripts by the plugin: 30
--- End load tests ---
```

Performances when a client is downloading an update (YMMV: downloading `dummy-plugin` - no license):

```
--- Start load tests ---
Time elapsed: 0.111
Total server memory used: 16.06 M / 256M
Total number of queries: 1
Total number of scripts: 173
Server memory used to run the plugin: 1.8 M / 256M
Number of queries executed by the plugin: 0
Number of included/required scripts by the plugin: 30
--- End load tests ---
```

Performances when a client is checking for updates (with license):

```
--- Start load tests ---
Time elapsed: 0.112
Total server memory used: 16.06 M / 256M
Total number of queries: 2
Total number of scripts: 174
Server memory used to run the plugin: 1.76 M / 256M
Number of queries executed by the plugin: 1
Number of included/required scripts by the plugin: 31
--- End load tests ---
```

Performances when a client is downloading an update (YMMV: downloading `dummy-plugin` - with license):

```
--- Start load tests ---
Time elapsed: 0.114
Total server memory used: 16.06 M / 256M
Total number of queries: 2
Total number of scripts: 174
Server memory used to run the plugin: 1.76 M / 256M
Number of queries executed by the plugin: 1
Number of included/required scripts by the plugin: 31
--- End load tests ---
```

### Public License API

Performances when a client is activating/deactivating a bogus license key:
```
--- Start load tests ---
Time elapsed: 0.108
Total server memory used: 15.24 M / 256M
Total number of queries: 2
Total number of scripts: 154
Server memory used to run the plugin: 966.85 K / 256M
Number of queries executed by the plugin: 1
Number of included/required scripts by the plugin: 11
--- End load tests ---
```

Performances when a client is activating a license key:
```
--- Start load tests ---
Time elapsed: 0.109
Total server memory used: 15.24 M / 256M
Total number of queries: 6
Total number of scripts: 154
Server memory used to run the plugin: 966.85 K / 256M
Number of queries executed by the plugin: 5
Number of included/required scripts by the plugin: 11
--- End load tests ---
```

Performances when a client is deactivating a license key:
```
--- Start load tests ---
Time elapsed: 0.098
Total server memory used: 15.24 M / 256M
Total number of queries: 6
Total number of scripts: 154
Server memory used to run the plugin: 966.85 K / 256M
Number of queries executed by the plugin: 5
Number of included/required scripts by the plugin: 11
--- End load tests ---
```

## Help

The following can also be found under the "Help" tab of the WP Update Plugin Server admin page.  

### Provide updates with WP Packages Update Server - packages requirements 

To link your packages to WP Packages Update Server, and maybe to prevent webmasters from getting updates of your plugins and themes unless they have a license, your plugins and themes need to include some extra code. It is a simple matter of adding a few lines in the main plugin file (for plugins) or in the `functions.php` file (for themes), and provide the necessary libraries in a lib directory at the root of the package - see [WP Package Updater](https://github.com/froger-me/wp-package-updater) for more information.  

See `wp-content/plugins/wp-packages-update-server/integration-examples/dummy-plugin` for an example of plugin, and  `wp-content/plugins/wp-packages-update-server/integration-examples/dummy-theme` for an example of theme. They are fully functionnal and can be used to test all the features of the server with a test client installation of WordPress.  

Unless "Use Remote Repository Service" is checked in "Remote Sources", you need to manually upload the packages zip archives (and subsequent updates) in `wp-content/wppus/packages`. Packages need to be valid WordPress plugin or theme packages, and in the case of a plugin the main plugin file must have the same name as the zip archive. For example, the main plugin file in `package-name.zip` would be `package-name.php`.  

### Requests optimisation

When the remote clients where your plugins and themes are installed send a request to check for updates or download a package, this server's WordPress installation is loaded, with its own plugins and themes. This is not optimised if left untouched because unnecessary action and filter hooks that execute before `parse_request` action hook are also triggered, even though the request is not designed to produce any on-screen output or further computation.

To solve this, the file `wp-content/plugins/wp-packages-update-server/optimisation/wppus-endpoint-optimiser.php` is automatically copied to `wp-content/mu-plugins/wppus-endpoint-optimiser.php`. This effectively creates a Must Use Plugin running before everything else and preventing themes and other plugins from being executed when an update request or a license API request is received by WP Packages Update Server.

You may edit the variable `$wppus_always_active_plugins` of the MU Plugin file to allow some plugins to run anyway, or set the `$wppus_bypass_themes` to `false` to allow `functions.php` files to be included, for example to hook into WP Plugin Server actions and filters. If in use and a new version is available, the MU Plugin will be backed-up to `wp-content/mu-plugins/wppus-endpoint-optimiser.php.backup` when updating WP Packages Update Server and will automatically be replaced with its new version. If necessary, make sure to report any previous customization from the backup to the new file.

The MU Plugin also provides the global variables `$wppus_doing_update_api_request` and `$wppus_doing_license_api_request` that can be tested when adding hooks and filters would you choose to keep some plugins active with `$wppus_always_active_plugins` or keep `functions.php` from themes included with `$wppus_bypass_themes` set to `false`.

### More help...

For more help on how to use WP Packages Update Server, please open an issue on Github or contact wppus-help@froger.me.  
Depending on the nature of the request, a fee may apply.

## Miscellaneous

WP Packages Update Server provides an API and offers a series of functions, actions and filters for developers to use in their own plugins and themes to modify the behavior of the plugin. Below is the documentation to interface with miscellaneous aspects of WP Packages Update Server. 
___
### Functions

The functions listed below are made publicly available by the plugin for theme and plugin developers. They can be used after the action `plugins_loaded` has been fired, or in a `plugins_loaded` action (just make sure the priority is above `-99`).  
Although the main classes can theoretically be instanciated without side effect if the `$hook_init` parameter is set to `false`, it is recommended to use only the following functions as there is no guarantee future updates won't introduce changes of behaviors.

___
#### php_log

```php
php_log( mixed $message = '', string $prefix = '' );
```

**Description**  
Convenience function to log a message to `error_log`.

**Parameters**  
$message  
> (mixed) the message to log ; can be any variable  

$prefix  
> (string) a prefix to add before the variable ; useful to add context  


___
#### cidr_match

```php
cidr_match( $ip, $range );
```

**Description**  
Check whether an IP address is a match for the provided CIDR range.

**Parameters**  
$ip  
> (string) the IP address to check  

$range  
> (string) a CIDR range  

**Return value**
> (bool) whether an IP address is a match for the provided CIDR range


___
#### wppus_is_doing_api_request

```php
wppus_is_doing_api_request()
```

**Description**  
Determine whether the current request is made by a remote client interacting with any of the APIs.

**Return value**
> (bool) `true` if the current request is made by a remote client interacting with any of the APIs, `false` otherwise


___
#### wppus_is_doing_webhook_api_request

```php
wppus_is_doing_webhook_api_request()
```

**Description**  
Determine wether the current request is made by a Webhook.

**Return value**
> (bool) `true` if the current request is made by a Webhook, `false` otherwise

___
#### wppus_init_nonce_auth

```php
wppus_init_nonce_auth( string $private_auth_key, string|null $auth_header_name = null )
```

**Description**  
Set the Private Authorization Key and the Authorization Header name used to request nonces via the `wppus-token` and `wppus-nonce` endpoints.  
If the Authentication Header name is not set, the `api_auth_key` variable set in `POST` method is used instead when requesting nonces.

**Parameters**  
$private_auth_key  
> (string) the Private Authorization Key  

$auth_header_name  
> (string|null) the Authorization Header name  

___
#### wppus_create_nonce

```php
wppus_create_nonce( bool $true_nonce = true, int $expiry_length = WPPUS_Nonce::DEFAULT_EXPIRY_LENGTH, array $data = array(), int $return_type = WPPUS_Nonce::NONCE_ONLY, bool $store = true, bool|callable $delegate = false, array $delegate_args = array() )
```

**Description**  
Creates a cryptographic token - allows creation of tokens that are true one-time-use nonces, with custom expiry length and custom associated data.

**Parameters**  
$true_nonce  
> (bool) whether the nonce is one-time-use ; default `true`  

$expiry_length  
> (int) the number of seconds after which the nonce expires ; default `WPPUS_Nonce::DEFAULT_EXPIRY_LENGTH` - 30 seconds 

$data  
> (array) custom data to save along with the nonce ; set an element with key `permanent` to a truthy value to create a nonce that never expires ; default `array()`  

$return_type  
> (int) whether to return the nonce, or an array of information ; default `WPPUS_Nonce::NONCE_ONLY` ; other accepted value is `WPPUS_Nonce::NONCE_INFO_ARRAY`  

$store  
> (bool) whether to store the nonce, or let a third party mechanism take care of it ; default `true`  

$delegate  
> (bool|callable) if needed, a function or method to create the nonce ; default `false`  

$delegate_args  
> (array) if `$delegate` is of type `callable`, the arguments to pass to the function or method used to create the nonce ; default `array()`  

**Return value**
> (bool|string|array) `false` in case of failure ; the cryptographic token string if `$return_type` is set to `WPPUS_Nonce::NONCE_ONLY` ; an array of information if `$return_type` is set to `WPPUS_Nonce::NONCE_INFO_ARRAY` with the following format:
```php
array(
	'nonce'      => 'some_value',	// cryptographic token
	'true_nonce' => true,			// whether the nonce is one-time-use
	'expiry'     => 9999,			// the expiry timestamp
	'data'       => array(),		// custom data saved along with the nonce
);
```  

___
#### wppus_get_nonce_expiry

```php
wppus_get_nonce_expiry( string $nonce )
```

**Description**  
Get the expiry timestamp of a nonce.  

**Parameters**  
$nonce  
> (string) the nonce  

**Return value**
> (int) the expiry timestamp  

___
#### wppus_validate_nonce

```php
wppus_validate_nonce( string $value )
```

**Description**  
Check whether the value is a valid nonce.  
Note: if the nonce is a true nonce, it will be invalidated and further calls to this function with the same `$value` will return `false`.  

**Parameters**  
$value  
> (string) the value to check  

**Return value**
> (bool) whether the value is a valid nonce  

___
#### wppus_delete_nonce

```php
wppus_delete_nonce( string $value )
```

**Description**  
Delete a nonce from the system if the corresponding value exists.  

**Parameters**  
$value  
> (string) the value to delete  

**Return value**
> (bool) whether the nonce was deleted  

___
#### wppus_delete_nonce

```php
wppus_clear_nonces()
```

**Description**  
Clear expired nonces from the system.  

**Return value**
> (bool) whether some nonces were cleared  

___
### Actions

WP Packages Update Server gives developers the possibility to have their plugins react to some events with a series of custom actions.  
**Warning**: the filters below with the mention "Fired during API requests" need to be used with caution. Although they may be triggered when using the functions above, these filters will possibly be called when the Update API, License API, Packages API or a Webhook is called. Registering functions doing heavy computation to these filters can seriously degrade the server's performances.  

___
### Filters

WP Packages Update Server gives developers the possibility to customise its behavior with a series of custom filters.  
**Warning**: the filters below with the mention "Fired during API requests" need to be used with caution. Although they may be triggered when using the functions above, these filters will possibly be called when the Update API, License API, Packages API or a Webhook is called. Registering functions doing heavy computation to these filters can seriously degrade the server's performances.  