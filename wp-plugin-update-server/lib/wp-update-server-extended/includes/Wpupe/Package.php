<?php
/**
 * @see Wpup_Package
 */
class Wpup_Package_Extended extends Wpup_Package {

	/**
	 * Load package information.
	 *
	 * @uses Wpup_ZipMetadataParser_Extended
	 *
	 * @param string $filename Path to a Zip archive that contains a WP plugin or theme.
	 * @param string $slug Optional plugin or theme slug. Will be detected automatically.
	 * @param Wpup_Cache $cache
	 * @return Wpup_Package
	 */
	public static function fromArchive($filename, $slug = null, Wpup_Cache $cache = null) {
		$metaObj = new Wpup_ZipMetadataParser_Extended($slug, $filename, $cache);
		$metadata = $metaObj->get();

		if ( $slug === null && isset($metadata['slug']) ) {
			$slug = $metadata['slug'];
		}

		return new self($slug, $filename, $metadata);
	}
}
