<?php

namespace WPSPCORE\Traits;

use WPSPCORE\Base\BaseRequest;

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
	public $environment         = null;

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
		$this->prepareEnvironment();

		// Unset extra params.
		if (isset($extraParams['unset_extra_params']) && $extraParams['unset_extra_params']) {
			unset($this->extraParams);
		}

		$this->afterConstruct();
		$this->afterInstanceConstruct();
	}

	/*
	 *
	 */

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

	/**
	 * prepare_funcs
	 * prepare_request
	 * prepare_validation
	 *
	 * unset_funcs
	 * unset_request
	 * unset_validation
	 * unset_extra_params
	 *
	 * funcs
	 * request
	 * validation
	 *
	 * environment
	 */

	public function prepareFuncs() {
		if ((!isset($this->extraParams['prepare_funcs']) || $this->extraParams['prepare_funcs']) && !$this->funcs) {
			$this->funcs = new \WPSPCORE\Funcs(
				$this->mainPath,
				$this->rootNamespace,
				$this->prefixEnv,
				[
					'environment'        => $this->extraParams['environment'] ?? null,
					'validation'         => null,

					'prepare_funcs'      => false,
					'prepare_request'    => false,

					'unset_funcs'        => true,
					'unset_request'      => true,
					'unset_validation'   => true,
					'unset_environment'  => false,

					'unset_extra_params' => true,
				]
			);
		}
		if (isset($this->extraParams['unset_funcs']) && $this->extraParams['unset_funcs']) {
			unset($this->funcs);
		}
		unset($this->extraParams['prepare_funcs']);
		unset($this->extraParams['unset_funcs']);
	}

	public function prepareLocale() {
		$this->locale = function_exists('get_locale') ? get_locale() : 'en';
	}

	public function prepareRequest() {
		if ((!isset($this->extraParams['prepare_request']) || $this->extraParams['prepare_request']) && !$this->request) {
			$this->request = BaseRequest::createFromGlobals();
			if (isset($this->validation) && $this->validation) {
				$this->request->validation = $this->validation;
			}
			else {
				unset($this->request->validation);
			}
		}
		if (isset($this->extraParams['unset_request']) && $this->extraParams['unset_request']) {
			unset($this->request);
		}
		unset($this->extraParams['prepare_request']);
		unset($this->extraParams['unset_request']);
	}

	public function prepareValidation() {
		if (isset($this->extraParams['validation']) && $this->extraParams['validation'] && !$this->validation) {
			$this->validation = $this->extraParams['validation'];
		}
		if (isset($this->extraParams['unset_validation']) && $this->extraParams['unset_validation']) {
			unset($this->validation);
		}
		unset($this->extraParams['validation']);
		unset($this->extraParams['unset_validation']);
	}

	public function prepareEnvironment() {
		if (isset($this->extraParams['environment']) && $this->extraParams['environment'] && !$this->environment) {
			$this->environment = $this->extraParams['environment'];
		}
		if (isset($this->extraParams['unset_environment']) && $this->extraParams['unset_environment']) {
			unset($this->environment);
		}
		unset($this->extraParams['environment']);
		unset($this->extraParams['unset_environment']);
	}


	/*
	 *
	 */

	public function beforeConstruct() {}

	public function beforeInstanceConstruct() {}

	public function afterConstruct() {}

	public function afterInstanceConstruct() {}

}