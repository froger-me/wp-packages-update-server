<?php

namespace Anyape\ProxyUpdateChecker\Generic;

use YahnisElsts\PluginUpdateChecker\v5p3\UpdateChecker as BaseUpdateChecker;

if (!class_exists(UpdateChecker::class, false)):

	class UpdateChecker extends BaseUpdateChecker {
		public $genericFile = '';

		public function __construct($metadataUrl, $directoryName, $slug = null, $checkPeriod = 12, $optionName = '') {
			$this->debugMode = (bool)(constant('WP_DEBUG'));
			$this->metadataUrl = $metadataUrl;
			$this->directoryName = $directoryName;
			$this->slug = !empty($slug) ? $slug : $this->directoryName;
			$this->optionName = $optionName;

			if ( empty($this->optionName) ) {

				if ( $this->filterSuffix === '' ) {
					$this->optionName = 'external_updates-' . $this->slug;
				} else {
					$this->optionName = $this->getUniqueName('external_updates');
				}
			}
		}

		/**
		 * For generics, the update array is indexed by generic directory name.
		 *
		 * @return string
		 */
		protected function getUpdateListKey() {
			php_log();
			return $this->directoryName;
		}

		public function requestUpdate() {
			php_log();
			list($genericUpdate, $result) = $this->requestMetadata(Update::class, 'request_update');

			if ($genericUpdate !== null) {
				/** @var Update $genericUpdate */
				$genericUpdate->slug = $this->slug;
			}

			$genericUpdate = $this->filterUpdateResult($genericUpdate, $result);

			return $genericUpdate;
		}

		protected function filterUpdateResult($update, $httpResult = null) {
			php_log();
			return apply_filters($this->getUniqueName('request_update_result'), $update, $httpResult);
		}

		protected function getNoUpdateItemFields() {
			php_log();
			$fields = parent::getNoUpdateItemFields();

			unset($fields['requires_php']);

			return array_merge(
				parent::getNoUpdateItemFields(),
				array('generic' => $this->directoryName)
			);
		}

		public function userCanInstallUpdates() {
			php_log();
			return false;
		}

		protected function createScheduler($checkPeriod) {
			php_log();
			return null;
		}

		public function isBeingUpgraded($upgrader = null) {
			php_log();
			return false;
		}

		public function addQueryArgFilter($callback){
			php_log();
			$this->addFilter('request_update_query_args', $callback);
		}

		public function addHttpRequestArgFilter($callback) {
			php_log();
			$this->addFilter('request_update_options', $callback);
		}

		public function addResultFilter($callback) {
			php_log();
			$this->addFilter('request_update_result', $callback, 10, 2);
		}

		protected function createInstalledPackage() {
			php_log();
			return null;
		}

		protected function getInstalledTranslations() {
			php_log();
			return array();
		}
	}

endif;
