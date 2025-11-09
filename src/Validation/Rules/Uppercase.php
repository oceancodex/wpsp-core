<?php

namespace WPSPCORE\Validation\Rules;

class Uppercase implements Rule {

	public function passes(string $attribute, $value): bool {
		return is_string($value) && $value === strtoupper($value);
	}

	public function message(): string {
		return 'The :attribute must be uppercase.';
	}

}