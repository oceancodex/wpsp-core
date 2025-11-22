<?php

namespace WPSPCORE\Traits;

use Illuminate\Http\Request;

/**
 * BaseInstancesTrait.
 *
 * @property \WPSPCORE\Funcs|null $funcs
 * @property \Illuminate\Http\Request|null $request
 */
trait BaseInstancesTrait {

	public $mainPath      = null;
	public $rootNamespace = null;
	public $prefixEnv     = null;
	public $extraParams   = [];
	public $funcs         = null;
	public $request       = null;

	public function baseInstanceConstruct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = null) {
		$this->beforeConstruct();
		if ($mainPath)      $this->mainPath         = $mainPath;
		if ($rootNamespace) $this->rootNamespace    = $rootNamespace;
		if ($prefixEnv)     $this->prefixEnv        = $prefixEnv;
		if ($extraParams)   $this->extraParams      = $extraParams;
		$this->prepareFuncs();
		$this->prepareRequest();
		$this->afterConstruct();
		if (empty($this->extraParams)) unset($this->extraParams);
	}

	/*
	 *
	 */

	/*
	 *
	 */

	private function prepareRequest(): void {
		if (isset($this->funcs->request) && $this->funcs->request) {
			$this->request = $this->funcs->request;
		}
		elseif (isset($this->funcs) && $this->funcs) {
			$this->request = $this->funcs->getApplication('request');
		}
		else {
			$this->request = Request::capture();
		}
		unset($this->extraParams['request']);
	}

	private function prepareFuncs(): void {
		if (isset($this->extraParams['funcs']) && $this->extraParams['funcs'] && !$this->funcs) {
			if (is_bool($this->extraParams['funcs'])) {
				$this->funcs = new \WPSPCORE\Funcs(
					$this->mainPath,
					$this->rootNamespace,
					$this->prefixEnv,
					$this->extraParams
				);
			}
			else {
				$this->funcs = $this->extraParams['funcs'];
			}
		}
		unset($this->extraParams['funcs']);
	}

	/*
	 *
	 */

	public function getLocale() {
		return $this->locale;
	}

	public function getRequest() {
		return $this->request;
	}

	public function getExtraParams() {
		return $this->extraParams;
	}

	/*
	 *
	 */

	public function beforeConstruct() {}

	public function afterConstruct() {}

}