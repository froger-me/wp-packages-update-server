<?php
require_once __DIR__ . '/includes/Wpupe/Package.php';
require_once __DIR__ . '/includes/Wpupe/ZipMetadataParser.php';


if ( ! class_exists( 'WshWordPressPackageParser_Extended' ) ) {
	require_once __DIR__ . '/includes/extension-meta/extension-meta.php';
}
