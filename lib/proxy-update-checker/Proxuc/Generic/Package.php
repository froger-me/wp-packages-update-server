<?php

namespace Anyape\ProxyUpdateChecker\Generic;

use YahnisElsts\PluginUpdateChecker\v5p3\InstalledPackage;

if (!class_exists(Package::class, false)):

	class Package extends InstalledPackage {

		public function getAbsoluteDirectoryPath() {
			return '';
		}

		public function getInstalledVersion() {
			return '';
		}

		public function getHeaderValue($headerName, $defaultValue = '') {
			return $defaultValue;
		}

		protected function getHeaderNames() {
			return array();
		}

	}

endif;
