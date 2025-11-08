<?php

namespace WPSPCORE\Traits;

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
		$this->prepareRequest();

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

	/*
	 *
	 */

	private function prepareFuncs() {
		if (isset($this->extraParams['funcs']) && $this->extraParams['funcs'] && !$this->funcs) {
			if (is_bool($this->extraParams['funcs'])) {
				$this->funcs = new \WPSPCORE\Funcs($this->mainPath, $this->rootNamespace, $this->prefixEnv, []);
			}
			else {
				$this->funcs = $this->extraParams['funcs'];
			}
			unset($this->extraParams['funcs']);
		}
	}

	private function prepareLocale() {
		$this->locale = function_exists('get_locale') ? get_locale() : 'en';
	}

	private function prepareRequest() {
//		if (isset($this->funcs) && $this->funcs) {
//			$requestClass = '\\' . $this->funcs->_getRootNamespace() . '\app\Workers\Requests\Request';
//			$this->request = $requestClass::createFromGlobals();
//		}
		echo '<pre style="background:white;z-index:9999;position:relative">'; print_r(app()); echo '</pre>'; die();
		$this->request = app('request');
	}

	/*
	 *
	 */

	public function getLocale() {
		return $this->locale;
	}

	public function getRequest() {
		return $this->request;
	}

	public function getExtraParams() {
		return $this->extraParams;
	}

	/*
	 *
	 */

	public function beforeConstruct() {}

	public function beforeInstanceConstruct() {}

	public function afterConstruct() {}

	public function afterInstanceConstruct() {}

}