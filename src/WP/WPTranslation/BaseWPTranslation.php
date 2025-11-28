<?php

namespace WPSPCORE\WP\WPTranslation;

use WPSPCORE\BaseInstances;

abstract class BaseWPTranslation extends BaseInstances {

	public $textDomain = null;
	public $relPath    = null;

	/*
	 *
	 */

	public function prepare() {
		try {
			$loaded = load_plugin_textdomain(
				$this->textDomain ?? $this->funcs->_getTextDomain(),
				false,
				$this->relPath ?? $this->funcs->_getMainBaseName() . '/resources/lang/'
			);
		}
		catch (\Throwable $e) {}
		return $this;
	}

	/*
	 *
	 */

	public function global() {
		$globalTranslator = $this->funcs->_getAppShortName();
		$globalTranslator = $globalTranslator . '_translator';
		global ${$globalTranslator};
		${$globalTranslator} = $this;
		return $this;
	}

}