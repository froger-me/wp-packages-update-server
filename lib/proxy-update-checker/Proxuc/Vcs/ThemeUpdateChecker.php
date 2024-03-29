<?php

require WPPUS_PLUGIN_PATH . '/lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5p3\Utils;
use YahnisElsts\PluginUpdateChecker\v5p3\Vcs\BaseChecker;
use YahnisElsts\PluginUpdateChecker\v5p3\Theme\Package;
use YahnisElsts\PluginUpdateChecker\v5p3\Theme\Update;
use YahnisElsts\PluginUpdateChecker\v5p3\Theme\UpdateChecker;

if (! class_exists(Proxuc_Vcs_ThemeUpdateChecker::class, false)):

	class Proxuc_Vcs_ThemeUpdateChecker extends UpdateChecker implements BaseChecker {

		public $themeAbsolutePath = ''; //Full path of the main plugin file.

		/**
		 * @var string The branch where to look for updates. Defaults to "master".
		 */
		protected $branch = 'master';

		/**
		 * @var Puc\v5p3\Vcs\Api Repository API client.
		 */
		protected $api = null;

		/**
		 * Puc\v5p3\Vcs\ThemeUpdateChecker constructor.
		 *
		 * @param Puc\v5p3\Vcs\Api $api
		 * @param null $stylesheet
		 * @param null $customSlug
		 * @param int $checkPeriod
		 * @param string $optionName
		 */
		public function __construct($api, $slug, $unused, $package_container, $optionName = '') {
			$this->api = $api;
			$this->api->setHttpFilterName($this->getUniqueName('request_update_options'));

			$this->stylesheet = $slug;
			$this->themeAbsolutePath = trailingslashit($package_container) . $slug;

			$this->debugMode = (bool)(constant('WP_DEBUG'));
			$this->metadataUrl = $api->getRepositoryUrl();
			$this->directoryName = basename(dirname($this->themeAbsolutePath));
			$this->slug = !empty($slug) ? $slug : $this->directoryName;

			$this->optionName = $optionName;

			if (empty($this->optionName)) {
				//BC: Initially the library only supported plugin updates and didn't use type prefixes
				//in the option name. Lets use the same prefix-less name when possible.
				if ($this->filterSuffix === '') {
					$this->optionName = 'external_updates-' . $this->slug;
				} else {
					$this->optionName = $this->getUniqueName('external_updates');
				}
			}
			$this->package = new Package($this->themeAbsolutePath, $this);
			$this->api->setSlug($this->slug);
		}

		public function Vcs_getAbsoluteDirectoryPath() {
			return trailingslashit($this->themeAbsolutePath);
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
			$file = $api->getRemoteFile('style.css', $ref);

			if (!empty($file)) {
				$remoteHeader = $this->package->getFileHeader($file);
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

		public function requestInfo($unused = null) {
			$update = $this->requestUpdate();
			$info   = null;

			if ($update && 'source_not_found' !== $update) {

				if (!empty($update->download_url)) {
					$update->download_url = $this->api->signDownloadUrl($update->download_url);
				}

				$info = array(
					'type'         => 'Theme',
					'version'      => $update->version,
					'main_file'    => 'style.css',
					'download_url' => $update->download_url,
				);
			} elseif ('source_not_found' === $update) {

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

		public function getUpdate() {
			$update = parent::getUpdate();

			if (isset($update) && !empty($update->download_url)) {
				$update->download_url = $this->api->signDownloadUrl($update->download_url);
			}

			return $update;
		}

		public function onDisplayConfiguration($panel) {
			parent::onDisplayConfiguration($panel);
			$panel->row('Branch', $this->branch);
			$panel->row('Authentication enabled', $this->api->isAuthenticationEnabled() ? 'Yes' : 'No');
			$panel->row('API client', get_class($this->api));
		}
	}

endif;