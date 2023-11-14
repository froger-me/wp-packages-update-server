# WP Packages Update Server - Packages - Developer documentation
(Looking for the main documentation page instead? [See here](https://github.com/froger-me/wp-packages-update-server/blob/master/README.md))  

WP Packages Update Server offers a series of functions, actions and filters for developers to use in their own plugins and themes to modify the behavior of the plugin when managing packages.  

* [WP Packages Update Server - Packages - Developer documentation](#wp-packages-update-server---packages---developer-documentation)
	* [API](#api)
		* [Public API](#public-api)
			* [download](#download)
		* [Private API](#private-api)
			* [browse](#browse)
			* [read](#read)
			* [edit](#edit)
			* [add](#add)
			* [delete](#delete)
			* [signed\_url](#signed_url)
	* [Functions](#functions)
		* [wppus\_get\_root\_data\_dir](#wppus_get_root_data_dir)
		* [wppus\_get\_packages\_data\_dir](#wppus_get_packages_data_dir)
		* [wppus\_get\_logs\_data\_dir](#wppus_get_logs_data_dir)
		* [wppus\_is\_doing\_update\_api\_request](#wppus_is_doing_update_api_request)
		* [wppus\_is\_doing\_package\_api\_request](#wppus_is_doing_package_api_request)
		* [wppus\_check\_remote\_package\_update](#wppus_check_remote_package_update)
		* [wppus\_check\_remote\_plugin\_update](#wppus_check_remote_plugin_update)
		* [wppus\_check\_remote\_theme\_update](#wppus_check_remote_theme_update)
		* [wppus\_download\_remote\_package](#wppus_download_remote_package)
		* [wppus\_download\_remote\_plugin](#wppus_download_remote_plugin)
		* [wppus\_download\_remote\_theme](#wppus_download_remote_theme)
		* [wppus\_force\_cleanup\_cache](#wppus_force_cleanup_cache)
		* [wppus\_force\_cleanup\_logs](#wppus_force_cleanup_logs)
		* [wppus\_force\_cleanup\_tmp](#wppus_force_cleanup_tmp)
		* [wppus\_get\_local\_package\_path](#wppus_get_local_package_path)
		* [wppus\_download\_local\_package](#wppus_download_local_package)
		* [wppus\_delete\_package](#wppus_delete_package)
		* [wppus\_get\_package\_info](#wppus_get_package_info)
		* [wppus\_get\_batch\_package\_info](#wppus_get_batch_package_info)
	* [Actions](#actions)
		* [wppus\_primed\_package\_from\_remote](#wppus_primed_package_from_remote)
		* [wppus\_did\_manual\_upload\_package](#wppus_did_manual_upload_package)
		* [wppus\_before\_packages\_download](#wppus_before_packages_download)
		* [wppus\_triggered\_package\_download](#wppus_triggered_package_download)
		* [wppus\_scheduled\_check\_remote\_event](#wppus_scheduled_check_remote_event)
		* [wppus\_registered\_check\_remote\_schedule](#wppus_registered_check_remote_schedule)
		* [wppus\_cleared\_check\_remote\_schedule](#wppus_cleared_check_remote_schedule)
		* [wppus\_scheduled\_cleanup\_event](#wppus_scheduled_cleanup_event)
		* [wppus\_registered\_cleanup\_schedule](#wppus_registered_cleanup_schedule)
		* [wppus\_cleared\_cleanup\_schedule](#wppus_cleared_cleanup_schedule)
		* [wppus\_did\_cleanup](#wppus_did_cleanup)
		* [wppus\_before\_handle\_update\_request](#wppus_before_handle_update_request)
		* [wppus\_downloaded\_remote\_package](#wppus_downloaded_remote_package)
		* [wppus\_saved\_remote\_package\_to\_local](#wppus_saved_remote_package_to_local)
		* [wppus\_checked\_remote\_package\_update](#wppus_checked_remote_package_update)
		* [wppus\_deleted\_package](#wppus_deleted_package)
		* [wppus\_before\_zip](#wppus_before_zip)
	* [Filters](#filters)
		* [wppus\_submitted\_data\_config](#wppus_submitted_data_config)
		* [wppus\_submitted\_remote\_sources\_config](#wppus_submitted_remote_sources_config)
		* [wppus\_schedule\_cleanup\_frequency](#wppus_schedule_cleanup_frequency)
		* [wppus\_check\_remote\_frequency](#wppus_check_remote_frequency)
		* [wppus\_handle\_update\_request\_params](#wppus_handle_update_request_params)
		* [wppus\_update\_api\_config](#wppus_update_api_config)
		* [wppus\_update\_server](#wppus_update_server)
		* [wppus\_update\_checker](#wppus_update_checker)



## API

The Package API is accessible via POST and GET requests on the `/wppus-package-api/` endpoint for both the Public and Private API, and via POST only for the Private API. It accepts form-data payloads (arrays, basically). This documentation page uses `wp_remote_post`, but `wp_remote_get` would work as well for the Public API.

In case the API is accessed with an invalid `action` parameter, the following response is returned (message's language depending on availabe translations), with HTTP response code set to `400`:

Response `$data` - malformed request:
```json
{
	"message": "Package API action not found"
}
```

The description of the API further below is using the following code as reference, where `$params` are the parameters passed to the API (other parameters can be adjusted, they are just WordPress' default) and `$data` is the JSON response:

```php
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin or theme), and package-slug with the slug of the package  

$response = wp_remote_post(
	$url,
	array(
		'method'      => 'POST',
		'timeout'     => 45,
		'redirection' => 5,
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => array(),
		'body'        => $params,
		'cookies'     => array(),
	);
);

if ( is_wp_error( $response ) ) {
	printf( esc_html__( 'Something went wrong: %s', 'text-domain' ), esc_html( $response->get_error_message() ) );
} else {
	$data         = wp_remote_retrieve_body( $response );
	$decoded_data = json_decode( $data );

	if ( '200' === $response->code ) {
		// Handle success with $decoded_data
	} else {
		// Handle failure with $decoded_data
	}
}
```

___
### Public API

The public API requires authorization through a nonce or token acquired via the [Nonce API](https://github.com/froger-me/wp-packages-update-server/blob/master/misc.md#nonce-api).

It provides a single operations: `download`.  

**The full URL is more easily acquired through the [signed_url](#signed_url) operation of the Private API. The URL provided by the `signed_url` operation already contains a valid token.**  

The URL can also be built manually, with a token can also be acquired with the following required parameters:

```php
$params = array(
	'data' => array(
		'package_id' => 'package-slug', // The slug of the package  
		'type'      => 'package-type',  // The type of package (plugin or theme)
		'actions'      => array(        // The actions the token can be used for
			'download',
		),
	),
);
```

___
#### download

The `download` operation retreives a package file. If no corresponding package exists on the file system, the operation fails.

```php
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin or theme), and package-slug with the slug of the package  
```

```php
$params = array(
	'action' => 'download',    // Action to perform when calling the Package API (required)
	'token'  => 'nonce_value', // The authorization token (required)
);
```

Response - **success**:
```
[filestream]
```

Response `$data` - **failure** (`404` response code - no result):
```json
{
    "message": "Package not found."
}
```

___
### Private API

The Private API, only accessible via the POST method, necessitates extra authentication for all its actions - `browse`, `edit`, `add`, `delete`.  

The first action, `browse`, is particular in the sense that, unlike the other actions, its endpoint must not include the `package-type/package-slug` part of the query string (`$url = 'https://domain.tld/wppus-package-api/';`).  

With the Private API, developers can perform any operation on the packages stored by WP Packages Update Server - **be careful to keep the Private API Authentication Key an absolute secret!**

The Private API Authentication Key can be provided either via the `api_auth_key` parameter, or by passing a `X-WPPUS-Private-Package-API-Key` header (recommended - it is then found in `$_SERVER['HTTP_X_WPPUS_PRIVATE_PACKAGE_API_KEY']` in PHP). 

In case the Private API Authentication Key is invalid, all the actions of the Private API return the same response (message's language depending on availabe translations), with HTTP response code set to `403`:

Response `$data` - forbidden access:
```json
{
	"message": "Unauthorized access"
}
```
In case the Private API is accessed via the `GET` method, all the actions return the same response (message's language depending on availabe translations), with HTTP response code set to `405`:

Response `$data` - unauthorized method:
```json
{
	"message": "Unauthorized GET method"
}
```
___
#### browse

The `browse` operation retreives package information, optionally filtered by a search keyword. If no corresponding package exists on the file system, or in the Remote Repository Service, the operation fails.

```php
$url = 'https://domain.tld/wppus-package-api/'; // Replace domain.tld with the domain where WP Packages Update Server is installed  
```

```php
$params = array(
	'action'       => 'browse',         // Action to perform when calling the Package API (required)
	'browse_query' => wp_json_encode(   
		array( 'search' => 'keyword' )
	),                                 // the JSON representation of an array with a single key 'search' with the value being the string to be used in package's slug and package's name (optional - case insensitive)
	'api_auth_key' => 'secret',        // The Private API Authentication Key (optional - must provided via X-WPPUS-Private-Package-API-Key headers if absent)
);
```

Response `$data` - **success**:
```json
{
	"theme-slug": {
		...
	},
	"plugin-slug": {
	   ...
	},
	...,
	"count": 99
}
```

Response `$data` - **failure** (`404` response code - no result):
```json
{
    "count": 0
}
```

___
#### read

The `read` operation retreives information for the specified package. If the package does not exist on the file system, or if the package does not exist in the Remote Repository Service, the operation fails.

```php
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin or theme), and package-slug with the slug of the package  
```

```php
$params = array(
	'action'       => 'read',   // Action to perform when calling the Package API (required)
	'api_auth_key' => 'secret', // The Private API Authentication Key (optional - must provided via X-WPPUS-Private-Package-API-Key headers if absent)
);
```

Response `$data` - **success**:

Values format in case of a plugin package:
```json
{
	"name": "Plugin Name",
	"version": "1.4.14",
	"homepage": "https:\/\/domain.tld\/",
	"author": "Author",
	"author_homepage": "https:\/\/domain.tld\/",
	"requires": "9.9.9",
	"tested": "9.9.9",
	"requires_php": "8.2",
	"sections": {
		"description": "<p>Plugin description. <strong>Basic HTML<\/strong> can be used in all sections.<\/p>",
		"extra_section": "<p>An extra section.<\/p>",
		"installation": "<p>Installation instructions.<\/p>",
		"changelog": "<p>This section will be displayed by default when the user clicks 'View version x.y.z details'.<\/p>"
	},
	"last_updated": "9999-00-00 99:99:99",
	"icons": {
		"1x": "https:\/\/domain.tld\/icon-128x128.png",
		"2x": "https:\/\/domain.tld\/icon-256x256.png"
	},
	"banners": {
		"low": "https:\/\/domain.tld\/banner-722x250.png",
		"high": "https:\/\/domain.tld\/banner-1544x500.png"
	},
	"slug": "plugin-slug",
	"type": "plugin",
	"file_name": "plugin-slug.zip",
	"file_path": "\/webroot\/wp-content\/wppus\/packages\/plugin-slug.zip",
	"file_size": 999,
	"file_last_modified": 9999
}
```

Values format in case of a theme package:
```json
{
	"name": "Theme Name",
	"version": "1.0.0",
	"homepage": "https:\/\/domain.tld\/",
	"author": "Author",
	"author_homepage": "https:\/\/domain.tld",
	"details_url": "https:\/\/domain.tld\/",
	"last_updated": "9999-00-00 99:99:99",
	"slug": "theme-slug",
	"type": "theme",
	"file_name": "theme-slug.zip",
	"file_path": "cloudStorage:\/\/wppus-packages\/theme-slug.zip",
	"file_size": 999,
	"file_last_modified": 9999
}
```

Response `$data` - **failure** (`404` response code - no result):
```json
false
```

___
#### edit

The `edit` operation downloads the package from the Remote Repository Service. If the "Use a Remote Repository Service" option is not active, or if the package does not exist in the Remote Repository Service, the operation fails.

```php
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin or theme), and package-slug with the slug of the package  
```

```php
$params = array(
	'action'              => 'edit',   // Action to perform when calling the Package API (required)
	'api_auth_key'        => 'secret', // The Private API Authentication Key (optional - must provided via X-WPPUS-Private-Package-API-Key headers if absent)
);
```

Response `$data` - **success**:

Values format in case of a plugin package:
```json
{
	"name": "Plugin Name",
	"version": "1.4.14",
	"homepage": "https:\/\/domain.tld\/",
	"author": "Author",
	"author_homepage": "https:\/\/domain.tld\/",
	"requires": "9.9.9",
	"tested": "9.9.9",
	"requires_php": "8.2",
	"sections": {
		"description": "<p>Plugin description. <strong>Basic HTML<\/strong> can be used in all sections.<\/p>",
		"extra_section": "<p>An extra section.<\/p>",
		"installation": "<p>Installation instructions.<\/p>",
		"changelog": "<p>This section will be displayed by default when the user clicks 'View version x.y.z details'.<\/p>"
	},
	"last_updated": "9999-00-00 99:99:99",
	"icons": {
		"1x": "https:\/\/domain.tld\/icon-128x128.png",
		"2x": "https:\/\/domain.tld\/icon-256x256.png"
	},
	"banners": {
		"low": "https:\/\/domain.tld\/banner-722x250.png",
		"high": "https:\/\/domain.tld\/banner-1544x500.png"
	},
	"slug": "plugin-slug",
	"type": "plugin",
	"file_name": "plugin-slug.zip",
	"file_path": "\/webroot\/wp-content\/wppus\/packages\/plugin-slug.zip",
	"file_size": 999,
	"file_last_modified": 9999
}
```

Values format in case of a theme package:
```json
{
	"name": "Theme Name",
	"version": "1.0.0",
	"homepage": "https:\/\/domain.tld\/",
	"author": "Author",
	"author_homepage": "https:\/\/domain.tld",
	"details_url": "https:\/\/domain.tld\/",
	"last_updated": "9999-00-00 99:99:99",
	"slug": "theme-slug",
	"type": "theme",
	"file_name": "theme-slug.zip",
	"file_path": "cloudStorage:\/\/wppus-packages\/theme-slug.zip",
	"file_size": 999,
	"file_last_modified": 9999
}
```

Response `$data` - **failure** (`400` response code):
```json
false
```

___
#### add

The `add` operation downloads the package from the Remote Repository Service if it does not exist on the file system. If the "Use a Remote Repository Service" option is not active, the package does not exist in the Remote Repository Service, or if the package already exists on the file system, the operation fails.

```php
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin or theme), and package-slug with the slug of the package  
```

```php
$params = array(
	'action'              => 'add',    // Action to perform when calling the Package API (required)
	'api_auth_key'        => 'secret', // The Private API Authentication Key (optional - must provided via X-WPPUS-Private-Package-API-Key headers if absent)
);
```

Response `$data` - **success**:


Values format in case of a plugin package:
```json
{
	"name": "Plugin Name",
	"version": "1.4.14",
	"homepage": "https:\/\/domain.tld\/",
	"author": "Author",
	"author_homepage": "https:\/\/domain.tld\/",
	"requires": "9.9.9",
	"tested": "9.9.9",
	"requires_php": "8.2",
	"sections": {
		"description": "<p>Plugin description. <strong>Basic HTML<\/strong> can be used in all sections.<\/p>",
		"extra_section": "<p>An extra section.<\/p>",
		"installation": "<p>Installation instructions.<\/p>",
		"changelog": "<p>This section will be displayed by default when the user clicks 'View version x.y.z details'.<\/p>"
	},
	"last_updated": "9999-00-00 99:99:99",
	"icons": {
		"1x": "https:\/\/domain.tld\/icon-128x128.png",
		"2x": "https:\/\/domain.tld\/icon-256x256.png"
	},
	"banners": {
		"low": "https:\/\/domain.tld\/banner-722x250.png",
		"high": "https:\/\/domain.tld\/banner-1544x500.png"
	},
	"slug": "plugin-slug",
	"type": "plugin",
	"file_name": "plugin-slug.zip",
	"file_path": "\/webroot\/wp-content\/wppus\/packages\/plugin-slug.zip",
	"file_size": 999,
	"file_last_modified": 9999
}
```

Values format in case of a theme package:
```json
{
	"name": "Theme Name",
	"version": "1.0.0",
	"homepage": "https:\/\/domain.tld\/",
	"author": "Author",
	"author_homepage": "https:\/\/domain.tld",
	"details_url": "https:\/\/domain.tld\/",
	"last_updated": "9999-00-00 99:99:99",
	"slug": "theme-slug",
	"type": "theme",
	"file_name": "theme-slug.zip",
	"file_path": "cloudStorage:\/\/wppus-packages\/theme-slug.zip",
	"file_size": 999,
	"file_last_modified": 9999
}
```

Response `$data` - **failure** (`409` response code - the package already exists on the file system):
```json
false
```

Response `$data` - **failure** (`400` response code - other cases):
```json
false
```

___
#### delete

The `delete` operation deletes the package from the file system. If the package does not exist on the file system, the operation fails.

```php
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin or theme), and package-slug with the slug of the package  
```

```php
$params = array(
	'action'       => 'delete', // Action to perform when calling the Package API (required)
	'api_auth_key' => 'secret', // The Private API Authentication Key (optional - must provided via X-WPPUS-Private-Package-API-Key headers if absent)
);
```

Response `$data` - **success**:
```json
true
```

Response `$data` - **failure** (`404` response code):
```json
false
```
___
#### signed_url

The `signed_url` operation returns a pubilc URL signed with a token to download a package with the `download` [operation](#download). By default, the token is reusable and the URL is valid for 60 minutes. If the package does not exist on the file system or in the Remote Repository Service, the operation fails.

```php
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin or theme), and package-slug with the slug of the package  
```

```php
$params = array(
	'action'       => 'signed_url', // Action to perform when calling the Package API (required)
	'api_auth_key' => 'secret',     // The Private API Authentication Key (optional - must provided via X-WPPUS-Private-Package-API-Key headers if absent)
);
```

Response `$data` - **success**:
```json
{
    "url": "https://domain.tld/wppus-package-api/package-type/package-slug/?token=nonce_value&action=download",
    "token": "nonce_value",
    "expiry": 999999999
}
```

Response `$data` - **failure** (`404` response code):
```json
{
	"message": "Package not found"
}
```

___
## Functions

The functions listed below are made publicly available by the plugin for theme and plugin developers. They can be used after the action `plugins_loaded` has been fired, or in a `plugins_loaded` action (just make sure the priority is above `-99`).  
Although the main classes can theoretically be instanciated without side effect if the `$hook_init` parameter is set to `false`, it is recommended to use only the following functions as there is no guarantee future updates won't introduce changes of behaviors.

___
### wppus_get_root_data_dir

```php
wppus_get_root_data_dir();
```

**Description**  
Get the path to the plugin's content directory.

**Return value**
> (string) the path to the plugin content's directory

___
### wppus_get_packages_data_dir

```php
wppus_get_packages_data_dir();
```

**Description**  
Get the path to the packages directory on the file system.

**Return value**
> (string) the path to the packages directory on the file system

___
### wppus_get_logs_data_dir

```php
wppus_get_logs_data_dir();
```

**Description**  
Get the path to the plugin's log directory.

**Return value**
> (string) the path to the plugin's log directory.

___
### wppus_is_doing_update_api_request

```php
wppus_is_doing_update_api_request();
```

**Description**  
Determine wether the current request is made by a client plugin or theme interacting with the plugin's API.

**Return value**
> (bool) `true` if the current request is a client plugin or theme interacting with the plugin's API, `false` otherwise

___
### wppus_is_doing_package_api_request

```php
wppus_is_doing_package_api_request()
```

**Description**
Determine wether the current request is made by a remote client interacting with the plugin's package API.

**Return value**
> (bool) `true` the current request is made by a remote client interacting with the plugin's package API, `false` otherwise

___
### wppus_check_remote_package_update

```php
wppus_check_remote_package_update( $package_slug, $type )
```

**Description**  
Determine wether the remote package is an updated version compared to the one on the file system.

**Parameters**  
`$package_slug`
> (string) slug of the package to check   

`$type`
> (string) type of the package  

**Return value**
> (string) path of the package on the file system

___
### wppus_check_remote_plugin_update

```php
wppus_check_remote_plugin_update( string $package_slug );
```

**Description**  
Determine wether the remote plugin package is an updated version compared to one on the file system.

**Parameters**  
`$package_slug`
> (string) slug of the plugin package to check  

**Return value**
> (bool) `true` if the remote plugin package is an updated version, `false` otherwise. If the local package does not exist, returns `true`

___
### wppus_check_remote_theme_update

```php
wppus_check_remote_theme_update( string $package_slug );
```

**Description**  
Determine wether the remote theme package is an updated version compared to the one on the file system.

**Parameters**  
`$package_slug`
> (string) slug of the theme package to check   

**Return value**
> (bool) `true` if the remote theme package is an updated version, `false` otherwise. If the package does not exist on the file system, returns `true`

___
### wppus_download_remote_package

```php
wppus_download_remote_package( $slug, $type )
```

**Description**  
Download a package from the Remote Repository down to the package directory on the file system.

**Parameters**  
`$package_slug`
> (string) slug of the package to download  

`$type`
> (string) type of the package  

**Return value**
> (bool) `true` if the plugin package was successfully downloaded, `false` otherwise

___
### wppus_download_remote_plugin

```php
wppus_download_remote_plugin( string $package_slug );
```

**Description**  
Download a plugin package from the Remote Repository down to the package directory on the file system.

**Parameters**  
`$package_slug`
> (string) slug of the plugin package to download  

**Return value**
> (bool) `true` if the plugin package was successfully downloaded, `false` otherwise

___
### wppus_download_remote_theme

```php
wppus_download_remote_theme( string $package_slug );
```

**Description**  
Download a theme package from the Remote Repository down to the package directory on the file system.

**Parameters**  
`$package_slug`
> (string) slug of the theme package to download  

**Return value**
> (bool) `true` if the theme package was successfully downloaded, `false` otherwise

___
### wppus_force_cleanup_cache

```php
wppus_force_cleanup_cache();
```

**Description**  
Force clean up the `cache` plugin data.

**Return value**
> (bool) `true` in case of success, `false` otherwise

___
### wppus_force_cleanup_logs

```php
wppus_force_cleanup_logs();
```

**Description**  
Force clean up the `logs` plugin data.

**Return value**
> (bool) `true` in case of success, `false` otherwise

___
### wppus_force_cleanup_tmp

```php
wppus_force_cleanup_tmp();
```

**Description**  
Force clean up the `tmp` plugin data.

**Return value**
> (bool) `true` in case of success, `false` otherwise

___
### wppus_get_local_package_path

```php
wppus_get_local_package_path( string $package_slug );
```

**Description**  
Get the path of a plugin or theme package on the file system

**Parameters**  
`$package_slug`
> (string) slug of the package  

**Return value**
> (string) path of the package on the file system

___
### wppus_download_local_package

```php
wppus_download_local_package( string $package_slug, string $package_path = null );
```

**Description**  
Start a download of a package from the file system and exits. 

**Parameters**  
`$package_slug`
> (string) slug of the package  

`$package_path`
> (string) path of the package on the file system - if `null`, will attempt to find it using `wppus_get_local_package_path( $package_slug )`   

___
### wppus_delete_package

```php
wppus_delete_package( $slug )
```

**Description**  
Deletes a package on the file system

**Parameters**  
`$package_slug`
> (string) slug of the package  

**Return value**
> (bool) whether the operation was successful
___

### wppus_get_package_info

```php
wppus_get_package_info( $package_slug, $json_encode = true )
```

**Description**  
Get information about a package on the file system

**Parameters**  
`$package_slug`
> (string) slug of the package  

`$json_encode`
> (bool) whether to return a JSON object (default) or a PHP associative array  

**Return value**
> (array|string) the package information as a PHP associative array or a JSON object  

Values format in case of a plugin package:
```json
{
	"name": "Plugin Name",
	"version": "1.4.14",
	"homepage": "https:\/\/domain.tld\/",
	"author": "Author",
	"author_homepage": "https:\/\/domain.tld\/",
	"requires": "9.9.9",
	"tested": "9.9.9",
	"requires_php": "8.2",
	"sections": {
		"description": "<p>Plugin description. <strong>Basic HTML<\/strong> can be used in all sections.<\/p>",
		"extra_section": "<p>An extra section.<\/p>",
		"installation": "<p>Installation instructions.<\/p>",
		"changelog": "<p>This section will be displayed by default when the user clicks 'View version x.y.z details'.<\/p>"
	},
	"last_updated": "9999-00-00 99:99:99",
	"icons": {
		"1x": "https:\/\/domain.tld\/icon-128x128.png",
		"2x": "https:\/\/domain.tld\/icon-256x256.png"
	},
	"banners": {
		"low": "https:\/\/domain.tld\/banner-722x250.png",
		"high": "https:\/\/domain.tld\/banner-1544x500.png"
	},
	"slug": "plugin-slug",
	"type": "plugin",
	"file_name": "plugin-slug.zip",
	"file_path": "\/webroot\/wp-content\/wppus\/packages\/plugin-slug.zip",
	"file_size": 999,
	"file_last_modified": 9999
}
```

Values format in case of a theme package:
```json
{
	"name": "Theme Name",
	"version": "1.0.0",
	"homepage": "https:\/\/domain.tld\/",
	"author": "Author",
	"author_homepage": "https:\/\/domain.tld",
	"details_url": "https:\/\/domain.tld\/",
	"last_updated": "9999-00-00 99:99:99",
	"slug": "theme-slug",
	"type": "theme",
	"file_name": "theme-slug.zip",
	"file_path": "cloudStorage:\/\/wppus-packages\/theme-slug.zip",
	"file_size": 999,
	"file_last_modified": 9999
}
```
___
### wppus_get_batch_package_info

```php
wppus_get_batch_package_info( $search, $json_encode = true )
```

**Description**  
Get batch information of packages on the file system

**Parameters**  
`$search`
> (string) search string to be used in package's slug and package's name (case insensitive)  

`$json_encode`
> (bool) whether to return a JSON object (default) or a PHP associative array  

**Return value**
> (array|string) the batch information as a PHP associative array or a JSON object ; each entry is formatted like in [wppus_get_package_info](#wppus_get_package_info)

Values format:
```json
{
	"theme-slug": {
		...
	},
	"plugin-slug": {
	   ...
	},
	...
}

```
___
## Actions

WP Packages Update Server gives developers the possibility to have their plugins react to some events with a series of custom actions.  
**Warning**: the actions below with the mention "Fired during client update API request" need to be used with caution. Although they may also be triggered when using the functions above, these actions will possibly be called when client packages request for updates. Registering functions doing heavy computation to these actions when client update API requests are handled can seriously degrade the server's performances.  

___
### wppus_primed_package_from_remote

```php
do_action( 'wppus_primed_package_from_remote', bool $result, string $slug );
```

**Description**  
Fired after an attempt to prime a package from a Remote Repository has been performed.  

**Parameters**  
`$result`
> (bool) `true` the operation was successful, `false` otherwise  

`$slug`
> (slug) slug of the package  

___
### wppus_did_manual_upload_package

```php
do_action( 'wppus_did_manual_upload_package', bool $result, string $type, string $slug );
```

**Description**  
Fired after an attempt to upload a package manually has been performed.  

**Parameters**  
`$result`
> (bool) `true` the operation was successful, `false` otherwise  

`$type`
> (string) type of package - "Plugin" or "Theme"  

`$slug`
> (slug) slug of the package  

___
### wppus_before_packages_download

```php
do_action( 'wppus_before_packages_download', string $archive_name, string $archive_path, array $package_slugs );
```

**Description**  
Fired before a packages download using the "Download" bulk action is triggered.  

**Parameters**  
`$archive_name`
> (string) the name of the archive containing the packages to be downloaded  

`$archive_path`
> (string) the path of the archive containing the packages to be downloaded  

`$package_slugs`
> (string) the slug of the packages to be downloaded  

___
### wppus_triggered_package_download

```php
do_action( 'wppus_triggered_package_download', string $archive_name, string $archive_path );
```

**Description**  
Fired after a download of an archive containing one or multiple packages has been triggered.  
Remark: at this point, headers have already been sent to the client of the request and registered actions should not produce any output.  

**Parameters**  
`$archive_name`
> (string) the name of the archive containing the packages to be downloaded  

`$archive_path`
> (string) the path of the archive containing the packages to be downloaded  

___
### wppus_scheduled_check_remote_event

```php
do_action( 'wppus_scheduled_check_remote_event', bool $result, string $slug, int $timestamp, string $frequency, string $hook, array $params );
```

**Description**  
Fired after a remote check event has been scheduled for a package.  
Fired during client update API request.  

**Parameters**  
`$result`
> (bool) `true` if the event was scheduled, `false` otherwise  

`$slug`
> (string) slug of the package for which the event was scheduled  

`$timestamp`
> (int) timestamp for when to run the event the first time after it's been scheduled  

`$frequency`
> (string) frequency at which the event would be ran  

`$hook`
> (string) event hook to fire when the event is ran  

`$params`
> (array) parameters passed to the actions registered to $hook when the event is ran  

___
### wppus_registered_check_remote_schedule

```php
do_action( 'wppus_registered_check_remote_schedule', string $slug, string $scheduled_hook, string $action_hook );
```

**Description**  
Fired after a remote check action has been registered for a package.  
Fired during client update API request.  

**Parameters**  
`$slug`
> (string) the slug of the package for which an action has been registered  

`$scheduled_hook`
> (string) the event hook the action has been registered to (see `wppus_scheduled_check_remote_event` action)  

`$action_hook`
> (string) the action that has been registered  

___
### wppus_cleared_check_remote_schedule

```php
do_action( 'wppus_cleared_check_remote_schedule', string $slug, string $scheduled_hook, array $params );
```

**Description**  
Fired after a remote check schedule event has been unscheduled for a package.  
Fired during client update API request.  

**Parameters**  
`$slug`
> (string) the slug of the package for which a remote check event has been unscheduled  

`$scheduled_hook`
> (string) the remote check event hook that has been unscheduled  

`$params`
> (array) the parameters that were passed to the actions registered to the remote check event    

___
### wppus_scheduled_cleanup_event

```php
do_action( 'wppus_scheduled_cleanup_event', bool $result, string $type, int $timestamp, string $frequency, string $hook, array $params );
```

**Description**  
Fired after a cleanup event has been scheduled for a type of plugin data.  

**Parameters**  
`$result`
> (bool) `true` if the event was scheduled, `false` otherwise  

`$type`
> (string) plugin data type for which the event was scheduled (`cache`, `logs`,or `tmp`)  

`$timestamp`
> (int) timestamp for when to run the event the first time after it's been scheduled  

`$frequency`
> (string) frequency at which the event would be ran  

`$hook`
> (string) event hook to fire when the event is ran  

`$params`
> (array) parameters passed to the actions registered to $hook when the event is ran  

___
### wppus_registered_cleanup_schedule

```php
do_action( 'wppus_registered_cleanup_schedule', string $type, array $params );
```

**Description**  
Fired after a cleanup action has been registered for a type of plugin data.  

**Parameters**  
`$type`
> (string) plugin data type for which or which an action has been registered (`cache`, `logs`,or `tmp`)  

`$params`
> (array) the parameters passed to the registered cleanup action  

___
### wppus_cleared_cleanup_schedule

```php
do_action( 'wppus_cleared_cleanup_schedule', string $type, array $params );
```

**Description**  
Fired after a cleanup schedule event has been unscheduled for a type of plugin data.  

**Parameters**  
`$slug`
> (string) plugin data type for which a cleanup event has been unscheduled (`cache`, `logs`,or `tmp`)  

`$params`
> (array) the parameters that were passed to the actions registered to the cleanup event  

___
### wppus_did_cleanup

```php
do_action( 'wppus_did_cleanup', bool $result, string $type, int $size, bool $force );
```

**Description**  
Fired after cleanup was attempted for a type of plugin data.  

**Parameters**  
`$result`
> (bool) `true` if the clean up was successful, flase otherwise  

`$type`
> (string) type of data to clean up (`cache`, `logs`,or `tmp`)  

`$size`
> (int) the size of the data in before the clean up attempt (in bytes)  

`$force`
> (bool) `true` if it was a forced cleanup (without checking if the size was beyond the maximum set size), `false` otherwise  

___
### wppus_before_handle_update_request

```php
do_action( 'wppus_before_handle_update_request', array $request_params );
```

**Description**  
Fired before handling the request made by a client plugin or theme to the plugin's API.  
Fired during client update API request.  

**Parameters**
`$request_params`
> (array) the parameters or the request to the API.

___
### wppus_downloaded_remote_package

```php
do_action( 'wppus_downloaded_remote_package', mixed $package, string $type, string $slug );
```

**Description**  
Fired after an attempt to download a package from the Remote Repository Service down to the file system has been performed.  
Fired during client update API request.  

**Parameters**  
`$package`
> (mixed) full path to the package temporary file in case of success, WP_Error object otherwise  

`$type`
> (string) type of the downloaded package - "Plugin" or "Theme"  

`$slug`
> (string) slug of the downloaded package  

___
### wppus_saved_remote_package_to_local

```php
do_action( 'wppus_saved_remote_package_to_local', bool $result, string $type, string $slug );
```

**Description**  
Fired after an attempt to save a downloaded package on the file system hase been performed.  
Fired during client update API request.  

**Parameters**  
`$result`
> (bool) `true` in case of success, `false` otherwise  

`$type`
> (string) type of the saved package - "Plugin" or "Theme"  

`$slug`
> (string) slug of the saved package  

___
### wppus_checked_remote_package_update

```php
do_action( 'wppus_checked_remote_package_update', bool $has_update, string $type, string $slug );
```

**Description**  
Fired after an update check on the Remote Repository has been performed for a package.  
Fired during client update API request.  

**Parameters**  
`$has_update`
> (bool) `true` is the package has updates on the Remote Repository, `false` otherwise  

`$type`
> (string) type of the package checked - "Plugin" or "Theme"  

`$slug`
> (string) slug of the package checked  

___
### wppus_deleted_package

```php
do_action( 'wppus_deleted_package', bool $result, string $type, string $slug );
```

**Description**  
Fired after a package has been deleted from the file system.  
Fired during client update API request.  

**Parameters**  
`$result`
> (bool) `true` if the package has been deleted on the file system  

`$type`
> (string) type of the deleted package - "Plugin" or "Theme"  

`$slug`
> (string) slug of the deleted package  

___
### wppus_before_zip


```php
do_action( 'wppus_before_remote_package_zip', (string) $package_slug, (string) $files_path, (string) $archive_path );
```

**Description**  
Fired before packing the files received from the Remote Repository. Can be used for extra files manipulation.  
Fired during client update API request.  

**Parameters**  
`$package_slug`
> (string) the slug of the package  

`$files_path`
> (string) the path of the directory where the package files are located  

`$archive_path`
> (string) the path where the package archive will be located after packing  

___
## Filters

WP Packages Update Server gives developers the possibility to customise its behavior with a series of custom filters.  
**Warning**: the filters below with the mention "Fired during client update API request" need to be used with caution. Although they may be triggered when using the functions above, these filters will possibly be called when client packages request for updates. Registering functions doing heavy computation to these filters when client update API requests are handled can seriously degrade the server's performances.  

___
### wppus_submitted_data_config

```php
apply_filters( 'wppus_submitted_data_config', array $config );
```

**Description**  
Filter the submitted plugin data configuration values before saving them.  

**Parameters**  
`$config`
> (array) the submitted plugin data configuration values

___
### wppus_submitted_remote_sources_config

```php
apply_filters( 'wppus_submitted_remote_sources_config', array $config );
```

**Description**  
Filter the submitted remote sources configuration values before saving them.  

**Parameters**  
`$config`
> (array) the submitted remote sources configuration values

___
### wppus_schedule_cleanup_frequency

```php
apply_filters( 'wppus_schedule_cleanup_frequency', string $frequency, string $type );
```

**Description**  
Filter the cleanup frequency. 

**Parameters**  
`$frequency`
> (string) the frequency - default 'hourly'  

`$type`
> (string) plugin data type to be clened up (`cache`, `logs`,or `tmp`)  

___
### wppus_check_remote_frequency

```php
apply_filters( 'wppus_check_remote_frequency', string $frequency, string $slug );
```

**Description**  
Filter the package update remote check frequency set in the configuration.  
Fired during client update API request.  

**Parameters**  
`$frequency`
> (string) the frequency set in the configuration  

`$slug`
> (string) the slug of the package to check for updates  

___
### wppus_handle_update_request_params

```php
apply_filters( 'wppus_handle_update_request_params' , array $params );
```

**Description**  
Filter the parameters used to handle the request made by a client plugin or theme to the plugin's API.  
Fired during client update API request.  

**Parameters**  
`$params`
> (array) the parameters of the request to the API  

___
### wppus_update_api_config

```php
apply_filters( 'wppus_update_api_config', array $config );
```

**Description**  
Filter the update API configuration values before using them.  
Fired during client update API request.  

**Parameters**  
`$config`
> (array) the update api configuration values  

___
### wppus_update_server

```php
apply_filters( 'wppus_update_server', mixed $update_server, array $config, string $slug );
```

**Description**  
Filter the Wppus_Update_Server object to use.  
Fired during client update API request.  

**Parameters**  
`$update_server`
> (mixed) the Wppus_Update_Server object  

`$config`
> (array) the configuration values passed to the Wppus_Update_Server object  

`$slug`
> (string) the slug of the package using the Wppus_Update_Server object  

___
### wppus_update_checker

```php
apply_filters( 'wppus_update_checker', mixed $update_checker, string $slug, string $type, string $package_file_name, string $repository_service_url, string $repository_branch, mixed $repository_credentials, bool $repository_service_self_hosted );
```

**Description**  
Filter the checker object used to perform remote checks and downloads.  
Fired during client update API request.  

**Parameters**  
`$update_checker`
> (mixed) the checker object  

`$slug`
> (string) the slug of the package using the checker object  

`$type`
> (string) the type of the package using the checker object - "Plugin" or "Theme" 

`$package_file_name`
> (string) the name of the main plugin file or "styles.css" for the package using the checker object  

`$repository_service_url`
> (string) URL of the repository service where the remote packages are located  

`$repository_branch`
> (string) the branch of the Remote Repository where the packages are located  

`$repository_credentials`
> (mixed) the credentials to access the Remote Repository where the packages are located  

`$repository_service_self_hosted`
> (bool) `true` if the Remote Repository is on a self-hosted repository service, `false` otherwiseark  
