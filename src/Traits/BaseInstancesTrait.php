<?php

namespace WPSPCORE\Traits;

use Symfony\Component\HttpFoundation\Request;
use WPSPCORE\Funcs;

trait BaseInstancesTrait {

	public $mainPath            = null;
	public $rootNamespace       = null;
	public $prefixEnv           = null;
	public $extraParams         = [];

	/** @var \Symfony\Component\HttpFoundation\Request */
	public $request             = null;
	public $locale              = null;
	/** @var \WPSPCORE\Funcs|null */
	public $funcs               = null;
	public $currentPathSlugify  = null;

	public function beforeBaseInstanceConstruct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		$this->locale = function_exists('get_locale') ? get_locale() : 'en';
		if (!$this->request) $this->request = Request::createFromGlobals();
		$this->beforeConstruct();
		$this->beforeInstanceConstruct();
		if ($mainPath) $this->mainPath = $mainPath;
		if ($rootNamespace) $this->rootNamespace = $rootNamespace;
		if ($prefixEnv) $this->prefixEnv = $prefixEnv;
		if (!empty($extraParams)) $this->extraParams = $extraParams;
		if (!isset($extraParams['prepare_funcs']) || $extraParams['prepare_funcs']) {
			$this->prepareFuncs();
			$this->prepareCurrentPathSlugify();
		}
		$this->afterConstruct();
		$this->afterInstanceConstruct();
	}

	/*
	 *
	 */

	public function wantJson() {
		return $this->request->headers->get('Accept') === 'application/json';
	}

	/*
	 *
	 */

	public function prepareFuncs() {
		$this->funcs = new Funcs(
			$this->mainPath,
			$this->rootNamespace,
			$this->prefixEnv,
			[
				'prepare_funcs' => false,
			]
		);
	}

	public function prepareCurrentPathSlugify() {
		$path = $this->request->getQueryString();
		$path = trim($path, '/');
		$path = preg_replace('/[^0-9a-zA-Z]/iu', '_', $path);
		$path = $this->funcs->_env('APP_SHORT_NAME', true) . '_' . $path;
		$this->currentPathSlugify = $path;
	}

	/*
	 *
	 */

	public function beforeConstruct() {}

	public function beforeInstanceConstruct() {}

	public function afterConstruct() {}

	public function afterInstanceConstruct() {}

}