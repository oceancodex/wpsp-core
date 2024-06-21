<?php

namespace OCBPCORE\Objects\Slugify;

use Illuminate\Support\Str;

class Slugify {

	public static ?Str $instance = null;

	public static function getInstance(): ?Str {
		if (self::$instance == null) {
			self::$instance = new Str();
		}
		return self::$instance;
	}

	public static function slug(?string $string, $separator = null): string {
		return self::getInstance()->slug($string, $separator);
	}

	public static function slugUnify(?string $string, $separator = null): ?string {
		if (!$string) return null;
		$slug  = self::getInstance()->slug($string, $separator);
		$unify = [
			'tphcm'          => 'ho_chi_minh',
			'tp_ho_chi_minh' => 'ho_chi_minh',
			'hue'            => 'thua_thien_hue',
		];
		return $unify[$slug] ?? $slug;
	}

}