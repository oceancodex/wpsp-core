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

	public static $funcs         = null;
	public static $mainPath      = null;
	public static $rootNamespace = null;
	public static $prefixEnv     = null;

	public static $extraParams   = [];
	public static $request       = null;

	public function baseInstanceConstruct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = null) {
		$this->instanceConstruct();
		$this->beforeConstruct();
		if ($mainPath)      static::$mainPath       = $mainPath;
		if ($rootNamespace) static::$rootNamespace  = $rootNamespace;
		if ($prefixEnv)     static::$prefixEnv      = $prefixEnv;
		if ($extraParams)   static::$extraParams    = $extraParams;
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

	private function prepareRequest(): void {
		if (isset(static::$funcs) && $funcs = static::$funcs) {
			if (isset($funcs::$request) && $funcs::$request) {
				static::$request = $funcs::$request;
			}
			else {
				static::$request = static::$funcs->getApplication('request');
			}
		}
		else {
			static::$request = Request::capture();
		}
		unset(static::$extraParams['request']);
	}

	private function prepareFuncs(): void {
		if (isset(static::$extraParams['funcs']) && static::$extraParams['funcs'] && !static::$funcs) {
			if (is_bool(static::$extraParams['funcs'])) {
				static::$funcs = new \WPSPCORE\Funcs(
					$this->mainPath,
					$this->rootNamespace,
					$this->prefixEnv,
					static::$extraParams
				);
			}
			else {
				static::$funcs = static::$extraParams['funcs'];
			}
		}
		unset(static::$extraParams['funcs']);
	}

	/*
	 *
	 */

	public function getRequest(): ?Request {
		return static::$request;
	}

	public function getExtraParams(): array {
		return static::$extraParams;
	}

	/*
	 *
	 */

	public function instanceConstruct() {}

	public function beforeConstruct() {}

	public function afterConstruct() {}

}