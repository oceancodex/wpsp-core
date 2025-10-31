<?php

namespace WPSPCORE\Traits;

use WPSPCORE\Base\BaseRequest;

/**
 * BaseInstancesTrait.
 *
 * @property \WPSPCORE\Funcs|null                                                                      $funcs
 * @property \Symfony\Component\HttpFoundation\Request|\WPSPCORE\Validation\RequestWithValidation|null $request
 * @property \WPSPCORE\Validation\Validation|null                                                      $validation
 * @property \WPSPCORE\Environment\Environment|null                                                    $environment
 * @property \WPSPCORE\Database\Eloquent|null                                                          $eloquent
 * @property \WPSPCORE\Migration\Migration|null                                                        $migration
 * @property \WPSPCORE\ErrorHandler\Ignition|null                                                      $ignition
 */
trait BaseInstancesTrait {

	public $mainPath      = null;
	public $rootNamespace = null;
	public $prefixEnv     = null;

	public $funcs         = null;
	public $locale        = null;
	public $request       = null;

	public $validation    = null;
	public $environment   = null;
	public $eloquent      = null;
	public $migration     = null;
	public $ignition      = null;

	public $extraParams   = [];

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

		$this->afterConstruct();
		$this->afterInstanceConstruct();

		if (isset($extraParams['unset_map_routes']) && $extraParams['unset_map_routes'] && isset($this->mapRoutes)) {
			unset($this->mapRoutes);
			unset($this->extraParams['unset_map_routes']);
		}

		// Unset extra params.
		if (isset($extraParams['unset_extra_params']) && $extraParams['unset_extra_params']) {
			unset($this->extraParams);
		}
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

	public function prepareFuncs() {
		if (isset($this->extraParams['funcs']) && $this->extraParams['funcs'] && !$this->funcs) {
			if (is_bool($this->extraParams['funcs'])) {
				$this->funcs = new \WPSPCORE\Funcs(
					$this->mainPath,
					$this->rootNamespace,
					$this->prefixEnv,
					[
						'environment' => $this->extraParams['environment'] ?? null,
					]
				);
			}
			else {
				$this->funcs = $this->extraParams['funcs'];
			}
		}
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