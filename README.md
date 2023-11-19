# WP Packages Update Server - Run your own update server for plugins and themes

* [WP Packages Update Server - Run your own update server for plugins and themes](#wp-packages-update-server---run-your-own-update-server-for-plugins-and-themes)
	* [General Description](#general-description)
		* [Overview](#overview)
		* [Special Thanks](#special-thanks)
		* [Compatibility](#compatibility)
		* [Screenshots](#screenshots)
			* [Overview](#overview-1)
			* [Remote Sources](#remote-sources)
			* [Licenses](#licenses)
			* [Client - plugin screens](#client---plugin-screens)
			* [Client - theme screens](#client---theme-screens)
			* [Client - updates screen](#client---updates-screen)
	* [User Interface](#user-interface)
		* [Overview Tab](#overview-tab)
		* [Remote Sources Tab](#remote-sources-tab)
		* [Licenses Tab](#licenses-tab)
	* [Performances](#performances)
		* [Benchmark](#benchmark)
		* [Update API](#update-api)
		* [Public License API](#public-license-api)
	* [Help](#help)
		* [Provide updates with WP Packages Update Server - packages requirements](#provide-updates-with-wp-packages-update-server---packages-requirements)
		* [Requests optimisation](#requests-optimisation)
		* [More help...](#more-help)


Developer documentation:
- [Packages](https://github.com/froger-me/wp-packages-update-server/blob/master/packages.md)
- [Licenses](https://github.com/froger-me/wp-packages-update-server/blob/master/licenses.md)
- [Miscellaneous](https://github.com/froger-me/wp-packages-update-server/blob/master/misc.md)

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

* Tested with PHP 8.x - may work with PHP 7.x versions for the most part
* WP Packages Update Server proper uses Plugin Update Checker Library 5.3 and WP Update Server Library 2.0.1
* Integration examples use Plugin Update Checker Library 5.3

**Pull requests to solve any bug, improve performance, and keep libraries up to date are welcome and highly encouraged.**  
**Requests to debug or troubleshoot specific setups will not be addressed.**

### Screenshots

Note: the screenshots are updated on a regular basis, but the actual interface may vary slightly.

#### Overview

<img src="https://ps.w.org/wp-plugin-update-server/assets/screenshot-1.png" alt="Overview" width="100%">

#### Remote Sources

<img src="https://ps.w.org/wp-plugin-update-server/assets/screenshot-2.png" alt="Remote Sources" width="100%">

#### Licenses

<img src="https://ps.w.org/wp-plugin-update-server/assets/screenshot-3.png" alt="Licenses" width="100%">

#### Client - plugin screens

<img src="https://ps.w.org/wp-plugin-update-server/assets/screenshot-4.png" alt="Plugins" width="100%">
<img src="https://ps.w.org/wp-plugin-update-server/assets/screenshot-5.png" alt="Plugin Details" width="100%">

#### Client - theme screens

<img src="https://ps.w.org/wp-plugin-update-server/assets/screenshot-6.png" alt="Themes" width="100%">
<img src="https://ps.w.org/wp-plugin-update-server/assets/screenshot-7.png" alt="Theme Details" width="100%">
<img src="https://ps.w.org/wp-plugin-update-server/assets/screenshot-8.png" alt="Theme License" width="100%">

#### Client - updates screen

<img src="https://ps.w.org/wp-plugin-update-server/assets/screenshot-9.png" alt="Updates" width="100%">

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

This tab allows administrators to configure how Remote Sources are handled with the follwing settings:

Name                                  | Type      | Description
------------------------------------- |:---------:|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Use Remote Repository Service         | checkbox  | Enables this server to download plugins and themes from a Remote Repository before delivering updates.<br/>Supports Bitbucket, Github and Gitlab.<br/>If left unchecked, zip packages need to be manually uploaded to `wp-content/plugins/wp-packages-update-server/packages`.<br/>**It affects all the packages delivered by this installation of WP Packages Update Server if they have a corresponding repository in the Remote Repository Service.**<br/>**Settings of the "Packages remote source" section will be saved only if this option is checked.**
Remote Repository Service URL         | text      | The URL of the Remote Repository Service where packages are hosted.<br/>Must follow the following pattern: `https://repository-service.tld/username` where `https://repository-service.tld` may be a self-hosted instance of Gitlab.<br/>Each package repository URL must follow the following pattern: `https://repository-service.tld/username/package-slug/` ; the package files must be located at the root of the repository, and in the case of plugins the main plugin file must follow the pattern `package-slug.php`.
Self-hosted Remote Repository Service | checkbox  | Check this only if the Remote Repository Service is a self-hosted instance of Gitlab.
Packages branch name                  | text      | The branch to download when getting remote packages from the Remote Repository Service.
Remote Repository Service credentials | text      | Credentials for non-publicly accessible repositories.<br/>In the case of Github and Gitlab, an access token (`token`).<br/>In the case of Bitbucket, the Consumer key and secret separated by a pipe (`consumer_key|consumer_secret`). IMPORTANT: when creating the consumer, "This is a private consumer" must be checked.	
Remote update check frequency         | select    | How often WP Packages Update Server will poll each Remote Repository for package updates - checking too often may slow down the server (recommended "Once Daily").

In addition, in case Webhooks are not used, the following actions are available to forcefully alter the packages schedules (maintenance, tests, etc):
- Clear all the scheduled remote updates
- Reschedule all the remote updates

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

Unless "Use Remote Repository Service" is checked in "Remote Sources", you need to manually upload the packages zip archives (and subsequent updates) in `wp-content/wppus/packages`. Packages need to be valid WordPress plugin or theme packages, and in the case of a plugin the main plugin file must have the same name as the zip archive. For example, the main plugin file in `package-slug.zip` would be `package-slug.php`.  

### Requests optimisation

When the remote clients where your plugins and themes are installed send a request to check for updates or download a package, this server's WordPress installation is loaded, with its own plugins and themes. This is not optimised if left untouched because unnecessary action and filter hooks that execute before `parse_request` action hook are also triggered, even though the request is not designed to produce any on-screen output or further computation.

To solve this, the file `wp-content/plugins/wp-packages-update-server/optimisation/wppus-endpoint-optimiser.php` is automatically copied to `wp-content/mu-plugins/wppus-endpoint-optimiser.php`. This effectively creates a Must Use Plugin running before everything else and preventing themes and other plugins from being executed when an update request or a license API request is received by WP Packages Update Server.

You may edit the variable `$wppus_always_active_plugins` of the MU Plugin file to allow some plugins to run anyway, or set the `$wppus_bypass_themes` to `false` to allow `functions.php` files to be included, for example to hook into WP Plugin Server actions and filters. If in use and a new version is available, the MU Plugin will be backed-up to `wp-content/mu-plugins/wppus-endpoint-optimiser.php.backup` when updating WP Packages Update Server and will automatically be replaced with its new version. If necessary, make sure to report any previous customization from the backup to the new file.

The MU Plugin also provides the global variables `$wppus_doing_update_api_request` and `$wppus_doing_license_api_request` that can be tested when adding hooks and filters would you choose to keep some plugins active with `$wppus_always_active_plugins` or keep `functions.php` from themes included with `$wppus_bypass_themes` set to `false`.

### More help...

For more help on how to use WP Packages Update Server, please open an issue - bugfixes are welcome via pull requests, detailed bug reports with accurate pointers as to where and how they occur in the code will be addressed in a timely manner, and a fee will apply for any other request if they are addressed.  
If and only if you found a security issue, please contact wppus-help@anyape.com with full details for responsible disclosure.
