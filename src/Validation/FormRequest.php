<?php

namespace WPSPCORE\Validation;

use Illuminate\Validation\Validator;
use WPSPCORE\Base\BaseInstances;

/**
 * @property \WPSPCORE\Validation\Validation $validation
 */
abstract class FormRequest extends BaseInstances {

	public $data          = [];
	public $validation;
	public $validatedData = [];

	/*
	 *
	 */

	abstract public function authorize(): bool;

	abstract public function rules(): array;

	/*
	 *
	 */

	public function messages(): array {
		return [];
	}

	public function attributes(): array {
		return [];
	}

	public function prepareForValidation() {
		// Override trong subclass nếu cần
	}

	/*
	 *
	 */

	public function validate() {
		if (!$this->authorize()) {
			throw $this->createAuthorizationException();
		}
		$validator = $this->funcs->getValidation()->make(
			$this->data,
			$this->rules(),
			$this->messages(),
			$this->attributes()
		);

		// Gọi withValidator để cho phép tùy chỉnh validator
		$this->withValidator($validator);

		// Kiểm tra validation
		if ($validator->fails()) {
			$this->failedValidation($validator);
		}

		// Lưu validated data
		$this->validatedData = $validator->validated();

		return $this->validatedData;
	}

	public function validated($key = null, $default = null) {
		if (empty($this->validatedData)) {
			$this->validate();
		}

		if ($key === null) {
			return $this->validatedData;
		}

		return $this->validatedData[$key] ?? $default;
	}

	/**
	 * @param Validator $validator
	 *
	 */
	public function withValidator($validator) {
		// Override trong subclass nếu cần
	}

	/**
	 * @param Validator $validator
	 *
	 */
	public function failedValidation($validator) {
		// Override trong subclass nếu cần
	}

	/*
	 *
	 */

	protected function collectData() {
		return array_merge(
			$_GET ?? [],
			$_POST ?? [],
			$_FILES ?? [],
			$this->collectRawInput() ?? []
		);
	}

	protected function collectRawInput() {
		$rawData = file_get_contents('php://input');
		if (empty($rawData)) {
			$rawData = $this->request->getContent();
		}
		return json_decode($rawData, true);
	}

	protected function createAuthorizationException() {
		// Kiểm tra xem project có custom AuthorizationException không
		$customExceptionClass = $this->getAuthorizationExceptionClass();

		if ($customExceptionClass && class_exists($customExceptionClass)) {
			return new $customExceptionClass('This action is unauthorized.');
		}

		// Fallback về Exception cơ bản
		return new \Exception('This action is unauthorized.');
	}

	protected function getAuthorizationExceptionClass() {
		return null;
	}

	/*
	 *
	 */

	public function safe() {
		return $this->validated();
	}

	public function all() {
		return $this->data;
	}

	public function has($key) {
		return isset($this->data[$key]);
	}

	public function only($keys) {
		return array_intersect_key($this->data, array_flip($keys));
	}

	public function merge($data) {
		$this->data = array_merge($this->data, $data);
	}

	public function input($key, $default = null) {
		return $this->data[$key] ?? $default;
	}

	public function except($keys) {
		return array_diff_key($this->data, array_flip($keys));
	}

	public function filled($key) {
		return $this->has($key) && !empty($this->data[$key]);
	}

	public function missing($key) {
		return !$this->has($key);
	}

	public function replace($data) {
		$this->data = $data;
	}

}