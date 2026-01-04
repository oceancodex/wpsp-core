<?php

namespace WPSPCORE\App\View;

trait DirectiveTrait {

	public static function arrayStringToArray(string $string): array {
		if (!str_starts_with(trim($string), '[')) {
			throw new \InvalidArgumentException('Invalid PHP array string');
		}

		return eval('return ' . $string . ';');
	}

}