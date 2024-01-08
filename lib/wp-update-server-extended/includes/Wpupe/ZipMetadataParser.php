<?php
/**
 * This class represents the metadata from one specific WordPress plugin or theme.
 */
class Wpup_ZipMetadataParser_Extended extends Wpup_ZipMetadataParser {

	protected $headerMap = array(
		'Name' => 'name',
		'Version' => 'version',
		'PluginURI' => 'homepage',
		'ThemeURI' => 'homepage',
		'Homepage' => 'homepage',
		'Author' => 'author',
		'AuthorURI' => 'author_homepage',
		'RequiresPHP' => 'requires_php',
		'Description' => 'description',
		'DetailsURI' => 'details_url', //Only for themes
		'Depends' => 'depends', // plugin-dependencies plugin
		'Provides' => 'provides', // plugin-dependencies plugin
	);

	/**
	 * @see Wpup_ZipMetadataParser
	 * @throws Wpup_InvalidPackageException if the input file can't be parsed as a plugin or theme.
	 */
	protected function extractMetadata(){
		$this->packageInfo = WshWordPressPackageParser_Extended::parsePackage($this->filename, true);

		if (is_array($this->packageInfo) && $this->packageInfo !== array()){
			$this->setInfoFromHeader();
			$this->setInfoFromReadme();
			$this->setLastUpdateDate();
			$this->setInfoFromAssets();
			$this->setSlug();
			$this->setType();
		} else {
			throw new Wpup_InvalidPackageException(sprintf('The specified file %s does not contain a valid Generic package or WordPress plugin or theme.', $this->filename));
		}
	}

	protected function settype(){
		$this->metadata['type'] = $this->packageInfo['type'];
	}

	protected function setSlug(){

		if ('plugin' === $this->packageInfo['type']) {
			$mainFile = $this->packageInfo['pluginFile'];
		} elseif ('theme' === $this->packageInfo['type']) {
			$mainFile = $this->packageInfo['stylesheet'];
		} elseif ('generic' === $this->packageInfo['type']) {
			$mainFile = $this->packageInfo['genericFile'];
		}

		$this->metadata['slug'] = basename(dirname(strtolower($mainFile)));
		//Idea: Warn the user if the package doesn't match the expected "/slug/other-files" layout.
	}

	/**
	 * Extract icons and banners info for plugins
	 */
	protected function setInfoFromAssets(){

		if ($this->packageInfo['type'] === 'plugin' && !empty($this->packageInfo['assets'])){
			$assetsMeta = $this->packageInfo['assets'];

			if (!empty($assetsMeta['icons'])) {
				$this->metadata['icons'] = $assetsMeta['icons'];
			}

			if (!empty($assetsMeta['banners'])) {
				$this->metadata['banners'] = $assetsMeta['banners'];
			}
		}
	}

	/**
	 * Make sure we do not lose the section name ; keep it in data-name attribute
	 */
	protected function setReadmeSections(){

		if (is_array($this->packageInfo['readme']['sections']) && $this->packageInfo['readme']['sections'] !== array()) {

			foreach($this->packageInfo['readme']['sections'] as $sectionName => $sectionContent){
				$sectionContent = '<div class="readme-section" data-name="'. $sectionName. '">'. $sectionContent. '</div>';
				$sectionName = str_replace(' ', '_', strtolower($sectionName));
				$this->metadata['sections'][$sectionName] = $sectionContent;
			}
		}
	}

	protected function generateCacheKey(){
		$cache_key = 'metadata-b64-' . $this->slug . '-';

		if (file_exists($this->filename)) {
			$cache_key .= md5($this->filename . '|' . filesize($this->filename) . '|' . filemtime($this->filename));
		}

		return apply_filters(
			'wpup_zip_metadata_parser_extended_cache_key',
			$cache_key,
			$this->slug,
			$this->filename
		);
	}
}
