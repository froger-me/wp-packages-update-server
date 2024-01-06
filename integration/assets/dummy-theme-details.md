# Dummy Theme details

This is an example of details page displayed in the WordPress admin when a new version is available.  
`Details URI` or `Theme URI` below are used to show new version details (`Details URI` is optional and used only if present) ; the one used **MUST** be the URL of a destination that can be displayed in an `iframe` from any source.

```php
/*
 Theme Name: Dummy Theme
 Theme URI: https://froger.me/
 Description: Empty Child Theme
 Author: Alexandre Froger
 Author URI: https://froger.me
 Version: X.X.X
 Template: twentyseventeen
 Tags: dummy, another dummy
 Text Domain: dummy-theme
 Domain Path: /languages
 Details URI: https://github.com/froger-me/wp-packages-update-server/blob/master/integration/assets/dummy-theme-details.md
 License: GNU General Public License v2 or later
 License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
```

The  `Details URI` example above is only for illustrative purposes and does **not** work (`Refused to frame 'https://github.com/' because an ancestor violates the following Content Security Policy directive: "frame-ancestors 'none'".`).