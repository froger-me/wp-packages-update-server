<?php

namespace Anyape\ProxyUpdateChecker\Generic;

use YahnisElsts\PluginUpdateChecker\v5p3\Update as BaseUpdate;

if (!class_exists(Update::class, false)):

	class Update extends BaseUpdate {
		protected static $extraFields = array('package_data');

		public static function fromJson($json) {
			php_log();
			$instance = new self();

			if (!parent::createFromJson($json, $instance)) {
				return null;
			}

			return $instance;
		}

		public static function fromObject($object) {
			php_log();
			$update = new self();
			$update->copyFields($object, $update);

			return $update;
		}

		protected function validateMetadata($apiResponse) {
			php_log();
			$required = array('version', 'package_data');

			foreach ($required as $key) {

				if (!isset($apiResponse->$key) || empty($apiResponse->$key)) {
					return new \WP_Error(
						'tuc-invalid-metadata',
						sprintf('The generic metadata is missing the required "%s" key.', $key)
					);
				}
			}
			return true;
		}

		protected function getPrefixedFilter($tag) {
			php_log();
			return parent::getPrefixedFilter($tag) . '_generic';
		}
	}

endif;
