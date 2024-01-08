<?php

namespace Anyape\ProxyUpdateChecker\Generic;

use YahnisElsts\PluginUpdateChecker\v5p3\Update as BaseUpdate;

if (!class_exists(Update::class, false)):

	class Update extends BaseUpdate {

		public static function fromJson($json) {
			$instance = new self();

			if (!parent::createFromJson($json, $instance)) {
				return null;
			}

			return $instance;
		}

		public static function fromObject($object) {
			$update = new self();
			$update->copyFields($object, $update);

			return $update;
		}

		protected function validateMetadata($apiResponse) {
			$required = array('version');

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
			return parent::getPrefixedFilter($tag) . '_generic';
		}
	}

endif;
