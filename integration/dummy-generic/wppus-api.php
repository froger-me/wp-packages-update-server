<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

### EXAMPLE INTEGRATION WITH WP PACKAGES UPDATE SERVER ###

# DO NOT USE THIS FILE AS IT IS IN PRODUCTION !!!
# It is just a collection of basic functions and snippets, and they do not
# perform the necessary checks to ensure data integrity ; they assume that all
# the requests are successful, and do not check paths or permissions.
# They also assume that the package necessitates a license key, stored in an
# environment variable WPPUS_GENERIC_PACKAGE_LICENSE

# replace https://server.domain.tld/ with the URL of the server where
# WP Packages Update Server is installed in wppus.json

class WPPUS_API {

	private static $config;
	private static $url;
	private static $package_name;
	private static $package_script;
	private static $script_name;
	private static $zip_name;
	private static $version;
	private static $license_key;
	private static $license_signature;
	private static $domain;

	private function __construct() {
		# load the configuration file
		self::$config = json_decode( file_get_contents( __DIR__ . '/wppus.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		# define the url of the server
		self::$url = self::$config['server'];
		# define the package name
		self::$package_name = basename( __DIR__ );
		# define the package script
		self::$package_script = __DIR__ . '/' . basename( __DIR__ ) . '.php';
		# define the current script name
		self::$script_name = basename( self::$package_script );
		# define the update zip archive name
		self::$zip_name = self::$package_name . '.zip';
		# define the current version of the package from the wppus.json file
		self::$version = self::$config['packageData']['Version'];
		# define license_key from the wppus.json file
		self::$license_key = isset( $config['licenseKey'] ) ? $config['licenseKey'] : '';
		# define license_signature from the wppus.json file
		self::$license_signature = isset( $config['licenseSignature'] ) ? $config['licenseSignature'] : '';

		# define the domain
		if ( 'Darwin' === PHP_OS ) {
			# macOS
			self::$domain = exec( 'ioreg -rd1 -c IOPlatformExpertDevice | awk -F\'"\' \'/IOPlatformUUID/{print $4}\'' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec, WordPress.WP.GlobalVariablesOverride.Prohibited
		} elseif ( 'Linux' === PHP_OS ) {
			# Ubuntu
			self::$domain = exec( 'cat /var/lib/dbus/machine-id' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec, WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	### INSTALLING THE PACKAGE ###

	public static function install( $license_key ) {
		# add the license key to wppus.json
		self::$config['licenseKey'] = $license_key;
		file_put_contents( __DIR__ . '/wppus.json', json_encode( self::$config, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.json_encode_json_encode

		# add a file '.installed' in current directory
		// touch "$(cd "$(dirname "$0")"; pwd -P)/.installed" (convert to php)
		file_put_contents( __DIR__ . '/.installed', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	### UNINSTALLING THE PACKAGE ###

	public static function uninstall() {
		# remove the license key from wppus.json
		unset( self::$config['licenseKey'] );
		file_put_contents( __DIR__ . '/wppus.json', json_encode( self::$config, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.json_encode_json_encode

		# remove the file '.installed' from current directory
		unlink( __DIR__ . '/.installed' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
	}

	### CHECKING IF THE PACKAGE IS INSTALLED ###

	public static function is_installed() {
		# check if the file '.installed' exists in current directory
		return file_exists( __DIR__ . '/.installed' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
	}

	### SENDNG AN API REQUEST ###

	private static function send_api_request( $action, $args = array() ) {
		# build the request url
		$full_url = self::$url . '/' . $action . '/?' . http_build_query( $args );

		# initialize cURL
		$ch = curl_init(); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init

		# set the options
		curl_setopt( $ch, CURLOPT_URL, $full_url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $ch, CURLOPT_TIMEOUT, 20 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt

		# make the request
		$response = curl_exec( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec

		# close the cURL resource
		curl_close( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close

		# return the response
		return $response;
	}


	### CHECKING FOR UPDATES ###

	public static function check_for_updates() {
		// do something
	}

	### ACTIVATING A LICENSE ###

	public static function activate( $license_key ) {
		// do something
	}

	### DEACTIVATING A LICENSE ###

	public static function deactivate() {
		// do something
	}

	### DOWNLOADING THE PACKAGE ###


	### GETTING THE PACKAGE INFO ###

	public static function get_update_info() {
		// do something
	}

	### GETTING THE PACKAGE VERSION ###

	public static function get_version() {
		return '';
	}
}
