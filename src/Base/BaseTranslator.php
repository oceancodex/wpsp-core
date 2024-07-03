<?php

namespace WPSPCORE\Base;

abstract class BaseTranslator extends BaseInstances {

	public ?string $textDomain = null;
	public ?string $relPath    = null;

	/*
	 *
	 */

	public function afterInstanceConstruct(): void {

		// Custom properties.
		$this->customProperties();

	}

	/*
	 *
	 */

	public function init(): void {
		load_plugin_textdomain(
			$this->textDomain ?? $this->funcs->_getTextDomain(),
			false,
			$this->relPath ?? $this->funcs->_getMainBaseName() . '/resources/lang/'
		);
	}

	/*
	 *
	 */

	public function customProperties() {}

}