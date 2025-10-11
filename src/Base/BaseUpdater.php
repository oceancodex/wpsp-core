<?php

namespace WPSPCORE\Base;

use WPSPCORE\Updater\PucFactory;

abstract class BaseUpdater extends BaseInstances {

	public $sslVerify            = true;
	public $checkForUpdatesLabel = null;
	public $packageUrl           = null;

	/*
	 *
	 */

	public function prepare() {
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
			);

//			return $updateChecker->requestInfo();
		}
		catch (\Exception $e) {
//			return null;
		}

		return $this;
	}

	/*
	 *
	 */

	public function global() {
		$globalUpdater = $this->funcs->_getAppShortName();
		$globalUpdater = $globalUpdater . '_updater';
		global ${$globalUpdater};
		${$globalUpdater} = $this;
	}

}