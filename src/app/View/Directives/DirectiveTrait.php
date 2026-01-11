<?php

namespace WPSPCORE\app\View\Directives;

trait DirectiveTrait {

	public function arrayStringToArray(string $string): array {
		if (!str_starts_with(trim($string), '[')) {
			throw new \InvalidArgumentException('Invalid PHP array string');
		}

		return eval('return ' . $string . ';');
	}

}