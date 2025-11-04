<?php

namespace WPSPCORE\Base;

abstract class BaseTranslation extends BaseInstances {

	public function initTranslation() {
		try {
			$translationPath   = $this->funcs->_getResourcesPath('/lang');
			$translationLoader = new \WPSPCORE\Translation\FileLoader(new \Illuminate\Filesystem\Filesystem, $translationPath);
			$translation = new \WPSPCORE\Translation\Translation($translationLoader, $this->funcs->_config('app.locale'));
		}
		catch (\Exception $e) {
			$translation = null;
		}
		return $translation;
	}

	public function global() {}

}