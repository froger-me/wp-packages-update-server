# WP Packages Update Server - Miscellaneous - Developer documentation
(Looking for the main documentation page instead? [See here](https://github.com/froger-me/wp-packages-update-server/blob/master/README.md))

WP Packages Update Server provides an API and offers a series of functions, actions and filters for developers to use in their own plugins and themes to modify the behavior of the plugin. Below is the documentation to interface with miscellaneous aspects of WP Packages Update Server. 

* [WP Packages Update Server - Miscellaneous - Developer documentation](#wp-packages-update-server---miscellaneous---developer-documentation)
	* [Nonce API](#nonce-api)
	* [Consuming Webhooks](#consuming-webhooks)
	* [Functions](#functions)
		* [php\_log](#php_log)
		* [cidr\_match](#cidr_match)
		* [wppus\_is\_doing\_api\_request](#wppus_is_doing_api_request)
			* [wppus\_is\_doing\_webhook\_api\_request](#wppus_is_doing_webhook_api_request)
			* [wppus\_init\_nonce\_auth](#wppus_init_nonce_auth)
			* [wppus\_create\_nonce](#wppus_create_nonce)
			* [wppus\_get\_nonce\_expiry](#wppus_get_nonce_expiry)
			* [wppus\_get\_nonce\_data](#wppus_get_nonce_data)
			* [wppus\_validate\_nonce](#wppus_validate_nonce)
			* [wppus\_delete\_nonce](#wppus_delete_nonce)
			* [wppus\_clear\_nonce](#wppus_clear_nonce)
			* [wppus\_build\_nonce\_api\_signature](#wppus_build_nonce_api_signature)
			* [wppus\_schedule\_webhook](#wppus_schedule_webhook)
			* [wppus\_fire\_webhook](#wppus_fire_webhook)
	* [Actions](#actions)
		* [wppus\_no\_api\_includes](#wppus_no_api_includes)
		* [wppus\_no\_license\_api\_includes](#wppus_no_license_api_includes)
		* [wppus\_remote\_sources\_options\_updated](#wppus_remote_sources_options_updated)
	* [Filters](#filters)
		* [wppus\_is\_api\_request](#wppus_is_api_request)
		* [wppus\_page\_wppus\_scripts\_l10n](#wppus_page_wppus_scripts_l10n)
		* [wppus\_nonce\_api\_payload](#wppus_nonce_api_payload)
		* [wppus\_nonce\_api\_code](#wppus_nonce_api_code)
		* [wppus\_nonce\_api\_response](#wppus_nonce_api_response)
		* [wppus\_created\_nonce](#wppus_created_nonce)
		* [wppus\_clear\_nonces\_query](#wppus_clear_nonces_query)
		* [wppus\_clear\_nonces\_query\_args](#wppus_clear_nonces_query_args)
		* [wppus\_expire\_nonce](#wppus_expire_nonce)
		* [wppus\_delete\_nonce](#wppus_delete_nonce-1)
		* [wppus\_fetch\_nonce](#wppus_fetch_nonce)
		* [wppus\_nonce\_authorize](#wppus_nonce_authorize)
		* [wppus\_api\_option\_update](#wppus_api_option_update)
		* [wppus\_api\_webhook\_events](#wppus_api_webhook_events)

___
## Nonce API

The nonce API is accessible via `POST` and `GET` requests on the `/wppus-token/` endpoint to acquire a reusable token, and `/wppus-nonce/` to acquire a true nonce.  
It accepts form-data payloads (arrays, basically). This documentation page uses `wp_remote_post`, but `wp_remote_get` would work as well.

Authorization is granted with either the `HTTP_X_WPPUS_API_CREDENTIALS` and `HTTP_X_WPPUS_API_SIGNATURE` headers or with the `api_credentials` and `api_signature` parameters.  
If requesting a token for an existing API, the `api` parameter value must be provided with one of `package` or `license` to specify the target API.  
The credentials and the signature are valid for 1 minute ; building them is the responsibility of the third-party client making use of the API - an implementation in PHP is provided below.  
**Using `GET` requests directly in the browser, whether through the URL bar or JavaScript, is strongly discouraged due to security concerns** ; it should be avoided at all cost to prevent the inadvertent exposure of the credentials and signature.  

Building API credentials and API signature - developers may use this function in their own project:

```php
if ( ! function_exists( 'wppus_build_nonce_api_signature' ) ) {
	/**
	* Build credentials and signature for WPPUS Nonce API
	*
	* @param string $api_key_id The ID of the Private API Key
	* @param string $api_key The Private API Key - will not be sent over the Internet
	* @param int    $timestamp The timestamp used to limit the validity of the signature (validity is MINUTE_IN_SECONDS)
	* @return array An array with keys `credentials` and `signature`
	*/
	function wppus_build_nonce_api_signature( $api_key_id, $api_key, $timestamp ) {
		$timestamp   = time();
		$credentials = $timestamp . '/' . $api_key_id;
		$time_key    = hash_hmac( 'sha256', $timestamp, $api_key, true );
		$signature   = hash_hmac( 'sha256', base64_encode( $api_key_id ), $time_key ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		return array(
			'credentials' => $credentials,
			'signature'   => $signature,
		);
	}
}

// Usage
$values = wppus_build_nonce_api_signature( getenv( 'WPPUS_API_KEY_ID' ), getenv( 'WPPUS_API_KEY' ), time() );

echo '<div>The credentials are: ' . esc_html( $values['credentials'] ) . '</div>';
echo '<div>The signature is: ' . esc_html( $values['signature'] ) . '</div>';
```

In case the Private API Key is invalid, the API will return the following response (message's language depending on availabe translations), with HTTP response code set to `403`:

Response `$data` - forbidden access:
```json
{
	"message": "Unauthorized access"
}
```

The description of the API below is using the following code as reference, where `$params` are the parameters passed to the API (other parameters can be adjusted, they are just WordPress' default) and `$data` is the JSON response:

```php
$url = 'https://domain.tld/wppus-nonce/'; // Replace domain.tld with the domain where WP Packages Update Server is installed.
$url = 'https://domain.tld/wppus-token/'; // Replace domain.tld with the domain where WP Packages Update Server is installed.

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

Parameters to aquire a reusable token or a true nonce:

```php
$params = array(
	'expiry_length' => 999,               // The expiry length in seconds (optional - default value to WPPUS_Nonce::DEFAULT_EXPIRY_LENGTH - 30 seconds)
	'data' => array(                      // Data to store along the token or true nonce (optional)
		'permanent' => false,             // set to a truthy value to create a nonce that never expires
		'key1'      => 'value1',          // custom data
		'key2'      => array(             // custom data can be as nested as needed
			'subkey1' => 'subval1',
			'subkey2' => 'subval2'
		),
	),
	'api_credentials' => '9999999999|private_key_id', // The credentials acting as public key `timestamp|key_id`, where `timestamp` is a past timestamp no older than 1 minutes, and `key_id` is the ID corresponding to the Private API Key (optional - must be provided in case X-WPPUS-API-Credentials header is absent)
	'api_signature'   => 'complex_signature',         // The signature built using the Private API Key (optional - must be provided in case X-WPPUS-API-Signature header is absent)
	'api'             => 'api_name',                  // The target API (required if requesting a nonce for the existing APIs ; one of `package` or `license`)
);
```

Response `$data` - **success**:
```json
{
	"nonce": "nonce_value",
	"true_nonce": true|false,
	"expiry": 9999999999,
	"data": {
		"key1": "value1",
		"key2": "value2",
		"key3": {
			"subkey1": "subval1",
			"subkey2": "subval2"
		},
	}
}
```
___
## Consuming Webhooks

Webhooks's payload is sent in JSON format via a POST request and is signed with a `secret-key` secret key using `sha1` algorithm and `sha256` algorithm.  
The resulting hashes are made available in the `X-WPPUS-Signature` and `X-WPPUS-Signature-256` headers respectively.  

Below is an example of how to consume a Webhook on another installation of WordPress with a plugin (webhooks can however be consumed by any system):

```php
<?php
/*
Plugin Name: WPPUS Webhook Consumer
Plugin URI: https://domain.tld/wppus-webhook-consumer/
Description: Consume WPPUS Webhooks.
Version: 1.0
Author: A Developer
Author URI: https://domain.tld/
Text Domain: wppus-consumer
Domain Path: /languages
*/

/* This is a simple example.
 * We would normally want to use a proper class, add an endpoint,
 * use the `parse_request` action, and `query_vars` filter
 * and check the `query_vars` attribute of the global `$wp` variable
 * to identify the destination of the Webhook.
 *
 * Here instead we will attempt to `json_decode` the payload and
 * look for the `event` attribute to proceed.
 *
 * Also note that we only check for the actually secure `sha256` signature.
 */
add_action( 'plugins_loaded', function() {
	global $wp_filesystem;
	
	// We assume the secret is stored in environment variables
	$secret = getenv( 'WPPUS_HOOK_SECRET' );

	if ( empty( $wp_filesystem ) ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';

		WP_Filesystem();
	}
	
	$payload = $wp_filesystem->get_contents( 'php://input' );
	$json    = json_decode( $payload );
	
	if ( $json && isset( $json->event ) ) {
		// Get the signature from headers
		$sign = isset( $_SERVER['HTTP_X_WPPUS_SIGNATURE_256'] ) ?
			$_SERVER['HTTP_X_WPPUS_SIGNATURE_256'] :
			false;

		if ( $sign ) {
			// Check our payload against the signature
			$sign_parts = explode( '=', $sign );
			$sign       = 2 === count( $sign_parts ) ? end( $sign_parts ) : false;
			$algo       = ( $sign ) ? reset( $sign_parts ) : false;
			$valid      = $sign && hash_equals( hash_hmac( $algo, $payload, $secret ), $sign );
			
			if ( $valid ) {
				error_log( 'The payload was successfully authenticated.' );
				// Log the headers and the body of the request
				// Typically, at this stage the client would use the consumed payload
				error_log(
					print_r(
						array(
							'headers' => array(
								'X-WPPUS-Action'        => $_SERVER['HTTP_X_WPPUS_ACTION'],
								'X-WPPUS-Signature'     => $_SERVER['HTTP_X_WPPUS_SIGNATURE'],
								'X-WPPUS-Signature-256' => $_SERVER['HTTP_X_WPPUS_SIGNATURE_256'],
							),
							'body' => $payload,
						),
						true
					)
				);
			} else {
				error_log( 'The payload could not be authenticated.' );
			}
		} else {
			error_log( 'Signature not found.' );
		}
	}
}, 10, 0 );

```
___
## Functions

The functions listed below are made publicly available by the plugin for theme and plugin developers. They can be used after the action `plugins_loaded` has been fired, or in a `plugins_loaded` action (just make sure the priority is above `-99`).  
Although the main classes can theoretically be instanciated without side effect if the `$hook_init` parameter is set to `false`, it is recommended to use only the following functions as there is no guarantee future updates won't introduce changes of behaviors.

___
### php_log

```php
php_log( mixed $message = '', string $prefix = '' );
```

**Description**  
Convenience function to log a message to `error_log`.

**Parameters**  
`$message`
> (mixed) the message to log ; can be any variable  

`$prefix`
> (string) a prefix to add before the variable ; useful to add context  

___
### cidr_match

```php
cidr_match( $ip, $range );
```

**Description**  
Check whether an IP address is a match for the provided CIDR range.

**Parameters**  
`$ip`
> (string) the IP address to check  

`$range`
> (string) a CIDR range  

**Return value**
> (bool) whether an IP address is a match for the provided CIDR range

___
### wppus_is_doing_api_request

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
wppus_init_nonce_auth( array $private_keys )
```

**Description**  
Set the private keys to check against when requesting nonces via the `wppus-token` and `wppus-nonce` endpoints.  

**Parameters**  
`$private_keys`
> (array) the private keys with the following format:  
```php
$private_keys = array(
	'api_key_id_1' => array(
		'key' => 'api_key_1',
		// ... other values are ignored
	),
	'api_key_id_2' => array(
		'key' => 'api_key_2',
		// ... other values are ignored
	),
);
```

___
#### wppus_create_nonce

```php
wppus_create_nonce( bool $true_nonce = true, int $expiry_length = WPPUS_Nonce::DEFAULT_EXPIRY_LENGTH, array $data = array(), int $return_type = WPPUS_Nonce::NONCE_ONLY, bool $store = true, bool|callable )
```

**Description**  
Creates a cryptographic token - allows creation of tokens that are true one-time-use nonces, with custom expiry length and custom associated data.

**Parameters**  
`$true_nonce`
> (bool) whether the nonce is one-time-use ; default `true`  

`$expiry_length`
> (int) the number of seconds after which the nonce expires ; default `WPPUS_Nonce::DEFAULT_EXPIRY_LENGTH` - 30 seconds 

`$data`
> (array) custom data to save along with the nonce ; set an element with key `permanent` to a truthy value to create a nonce that never expires ; default `array()`  

`$return_type`
> (int) whether to return the nonce, or an array of information ; default `WPPUS_Nonce::NONCE_ONLY` ; other accepted value is `WPPUS_Nonce::NONCE_INFO_ARRAY`  

`$store`
> (bool) whether to store the nonce, or let a third party mechanism take care of it ; default `true`  

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
`$nonce`
> (string) the nonce  

**Return value**
> (int) the expiry timestamp  

___
#### wppus_get_nonce_data

```php
wppus_get_nonce_data( string $nonce )
```

**Description**  
Get the data stored along a nonce.  

**Parameters**  
`$nonce`
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
`$value`
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
`$value`
> (string) the value to delete  

**Return value**
> (bool) whether the nonce was deleted  

___
#### wppus_clear_nonce

```php
wppus_clear_nonces()
```

**Description**  
Clear expired nonces from the system.  

**Return value**
> (bool) whether some nonces were cleared  

___
#### wppus_build_nonce_api_signature

```php
wppus_build_nonce_api_signature( string $api_key_id, string $api_key, int $timestamp )
```

**Description**  
Build credentials and signature for WPPUS Nonce API  

**Parameters**  
`$api_key_id`
> (string) the ID of the Private API Key  

`$api_key`
> (string) the Private API Key - will not be sent over the Internet  

`$timestamp`
> (int) the timestamp used to limit the validity of the signature (validity is `MINUTE_IN_SECONDS`)  

**Return value**
> (array) an array with keys `credentials` and `signature`  

___
#### wppus_schedule_webhook

```php
wppus_schedule_webhook( array $payload, string $event_type )
```

**Description**  
Schedule an event notification to be sent to registered Webhook URLs at next cron run.  

**Parameters**  
`$payload`
> (array) the data used to schedule the notification with the following format:  
```php
$payload = array(
	'event'       => 'event_name',                                // required - the name of the event that triggered the notification
	'description' => 'A description of what the event is about.', // optional - Description of the notification
	'content'     => 'The data of the payload',                   // required - the data to be consumed by the recipient
);
```

`$event_type`
> (string) the type of event ; the payload will only be delivered to URLs subscribed to this type  

**Return value**
> (null|WP_error) `null` in case of success, a `WP_Error` otherwise  

___
#### wppus_fire_webhook

```php
wppus_fire_webhook( string $url, string $secret, string $body, string $action )
```

**Description**  
Immediately send a event notification to `$url`, signed with `$secret` in `X-WPPUS-Signature` and `X-WPPUS-Signature-256`, with `$action` in `X-WPPUS-Action`.  

**Parameters**  
`$url`
> (string) the destination of the notification  

`$secret`
> (string) the secret used to sign the notification  

`$body`
> (string) the JSON string sent in the notification  

`$action`
> (string) the WordPress action responsible for fireing the webhook  

**Return value**
> (array|WP_Error) the response of the request in case of success, a `WP_Error` otherwise  

___
## Actions

WP Packages Update Server gives developers the possibility to have their plugins react to some events with a series of custom actions.  
**Warning**: the filters below with the mention "Fired during API requests" need to be used with caution. Although they may be triggered when using the functions above, these filters will possibly be called when the Update API, License API, Packages API or a Webhook is called. Registering functions doing heavy computation to these filters can seriously degrade the server's performances.  

___
### wppus_no_api_includes

```php
do_action( 'wppus_no_api_includes' );
```

**Description**  
Fired when the plugin is including files and the current request is not made by a remote client interacting with any of the plugin's API.

___
### wppus_no_license_api_includes

```php
do_action( 'wppus_no_license_api_includes' );
```

**Description**  
Fired when the plugin is including files and the current request is not made by a client plugin or theme interacting with the plugin's license API.

___
### wppus_remote_sources_options_updated

```php
do_action( 'wppus_api_options_updated', array $errors );
```

**Description**  
Fired after the options in "API & Webhooks" have been updated.

**Parameters**  
`$errors`
> (array) an array of containing errors if any  

___
## Filters

WP Packages Update Server gives developers the possibility to customise its behavior with a series of custom filters.  
**Warning**: the filters below with the mention "Fired during API requests" need to be used with caution. Although they may be triggered when using the functions above, these filters will possibly be called when the Update API, License API, Packages API or a Webhook is called. Registering functions doing heavy computation to these filters can seriously degrade the server's performances.  

___
### wppus_is_api_request

```php
apply_filters( 'wppus_is_api_request', bool $is_api_request );
```

**Description**  
Filter whether the current request must be treated as an API request.  

**Parameters**  
`$is_api_request`
> (bool) whether the current request must be treated as an API request  

___
### wppus_page_wppus_scripts_l10n

```php
apply_filters( 'wppus_page_wppus_scripts_l10n', array $l10n );
```

**Description**  
Filter the internationalization strings passed to the frontend scripts.  

**Parameters**  
`$l10n`
> (array) the internationalization strings passed to the frontend scripts  

___
### wppus_nonce_api_payload

```php
apply_filters( 'wppus_nonce_api_payload', array $payload, string $action );
```

**Description**  
Filter the payload sent to the Nonce API.  

**Parameters**  
`$code`
> (string) the payload sent to the Nonce API  

`$action`
> (string) the api action - `token` or `nonce`  

___
### wppus_nonce_api_code

```php
apply_filters( 'wppus_nonce_api_code', string $code, array $request_params );
```

**Description**  
Filter the HTTP response code to be sent by the Nonce API.  

**Parameters**  
`$code`
> (string) the HTTP response code to be sent by the Nonce API  

`$request_params`
> (array) the request's parameters  

___
### wppus_nonce_api_response

```php
apply_filters( 'wppus_nonce_api_response', array $response, string $code, array $request_params );
```

**Description**  
Filter the response to be sent by the Nonce API.  

**Parameters**  
`$response`
> (array) the response to be sent by the Nonce API  

`$code`
> (string) the HTTP response code sent by the Nonce API  

`$request_params`
> (array) the request's parameters  

___
### wppus_created_nonce

```php
apply_filters( 'wppus_created_nonce', bool|string|array $nonce_value, bool $true_nonce, int $expiry_length, array $data, int $return_type );
```

**Description**  
Filter the value of the nonce before it is created ; if `$nonce_value` is truthy, the value is used as nonce and the default generation algorithm is bypassed ; developers must respect the `$return_type`.

**Parameters**  
`$nonce_value`
> (bool|string|array) the value of the nonce before it is created - if truthy, the nonce is considered created with this value  

`$true_nonce`
> (bool) whether the nonce is a true, one-time-use nonce  

`$expiry_length`
> (int) the expiry length of the nonce in seconds  

`$data`
> (array) data to store along the nonce  

`$return_type`
> (int) `WPPUS_Nonce::NONCE_ONLY` or `WPPUS_Nonce::NONCE_INFO_ARRAY`  

___
### wppus_clear_nonces_query

```php
apply_filters( 'wppus_clear_nonces_query', string $sql, array $sql_args );
```

**Description**  
Filter the SQL query used to clear expired nonces.

**Parameters**  
`$sql`
> (string) the SQL query used to clear expired nonces  

`$sql_args`
> (array) the arguments passed to the SQL query used to clear expired nonces  

___
### wppus_clear_nonces_query_args

```php
apply_filters( 'wppus_clear_nonces_query_args', array $sql_args, string $sql );
```

**Description**  
Filter the arguments passed to the SQL query used to clear expired nonces.

**Parameters**  
`$sql_args`
> (array) the arguments passed to the SQL query used to clear expired nonces  

`$sql`
> (string) the SQL query used to clear expired nonces  

___
### wppus_expire_nonce

```php
apply_filters( 'wppus_expire_nonce', bool $expire_nonce, string $nonce_value, bool $true_nonce, int $expiry, array $data, object $row );
```

**Description**  
Filter whether to consider the nonce has expired.

**Parameters**  
`$expire_nonce`
> (bool) whether to consider the nonce has expired  

`$nonce_value`
> (string) the value of the nonce  

`$true_nonce`
> (bool) whether the nonce is a true, one-time-use nonce  

`$expiry`
> (int) the timestamp at which the nonce expires  

`$data`
> (array) data stored along the nonce  

`$row`
> (object) the database record corresponding to the nonce  

___
### wppus_delete_nonce

```php
apply_filters( 'wppus_delete_nonce', bool $delete, string $nonce_value, bool $true_nonce, int $expiry, array $data, object $row );
```

**Description**  
Filter whether to delete the nonce.

**Parameters**  
`$delete`
> (bool) whether to delete the nonce  

`$nonce_value`
> (string) the value of the nonce  

`$true_nonce`
> (bool) whether the nonce is a true, one-time-use nonce  

`$expiry`
> (int) the timestamp at which the nonce expires  

`$data`
> (array) data stored along the nonce  

`$row`
> (object) the database record corresponding to the nonce  

___
### wppus_fetch_nonce

```php
apply_filters( 'wppus_fetch_nonce', string $nonce_value, bool $true_nonce, int $expiry, array $data, object $row );
```

**Description**  
Filter the value of the nonce after it has been fetched from the database.

**Parameters**  
`$nonce_value`
> (string) the value of the nonce after it has been fetched from the database  

`$true_nonce`
> (bool) whether the nonce is a true, one-time-use nonce  

`$expiry`
> (int) the timestamp at which the nonce expires  

`$data`
> (array) data stored along the nonce  

`$row`
> (object) the database record corresponding to the nonce  

___
### wppus_nonce_authorize

```php
apply_filters( 'wppus_nonce_authorize', $authorized, $received_key, $private_auth_keys );
```

**Description**  
Filter whether the request for a nonce is authorized.

**Parameters**  
`$authorized`
> (bool) whether the request is authorized  

`$received_key`
> (string) the key use to attempt the authorization  

`$private_auth_keys`
> (array) the valid authorization keys  

___
### wppus_api_option_update

```php
apply_filters( 'wppus_api_option_update', bool $update, string $option_name, array $option_info, array $options );
```

**Description**  
Filter whether to update the API plugin option.  

**Parameters**  
`$update`
> (bool) whether to update the API option  

`$option_name`
> (string) the name of the option  

`$option_info`
> (array) the info related to the option  

`$options`
> (array) the values submitted along with the option  

___
### wppus_api_webhook_events

```php
apply_filters( 'wppus_api_webhook_events',  array $events )
```

**Description**  
Filter whether the available webhook events.  

**Parameters**  
`$events`
> (array) the info related to the option  

___
