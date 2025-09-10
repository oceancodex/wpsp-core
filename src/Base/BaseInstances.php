<?php

namespace WPSPCORE\Base;

use Symfony\Component\HttpFoundation\Request;
use WPSPCORE\Funcs;

abstract class BaseInstances {

	public ?string  $mainPath      = null;
	public ?string  $rootNamespace = null;
	public ?string  $prefixEnv     = null;
	public ?Request $request       = null;
	public ?string  $locale        = null;

	public ?Funcs $funcs = null;

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null) {
		$this->locale = get_locale();
		if (!$this->request) $this->request = Request::createFromGlobals();
		$this->beforeConstruct();
		$this->beforeInstanceConstruct();
		if ($mainPath) $this->mainPath = $mainPath;
		if ($rootNamespace) $this->rootNamespace = $rootNamespace;
		if ($prefixEnv) $this->prefixEnv = $prefixEnv;
		$this->prepareFuncs();
		$this->afterConstruct();
		$this->afterInstanceConstruct();
	}

	/*
	 *
	 */

	protected function prepareFuncs(): void {
		$this->funcs = new Funcs(
			$this->mainPath,
			$this->rootNamespace,
			$this->prefixEnv
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