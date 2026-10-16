<?php

namespace WPSPCORE\App\Console\Traits;

use Illuminate\Support\Facades\File;
use WPSPCORE\Funcs;

/**
 * @property Funcs $funcs
 */
trait CommandsTrait {

	public $coreNamespace = 'WPSPCORE';
	public $funcs         = null;

	/*
	 *
	 */

	public function replaceNamespaces($content) {
		$content = str_replace('{{ rootNamespace }}', $this->funcs->rootNamespace, $content);
		$content = str_replace('{{ coreNamespace }}', $this->coreNamespace, $content);
		$content = str_replace('{{ root_namespace }}', $this->funcs->rootNamespace, $content);
		$content = str_replace('{{ core_namespace }}', $this->coreNamespace, $content);
		return $content;
	}

	public function validateClassName($className = null, $inputName = 'name') {
		if (empty($className) || @preg_match('/[^A-Za-z0-9_]/', $className)) {
			$this->error('The ' . $inputName . ': "' . $className . '" is invalid! Allow characters: A-Z, a-z, 0-9, _');
			exit();
		}
	}

	public function validateSlug($string = null, $inputName = 'slug') {
		if (empty($string) || @preg_match('/[^A-Za-z0-9_-]/', $string)) {
			$this->error('The ' . $inputName . ': "' . $string . '" is invalid! Allow characters: A-Z, a-z, 0-9, _ and -');
			exit();
		}
	}

	public function validatePath($path = null, $inputName = 'path') {
		if (empty($path) || @preg_match('/[^A-Za-z0-9_\/]/', $path)) {
			$this->error('The ' . $inputName . ': "' . $path . '" is invalid! Allow characters: A-Z, a-z, 0-9, _, and /');
			exit();
		}
	}

	/*
	 *
	 */

	public function getRouteContent($routeName) {
		return File::get($this->funcs->mainPath . '/routes/' . $routeName . '.php');
	}

	/*
	 *
	 */

	public function saveRouteContent($routeName, $content) {
		File::put($this->funcs->mainPath . '/routes/' . $routeName . '.php', $content);
	}

	/*
	 *
	 */

	public function addClassToRoute($routeName, $findFunction, $newLineForFindFunction, $newLineUseClass) {
		$routeContent = $this->getRouteContent($routeName);
		$routeContent = preg_replace('/public function ' . $findFunction . '([\S\s]*?)\{/iu', 'public function ' . $findFunction . "$1{\n" . $newLineForFindFunction, $routeContent);
		if (!strpos($routeContent, $newLineUseClass)) {
			$routeContent = preg_replace('/(\n\s*)class ' . $routeName . ' \{/iu', "\n" . $newLineUseClass . '$1class ' . $routeName . ' {', $routeContent);
		}
		$this->saveRouteContent($routeName, $routeContent);
	}

	/*
	 *
	 */

	public function slug($str, $separator = '-') {
		if (empty($str)) {
			return '';
		}

		// 1. Chuyển về chữ thường
		$str = mb_strtolower(trim($str), 'UTF-8');

		// 2. Thay thế các ký tự có dấu tiếng Việt thành không dấu
		$unicode = [
			'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
			'd' => 'đ',
			'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
			'i' => 'í|ì|ỉ|ĩ|ị',
			'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
			'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
			'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
		];

		foreach ($unicode as $nonUnicode => $uni) {
			$str = preg_replace("/($uni)/u", $nonUnicode, $str);
		}

		// 3. Thay thế các ký tự không phải chữ cái, số, hoặc khoảng trắng thành khoảng trắng
		$str = preg_replace('/[^a-z0-9\s]/u', '', $str);

		// 4. Thay thế khoảng trắng liên tiếp bằng ký tự separator
		$str = preg_replace('/[\s]+/', $separator, $str);

		// 5. Cắt bỏ separator thừa ở đầu và cuối chuỗi
		$str = trim($str, $separator);

		return $str;
	}

}