<?php

namespace WPSPCORE\Base;

abstract class BaseTranslator extends BaseInstances {

	public ?string $textDomain = null;
	public ?string $relPath    = null;

	/*
	 *
	 */

	public function prepare(): ?self {
		load_plugin_textdomain(
			$this->textDomain ?? $this->funcs->_getTextDomain(),
			false,
			$this->relPath ?? $this->funcs->_getMainBaseName() . '/resources/lang/'
		);
		return $this;
	}

	/*
	 *
	 */

	public function global(): void {
		$globalTranslator = $this->funcs->_getAppShortName();
		$globalTranslator = $globalTranslator . '_translator';
		global ${$globalTranslator};
		${$globalTranslator} = $this;
	}

}