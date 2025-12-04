<?php

namespace WPSPCORE\App\Traits;

use Illuminate\Http\Request;

/**
 * BaseInstancesTrait.
 *
 * @property \WPSPCORE\Funcs          $funcs
 * @property \Illuminate\Http\Request $request
 */
trait BaseInstancesTrait {

	public $funcs         = null;
	public $mainPath      = null;
	public $rootNamespace = null;
	public $prefixEnv     = null;
	public $extraParams   = [];
	public $request       = null;

	public function baseInstanceConstruct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		$this->instanceConstruct();
		$this->beforeConstruct();
		if ($mainPath)      $this->mainPath      = $mainPath;
		if ($rootNamespace) $this->rootNamespace = $rootNamespace;
		if ($prefixEnv)     $this->prefixEnv     = $prefixEnv;
		if ($extraParams)   $this->extraParams   = $extraParams;
		$this->prepareFuncs();
		$this->prepareRequest();
		$this->afterConstruct();
		$this->customProperties();
	}

	/*
	 *
	 */

	public function customProperties() {}

	/*
	 *
	 */

	private function prepareFuncs() {
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

	private function prepareRequest() {
		if (isset($this->funcs) && $funcs = $this->funcs) {
			if (isset($funcs::$request) && $funcs::$request) {
				$this->request = $funcs::$request;
			}
			else {
				$this->request = $this->funcs->getApplication('request');
			}
		}
		else {
			$this->request = Request::capture();
		}
		unset($this->extraParams['request']);
	}

	/*
	 *
	 */

	public function instanceConstruct() {}

	public function beforeConstruct() {}

	public function afterConstruct() {}

}