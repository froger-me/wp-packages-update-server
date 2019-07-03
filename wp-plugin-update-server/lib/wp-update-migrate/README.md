
# WP Update Migrate - WordPress plugins and themes update path library

### Description

Provide a clear upgrade path to plugins and themes: each version has its own script to upgrade database and file system in case of major update.

### Requirements

Before enabling migrations with the code below, setup your plugin properly by making sure:
 - the `wp-update-migrate` folder library is present in a `lib` directory at the root of the plugin or theme
 - to change `'example_prefix'` with a unique prefix identifier for your plugin or theme, `snake_case` format
 - to change `'example_function'` with the name of the function to remove from or add to the action queue
 - to have an `updates` directory at the root of the plugin or theme
 - to name each file in the `updates` folder with a version number as file name (example: `1.5.3.php`)
 - each update file in the `updates` directory have a single update function, and do not include any logic outside of that function
 - the update function name in each update file follows the pattern: `[example_prefix]_update_to_[version]`
     - example: in `1.5.3.php`, the function is `my_plugin_update_to_1_5_3` with `[example_prefix]` of value `my_plugin`
     - example: in `2.4.9.php`, the function is `my_theme_update_to_2_4_9` with `[example_prefix]` of value `my_theme`
 - each update function returns `(bool) true` in case of success, a `WP_Error` object otherwise


### Code to include in the main plugin file

```php
require_once plugin_dir_path( __FILE__ ) . 'lib/wp-update-migrate/class-wp-update-migrate.php';

$hook = 'plugins_loaded';

add_action( $hook, function() {
  $update_migrate = WP_Update_Migrate::get_instance( __FILE__, 'example_prefix' );

  if ( false === $update_migrate->get_result() ) {
    /**
    * @todo
    * Execute your own logic here in case the update failed.
    *
    * if ( false !== has_action( 'example_action', 'example_function' ) ) {
    *     remove_action( 'example_action', 'example_function', 10 );
    * }
    **/
  }

  if ( true === $update_migrate->get_result() ) {
    /**
    * @todo
    * Execute your own logic here in case an update was applied successfully.
    *
    * if ( false === has_action( 'example_action', 'example_function' ) ) {
    *     add_action( 'example_action', 'example_function', 10 );
    * }
    **/
  }
}, PHP_INT_MIN );
```

### Code to include in theme's functions.php

```php
require_once plugin_dir_path( __FILE__ ) . 'lib/wp-update-migrate/class-wp-update-migrate.php';

$hook = 'after_setup_theme';

add_action( $hook, function() {
  $update_migrate = WP_Update_Migrate::get_instance( __FILE__, 'example_prefix' );

  if ( false === $update_migrate->get_result() ) {
    /**
    * @todo
    * Execute your own logic here in case the update failed.
    *
    * if ( false !== has_action( 'example_action', 'example_function' ) ) {
    *     remove_action( 'example_action', 'example_function', 10 );
    * }
    **/
  }

  if ( true === $update_migrate->get_result() ) {
    /**
    * @todo
    * Execute your own logic here in case an update was applied successfully.
    *
    * if ( false === has_action( 'example_action', 'example_function' ) ) {
    *     add_action( 'example_action', 'example_function', 10 );
    * }
    **/
  }
}, PHP_INT_MIN );
```
