# WP Packages Update Server - Run your own update server

* [WP Packages Update Server - Run your own update server](#wp-packages-update-server---run-your-own-update-server)
	* [Introduction](#introduction)
		* [Overview](#overview)
		* [Special Thanks](#special-thanks)
		* [Compatibility](#compatibility)
		* [Screenshots](#screenshots)
			* [Packages Overview](#packages-overview)
			* [Remote Sources](#remote-sources)
			* [Licenses](#licenses)
			* [API \& Webhooks](#api--webhooks)
			* [Client - plugin screens](#client---plugin-screens)
			* [Client - theme screens](#client---theme-screens)
			* [Client - updates screen](#client---updates-screen)
	* [User Interface](#user-interface)
		* [Packages Overview](#packages-overview-1)
		* [Remote Sources](#remote-sources-1)
		* [Licenses](#licenses-1)
		* [API \& Webhooks](#api--webhooks-1)
	* [Performances](#performances)
		* [Benchmark](#benchmark)
		* [Update API](#update-api)
		* [Public License API](#public-license-api)
	* [Help](#help)
		* [Provide updates with WP Packages Update Server - packages requirements](#provide-updates-with-wp-packages-update-server---packages-requirements)
		* [Requests optimisation](#requests-optimisation)
		* [More help...](#more-help)


Developer documentation:
- [Packages](https://github.com/froger-me/wp-packages-update-server/blob/main/integration/docs/packages.md)
- [Licenses](https://github.com/froger-me/wp-packages-update-server/blob/main/integration/docs/licenses.md)
- [Miscellaneous](https://github.com/froger-me/wp-packages-update-server/blob/main/integration/docs/misc.md)
- [Generic Updates Integration](https://github.com/froger-me/wp-packages-update-server/blob/main/integration/docs/generic.md)

## Introduction

WP Packages Update Server allows developers to provide updates for plugins & themes not hosted on `wordpress.org` (if not compliant with the GPLv2 or later, for example), or for generic packages unrelated to WordPress altogether. It also allows to control the updates with license.
Package updates may be either uploaded directly, or hosted in a Remote Repository, public or private, with the latest version of packages stored either locally or in the Cloud. It supports Bitbucket, Github, Gitlab, and self-hosted installations of Gitlab for package updates ; S3 compatible service providers are supported for package storage.

**The `main` branch contains a beta version of WPPUS v2. The `dev` branch contains an alpha version of WPPUS v2. For stable versions, please use releases.**  
**The `v1` branch is only maintained by the community via pull requests, and releases are published only based on community feedback. No new feature will be added and no more in-depth maintenance will be performed by the original author.**  
**There is no formal upgrade path from v1 to v2.**


### Overview

This plugin adds the following major features to WordPress:

* **Packages Overview:** manage package updates with a table showing Package Name, Version, Type, File Name, Size, Last Modified and License Status ; includes bulk operations to delete, download and change the license status, and the ability to delete all the packages. Upload updates from your local machine to WPPUS, or let the system to automatically download them to WPPUS from a Remote Repository. Store packages either locally, or in the Cloud with an S3 compatible service. Packages can also be managed through their own API.
* **Remote Sources:** configure the Remote Repository Service of your choice (Bitbucket, Github, Gitlab, or a self-hosted installation of Gitlab) with secure credentials and a branch name where the updates are hosted ; choose to check for updates recurringly, or when receiveing a webhook notification. WPPUS acts as a middleman between your Remote Repository, your udpates storage (local or Cloud), and your clients.
* **Licenses:** manage licenses with a table showing ID, License Key, Registered Email, Status, Package Type, Package Slug, Creation Date, and Expiry Date ; add and edit them with a form, or use the API for more control. Licenses prevent packages installed on client machines from being updated without a valid license. Licenses are generated automatically by default and the values are unguessable (it is recommended to keep the default). When checking the validity of licenses an extra license signature is also checked to prevent the use of a license on more than the configured allowed domains.
* **Not limited to WordPress:** with a platform-agnostic API, updates can be served for any type of package, not just WordPress plugins & themes. Basic examples of integration with Node.js, PHP, bash, and Python are provided in the [documentation](https://github.com/froger-me/wp-packages-update-server/blob/main/integration/docs/generic.md).
* **API & Webhooks:** Use the Package API to administer packages (browse, read, edit, add, delete), and request for expirable signed URLs of packages to allow secure downloads. Use the License API to administer licenses (browse, read, edit, add, delete) and check, activate or deactivate licenses. Fire Webhooks to notify any URL of your choice of key events affecting packages and licenses. 

To connect their packages and WP Packages Update Server, developers can find integration examples in `wp-packages-update-server/integration`:
* **Dummy Plugin:** a folder `dummy-plugin` with a simple, empty plugin that includes the necessary code in the `dummy-plugin.php` main plugin file and the necessary libraries in a `lib` folder.
* **Dummy Theme:** a folder `dummy-theme` with a simple, empty child theme of Twenty Seventeen that includes the necessary code in the `functions.php` file and the necessary libraries in a `lib` folder.
* **Dummy Generic:** a folder `dummy-generic` with a simple command line program written bash, Node.js, PHP, bash, and Python. Execute by calling `./dummy-generic.[js|php|sh|py]` from the command line. See `wppus-api.[js|php|sh|py]` for simple examples of the API calls.

In addition, requests to the various APIs are optimised with a customisable [Must Use Plugin](https://codex.wordpress.org/Must_Use_Plugins) automatically added upon install of WP Packages Update Server. The original file can be found in `wp-packages-update-server/optimisation/wppus-endpoint-optimizer.php`.  

### Special Thanks
A warm thank you to [Yahnis Elsts](https://github.com/YahnisElsts), the author of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) and [WP Update Server](https://github.com/YahnisElsts/wp-update-server) libraries, without whom the creation of this plugin would not have been possible.  
Authorisation to use these libraries freely provided relevant licenses are included has been graciously granted [here](https://github.com/YahnisElsts/wp-update-server/issues/37#issuecomment-386814776).

### Compatibility

* Tested with PHP 8.x - may work with PHP 7.x versions for the most part, but it is not guaranteed
* WP Packages Update Server proper uses Plugin Update Checker Library 5.3 and WP Update Server Library 2.0.1
* Integration examples for WordPress packages use Plugin Update Checker Library 5.3

**Pull requests to solve any bugs, improve performance, and keep libraries up to date are welcome and highly encouraged.**  
**Requests to debug or troubleshoot specific setups will not be addressed.**

### Screenshots

Note: the screenshots are updated regularly, but the actual interface may vary slightly.

#### Packages Overview

<img src="https://anyape.com/resources/wppus/screenshots/packages-overview.png" alt="Packages Overview" width="100%">

#### Remote Sources

<img src="https://anyape.com/resources/wppus/screenshots/remote-sources.png" alt="Remote Sources" width="100%">

#### Licenses

<img src="https://anyape.com/resources/wppus/screenshots/licenses.png" alt="Licenses" width="100%">

#### API & Webhooks

<img src="https://anyape.com/resources/wppus/screenshots/api.png" alt="API & Webhooks" width="100%">

#### Client - plugin screens

<img src="https://anyape.com/resources/wppus/screenshots/admin_plugins.png" alt="Plugins" width="100%">
<img src="https://anyape.com/resources/wppus/screenshots/admin_plugins-2.png" alt="Plugin Details" width="100%">

#### Client - theme screens

<img src="https://anyape.com/resources/wppus/screenshots/admin_themes.png" alt="Themes" width="100%">
<img src="https://anyape.com/resources/wppus/screenshots/admin_themes-2.png" alt="Theme Details" width="100%">
<img src="https://anyape.com/resources/wppus/screenshots/admin_themes-3.png" alt="Theme License" width="100%">

#### Client - updates screen

<img src="https://anyape.com/resources/wppus/screenshots/admin_update-core.png" alt="Updates" width="100%">

## User Interface

Aside from a help page, WP Packages Update Server provides a user interface to manage packages, manage licenses, manage Remote Repository connection, and to configure API & Webhooks.

### Packages Overview

This tab allows administrators to:
- View the list of packages currently available in WP Packages Update Server, with Package Name, Version, Type (Plugin or Theme), File Name, Size, Last Modified and License Status (if enabled)
- Download a package
- Toggle between "Require License" and "Do not Require License" for a package when "Enable Package Licenses" is checked under the "Licenses" tab
- Delete a package
- Apply bulk actions on the list of packages (download, delete, change license status of the package if licenses are enabled)
- Add a package (either by uploading it directly, or by priming it by pulling it from a configured Remote Repository)
- Configure and test a Cloud Storage service
- Configure other packages-related settings - file upload, cache and logs max sizes.

The following settings are available:

Name                                | Type     | Description
----------------------------------- |:--------:| ------------------------------------------------------------------------------------------------------------------------------
Use Cloud Storage                   | checkbox | Check to use a Cloud Storage Service - S3 Compatible.<br>If it does not exist, a virtual folder `wppus-packages` will be created in the Storage Unit chosen for package storage.
Cloud Storage Access Key            | text     | The Access Key provided by the Cloud Storage service provider.
Cloud Storage Secret Key            | text     | The Secret Key provided by the Cloud Storage service provider.
Cloud Storage Endpoint              | text     | The domain (without `http://` or `https://`) of the endpoint for the Cloud Storage Service.
Cloud Storage Unit                  | text     | Usually known as a "bucket" or a "container" depending on the Cloud Storage service provider.
Cloud Storage Region                | text     | The region of the Cloud Storage Unit, as indicated by the Cloud Storage service provider.
Archive max size (in MB)            | number   | Maximum file size when uploading or downloading packages.
Cache max size (in MB)              | number   | Maximum size in MB for the `wp-content/plugins/wp-packages-update-server/cache` directory. If the size of the directory grows larger, its content will be deleted at next cron run (checked hourly). The size indicated in the "Force Clean" button is the real current size.
Logs max size (in MB)               | number   | Maximum size in MB for the `wp-content/plugins/wp-packages-update-server/logs` directory. If the size of the directory grows larger, its content will be deleted at next cron run (checked hourly). The size indicated in the "Force Clean" button is the real current size.

A button is available to send a test request to the Cloud Storage Service. The request checks whether the provider is reachable and if the Storage Unit exists and is writable.  
If it does not exist during the test, a virtual folder `wppus-packages` will be created in the Storage Unit chosen for package storage.  

### Remote Sources

This tab allows administrators to configure how Remote Sources are handled with the following settings:

Name                                  | Type      | Description
------------------------------------- |:---------:| --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Use Remote Repository Service         | checkbox  | Enables this server to download packages from a Remote Repository before delivering updates.<br/>Supports Bitbucket, Github and Gitlab.<br/>If left unchecked, zip packages need to be manually uploaded to `wp-content/plugins/wp-packages-update-server/packages`.<br/>**It affects all the packages delivered by this installation of WP Packages Update Server if they have a corresponding repository in the Remote Repository Service.**<br/>**Settings of the "Packages remote source" section will be saved only if this option is checked.**
Remote Repository Service URL         | text      | The URL of the Remote Repository Service where packages are hosted.<br/>Must follow the following pattern: `https://repository-service.tld/username` where `https://repository-service.tld` may be a self-hosted instance of Gitlab.<br/>Each package repository URL must follow the following pattern: `https://repository-service.tld/username/package-slug/` ; the package files must be located at the root of the repository, and in the case of plugins the main plugin file must follow the pattern `package-slug.php`.
Self-hosted Remote Repository Service | checkbox  | Check this only if the Remote Repository Service is a self-hosted instance of Gitlab.
Packages branch name                  | text      | The branch to download when getting remote packages from the Remote Repository Service.
Remote Repository Service credentials | text      | Credentials for non-publicly accessible repositories.<br/>In the case of Github and Gitlab, an access token (`token`).<br/>In the case of Bitbucket, the Consumer key and secret separated by a pipe (`consumer_key\|consumer_secret`). IMPORTANT: when creating the consumer, "This is a private consumer" must be checked.	
Use Webhooks                          | checkbox  | Check so that each repository of the Remote Repository Service calls a Webhook when updates are pushed.<br>When checked, WP Packages Update Server will not regularly poll repositories for package version changes, but relies on events sent by the repositories to schedule a package download.<br>Webhook URL: `https://domain.tld/wppus-webhook/package-type/package-slug` - where `package-type` is the package type (`plugin`, `theme`, or `generic`) and `package-slug` is the slug of the package that needs updates.<br>Note that WP Packages Update Server does not rely on the content of the payload to schedule a package download, so any type of event can be used to trigger the Webhook.
Remote Download Delay                 | number    | Delay in minutes after which WP Packages Update Server will poll the Remote Repository for package updates when the Webhook has been called.<br>Leave at `0` to schedule a package update during the cron run happening immediately after the Webhook notification was received.
Remote Repository Webhook Secret      | text      | Ideally a random string, the secret string included in the request by the repository service when calling the Webhook.<br>**WARNING: Changing this value will invalidate all the existing Webhooks set up on all package repositories.**<br>After changing this setting, make sure to update the Webhooks secrets in the repository service.
Remote update check frequency         | select    | Only available in case Webhooks are not used - How often WP Packages Update Server will poll each Remote Repository for package updates - checking too often may slow down the server (recommended "Once Daily").

A button is available to send a test request to the Remote Repository Service. The request checks whether the service is reachable and if the request can be authenticated.  
Tests via this button are not supported for Bitbucket ; if Bitbucket is used, testing should be done after saving the settings and trying to prime a package in the Packages Overview tab.  

In case Webhooks are not used, the following actions are available to forcefully alter the packages schedules (maintenance, tests, etc):
- Clear all the scheduled remote updates
- Reschedule all the remote updates

### Licenses

This tab allows administrators to:
- Entirely enable/disable package licenses. **It affects all the packages with a "Requires License" license status delivered by WP Packages Update Server.**
- View the list of licenses currently stored by WP Packages Update Server, with License Key, Registered Email, Status, Package Type (Plugin or Theme), Package Slug, Creation Date, Expiry Date, ID
- Add a license
- Edit a license
- Delete a license
- Apply bulk actions on the list of licenses (delete, change license status)

### API & Webhooks

This tab allows administrators to configure:
- the Package API to administer packages (browse, read, edit, add, delete), request for expirable signed URLs of packages to allow secure downloads, and requests for tokens & true nonces.
- the License API to administer licenses (browse, read, edit, add, delete) and check, activate or deactivate licenses.
- the list of URLs notified via Webhooks, with the following available events:
	-  Package events `(package)`
		- Package added or updated `(package_update)`
		- Package deleted `(package_delete)`
		- Package downloaded via a signed URL `(package_download)`
	- License events `(license)`
		- License activated `(license_activate)`
		- License deactivated `(license_deactivate)`
		- License added `(license_add)`
		- License edited `(license_edit)`
		- License deleted `(license_delete)`
		- License becomes required for a package `(license_require)`
		- License becomes not required a for package `(license_unrequire)`

Available settings:

Name                                     | Description
---------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Private API Keys (Package API)           | Multiple values ; creating a key required a "Package Key ID" used to identify the package key.<br>Used to sign requests to obtain tokens for package administration operations (browse, read, edit, add, delete) and obtaining signed URLs of package.<br>The Package Key ID must contain only numbers, letters, `-` and `_`.<br>**WARNING: Keep these keys secret, do not share any of them with customers!**
IP Whitelist (Package API)               | Multiple values.<br>List of IP addresses and/or CIDRs of remote sites authorized to use the Package Private API (one IP address or CIDR per line).<br>Leave blank to accept any IP address (not recommended).
Private API Keys (License API)	         | Multiple values ; creating a key required a "License Key ID" used to identify the package key.<br>Used to sign requests to obtain tokens for license administration operations (browse, read, edit, add, delete).<br>The License Key ID must contain only numbers, letters, `-` and `_`.<br>**WARNING: Keep these keys secret, do not share any of them with customers!**
IP Whitelist (License API)               | Multiple values.<br>List of IP addresses and/or CIDRs of remote sites authorized to use the License Private API (one IP address or CIDR per line).<br>Leave blank to accept any IP address (not recommended).
Webhook                                  | Multiple values ; creating a Webhook requires a "Payload URL", a `secret-key`, and a list of events.<br>Webhooks are event notifications sent to arbitrary URLs at next cronjob (1 min. latest after the event occured, depending on the server configuration) with a payload of data for third party services integration.<br>To allow the recipients to authenticate the notifications, the payload is signed with a `secret-key` secret key using `sha1` algorithm and `sha256` algorithm ; the resulting hashes are made available in the `X-WPPUS-Signature` and `X-WPPUS-Signature-256` headers respectively.<br>**The `secret-key` must be at least 16 characters long, ideally a random string.**<br>The payload is sent in JSON format via a `POST` request.<br>**WARNING: Only add URLs you trust!**

## Performances

Performance can be evaluated using the script `tests.php` located at the plugin's root. It is included only if the WordPress constants `WP_DEBUG` and `SAVEQUERIES` are truthy. Developers can edit the script freely by uncommenting relevant parts to  activate the desired tests.  

The performance insights below have been gathered on a cheap shared hosting server (less than $10 per month) with 256 MB of RAM, without any function hooked to WP Packages Update Server actions or filters, no Webhook, and with the MU Plugin endpoint optimizer active. Your Mileage May Vary depending on your server configuration and various optimisations you may add to your WordPress installation.  

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

The following can also be found under the "Help" tab of the WP Packages Update Server admin page.  

### Provide updates with WP Packages Update Server - packages requirements 

To link your packages to WP Packages Update Server, and optionally to prevent webmasters from getting updates of your ppackages without a license, your packages need to include some extra code.  

For plugins, and themes, it is fairly straightforward:
- Add a `lib` directory with the `plugin-update-checker` and `wp-update-checker` libraries to the root of the package (provided in `dummy-[plugin|theme]` ; `wp-update-checker` can be customized as you see fit, but `plugin-update-checker` should be left untouched).
- Add the following code to the main plugin file (for plugins) or in the `functions.php` file (for themes) :
```php
/** Enable updates - note the  `$prefix_updater` variable: change `prefix` to a unique string for your package **/
require_once __DIR__ . '/lib/wp-package-updater/class-wp-package-updater.php';

$prefix_updater = new WP_Package_Updater(
	wp_normalize_path( __FILE__ ),
	0 === strpos( __DIR__, WP_PLUGIN_DIR ) ? wp_normalize_path( __DIR__ ) : get_stylesheet_directory()
);
```
- Add a `wppus.json` file at the root of the package with the following content - change the value of `"server"` to your own (required), and select a value for `"requireLicense"` (optional):
```json
{
   "server": "https://server.domain.tld/",
   "requireLicense": true|false
}
```
- Connect WPPUS with your repository and prime your package, or manually upload your package to WPPUS.

For generic packages, the steps involved entirely depend on the language used to write the package and the update process of the target platform.  
You may refer to the documentation found [here](https://github.com/froger-me/wp-packages-update-server/blob/main/integration/docs/generic.md).
___

See `wp-content/plugins/wp-packages-update-server/integration/dummy-plugin` for an example of plugin, and  `wp-content/plugins/wp-packages-update-server/integration/dummy-theme` for an example of theme. They are fully functionnal and can be used to test all the features of the server with a test client installation of WordPress.  

See `wp-content/plugins/wp-packages-update-server/integration/dummy-generic` for examples of a generic package written in Bash, NodeJS, PHP with Curl, and Python. The API calls made by generic packages to the license API and Update API are the same as the WordPress packages. Unlike the upgrade library provided with plugins & themes, the code found in `wppwus-api.[sh|php|js|py]` files is **NOT ready for production environment and MUST be adapted**.

Unless "Use Remote Repository Service" is checked in "Remote Sources", you need to manually upload the packages zip archives (and subsequent updates) in `wp-content/wppus/packages` or `CloudStorageUnit://wppus-packages/`.  A package needs to a valid generic package, or a valid WordPress plugin or theme package, and in the case of a plugin the main plugin file must have the same name as the zip archive. For example, the main plugin file in `package-slug.zip` would be `package-slug.php`.  

### Requests optimisation

When the remote clients where your plugins and themes are installed send a request to check for updates or download a package, this server's WordPress installation is loaded, with its own plugins and themes. This is not optimised if left untouched because unnecessary action and filter hooks that execute before `parse_request` action hook are also triggered, even though the request is not designed to produce any on-screen output or further computation.

To solve this, the file `wp-content/plugins/wp-packages-update-server/optimisation/wppus-endpoint-optimiser.php` is automatically copied to `wp-content/mu-plugins/wppus-endpoint-optimiser.php`. This effectively creates a Must Use Plugin running before everything else and preventing themes and other plugins from being executed when an update request or a license API request is received by WP Packages Update Server.

You may edit the variable `$wppus_always_active_plugins` of the MU Plugin file to allow some plugins to run anyway, or set the `$wppus_bypass_themes` to `false` to allow `functions.php` files to be included, for example to hook into WP Plugin Server actions and filters. If in use and a new version is available, the MU Plugin will be backed-up to `wp-content/mu-plugins/wppus-endpoint-optimiser.php.backup` when updating WP Packages Update Server and will automatically be replaced with its new version. If necessary, make sure to report any previous customization from the backup to the new file.

The MU Plugin also provides the global variables `$wppus_doing_update_api_request` and `$wppus_doing_license_api_request` that can be tested when adding hooks and filters would you choose to keep some plugins active with `$wppus_always_active_plugins` or keep `functions.php` from themes included with `$wppus_bypass_themes` set to `false`.

### More help...

For more help on how to use WP Packages Update Server, please open an issue - bugfixes are welcome via pull requests, detailed bug reports with accurate pointers as to where and how they occur in the code will be addressed in a timely manner, and a fee will apply for any other request if they are addressed.  
If and only if you found a security issue, please contact `wppus-help@anyape.com` with full details for responsible disclosure.