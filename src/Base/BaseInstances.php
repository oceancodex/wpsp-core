<?php

namespace WPSPCORE\Base;

use Symfony\Component\HttpFoundation\Request;
use WPSPCORE\Funcs;

abstract class BaseInstances {

	public ?string  $mainPath      = null;
	public ?string  $rootNamespace = null;
	public ?string  $prefixEnv     = null;
	public ?Request $request       = null;

	public ?Funcs $funcs = null;

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null) {
		$this->beforeConstruct();
		$this->beforeInstanceConstruct();
		if ($mainPath) $this->mainPath = $mainPath;
		if ($rootNamespace) $this->rootNamespace = $rootNamespace;
		if ($prefixEnv) $this->prefixEnv = $prefixEnv;
		if (!$this->request) $this->request = Request::createFromGlobals();
		$this->prepareFuncs();
		$this->afterConstruct();
		$this->afterInstanceConstruct();
	}

	public function prepareFuncs(): void {
		$this->funcs = new Funcs(
			$this->mainPath,
			$this->rootNamespace,
			$this->prefixEnv
		);
	}

	/*
	 *
	 */

	public function beforeConstruct() {}

	public function beforeInstanceConstruct() {}

	public function afterConstruct() {}

	public function afterInstanceConstruct() {}

}