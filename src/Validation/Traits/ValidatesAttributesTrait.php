<?php

namespace WPSPCORE\Validation\Traits;

use WPSPCORE\Validation\Validation;

trait ValidatesAttributesTrait {

	protected $rules            = [];
	protected $messages         = [];
	protected $customAttributes = [];

	public function validateAttributes(array $data) {
		if (empty($this->rules)) {
			return $data;
		}

		return Validation::validate(
			$data,
			$this->rules,
			$this->messages,
			$this->customAttributes
		);
	}

	public function getRules() {
		return $this->rules;
	}

	public function setRules(array $rules) {
		$this->rules = $rules;
		return $this;
	}

	public function getMessages() {
		return $this->messages;
	}

	public function setMessages(array $messages) {
		$this->messages = $messages;
		return $this;
	}

	public static function bootValidatesAttributes() {
		static::saving(function($model) {
			if (!empty($model->rules)) {
				$model->validateAttributes($model->getAttributes());
			}
		});
	}

}