<?php

namespace WPSPCORE\App\Updater;

use WPSPCORE\BaseInstances;

abstract class BaseUpdater extends BaseInstances {

	public $sslVerify            = true;	// Whether to verify SSL certificates.
	public $checkForUpdatesLabel = null;	// The label "Check fo updates" in Plugin list page.
	public $packageUrl           = null;	// The URL of the metadata file, a GitHub repository, or another supported update source.
	public $checkPeriod          = 6;		// How often to check for updates (in hours).
	public $optionName           = '';		// Where to store bookkeeping info about update checks.
	public $muPluginFile		 = '';		// The plugin filename relative to the mu-plugins directory.

	/*
	 *
	 */

	public function init() {
		// Disable SSL verification.
		if (!$this->sslVerify) {
			add_filter('puc_request_info_options-' . $this->funcs->_getTextDomain(), function($options) {
				$options['sslverify'] = false;
				return $options;
			});
		}

		// Change "Check for updates" link text.
		if ($this->checkForUpdatesLabel) {
			add_filter('puc_manual_check_link-' . $this->funcs->_getTextDomain(), function($text) {
				return $this->checkForUpdatesLabel;
			});
		}

		try {
			$updateChecker = PucFactory::buildUpdateChecker(
				$this->packageUrl ?: $this->funcs->_config('updater.package_url') ?: $this->funcs->_getPublicUrl() . '/plugin.json',
				$this->funcs->_getMainFilePath(),
				$this->funcs->_getTextDomain(),
				$this->checkPeriod,
				$this->optionName,
				$this->muPluginFile,
			);

//			return $updateChecker->requestInfo();
		}
		catch (\Throwable $e) {
//			return null;
		}

		return $this;
	}

}