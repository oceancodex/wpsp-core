<?php

namespace WPSPCORE\Base;

use Symfony\Component\HttpFoundation\Request;
use WPSPCORE\Funcs;

abstract class BaseInstances {

	public ?string  $mainPath         = null;
	public ?string  $rootNamespace    = null;
	public ?string  $prefixEnv        = null;
	public ?Request $request          = null;
	public ?string  $locale           = null;
	public ?array   $customProperties = [];

	public ?Funcs   $funcs            = null;

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $customProperties = []) {
		$this->locale = get_locale();
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

	protected function prepareFuncs(): void {
		$this->funcs = new Funcs(
			$this->mainPath,
			$this->rootNamespace,
			$this->prefixEnv,
			[
				'prepare_funcs' => false
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