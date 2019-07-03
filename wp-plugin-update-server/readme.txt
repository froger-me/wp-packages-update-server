=== WP Plugin Update Server ===
Contributors: frogerme
Tags: plugins, themes, updates, license
Requires at least: 4.9.5
Tested up to: 5.0
Stable tag: trunk
Requires PHP: 7.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Run your own update server for plugins and themes.

== Description ==

WP Plugin Update Server allows developers to provide updates for plugins and themes packages not hosted on wordpress.org. It is useful to provide updates for plugins or themes not compliant with the GPLv2 (or later).
Packages may be either uploaded directly, or hosted in a remote repository, public or private. It supports Bitbucket, Github and Gitlab, as well as self-hosted installations of Gitlab.
Package updates may require a license, and licenses can be managed through an API or a user interface within WP Plugin Update Server.

== Important notes ==

This plugin is for developers only.

Zip PHP extension is required (use ZipArchive, no fallback to PclZip).

For more information, available APIs, functions, actions and filters, see [the plugin's full documentation](https://github.com/froger-me/wp-plugin-update-server/blob/master/README.md).

Make sure to read the full documentation and the content of the "Help" tab under "WP Plugin Update Server" settings before opening an issue or contacting the author.

== Overview ==

This plugin adds the following major features to WordPress:

* **WP Plugin Update Server admin page:** to manage the list of packages and configure the plugin.
* **Package management:** to manage update packages, showing a listing with Package Name, Version, Type, File Name, Size, Last Modified and License Status ; includes bulk operations to delete, download and change the license status, and the ability to delete all the packages.
* **Add Packages:** Upload update packages from a local machine to the server, or download them to the server from a remote repository.
* **General settings:** for archive files download size, cache, and logs, with force clean.
* **Packages licensing:** Prevent plugins and themes installed on remote WordPress installation from being updated without a valid license. Licenses are generated automatically by default and the values are unguessable (it is recommended to keep the default). When checking the validity of licenses an extra license signature is also checked to prevent the use of a license on more than the configured allowed domains.
* **Packages remote source:** WP Plugin Update Server can act as a proxy and will help you to connect your clients with your plugins and themes kept on a remote repository, so that they are always up to date. Supports Bitbucket, Github and Gitlab, as well as self-hosted installations of Gitlab. Packages will not be installed on your server, only transferred to the clients whenever they request them.

To connect their plugins or themes and WP Plugin Update Server, developers can find integration examples in the `wp-plugin-update-server/integration-examples` directory, or check the [documentation of the WP Package Updater](https://github.com/froger-me/wp-package-updater/blob/master/README.md).

In addition, a [Must Use Plugin](https://codex.wordpress.org/Must_Use_Plugins) developers can add to the WordPress installation running WP Plugin Update Server is available in `wp-plugin-update-server/optimisation/wppus-endpoint-optimizer.php`.  

== Upgrade Information ==

When upgrading from v1.3, licenses from Software License Manager plugin will be migrated automatically, and SLM will be bypassed while doing license checks using the old API, and compatibility will be ensured until v2.0. It is recommended to dactivate or uninstall Software license Manager entirely. Developers will need to update the [WP Package Updater](https://github.com/froger-me/wp-package-updater) library before v2.0 as support for SLM API calls will be removed then.

== Roadmap ==

Aside from minor version updates (bugfixes, interface improvements and simple new features), below is the current roadmap. This can evolve depending on feedback, reviews and popularity.

* **v1.5**: Add statistics for packages passing through WP Plugin Update Server – number of updates, number of installs, etc for each package – potentially with CSV export.
* **v1.6**: Optionally include the [WP Package Updater](https://github.com/froger-me/wp-package-updater) and its logic to packages passing through WP Plugin Update Server (has to be done manually in version prior to 1.6, which may potentially lead to errors if not done properly). This feature would be enabled only on an opt-in basis.

== Special Thanks ==

A warm thank you to [Yahnis Elsts](https://github.com/YahnisElsts), the author of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) and [WP Update Server](https://github.com/YahnisElsts/wp-update-server) libraries, without whom the creation of this plugin would not have been possible.  
Authorisation to use these libraries freely provided relevant licenses are included has been graciously granted [here](https://github.com/YahnisElsts/wp-update-server/issues/37#issuecomment-386814776).

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/wp-plugin-update-server` directory, or install the plugin through the WordPress plugins screen directly
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Edit plugin settings

== Changelog ==

= 1.4.13 =
* Fix double slash in `WPPUS_Update_Server::$packageDirectory` path
* Use `basename` instead of `explode( DIRECTORY_SEPARATOR, $package_path )` in `WPPUS_Update_Manager`

= 1.4.12 =
* More in-depth Gitlab fix

= 1.4.11 =
* Bump version - supported by WordPress 5.0

= 1.4.10 =
* Fix Gitlab remote sources breaking after [Gitlab Security Release: 11.5.1, 11.4.8, and 11.3.11](https://about.gitlab.com/2018/11/28/security-release-gitlab-11-dot-5-dot-1-released/)
* Fix some minor warnings

= 1.4.9 =
* Better Remote Sources tab on-screen help (especially regarding Gitlab)
* Fix minor javascript issue on Licenses tab
* Minor code cleanup

= 1.4.8 =
* Require the zip PHP extension (use ZipArchive, no fallback to PclZip) - thanks @jacobrossdev

= 1.4.7 =
* Add server log in case of error when repacking package zip files.

= 1.4.6 =
* Fix path handling on Windows in WP Plugin Updater library (packages update recommended but not required if not using Windows Server in production environment)
* Add more server logs and more explicit error messages to ease troubleshoot

= 1.4.5 =
* Better error message handling in WP Plugin Updater library (packages update recommended but not required)
* Add default error in case of invalid license

= 1.4.4 =
* Fix PHP version check condition
* Fix database checks

= 1.4.3 =
* Licenses - fix typo in variable name
* Uninstall - simplify query
* Enforce PHP version 7.0 or higher on plugin activation
* Check if license table is present on activation after creation when necessary - die otherwise
* Add action hook before rebuilding packages for developers to perform file operations (feature request)

= 1.4.2 =
* Make sure to return a response (400) if package or action parameters are missing when calling update API
* Make sure to return a response (404) if a package cannot be found when calling update API

= 1.4.1 =
* Fix license expiry - make sure licenses without expiry do not expire
* Fix license expiry - make sure licenses without expiry can be bulk activated
* Fix license activation and deactivation when no expiry is provided
* Fix order in licenses table

= 1.4 =
* Handle licenses in WP Plugin Update Server
* Provide a compatible public API with SLM API (check, activate, deactivate)
* Provide a secure private license API (browse, read, edit, add, delete) - "add" compatible with "slm_create_new"
* Refactor main aspects of the plugin structure and UI
* Seamless upgrade from v1.3, migration of settings and licenses from SLM
* Automatic expiration of licenses wherever an expiration date is set
* Upgrade [WP Package Updater](https://github.com/froger-me/wp-package-updater) to v1.4.0
* Upgrade Dummy Plugin and Dummy Theme to v1.4 - ensure older versions are compatible with WP Plugin Update Server until v2.0
* Compatibility with v1.3 endpoints and v1.0.0 WP Package Updater ensured until v2.0
* License APIs, functions, actions and filters, with complete documentation
* Package download URLs now contains an unguessable token that changes everyday to prevent hotlinking
* Performance optimisations and tests script
* Add packages search
* Update [WP Update Migrate](https://github.com/froger-me/wp-update-migrate) to 1.2.2

= 1.3 =
* Full support for plugin icons & banners and theme details displayed to the client when there is a package update available.
* The MU Plugin <code>wppus-endpoint-optimizer.php</code> is now automatically included on plugin activation (still can be customized, still can safely be deleted, still optional - just very much recommended).
* Plugin interface revamped, with truely separated pages for each section.
* Refactor: schedule logic and zip packages management logic in dedicated classes
* Support for drag and drop uploads of packages

= 1.2.1 =
* Update MU Plugin `wppus-endpoint-optimizer.php`: add support for bypassing themes, add support for bypassing core actions and filters yet to be executed.
* Update [WP Update Migrate](https://github.com/froger-me/wp-update-migrate) to 1.2.0
* Update onscreen help
* Update documentation

= 1.2 =
* Move the plugin data directories to wp-content/wppus directory (or equivalent if WP_CONTENT_DIR is not the default)
* Add 13 functions, 18 actions and 8 filters for developers to use in their plugins and themes
* Update the client updater: change from `wp-plugin-updater` library to [WP Package Updater](https://github.com/froger-me/wp-package-updater), with backward compatibility until v2.0
* Include [WP Update Migrate](https://github.com/froger-me/wp-update-migrate) library to provide clean and full update path for current and future versions
* Interface improvements, documentation and various code refactoring

= 1.1 =
* General cleanup
* Add updater (prototype of WP Update Migrate library)

= 1.0.6 =
* Remove duplicates for "Remote update check frequency" values
* Fallback to the default "Once Daily" if the configured frequency has been removed (i.e. after a third party plugin has been deactivated)
* Add "Clear scheduled remote updates" button - useful to clean orphan wp-cron events
* Add "Reschedule remote updates" button - useful to reschedule wp-cron events for all existing packages

= 1.0.5 =
* Refactor update lock mechanism
* Force unlock update when adding a package
* Refresh interface when clearing cache and logs
* Cleanup - remove unused method and reorder methods by scope
* Fix "[License manager integration disabled, notification asks for it anyways](https://wordpress.org/support/topic/license-manager-integration-disabled-notification-asks-for-it-anyways/)"

= 1.0.4 =
* More zip MIME types supported - frontend
* Cleanup Dummy Theme
* Clarification of help tab - description of remote repository structure
* Add warning to readme.txt

= 1.0.3 =
* More path normalization of dummy packages (Windows support)
* More zip MIME types supported - backend
* Clarification of help tab - update checker code and libraries are **required**

= 1.0.2 =
* Use proper directory separator
* Add WP Plugin Udpate Server to the list of plugins to keep active in the request optimizer

= 1.0.1 =
* Normalize path of dummy packages (Windows support)
* Remove development comments
* Fix path in help tab

= 1.0 =
* First version