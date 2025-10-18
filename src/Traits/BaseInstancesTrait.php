<?php

namespace WPSPCORE\Traits;

use Symfony\Component\HttpFoundation\Request;
use WPSPCORE\Funcs;

trait BaseInstancesTrait {

	public $mainPath            = null;
	public $rootNamespace       = null;
	public $prefixEnv           = null;
	public $extraParams         = [];

	/** @var \Symfony\Component\HttpFoundation\Request|\WPSPCORE\Validation\ValidatedRequest */
	public $request             = null;
	public $locale              = null;
	/** @var \WPSPCORE\Funcs|null */
	public $funcs               = null;

	public function beforeBaseInstanceConstruct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		$this->locale = function_exists('get_locale') ? get_locale() : 'en';
		if (!$this->request) {
			if (!$this->request) {
				if (class_exists('\WPSPCORE\Validation\ValidatedRequest')) {
					$this->request = \WPSPCORE\Validation\ValidatedRequest::createFromGlobals();
				} else {
					$this->request = Request::createFromGlobals();
				}
			}
		}
		$this->beforeConstruct();
		$this->beforeInstanceConstruct();
		if ($mainPath) $this->mainPath = $mainPath;
		if ($rootNamespace) $this->rootNamespace = $rootNamespace;
		if ($prefixEnv) $this->prefixEnv = $prefixEnv;
		if (!empty($extraParams)) $this->extraParams = $extraParams;
		if (!isset($extraParams['prepare_funcs']) || $extraParams['prepare_funcs']) {
			$this->prepareFuncs();
		}
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

	public function prepareFuncs() {
		$this->funcs = new Funcs(
			$this->mainPath,
			$this->rootNamespace,
			$this->prefixEnv,
			[
				'prepare_funcs' => false,
			]
		);
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

	public function beforeConstruct() {}

	public function beforeInstanceConstruct() {}

	public function afterConstruct() {}

	public function afterInstanceConstruct() {}

}