<?php

class WshWordPressPackageParser_Extended extends WshWordPressPackageParser {
	/**
	 * @uses WshWordPressPackageParser_Extended::getAssetsHeaders
	 * @see WshWordPressPackageParser
	 */
	public static function parsePackage($packageFilename, $applyMarkdown = false){
		if ( !file_exists($packageFilename) || !is_readable($packageFilename) ){
			return false;
		}

		//Open the .zip
		$zip = WshWpp_Archive::open($packageFilename);
		if ( $zip === false ){
			return false;
		}

		//Find and parse the plugin or theme file and (optionally) readme.txt.
		$header = null;
		$readme = null;
		$pluginFile = null;
		$stylesheet = null;
		$type = null;

		$entries = $zip->listEntries();
		for ( $fileIndex = 0; ($fileIndex < count($entries)) && (empty($readme) || empty($header)); $fileIndex++ ){
			$info = $entries[$fileIndex];

			//Normalize filename: convert backslashes to slashes, remove leading slashes.
			$fileName = trim(str_replace('\\', '/', $info['name']), '/');
			$fileName = ltrim($fileName, '/');

			$fileNameParts = explode('.', $fileName);
			$extension = strtolower(end($fileNameParts));
			$depth = substr_count($fileName, '/');

			//Skip empty files, directories and everything that's more than 1 sub-directory deep.
			if ( ($depth > 1) || $info['isFolder'] ) {
				continue;
			}

			//readme.txt (for plugins)?
			if ( empty($readme) && (strtolower(basename($fileName)) == 'readme.txt') ){
				//Try to parse the readme.
				$readme = self::parseReadme($zip->getFileContents($info), $applyMarkdown);
			}

			//Theme stylesheet?
			if ( empty($header) && (strtolower(basename($fileName)) == 'style.css') ) {
				$fileContents = substr($zip->getFileContents($info), 0, 8*1024);
				$header = self::getThemeHeaders($fileContents);
				if ( !empty($header) ){
					$stylesheet = $fileName;
					$type = 'theme';
				}
			}

			//Main plugin file?
			if ( empty($header) && ($extension === 'php') ){
				$fileContents = substr($zip->getFileContents($info), 0, 8*1024);
				$header = self::getPluginHeaders($fileContents);
				if ( !empty($header) ){
					$pluginFile = $fileName;
					$type = 'plugin';
					$assets = self::getAssetsHeaders($fileContents);
				}
			}
		}

		if ( empty($type) ){
			return false;
		} else {
			return compact('header', 'assets', 'readme', 'pluginFile', 'stylesheet', 'type');
		}
	}


	/**
	 * Parse the plugin contents to retrieve icons and banners information.
	 *
	 * Adapted from @see WshWordPressPackageParser::getPluginHeaders.
	 * Returns an array that may contain the following:
	 * 'icons':
	 *		'Icon1x'
	 *		'Icon2x'
	 * 'banners':
	 *		'BannerHigh'
	 *		'BannerLow'
	 *
	 * If the data is not found, the function
	 * will return NULL.
	 *
	 * @param string $fileContents Contents of the plugin file
	 * @return array|null See above for description.
	 */
	public static function getAssetsHeaders($fileContents) {
		//[Internal name => Name used in the plugin file]
		$assetsHeaderNames = array(
			'Icon1x' => 'Icon1x',
			'Icon2x' => 'Icon2x',
			'BannerHigh' => 'BannerHigh',
			'BannerLow' => 'BannerLow',

			//Site Wide Only is deprecated in favor of Network.
			'_sitewide' => 'Site Wide Only',
		);

		$headers = self::getFileHeaders($fileContents, $assetsHeaderNames);
		$assetsHeaders = array();

		if ( !empty($headers['Icon1x']) || !empty($headers['Icon2x']) ) {
			$assetsHeaders['icons'] = array();

			if ( !empty($headers['Icon1x']) ) {
				$assetsHeaders['icons']['1x'] = $headers['Icon1x'];
			}

			if ( !empty($headers['Icon2x']) ) {
				$assetsHeaders['icons']['2x'] = $headers['Icon2x'];
			}
		}

		if ( !empty($headers['BannerLow']) || !empty($headers['BannerHigh']) ) {
			$assetsHeaders['banners'] = array();

			if ( !empty($headers['BannerLow']) ) {
				$assetsHeaders['banners']['low'] = $headers['BannerLow'];
			}

			if ( !empty($headers['BannerHigh']) ) {
				$assetsHeaders['banners']['high'] = $headers['BannerHigh'];
			}
		}

		if ( empty($assetsHeaders) ){
			return null;
		} else {
			return $assetsHeaders;
		}
	}

}
