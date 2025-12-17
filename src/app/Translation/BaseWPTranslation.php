<?php

namespace WPSPCORE\App\Translation;

use WPSPCORE\BaseInstances;

abstract class BaseWPTranslation extends BaseInstances {

	public $textDomain = null;
	public $relPath    = null;

	/*
	 *
	 */

	public function init() {
		try {
			load_plugin_textdomain(
				$this->textDomain ?? $this->funcs->_getTextDomain(),
				false,
				$this->relPath ?? $this->funcs->_getMainBaseName() . '/resources/lang/'
			);
		}
		catch (\Throwable $e) {}
		return $this;
	}

}