<?php

require WPPUS_PLUGIN_PATH . '/lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5p3\Utils;
use YahnisElsts\PluginUpdateChecker\v5p3\Vcs\BaseChecker;
use Anyape\ProxyUpdateChecker\Generic\Package;
use Anyape\ProxyUpdateChecker\Generic\Update;
use Anyape\ProxyUpdateChecker\Generic\UpdateChecker;

if ( ! class_exists(Proxuc_Vcs_GenericUpdateChecker::class, false) ):

	class Proxuc_Vcs_GenericUpdateChecker extends UpdateChecker implements BaseChecker {

		public $genericAbsolutePath = '';

		protected $branch = 'master';

		protected $api = null;

		public function __construct($api, $slug, $generic_file_name, $package_container, $optionName = '') {
			$this->api = $api;
			$this->api->setHttpFilterName($this->getUniqueName('request_update_options'));

			$this->genericAbsolutePath = trailingslashit($package_container) . $slug;
			$this->genericFile = $slug . '/' . $generic_file_name . '.json';

			$this->debugMode = (bool)(constant('WP_DEBUG'));
			$this->metadataUrl = $api->getRepositoryUrl();
			$this->directoryName = basename(dirname($this->genericAbsolutePath));
			$this->slug = !empty($slug) ? $slug : $this->directoryName;

			$this->optionName = $optionName;

			if ( empty($this->optionName) ) {

				if ( $this->filterSuffix === '' ) {
					$this->optionName = 'external_updates-' . $this->slug;
				} else {
					$this->optionName = $this->getUniqueName('external_updates');
				}
			}
			$this->package = new Package($this->genericAbsolutePath, $this);
			$this->api->setSlug($this->slug);
		}

		public function Vcs_getAbsoluteDirectoryPath() {
			return trailingslashit($this->genericAbsolutePath);
		}

		public function requestInfo($unused = null) {
			$update = $this->requestUpdate();
			$info   = null;

			if ($update && 'source_not_found' !== $update) {

				if (!empty($update->download_url)) {
					$update->download_url = $this->api->signDownloadUrl($update->download_url);
				}

				$info = array(
					'type'         => 'Generic',
					'version'      => $update->version,
					'main_file'    => $this->genericFile,
					'download_url' => $update->download_url,
				);
			} elseif ( 'source_not_found' === $update ) {

				return new WP_Error(
					'puc-no-update-source',
					'Could not retrieve version information from the repository for '
					. $this->slug . '.'
					. 'This usually means that the update checker either can\'t connect '
					. 'to the repository or it\'s configured incorrectly.'
				);
			}

			return $info;
		}

		public function setBranch($branch) {
			$this->branch = $branch;

			return $this;
		}

		public function setAuthentication($credentials) {
			$this->api->setAuthentication($credentials);

			return $this;
		}

		public function getVcsApi() {
			return $this->api;
		}
	}

endif;
