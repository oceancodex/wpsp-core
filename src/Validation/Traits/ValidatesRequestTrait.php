<?php

namespace WPSPCORE\Validation\Traits;

use Illuminate\Validation\ValidationException;

trait ValidatesRequestTrait {

	public function passes($rules, $messages = [], $customAttributes = []): bool {
		try {
			$this->validate($rules, $messages, $customAttributes);
			return true;
		}
		catch (ValidationException $e) {
			return false;
		}
	}

	public function fails($rules, $messages = [], $customAttributes = []): bool {
		return !$this->passes($rules, $messages, $customAttributes);
	}

	public function errors($rules, $messages = [], $customAttributes = []) {
		try {
			$this->validate($rules, $messages, $customAttributes);
			return [];
		}
		catch (ValidationException $e) {
			return $e->errors();
		}
	}

	/*
	 *
	 */

	public function validate($rules, $messages = [], $customAttributes = []) {
		$data = $this->all();
		return $this->validation->validate($data, $rules, $messages, $customAttributes);
	}

	public function validator($rules, $messages = [], $customAttributes = []) {
		$data = $this->all();

		return $this->validation->make($data, $rules, $messages, $customAttributes);
	}

	public function validated($rules, $messages = [], $customAttributes = []) {
		try {
			return $this->validate($rules, $messages, $customAttributes);
		}
		catch (ValidationException $e) {
			return null;
		}
	}

	public function validateOnly($keys, $rules, $messages = [], $customAttributes = []) {
		$data = $this->only($keys);

		return $this->validation->validate($data, $rules, $messages, $customAttributes);
	}

	/*
	 *
	 */

	public function all(): array {
		$data = array_merge(
			$this->query->all(),
			$this->request->all(),
			$this->files->all(),
			json_decode($this->getContent(), true) ?? []
		);

		// For WP_REST_Request compatibility
		if (method_exists($this, 'get_params')) {
			$data = array_merge($data, $this->get_params());
		}

		return $data;
	}

	public function only($keys): array {
		$data = $this->all();
		return array_intersect_key($data, array_flip($keys));
	}

	public function except($keys): array {
		$data = $this->all();
		return array_diff_key($data, array_flip($keys));
	}

}