# WP Packages Update Server - Miscellaneous - Developer documentation
(Looking for the main documentation page instead? [See here](https://github.com/froger-me/wp-packages-update-server/blob/master/README.md))

WP Packages Update Server provides an API and offers a series of functions, actions and filters for developers to use in their own plugins and themes to modify the behavior of the plugin. Below is the documentation to interface with miscellaneous aspects of WP Packages Update Server. 

- [WP Packages Update Server - Miscellaneous - Developer documentation](#wp-packages-update-server---miscellaneous---developer-documentation)
	- [Functions](#user-content-functions)
		- [php_log](#user-content-php_log)
		- [cidr_match](#user-content-cidr_match)
		- [wppus_is_doing_api_request](#user-content-wppus_is_doing_api_request)
		- [wppus_is_doing_webhook_api_request](#user-content-wppus_is_doing_webhook_api_request)
		- [wppus_init_nonce_auth](#user-content-wppus_init_nonce_auth)
		- [wppus_create_nonce](#user-content-wppus_create_nonce)
		- [wppus_get_nonce_expiry](#user-content-wppus_get_nonce_expiry)
		- [wppus_validate_nonce](#user-content-wppus_validate_nonce)
		- [wppus_delete_nonce](#user-content-wppus_delete_nonce)
		- [wppus_clear_nonces](#user-content-wppus_clear_nonces)
	- [Actions](#user-content-actions)
	- [Filters](#user-content-filters)
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