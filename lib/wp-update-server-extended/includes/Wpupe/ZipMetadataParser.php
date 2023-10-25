<?php
/**
 * This class represents the metadata from one specific WordPress plugin or theme.
 */
class Wpup_ZipMetadataParser_Extended extends Wpup_ZipMetadataParser {

	/**
	 * @see Wpup_ZipMetadataParser
	 * @throws Wpup_InvalidPackageException if the input file can't be parsed as a plugin or theme.
	 */
	protected function extractMetadata(){
		$this->packageInfo = WshWordPressPackageParser_Extended::parsePackage($this->filename, true);
		if ( is_array($this->packageInfo) && $this->packageInfo !== array() ){
			$this->setInfoFromHeader();
			$this->setInfoFromReadme();
			$this->setLastUpdateDate();
			$this->setInfoFromAssets();
			$this->setSlug();
		} else {
			throw new Wpup_InvalidPackageException( sprintf('The specified file %s does not contain a valid WordPress plugin or theme.', $this->filename));
		}
	}

	/**
	 * Extract icons and banners info for plugins
	 */
	protected function setInfoFromAssets(){
		if ( $this->packageInfo['type'] === 'plugin' && !empty($this->packageInfo['assets']) ){
			$assetsMeta = $this->packageInfo['assets'];
			if ( !empty($assetsMeta['icons']) ) {
				$this->metadata['icons'] = $assetsMeta['icons'];
			}
			if ( !empty($assetsMeta['banners']) ) {
				$this->metadata['banners'] = $assetsMeta['banners'];
			}
		}
	}

}
