<?php

namespace WPSPCORE\Base;

use Symfony\Component\HttpFoundation\Request;
use WPSPCORE\Funcs;

abstract class BaseInstances {

	public $mainPath         = null;
	public $rootNamespace    = null;
	public $prefixEnv        = null;
	public $customProperties = [];
	/** @var Request */
	public $request          = null;
	public $locale           = null;
	/** @var Funcs|null */
	public $funcs            = null;

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $customProperties = []) {
		$this->locale = function_exists('get_locale') ? get_locale() : 'en';
		if (!$this->request) $this->request = Request::createFromGlobals();
		$this->beforeConstruct();
		$this->beforeInstanceConstruct();
		if ($mainPath) $this->mainPath = $mainPath;
		if ($rootNamespace) $this->rootNamespace = $rootNamespace;
		if ($prefixEnv) $this->prefixEnv = $prefixEnv;
		if (!empty($customProperties)) $this->customProperties = $customProperties;
		if (!isset($customProperties['prepare_funcs'])) $this->prepareFuncs();
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
//		$this->funcs = new Funcs(
////			$this->mainPath,
////			$this->rootNamespace,
////			$this->prefixEnv,
////			[
////				'prepare_funcs' => false
////			]
////		);
		$this->funcs = Funcs::getInstance();
	}

	/*
	 *
	 */

	protected function beforeConstruct() {}

	protected function beforeInstanceConstruct() {}

	protected function afterConstruct() {}

	protected function afterInstanceConstruct() {}

}