<?php

namespace WPSPCORE\Validation\Rules;

interface Rule {

	public function passes(string $attribute, $value): bool;

	public function message(): string;

}