<?php

namespace WPSPCORE\Traits;

use Illuminate\Http\Request;

/**
 * BaseInstancesTrait.
 *
 * @property \WPSPCORE\Funcs|null $funcs
 */
trait BaseInstancesTrait {

	public $mainPath      = null;
	public $rootNamespace = null;
	public $prefixEnv     = null;
	public $extraParams   = [];

	public $funcs         = null;
	public $request       = null;

	public function baseInstanceConstruct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = null) {
		$this->beforeConstruct();
		if ($mainPath)      $this->mainPath         = $mainPath;
		if ($rootNamespace) $this->rootNamespace    = $rootNamespace;
		if ($prefixEnv)     $this->prefixEnv        = $prefixEnv;
		if ($extraParams)   $this->extraParams      = $extraParams;
		$this->prepareFuncs();
		$this->prepareRequest();
		$this->afterConstruct();
		unset($this->extraParams);
	}

	/*
	 *
	 */

	public function slugParams($params = [], $separator = '_') {
		// Lấy toàn bộ query string từ URL
		$request = $this->request ?: $this->funcs->getApplication('request');
		$queryParams = $request->query->all();

		$selectedParts = [];

		// Chỉ lấy những params được khai báo
		foreach ($params as $key) {
			if (isset($queryParams[$key])) {
				// Ghép key và value để phân biệt
				$selectedParts[] = $key . '=' . $queryParams[$key];
			}
		}

		// Ghép các phần lại thành một chuỗi
		$slug = implode($separator, $selectedParts);

		// Làm sạch chuỗi thành dạng slug
		$slug = preg_replace('/[^0-9a-zA-Z]/iu', $separator, $slug);

		// Thêm tiền tố app name (nếu có)
		$prefix = $this->funcs->_env('APP_SHORT_NAME', true);
		if ($prefix) {
			$slug = $prefix . $separator . $slug;
		}

		// Gán vào biến class
		return $slug;
	}

	/*
	 *
	 */

	private function prepareRequest(): void {
		if (isset($this->funcs->request) && $this->funcs->request) {
			$this->request = $this->funcs->request;
		}
		else {
			$this->request = \Illuminate\Http\Request::capture();
		}

		if (!$this->request) {
			unset($this->request);
		}
	}

	private function prepareFuncs(): void {
		if (isset($this->extraParams['funcs']) && $this->extraParams['funcs'] && !$this->funcs) {
			if (is_bool($this->extraParams['funcs'])) {
				$this->funcs = new \WPSPCORE\Funcs(
					$this->mainPath,
					$this->rootNamespace,
					$this->prefixEnv,
					$this->extraParams
				);
			}
			else {
				$this->funcs = $this->extraParams['funcs'];
			}
		}

		unset($this->extraParams['funcs']);

		if (!$this->funcs) {
			unset($this->funcs);
		}
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

	public function afterConstruct() {}

}