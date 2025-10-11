<?php

namespace WPSPCORE\Base;

abstract class BaseTranslator extends BaseInstances {

	public $textDomain = null;
	public $relPath    = null;

	/*
	 *
	 */

	public function prepare() {
		$loaded = load_plugin_textdomain(
			$this->textDomain ?? $this->funcs->_getTextDomain(),
			false,
			$this->relPath ?? $this->funcs->_getMainBaseName() . '/resources/lang/'
		);
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
	}

}