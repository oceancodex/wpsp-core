<?php

namespace WPSPCORE\Traits;

/**
 * BaseInstancesTrait.
 *
 * @property \WPSPCORE\Funcs|null $funcs
 * @property \Symfony\Component\HttpFoundation\Request|\WPSPCORE\Validation\RequestWithValidation|null $request
 * @property \WPSPCORE\Validation\Validation|null $validation
 */
trait BaseInstancesTrait {

	public $mainPath            = null;
	public $rootNamespace       = null;
	public $prefixEnv           = null;

	public $funcs               = null;
	public $locale              = null;
	public $request             = null;
	public $validation          = null;

	public $extraParams         = [];

	public function beforeBaseInstanceConstruct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = null) {
		$this->beforeConstruct();
		$this->beforeInstanceConstruct();

		if ($mainPath)      $this->mainPath         = $mainPath;
		if ($rootNamespace) $this->rootNamespace    = $rootNamespace;
		if ($prefixEnv)     $this->prefixEnv        = $prefixEnv;
		if ($extraParams)   $this->extraParams      = $extraParams;

		$this->prepareFuncs();
		$this->prepareLocale();
		$this->prepareValidation();

		$this->prepareRequest();

		$this->afterConstruct();
		$this->afterInstanceConstruct();
	}

	/*
	 *
	 */

	public function wantJson() {
		return $this->request->headers->get('Accept') === 'application/json';
	}

	public function getQueryStringSlugify($params = []) {
		// Lấy toàn bộ query string từ URL
		$queryParams = $this->request->query->all();

		$selectedParts = [];

		// Chỉ lấy những params được khai báo
		foreach ($params as $key) {
			if (isset($queryParams[$key])) {
				// Ghép key và value để phân biệt
				$selectedParts[] = $key . '=' . $queryParams[$key];
			}
		}

		// Ghép các phần lại thành một chuỗi
		$slug = implode('_', $selectedParts);

		// Làm sạch chuỗi thành dạng slug
		$slug = preg_replace('/[^0-9a-zA-Z]/iu', '_', $slug);

		// Thêm tiền tố app name (nếu có)
		$prefix = $this->funcs->_env('APP_SHORT_NAME', true);
		if ($prefix) {
			$slug = $prefix . '_' . $slug;
		}

		// Gán vào biến class
		return $slug;
	}

	/*
	 *
	 */

	public function prepareFuncs() {
		if (isset($this->extraParams['prepare_funcs']) && $this->extraParams['prepare_funcs']) {
			$this->funcs = new \WPSPCORE\Funcs(
				$this->mainPath,
				$this->rootNamespace,
				$this->prefixEnv,
				[
					'prepare_funcs'      => false,
					'prepare_request'    => false,
					'prepare_validation' => false,
				]
			);
		}
		else {
			unset($this->funcs);
		}
	}

	public function prepareLocale() {
		$this->locale = function_exists('get_locale') ? get_locale() : 'en';
	}

	public function prepareRequest() {
		if (isset($this->extraParams['prepare_request']) && $this->extraParams['prepare_request']) {
			if (class_exists('\WPSPCORE\Validation\RequestWithValidation')) {
				$this->request = \WPSPCORE\Validation\RequestWithValidation::createFromGlobals();
			} else {
				$this->request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
			}
		}
		else {
			unset($this->request);
		}
	}

	public function prepareValidation() {
		if (isset($this->extraParams['prepare_validation']) && $this->extraParams['prepare_validation']) {
			$this->validation = $this->extraParams['validation'] ?? null;
		}
		else {
			unset($this->validation);
		}
	}


	/*
	 *
	 */

	public function beforeConstruct() {}

	public function beforeInstanceConstruct() {}

	public function afterConstruct() {}

	public function afterInstanceConstruct() {}

}