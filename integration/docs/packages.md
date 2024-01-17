# WP Packages Update Server - Packages - Developer documentation
(Looking for the main documentation page instead? [See here](https://github.com/froger-me/wp-packages-update-server/blob/main/README.md))  

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
		* [wppus\_removed\_package](#wppus_removed_package)
		* [wppus\_before\_zip](#wppus_before_zip)
		* [wppus\_did\_browse\_package](#wppus_did_browse_package)
		* [wppus\_did\_read\_package](#wppus_did_read_package)
		* [wppus\_did\_edit\_package](#wppus_did_edit_package)
		* [wppus\_did\_add\_package](#wppus_did_add_package)
		* [wppus\_pre\_delete\_package](#wppus_pre_delete_package)
		* [wppus\_did\_delete\_package](#wppus_did_delete_package)
		* [wppus\_did\_download\_package](#wppus_did_download_package)
		* [wppus\_did\_signed\_url\_package](#wppus_did_signed_url_package)
		* [wppus\_package\_api\_request](#wppus_package_api_request)
		* [wppus\_remote\_sources\_options\_updated](#wppus_remote_sources_options_updated)
		* [wppus\_package\_options\_updated](#wppus_package_options_updated)
		* [wppus\_check\_remote\_update](#wppus_check_remote_update)
		* [wppus\_udpdate\_manager\_request\_action](#wppus_udpdate_manager_request_action)
		* [wppus\_package\_manager\_pre\_delete\_package](#wppus_package_manager_pre_delete_package)
		* [wppus\_package\_manager\_deleted\_package](#wppus_package_manager_deleted_package)
		* [wppus\_package\_manager\_pre\_delete\_packages\_bulk](#wppus_package_manager_pre_delete_packages_bulk)
		* [wppus\_package\_manager\_deleted\_packages\_bulk](#wppus_package_manager_deleted_packages_bulk)
		* [wppus\_before\_packages\_download\_repack](#wppus_before_packages_download_repack)
		* [wppus\_triggered\_packages\_download](#wppus_triggered_packages_download)
		* [wppus\_after\_packages\_download](#wppus_after_packages_download)
		* [wppus\_get\_package\_info](#wppus_get_package_info-1)
		* [wppus\_find\_package\_no\_cache](#wppus_find_package_no_cache)
		* [wppus\_update\_server\_action\_download](#wppus_update_server_action_download)
		* [wppus\_webhook\_before\_processing\_request](#wppus_webhook_before_processing_request)
		* [wppus\_webhook\_after\_processing\_request](#wppus_webhook_after_processing_request)
		* [wppus\_packages\_table\_cell](#wppus_packages_table_cell)
	* [Filters](#filters)
		* [wppus\_submitted\_package\_config](#wppus_submitted_package_config)
		* [wppus\_submitted\_remote\_sources\_config](#wppus_submitted_remote_sources_config)
		* [wppus\_schedule\_cleanup\_frequency](#wppus_schedule_cleanup_frequency)
		* [wppus\_check\_remote\_frequency](#wppus_check_remote_frequency)
		* [wppus\_handle\_update\_request\_params](#wppus_handle_update_request_params)
		* [wppus\_update\_api\_config](#wppus_update_api_config)
		* [wppus\_update\_server](#wppus_update_server)
		* [wppus\_update\_checker](#wppus_update_checker)
		* [wppus\_cloud\_storage\_virtual\_dir](#wppus_cloud_storage_virtual_dir)
		* [wppus\_could\_storage\_api\_config](#wppus_could_storage_api_config)
		* [wppus\_package\_api\_config](#wppus_package_api_config)
		* [wppus\_package\_browse](#wppus_package_browse)
		* [wppus\_package\_read](#wppus_package_read)
		* [wppus\_package\_edit](#wppus_package_edit)
		* [wppus\_package\_add](#wppus_package_add)
		* [wppus\_package\_delete](#wppus_package_delete)
		* [wppus\_package\_signed\_url](#wppus_package_signed_url)
		* [wppus\_package\_signed\_url\_token](#wppus_package_signed_url_token)
		* [wppus\_package\_public\_api\_actions](#wppus_package_public_api_actions)
		* [wppus\_package\_api\_request\_authorized](#wppus_package_api_request_authorized)
		* [wppus\_packages\_table\_columns](#wppus_packages_table_columns)
		* [wppus\_packages\_table\_sortable\_columns](#wppus_packages_table_sortable_columns)
		* [wppus\_packages\_table\_bulk\_actions](#wppus_packages_table_bulk_actions)
		* [wppus\_use\_recurring\_schedule](#wppus_use_recurring_schedule)
		* [wppus\_remote\_sources\_manager\_get\_package\_slugs](#wppus_remote_sources_manager_get_package_slugs)
		* [wppus\_server\_class\_name](#wppus_server_class_name)
		* [wppus\_delete\_packages\_bulk\_paths](#wppus_delete_packages_bulk_paths)
		* [wppus\_package\_info](#wppus_package_info)
		* [wppus\_batch\_package\_info\_include](#wppus_batch_package_info_include)
		* [wppus\_package\_manager\_batch\_package\_info](#wppus_package_manager_batch_package_info)
		* [wppus\_check\_remote\_package\_update\_local\_meta](#wppus_check_remote_package_update_local_meta)
		* [wppus\_check\_remote\_package\_update\_no\_local\_meta\_needs\_update](#wppus_check_remote_package_update_no_local_meta_needs_update)
		* [wppus\_remove\_package\_result](#wppus_remove_package_result)
		* [wppus\_update\_server\_action\_download\_handled](#wppus_update_server_action_download_handled)
		* [wppus\_save\_remote\_to\_local](#wppus_save_remote_to_local)
		* [wppus\_webhook\_package\_exists](#wppus_webhook_package_exists)
		* [wppus\_webhook\_process\_request](#wppus_webhook_process_request)
		* [wppus\_package\_option\_update](#wppus_package_option_update)
		* [wppus\_remote\_source\_option\_update](#wppus_remote_source_option_update)
		* [wppus\_api\_package\_actions](#wppus_api_package_actions)

## API

The Package API is accessible via POST and GET requests on the `/wppus-package-api/` endpoint for both the Public and Private API, and via POST only for the Private API. It accepts form-data payloads (arrays, basically). This documentation page uses `wp_remote_post`, but `wp_remote_get` would work as well for the Public API.

In case the API is accessed with an invalid `action` parameter, the following response is returned (message's language depending on available translations), with HTTP response code set to `400`:

Response `$data` - malformed request:
```json
{
	"message": "Package API action not found"
}
```

The description of the API further below is using the following code as reference, where `$params` are the parameters passed to the API (other parameters can be adjusted, they are just WordPress' default) and `$data` is the JSON response:

```php
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin, theme. generic), and package-slug with the slug of the package  

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

	if ( '200' === $response['response']['code'] ) {
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
		'package_slug' => 'package-slug', // The slug of the package  
		'type'         => 'package-type', // The type of package (plugin, theme, generic)
		'actions'      => array(          // The actions the token can be used for
			'download',
		),
	),
);
```

___
#### download

The `download` operation retrieves a package file. If no corresponding package exists on the file system, the operation fails.

```php
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin, theme. generic), and package-slug with the slug of the package  
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

The Private API, only accessible via the POST method, requires extra authentication for all its actions - `browse`, `edit`, `add`, `delete`.  
The first action, `browse`, is particular in the sense that, unlike the other actions, its endpoint must not include the `package-type/package-slug` part of the query string (`$url = 'https://domain.tld/wppus-package-api/';`).  
With the Private API, depending on granted privileges, developers can theoretically perform any operation on the packages stored by WP Packages Update Server - **be careful to keep the Private API Authentication Key an absolute secret!**

To access the Private API, an authentication token must first be obtained with the [Nonce API](https://github.com/froger-me/wp-packages-update-server/blob/master/misc.md#nonce-api) ; for example:

```php
// We assume the API Key is stored in environment variables
$api_key = getenv( 'WPPUS_PACKAGE_API_KEY' );
$url     = 'https://domain.tld/wppus-token/'; // Replace domain.tld with the domain where WP Packages Update Server is installed.

$response = wp_remote_post(
	$url,
	array(
		'headers'     => array(
			'X-WPPUS-Private-Package-API-Key' => $api_key,
		),
		'body'        => array(
			'api_auth_key'  => 'secret',  // Only used if X-WPPUS-Private-Package-API-Key is not set
			'api'           => 'package', // Only used if X-WPPUS-Private-Package-API-Key is not set
		),
	);
);

if ( is_wp_error( $response ) ) {
	printf( esc_html__( 'Something went wrong: %s', 'text-domain' ), esc_html( $response->get_error_message() ) );
} else {
	$data = wp_remote_retrieve_body( $response );

	if ( '200' === $response['response']['code'] ) {
		error_log( $data );
	} else {
		// Handle failure with $data
	}
}
```

In the above example, the `$data` variable looks like:

```json
{
    "nonce": "e7466375e8c851564653c6f7de81cd8f", // the authentication token
    "true_nonce": false,                         // whether the token can be only used once before it expires
    "expiry": 9999999999,                        // when the token expires - default is +30 minutes
    "data": {                                    // the data stored with the token
        "package_api": {                         // the package API data corresponding to the API key, generated by WPPUS
            "id": "api_key_id",                  // the ID of the API key
            "access": [                          // the list of authorized access privileges - `all` means access to everything related to packages on WPPUS
                ...
            ]
        }
    }
}
```
Once an authentication token has been obtained, it needs to be provided to API actions, either via the `api_token` parameter, or by passing a `X-WPPUS-Token` header (recommended - it is then found in `$_SERVER['HTTP_X_WPPUS_TOKEN']` in PHP).  
In case the token is invalid, all the actions of the Private API return the same response (message's language depending on available translations), with HTTP response code set to `403`:

Response `$data` - forbidden access:
```json
{
	"message": "Unauthorized access"
}
```
In case the Private API is accessed via the `GET` method, all the actions return the same response (message's language depending on available translations), with HTTP response code set to `405`:

Response `$data` - unauthorized method:
```json
{
	"message": "Unauthorized GET method"
}
```
___
#### browse

The `browse` operation retrieves package information, optionally filtered by a search keyword. If no corresponding package exists on the file system, or in the Remote Repository Service, the operation fails.

```php
$url = 'https://domain.tld/wppus-package-api/'; // Replace domain.tld with the domain where WP Packages Update Server is installed  
```

```php
$params = array(
	'action'       => 'browse',         // Action to perform when calling the Package API (required)
	'browse_query' => wp_json_encode(   
		array( 'search' => 'keyword' )
	),                                 // the JSON representation of an array with a single key 'search' with the value being the keyword used to search in package's slug and package's name (optional - case insensitive)
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
	"generic-slug": {
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

The `read` operation retrieves information for the specified package. If the package does not exist on the file system, or if the package does not exist in the Remote Repository Service, the operation fails.

```php
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin, theme, generic), and package-slug with the slug of the package  
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

Values format in case of a generic package:
```json
{
	"name": "Generic Package Name",
	"version": "1.0.0",
	"homepage": "https:\/\/domain.tld\/",
	"author": "Author",
	"author_homepage": "https:\/\/domain.tld",
	"description": "Generic package description.",
	"last_updated": "9999-00-00 99:99:99",
	"slug": "generic-slug",
	"type": "generic",
	"file_name": "generic-slug.zip",
	"file_path": "cloudStorage:\/\/wppus-packages\/generic-slug.zip",
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
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin, theme, generic), and package-slug with the slug of the package  
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

Values format in case of a generic package:
```json
{
	"name": "Generic Package Name",
	"version": "1.0.0",
	"homepage": "https:\/\/domain.tld\/",
	"author": "Author",
	"author_homepage": "https:\/\/domain.tld",
	"description": "Generic package description.",
	"last_updated": "9999-00-00 99:99:99",
	"slug": "generic-slug",
	"type": "generic",
	"file_name": "generic-slug.zip",
	"file_path": "cloudStorage:\/\/wppus-packages\/generic-slug.zip",
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
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin, theme, generic), and package-slug with the slug of the package  
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

Values format in case of a generic package:
```json
{
	"name": "Generic Package Name",
	"version": "1.0.0",
	"homepage": "https:\/\/domain.tld\/",
	"author": "Author",
	"author_homepage": "https:\/\/domain.tld",
	"description": "Generic package description.",
	"last_updated": "9999-00-00 99:99:99",
	"slug": "generic-slug",
	"type": "generic",
	"file_name": "generic-slug.zip",
	"file_path": "cloudStorage:\/\/wppus-packages\/generic-slug.zip",
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
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin, theme, generic), and package-slug with the slug of the package  
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

The `signed_url` operation returns a public URL signed with a token to download a package with the `download` [operation](#download). By default, the token is reusable and the URL is valid for 60 minutes. If the package does not exist on the file system or in the Remote Repository Service, the operation fails.

```php
$url = 'https://domain.tld/wppus-package-api/package-type/package-slug/'; // Replace domain.tld with the domain where WP Packages Update Server is installed, package-type with the type of package (plugin, theme, generic), and package-slug with the slug of the package  
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
Although the main classes can theoretically be instantiated without side effect if the `$hook_init` parameter is set to `false`, it is recommended to use only the following functions as there is no guarantee future updates won't introduce changes of behaviors.

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
Determine whether the current request is made by a client plugin, theme, or generic package interacting with the plugin's API.

**Return value**
> (bool) `true` if the current request is a client plugin, theme, or generic package interacting with the plugin's API, `false` otherwise

___
### wppus_is_doing_package_api_request

```php
wppus_is_doing_package_api_request()
```

**Description**
Determine whether the current request is made by a remote client interacting with the plugin's package API.

**Return value**
> (bool) `true` the current request is made by a remote client interacting with the plugin's package API, `false` otherwise

___
### wppus_check_remote_package_update

```php
wppus_check_remote_package_update( $package_slug, $type )
```

**Description**  
Determine whether the remote package is an updated version compared to the one on the file system.

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
Determine whether the remote plugin package is an updated version compared to one on the file system.

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
Determine whether the remote theme package is an updated version compared to the one on the file system.

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
Get the path of a plugin, theme, or generic package on the file system

**Parameters**  
`$package_slug`
> (string) slug of the package  

**Return value**
> (string) path of the package on the **local** file system

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
> (string) path of the package on the **local** file system - if `null`, will attempt to find it using `wppus_get_local_package_path( $package_slug )`   

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

Values format in case of a generic package:
```json
{
	"name": "Generic Package Name",
	"version": "1.0.0",
	"homepage": "https:\/\/domain.tld\/",
	"author": "Author",
	"author_homepage": "https:\/\/domain.tld",
	"description": "Generic package description.",
	"last_updated": "9999-00-00 99:99:99",
	"slug": "generic-slug",
	"type": "generic",
	"file_name": "generic-slug.zip",
	"file_path": "cloudStorage:\/\/wppus-packages\/generic-slug.zip",
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
	"generic-slug": {
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
do_action( 'wppus_cleared_check_remote_schedule', string $slug, string $scheduled_hook );
```

**Description**  
Fired after a remote check schedule event has been unscheduled for a package.  
Fired during client update API request.  

**Parameters**  
`$slug`
> (string) the slug of the package for which a remote check event has been unscheduled  

`$scheduled_hook`
> (string) the remote check event hook that has been unscheduled  

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
> (bool) `true` if the clean up was successful, `false` otherwise  

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
Fired before handling the request made by a client plugin, theme, or generic package to the plugin's API.  
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
Fired after an attempt to save a downloaded package on the file system has been performed.  
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
### wppus_removed_package

```php
do_action( 'wppus_removed_package', bool $result, string $type, string $slug );
```

**Description**  
Fired after a package has been removed from the file system.  
Fired during client update API request.  

**Parameters**  
`$result`
> (bool) `true` if the package has been removed on the file system  

`$type`
> (string) type of the removed package - "Plugin" or "Theme"  

`$slug`
> (string) slug of the removed package  

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
### wppus_did_browse_package

```php
do_action( 'wppus_did_browse_package', array $result );
```

**Description**  
Fired after the `browse` Package API action.

**Parameters**  
`$result`
> (array) the result of the action  

___
### wppus_did_read_package

```php
do_action( 'wppus_did_read_package', array $result );
```

**Description**  
Fired after the `read` Package API action.

**Parameters**  
`$result`
> (array) the result of the action  

___
### wppus_did_edit_package

```php
do_action( 'wppus_did_edit_package', array $result );
```

**Description**  
Fired after the `edit` Package API action.

**Parameters**  
`$result`
> (array) the result of the action  

___
### wppus_did_add_package

```php
do_action( 'wppus_did_add_package', array $result );
```

**Description**  
Fired after the `add` Package API action.

**Parameters**  
`$result`
> (array) the result of the action  

___
### wppus_pre_delete_package

```php
do_action( 'wppus_pre_delete_package', string $package_slug, string $type );
```

**Description**  
Fired before the `delete` Package API action.  

**Parameters**  
`$package_slug`
> (string) the slug of the package to be deleted  

`$type`
> (string) the type of the package to be deleted  

___
### wppus_did_delete_package

```php
do_action( 'wppus_did_delete_package', bool $result, string $package_slug, string $type );
```

**Description**  
Fired after the `delete` Package API action.  

**Parameters**  
`$result`
> (bool) the result of the `delete` operation  

`$package_slug`
> (string) the slug of the deleted package  

`$type`
> (string) the type of the deleted package  

___
### wppus_did_download_package

```php
do_action( 'wppus_did_download_package', string $package_slug );
```

**Description**  
Fired after the `download` Package API action.

**Parameters**  
`$package_slug`
> (string) the slug of the downloaded package  

___
### wppus_did_signed_url_package

```php
do_action( 'wppus_did_signed_url_package', array $result );
```

**Description**  
Fired after the `signed_url` Package API action.

**Parameters**  
`$result`
> (array) the result of the action  

___
### wppus_package_api_request

```php
do_action( 'wppus_package_api_request', string $method, array $payload );
```

**Description**  
Fired before the Package API request is processed ; useful to bypass the execution of currently implemented actions, or implement new actions. 

**Parameters**  
`$action`
> (string) the Package API action  

`$payload`
> (array) the payload of the request  

___
### wppus_remote_sources_options_updated

```php
do_action( 'wppus_remote_sources_options_updated', array $errors );
```

**Description**  
Fired after the options in "Remote Sources" have been updated.

**Parameters**  
`$errors`
> (array) an array of containing errors if any  

___

### wppus_package_options_updated

```php
do_action( 'wppus_package_options_updated', array $errors );
```

**Description**  
Fired after the options in "Packages Overview" have been updated.

**Parameters**  
`$errors`
> (array) an array of containing errors if any  

___
### wppus_check_remote_update

```php
do_action( 'wppus_check_remote_update', string $package_slug );
```

**Description**  
Fired before checking if the package on the remote repository has updates.  
Fired during client update API request.

**Parameters**  
`$package_slug`
> (string) the slug of the package on the remote repository  

___
### wppus_udpdate_manager_request_action

```php
do_action( 'wppus_udpdate_manager_request_action', string $action, array $package_slugs );
```

**Description**  
Fired if the action sent by the admin interface is not `'delete'` or `'download'`.

**Parameters**  
`$action`
> (string) the action sent by the admin interface  

`$package_slugs`
> (array) the slugs of the packages on which to perform the action  

___
### wppus_package_manager_pre_delete_package

```php
do_action( 'wppus_package_manager_pre_delete_package', string $package_slug );
```

**Description**  
Fired before a package is deleted as part of a bulk from the file system.

**Parameters**  
`$package_slug`
> (string) the slug of the package to be deleted  

___
### wppus_package_manager_deleted_package

```php
do_action( 'wppus_package_manager_deleted_package', string $package_slug );
```

**Description**  
Fired after a package was deleted as part of a bulk from the file system.

**Parameters**  
`$package_slug`
> (string) the slug of the deleted package  

___
### wppus_package_manager_pre_delete_packages_bulk

```php
do_action( 'wppus_package_manager_pre_delete_packages_bulk', array $package_slugs );
```

**Description**  
Fired before packages are deleted in bulk from the file system.

**Parameters**  
`$package_slugs`
> (array) the slugs of the packages to be deleted  

___
### wppus_package_manager_deleted_packages_bulk

```php
do_action( 'wppus_package_manager_deleted_packages_bulk', array $deleted_package_slugs );
```

**Description**  
Fired after packages were deleted in bulk from the file system.

**Parameters**  
`$deleted_package_slugs`
> (array) the slugs of the deleted packages  

___
### wppus_before_packages_download_repack

```php
do_action( 'wppus_before_packages_download_repack', string $archive_name, string $archive_path, array $package_slugs );
```

**Description**  
Fired before an archive containing multiple packages for download is created.

**Parameters**  
`$archive_name`
> (string) the name of the archive to create  

`$archive_path`
> (string) the absolute path of the archive to create  

`$package_slugs`
> (array) the slugs of the packages to include in the archive  

___
### wppus_triggered_packages_download

```php
do_action( 'wppus_triggered_packages_download', string $archive_name, string $archive_path );
```

**Description**  
Fired after download for an archive containing one or multiple packages has been triggered and before the content is streamed.

**Parameters**  
`$archive_name`
> (string) the name of the archive  

`$archive_path`
> (string) the absolute path of the archive  

___
### wppus_after_packages_download

```php
do_action( 'wppus_after_packages_download', string $archive_name, string $archive_path );
```

**Description**  
Fired after download for an archive containing one or multiple packages has been performed, regardless of whether the content has been streamed.

**Parameters**  
`$archive_name`
> (string) the name of the archive  

`$archive_path`
> (string) the absolute path of the archive  

___
### wppus_get_package_info

```php
do_action( 'wppus_get_package_info', array $package_info, string $package_slug, string $package_path );
```

**Description**  
Fired before getting information from a package.  

**Parameters**  
`$package_info`
> (array) the information of the package in WP cache  

`$package_slug`
> (string) the slug of the package  

`$package_path`
> (string) the absolute path of the package on the **local** file system  

___
### wppus_find_package_no_cache

```php
do_action( 'wppus_find_package_no_cache', string $package_slug, string $package_path, Wpup_FileCache $cache );
```

**Description**  
Fired if a package exist and was found, but the cache containing the package information does not.  
Fired during client update API request.

**Parameters**  
`$package_slug`
> (string) the slug of the package  

`$package_path`
> (string) the absolute path of the package on the **local** file system  

`$cache`
> (Wpup_FileCache) the cache object  

___
### wppus_update_server_action_download

```php
do_action( 'wppus_update_server_action_download', Wpup_Request $request );
```

**Description**  
Fired before starting a package download from the update API.  
Fired during client update API request.  

**Parameters**  
`$request`
> (Wpup_Request) the request object  

___
### wppus_webhook_before_processing_request

```php
do_action( 'wppus_webhook_before_processing_request', array $payload, string $package_slug, string $type, bool $package_exists, array $config );
```

**Description**  
Fired before processing a webhook request.  

**Parameters**  
`$payload`
> (array) the data sent by the Remote Repository Service  

`$package_slug`
> (string) the slug of the package triggering the webhook  

`$type`
> (string) the type of the package triggering the webhook  

`$package_exists`
> (bool) whether the package exists on the file system  

`$config`
> (array) the webhook configuration  

___
### wppus_webhook_after_processing_request

```php
do_action( 'wppus_webhook_after_processing_request', array $payload, string $package_slug, string $type, bool $package_exists, array $config );
```

**Description**  
Fired after a webhook request has been processed.  

**Parameters**  
`$payload`
> (array) the data sent by the Remote Repository Service  

`$package_slug`
> (string) the slug of the package triggering the webhook  

`$type`
> (string) the type of the package triggering the webhook  

`$package_exists`
> (bool) whether the package exists on the file system  

`$config`
> (array) the webhook configuration  

___
### wppus_packages_table_cell

```php
do_action( 'wppus_packages_table_cell', string $column_name, array $record, string $record_key );
```

**Description**  
Fired when outputing a table cell in the admin interface where `$column_name` is not one of the following: `col_name`, `col_version`, `col_type`, `col_file_name`, `col_file_size`, `col_file_last_modified`.

**Parameters**  
`$column_name`
> (string) the name of the column of the cell  

`$record`
> (array) the record corresponding to the cell  

`$record_key`
> (string) the record key  

___
## Filters

WP Packages Update Server gives developers the possibility to customize its behavior with a series of custom filters.  
**Warning**: the filters below with the mention "Fired during client update API request" need to be used with caution. Although they may be triggered when using the functions above, these filters will possibly be called when client packages request for updates. Registering functions doing heavy computation to these filters when client update API requests are handled can seriously degrade the server's performances.  

___
### wppus_submitted_package_config

```php
apply_filters( 'wppus_submitted_package_config', array $config );
```

**Description**  
Filter the submitted package configuration values before saving them.  

**Parameters**  
`$config`
> (array) the submitted package configuration values

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
Filter the parameters used to handle the request made by a client plugin, theme, or generic package to the plugin's API.  
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

___
### wppus_cloud_storage_virtual_dir

```php
apply_filters( 'wppus_cloud_storage_virtual_dir', string $virtual_dir );
```

**Description**  
Filter the name of the virtual directory where the packages are stored in the Cloud Storage Service.  
Fired during client update API request.

**Parameters**  
`$virtual_dir`
> (string) the name of the virtual directory where the packages are stored in the Cloud Storage Service - default `wppus-packages`  

___
### wppus_could_storage_api_config

```php
apply_filters( 'wppus_could_storage_api_config', array $config );
```

**Description**  
Filter the configuration to use the Cloud Storage Service.  
Fired during client update API request.

**Parameters**  
`$config`
> (array) the configuration to use the Cloud Storage Service  

___
### wppus_package_api_config

```php
apply_filters( 'wppus_package_api_config', array $config );
```

**Description**  
Filter the configuration of the Package API.

**Parameters**  
`$config`
> (array) the configuration of the Package API  

___
### wppus_package_browse

```php
apply_filters( 'wppus_package_browse', array $result, array $query );
```

**Description**  
Filter the result of the `browse` operation of the Package API.

**Parameters**  
`$result`
> (array) the result of the `browse` operation  

`$query`
> (array) the query - see [browse](#browse)  

___
### wppus_package_read

```php
apply_filters( 'wppus_package_read', array $result, string $package_slug, string $type );
```

**Description**  
Filter the result of the `read` operation of the Package API.

**Parameters**  
`$result`
> (array) the result of the `read` operation  

`$package_slug`
> (string) the slthe slug of the read package  

`$type`
> (string) the type of the read package  

___
### wppus_package_edit

```php
apply_filters( 'wppus_package_edit', array $result, string $package_slug, string $type );
```

**Description**  
Filter the result of the `edit` operation of the Package API.

**Parameters**  
`$result`
> (array) the result of the `edit` operation  

`$package_slug`
> (string) the slthe slug of the edited package  

`$type`
> (string) the type of the edited package  

___
### wppus_package_add

```php
apply_filters( 'wppus_package_add', array $result, $package_slug, string $type );
```

**Description**  
Filter the result of the `add` operation of the Package API.

**Parameters**  
`$result`
> (array) the result of the `add` operation  

`$package_slug`
> ($package_slug)the slug of the added package   

`$type`
> (string) the type of the added package  

___
### wppus_package_delete

```php
apply_filters( 'wppus_package_delete', bool $result, string $package_slug, string $type );
```

**Description**  
Filter the result of the `delete` operation of the Package API.

**Parameters**  
`$result`
> (bool) the result of the `delete` operation  

`$package_slug`
> (string) the slug of the deleted package  

`$type`
> (string) the type of the deleted package  

___
### wppus_package_signed_url

```php
apply_filters( 'wppus_package_signed_url', array $result, string $package_slug, string $type );
```

**Description**  
Filter the result of the `signed_url` operation of the Package API.

**Parameters**  
`$result`
> (array) the result of the `signed_url` operation  

`$package_slug`
> (string) the slug of the package for which the URL was signed  

`$type`
> (string) the type of the package for which the URL was signed  

___
### wppus_package_signed_url_token

```php
apply_filters( 'wppus_package_signed_url_token', $token, string $package_slug, string $type );
```

**Description**  
Filter the token used to sign the URL.  

**Parameters**  
`$token`
> ($token) the token used to sign the URL

`$package_slug`
> (string) the slug of the package for which the URL needs to be signed  

`$type`
> (string) the type of the package for which the URL needs to be signed  

___
### wppus_package_public_api_actions

```php
apply_filters( 'wppus_package_public_api_actions', array $public_api_actions );
```

**Description**  
Filter the public API actions ; public actions can be accessed via the `GET` method and a token, all other actions are considered private and can only be accessed via the `POST` method.

**Parameters**  
`$public_api_actions`
> (array) the public API actions  

___
### wppus_package_api_request_authorized

```php
apply_filters( 'wppus_package_api_request_authorized', bool $authorized, string $method, array $payload );
```

**Description**  
Filter whether the Package API request is authorized

**Parameters**  
`$authorized`
> (bool) whether the Package API request is authorized  

`$method`
> (string) the method of the request - `GET` or `POST`  

`$payload`
> (array) the payload of the request  

___
### wppus_packages_table_columns

```php
apply_filters( 'wppus_packages_table_columns', array $columns );
```

**Description**  
Filter the columns to display in the packages Overview table.  

**Parameters**  
`$columns`
> (array) the columns to display in the packages Overview table  

___
### wppus_packages_table_sortable_columns

```php
apply_filters( 'wppus_packages_table_sortable_columns', array $columns );
```

**Description**  
Filter the sortable columns in the packages Overview table.  

**Parameters**  
`$columns`
> (array) the sortable columns in the packages Overview table  

___
### wppus_packages_table_bulk_actions

```php
apply_filters( 'wppus_packages_table_bulk_actions', array $actions );
```

**Description**  
Filter the bulk actions in the packages Overview table.

**Parameters**  
`$actions`
> (array) the bulk actions in the packages Overview table  

___
### wppus_use_recurring_schedule

```php
apply_filters( 'wppus_use_recurring_schedule', bool $use_recurring_schedule );
```

**Description**  
Filter whether WPPUS is using recurring schedules to check to update packages from the Remote Repository Service.  

**Parameters**  
`$use_recurring_schedule`
> (bool) whether WPPUS is using recurring schedules to check to update packages from the Remote Repository Service  

___
### wppus_remote_sources_manager_get_package_slugs

```php
apply_filters( 'wppus_remote_sources_manager_get_package_slugs', array $package_slugs );
```

**Description**  
Filter the slugs of packages currently available on the file system to display in the packages Overview table.  

**Parameters**  
`$package_slugs`
> (array) the slugs of packages currently available on the file system to display in the packages Overview table  

___
### wppus_server_class_name

```php
apply_filters( 'wppus_server_class_name', string $class_name, string $package_slug, array $config );
```

**Description**  
Filter the class name to use to instanciate a `Wpup_UpdateServer` object.  
WPPUS uses 2 classes inheriting from `Wpup_UpdateServer`:
- `WPPUS_License_Update_Server` in case the package needs a license
- `WPPUS_Update_Server` for all the other packages

Fired during client update API request.

**Parameters**  
`$class_name`
> (string) the class name to use to instanciate a `Wpup_UpdateServer` object  

`$package_slug`
> (string) the slug of the package to serve  

`$config`
> (array) the update API configuration  

___
### wppus_delete_packages_bulk_paths

```php
apply_filters( 'wppus_delete_packages_bulk_paths', string $package_paths, array $package_slugs );
```

**Description**  
Filter the paths or the package archives to delete.

**Parameters**  
`$package_paths`
> (string) the paths or the package archives to delete from the file system  

`$package_slugs`
> (array) the slugs or the package to delete from the file system  

___
### wppus_package_info

```php
apply_filters( 'wppus_package_info', array $package_info, string $package_slug );
```

**Description**  
Filter the package information retrieved by the admin interface or through [`wppus_get_package_info`](#wppus_get_package_info).

**Parameters**  
`$package_info`
> (array) the information of the package  

`$package_slug`
> (string) the slug of the package  

___
### wppus_batch_package_info_include

```php
apply_filters( 'wppus_batch_package_info_include', bool $include, array $package_info, string $search );
```

**Description**  
Filter whether to include the package in the batch of information.

**Parameters**  
`$include`
> (bool) whether to include the package in the batch of information  

`$package_info`
> (array) the information of the package  

`$search`
> (string) the keyword used to search in package's slug and package's name  

___
### wppus_package_manager_batch_package_info

```php
apply_filters( 'wppus_package_manager_batch_package_info', array $packages_information, string $search );
```

**Description**  
Filter the array of package information retrieved by the admin interface or through [`wppus_get_batch_package_info`](#wppus_get_batch_package_info).

**Parameters**  
`$packages_information`
> (array) the array of package information  

`$search`
> (string) the keyword used to search in package's slug and package's name  

___
### wppus_check_remote_package_update_local_meta

```php
apply_filters( 'wppus_check_remote_package_update_local_meta', array $package_info, Wpup_Package $package, string $package_slug );
```

**Description**  
Filter the package information gathered from the file system before checking for updates in the Remote Repository Service.  
Fired during client update API request.

**Parameters**  
`$package_info`
> (array) the package information  

`$package`
> (Wpup_Package) the package object retrieved from the file system, either from cache or from the package archive  

`$package_slug`
> (string) the slug of the package  

___
### wppus_check_remote_package_update_no_local_meta_needs_update

```php
apply_filters( 'wppus_check_remote_package_update_no_local_meta_needs_update', bool $needs_update, Wpup_Package $package, string $package_slug );
```

**Description**  
Filter whether the package in the file system needs to be updated with the one hosted on the Remote Repository Service when no corresponding package information was found on the file system.  
Fired during client update API request.

**Parameters**  
`$needs_update`
> (bool) whether the package in the file system needs to be updated  

`$package`
> (Wpup_Package) the package object retrieved from the file system, either from cache or from the package archive  

`$package_slug`
> (string) the slug of the package  

___
### wppus_remove_package_result

```php
apply_filters( 'wppus_remove_package_result', bool $removed, string $type, string $package_slug );
```

**Description**  
Filter whether the package was removed from the file system.  

**Parameters**  
`$removed`
> (bool) whether the package was removed from the file system  

`$type`
> (string) the type of the package  

`$package_slug`
> (string) the slug of the package  

___
### wppus_update_server_action_download_handled

```php
apply_filters( 'wppus_update_server_action_download_handled', bool $download_handled, Wpup_Request $request );
```

**Description**  
Filter whether the package download has been handled. Returning `true` considers the download handled and prevents WPPUS from streaming the file to the remote client.  
Fired during client update API request.

**Parameters**  
`$download_handled`
> (bool) whether the package download has been handled  

`$request`
> (Wpup_Request) the request object  

___
### wppus_save_remote_to_local

```php
apply_filters( 'wppus_save_remote_to_local', bool $save_to_local, string $package_slug, string $package_path, bool $check_remote );
```

**Description**  
Filter whether WPPUS needs to attempt to download a package from the Remote Repository Service onto the file system.  
Fired during client update API request.

**Parameters**  
`$save_to_local`
> (bool) whether WPPUS needs to attempt to download a package from the Remote Repository Service onto the file system  

`$package_slug`
> (string) the slug of the package  

`$package_path`
> (string) the absolute path of the package on the **local** file system  

`$check_remote`
> (bool) `true` if the Remote Repository Service is about to be checked and the package downloaded, `false` if the local cache is about to be used  

___
### wppus_webhook_package_exists

```php
apply_filters( 'wppus_webhook_package_exists', bool $package_exists, array $payload, string $package_slug, string $type, array $config );
```

**Description**  
Filter whether the package exists on the file system before processing the Webhook.  

**Parameters**  
`$package_exists`
> (bool) whether the package exists on the file system  

`$payload`
> (array) the payload of the request  

`$package_slug`
> (string) the slug of the package  

`$type`
> (string) the type of the package  


`$config`
> (array) the webhook configuration  

___
### wppus_webhook_process_request

```php
apply_filters( 'wppus_webhook_process_request', bool $process_request, array $payload, string $package_slug, string $type, bool $package_exists, array $config );
```

**Description**  
Filter whether to process the Webhook request.  

**Parameters**  
`$process_request`
> (bool) whether to process the Webhook request  

`$payload`
> (array) the payload of the request  

`$package_slug`
> (string) the slug of the package  

`$type`
> (string) the type of the package  

`$package_exists`
> (bool) whether the package exists on the file system  

`$config`
> (array) the webhook configuration  

___
### wppus_package_option_update

```php
apply_filters( 'wppus_package_option_update', bool $update, string $option_name, array $option_info, array $options );
```

**Description**  
Filter whether to update the packages plugin option.  

**Parameters**  
`$update`
> (bool) whether to update the package option  

`$option_name`
> (string) the name of the option  

`$option_info`
> (array) the info related to the option  

`$options`
> (array) the values submitted along with the option  

___
### wppus_remote_source_option_update

```php
apply_filters( 'wppus_remote_source_option_update', bool $update, string $option_name, array $option_info, array $options );
```

**Description**  
Filter whether to update the remote sources plugin option.  

**Parameters**  
`$update`
> (bool) whether to update the remote sources option  

`$option_name`
> (string) the name of the option  

`$option_info`
> (array) the info related to the option  

`$options`
> (array) the values submitted along with the option  

___
### wppus_api_package_actions

```php
apply_filters( 'wppus_api_package_actions', array $actions );
```

**Description**  
Filter the Package API actions available for API access control.  

**Parameters**  
`$actions`
> (array) the API actions  

___

