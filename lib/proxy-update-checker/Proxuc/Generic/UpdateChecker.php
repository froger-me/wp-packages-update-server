<?php

namespace Anyape\ProxyUpdateChecker\Generic;

use YahnisElsts\PluginUpdateChecker\v5p3\UpdateChecker as BaseUpdateChecker;
use YahnisElsts\PluginUpdateChecker\v5p3\Utils;

if (!class_exists(UpdateChecker::class, false)):

	class UpdateChecker extends BaseUpdateChecker {
		public $genericFile = '';

		public function __construct($metadataUrl, $directoryName, $slug = null, $checkPeriod = 12, $optionName = '') {
			$this->debugMode = (bool)(constant('WP_DEBUG'));
			$this->metadataUrl = $metadataUrl;
			$this->directoryName = $directoryName;
			$this->slug = !empty($slug) ? $slug : $this->directoryName;
			$this->optionName = $optionName;

			if (empty($this->optionName)) {

				if ($this->filterSuffix === '') {
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
			return $this->directoryName;
		}

		public function requestUpdate() {
			$api = $this->api;
			$api->setLocalDirectory($this->Vcs_getAbsoluteDirectoryPath());

			$update = new Update();
			$update->slug = $this->slug;
			$update->version = null;

			//Figure out which reference (tag or branch) we'll use to get the latest version of the theme.
			$updateSource = $api->chooseReference($this->branch);
			if ($updateSource) {
				$ref = $updateSource->name;
				$update->download_url = $updateSource->downloadUrl;
			} else {
				return 'source_not_found';
			}

			//Get headers from the main stylesheet in this branch/tag. Its "Version" header and other metadata
			//are what the WordPress install will actually see after upgrading, so they take precedence over releases/tags.
			$file = $api->getRemoteFile('wppus.json', $ref);

			if (!empty($file)) {
				$remoteHeader = json_decode($file, true);
				$update->version = Utils::findNotEmpty(array(
					$remoteHeader['Version'],
					Utils::get($updateSource, 'version'),
				));
			}

			if (empty($update->version)) {
				//It looks like we didn't find a valid update after all.
				$update = null;
			}

			$update = $this->filterUpdateResult($update);

			return $update;
		}

		protected function filterUpdateResult($update, $httpResult = null) {
			return apply_filters($this->getUniqueName('request_update_result'), $update, $httpResult);
		}

		protected function getNoUpdateItemFields() {
			$fields = parent::getNoUpdateItemFields();

			unset($fields['requires_php']);

			return array_merge(
				parent::getNoUpdateItemFields(),
				array('generic' => $this->directoryName)
			);
		}

		public function userCanInstallUpdates() {
			return false;
		}

		protected function createScheduler($checkPeriod) {
			return null;
		}

		public function isBeingUpgraded($upgrader = null) {
			return false;
		}

		public function addQueryArgFilter($callback){
			$this->addFilter('request_update_query_args', $callback);
		}

		public function addHttpRequestArgFilter($callback) {
			$this->addFilter('request_update_options', $callback);
		}

		public function addResultFilter($callback) {
			$this->addFilter('request_update_result', $callback, 10, 2);
		}

		protected function createInstalledPackage() {
			return null;
		}

		protected function getInstalledTranslations() {
			return array();
		}
	}

endif;
