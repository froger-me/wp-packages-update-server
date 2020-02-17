<?php
if ( !class_exists('Proxuc_Factory', false) ):

	/**
	 * A factory that builds update checker instances.
	 *
	 * When multiple versions of the same class have been loaded (e.g. PluginUpdateChecker 4.0
	 * and 4.1), this factory will always use the latest available minor version. Register class
	 * versions by calling {@link PucFactory::addVersion()}.
	 *
	 * At the moment it can only build instances of the UpdateChecker class. Other classes are
	 * intended mainly for internal use and refer directly to specific implementations.
	 */
	class Proxuc_Factory {
		protected static $classVersions = array();
		protected static $sorted = false;

		protected static $majorVersion = '';
		protected static $latestCompatibleVersion = '';
		protected static $apiVersion = '';
		protected static $checkerVersion = '';

		/**
		 * Create a new instance of the update checker.
		 *
		 * This method automatically detects if you're using it for a plugin or a theme and chooses
		 * the appropriate implementation for your update source (JSON file, GitHub, BitBucket, etc).
		 *
		 * @see Puc_v4p4_UpdateChecker::__construct
		 *
		 * @param string $metadataUrl The URL of the metadata file, a GitHub repository, or another supported update source.
		 * @param string $fullPath Full path to the main plugin file or to the theme directory.
		 * @param string $slug Custom slug. Defaults to the name of the main plugin file or the theme directory.
		 * @param int $checkPeriod How often to check for updates (in hours).
		 * @param string $optionName Where to store book-keeping info about update checks.
		 * @param string $muPluginFile The plugin filename relative to the mu-plugins directory.
		 * @return Puc_v4p4_Plugin_UpdateChecker|Puc_v4p4_Theme_UpdateChecker|Puc_v4p4_Vcs_BaseChecker|false
		 */
		public static function buildUpdateChecker($metadataUrl, $slug, $plugin_file_name, $type, $package_container, $optionName = '') {
			//Plugin or theme?
			if ( $type !== 'Plugin' && $type !== 'Theme') {

				return false;
			}

			//Which hosting service does the URL point to?
			$service = self::getVcsService($metadataUrl);

			$apiClass = null;
			$checkerClass = null;

			if ( !empty($service) ) {
				$checkerClass = 'Vcs_' . $type . 'UpdateChecker';
				$apiClass = $service . 'Api';
			}

			$checkerClass = self::getCompatibleClassVersion($checkerClass, 'checker');

			if ( $checkerClass === null ) {
				trigger_error(
					sprintf(
						'PUC %s does not support updates for %ss %s',
						htmlentities(self::$latestCompatibleVersion['checker']),
						strtolower($type),
						$service ? ('hosted on ' . htmlentities($service)) : 'using JSON metadata'
					),
					E_USER_ERROR
				);

				return null;
			}
      
			//VCS checker + an API client.
			$apiClass = self::getCompatibleClassVersion($apiClass, 'api');
			if ( $apiClass === null ) {
				trigger_error(sprintf(
					'PUC %s does not support %s',
					htmlentities(self::$latestCompatibleVersion['api']),
					htmlentities($service)
				), E_USER_ERROR);

				return null;
			}

			return new $checkerClass(
				new $apiClass($metadataUrl),
				$slug,
				$plugin_file_name,
				$package_container,
				$optionName
			);
		}

		/**
		 * Get the name of the hosting service that the URL points to.
		 *
		 * @param string $metadataUrl
		 * @return string|null
		 */
		protected static function getVcsService($metadataUrl) {
			$service = null;

			//Which hosting service does the URL point to?
			$host = @parse_url($metadataUrl, PHP_URL_HOST);
			$path = @parse_url($metadataUrl, PHP_URL_PATH);
			//Check if the path looks like "/user-name/repository".
			$usernameRepoRegex = '@^/?([^/]+?)/([^/#?&]+?)/?$@';
			if ( preg_match($usernameRepoRegex, $path) ) {
				$knownServices = array(
					'github.com' => 'GitHub',
					'bitbucket.org' => 'BitBucket',
					'gitlab.com' => 'GitLab',
				);
				if ( isset($knownServices[$host]) ) {
					$service = $knownServices[$host];
				}
			}
      
			return $service;
		}

		/**
		 * Get the latest version of the specified class that has the same major version number
		 * as this factory class.
		 *
		 * @param string $class Partial class name.
		 * @return string|null Full class name.
		 */
		protected static function getCompatibleClassVersion($class, $versionHolder) {

			if ( isset(self::$classVersions[$class][self::$latestCompatibleVersion[$versionHolder]]) ) {
				return self::$classVersions[$class][self::$latestCompatibleVersion[$versionHolder]];
			}
			return null;
		}

		/**
		 * Get the specific class name for the latest available version of a class.
		 *
		 * @param string $class
		 * @return null|string
		 */
		public static function getLatestClassVersion($class) {
			if ( !self::$sorted ) {
				self::sortVersions();
			}

			if ( isset(self::$classVersions[$class]) ) {
				return reset(self::$classVersions[$class]);
			} else {
				return null;
			}
		}

		/**
		 * Sort available class versions in descending order (i.e. newest first).
		 */
		protected static function sortVersions() {
			foreach ( self::$classVersions as $class => $versions ) {
				uksort($versions, array(__CLASS__, 'compareVersions'));
				self::$classVersions[$class] = $versions;
			}
			self::$sorted = true;
		}

		protected static function compareVersions($a, $b) {
			return -version_compare($a, $b);
		}

		/**
		 * Register a version of a class.
		 *
		 * @access private This method is only for internal use by the library.
		 *
		 * @param string $generalClass Class name without version numbers, e.g. 'PluginUpdateChecker'.
		 * @param string $versionedClass Actual class name, e.g. 'PluginUpdateChecker_1_2'.
		 * @param string $version Version number, e.g. '1.2'.
		 */
		public static function addVersion($generalClass, $versionedClass, $version) {
			$versionHolder = 'api';
			$emptyHolder = array(
				'api' => '',
				'checker' => '',
			);

			if (substr($versionedClass, 0, strlen('Proxuc')) === 'Proxuc') {
				$versionHolder = 'checker';
			}

			if ( empty(self::$majorVersion) ) {
				self::$majorVersion = $emptyHolder;
			}

			if ( empty(self::$latestCompatibleVersion) ) {
				self::$latestCompatibleVersion = $emptyHolder;;
			}

			if ( empty(self::$majorVersion[$versionHolder]) ) {
				$components = explode('.', self::${$versionHolder . 'Version'});

				self::$majorVersion[$versionHolder] = $components[0];
			}

			//Store the greatest version number that matches our major version.
			$components = explode('.', $version);

			if ( $components[0] === self::$majorVersion[$versionHolder] ) {

				if (
					empty(self::$latestCompatibleVersion[$versionHolder])
					|| version_compare($version, self::$latestCompatibleVersion[$versionHolder], '>')
				) {
					self::$latestCompatibleVersion[$versionHolder] = $version;
				}

			}

			if ( !isset(self::$classVersions[$generalClass]) ) {
				self::$classVersions[$generalClass] = array();
			}

			self::$classVersions[$generalClass][$version] = $versionedClass;
			self::$sorted = false;
		}

		public static function setApiVersion($apiVersion) {
			self::$apiVersion = $apiVersion;
		}

		public static function setCheckerVersion($checkerVersion) {
			self::$checkerVersion = $checkerVersion;
		}
	}

endif;
