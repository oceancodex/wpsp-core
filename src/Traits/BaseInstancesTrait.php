<?php

namespace WPSPCORE\Traits;

use Symfony\Component\HttpFoundation\Request;
use WPSPCORE\Funcs;

trait BaseInstancesTrait {

	public $mainPath         = null;
	public $rootNamespace    = null;
	public $prefixEnv        = null;
	public $customProperties = [];
	/** @var \Symfony\Component\HttpFoundation\Request */
	public $request          = null;
	public $locale           = null;
	/** @var \WPSPCORE\Funcs|null */
	public $funcs            = null;

	public function beforeBaseInstanceConstruct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $customProperties = []) {
		$this->locale = function_exists('get_locale') ? get_locale() : 'en';
		if (!$this->request) $this->request = Request::createFromGlobals();
		$this->beforeConstruct();
		$this->beforeInstanceConstruct();
		if ($mainPath) $this->mainPath = $mainPath;
		if ($rootNamespace) $this->rootNamespace = $rootNamespace;
		if ($prefixEnv) $this->prefixEnv = $prefixEnv;
		if (!empty($customProperties)) $this->customProperties = $customProperties;
		if (!isset($customProperties['prepare_funcs']) || $customProperties['prepare_funcs']) $this->prepareFuncs();
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

	protected function prepareFuncs() {
		$this->funcs = new Funcs(
			$this->mainPath,
			$this->rootNamespace,
			$this->prefixEnv,
			[
				'prepare_funcs' => false,
			]
		);
	}

	/*
	 *
	 */

	protected function beforeConstruct() {}

	protected function beforeInstanceConstruct() {}

	protected function afterConstruct() {}

	protected function afterInstanceConstruct() {}

}