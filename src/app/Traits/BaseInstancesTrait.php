<?php

namespace WPSPCORE\App\Traits;

use Illuminate\Http\Request;
use WPSPCORE\App\Funcs;

/**
 * BaseInstancesTrait.
 *
 * @property \WPSPCORE\App\Funcs|null      $funcs
 * @property \Illuminate\Http\Request|null $request
 */
trait BaseInstancesTrait {

	public ?Funcs   $funcs         = null;

	public ?string  $mainPath      = null;
	public ?string  $rootNamespace = null;
	public ?string  $prefixEnv     = null;

	public array    $extraParams   = [];
	public ?Request $request       = null;

	public function baseInstanceConstruct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []): void {
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

	private function prepareRequest(): void {
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

	private function prepareFuncs(): void {
		if (isset($this->extraParams['funcs']) && $this->extraParams['funcs'] && !$this->funcs) {
			if (is_bool($this->extraParams['funcs'])) {
				$this->funcs = new \WPSPCORE\App\Funcs(
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

	public function getRequest(): ?Request {
		return $this->request;
	}

	public function getExtraParams(): array {
		return $this->extraParams;
	}

	/*
	 *
	 */

	public function instanceConstruct() {}

	public function beforeConstruct() {}

	public function afterConstruct() {}

}