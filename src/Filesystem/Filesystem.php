<?php

namespace WPSPCORE\Filesystem;

class Filesystem {

	private static ?\Illuminate\Filesystem\Filesystem $instance = null;

	public static function instance(): ?\Illuminate\Filesystem\Filesystem {
		if (!self::$instance) {
			self::$instance = new \Illuminate\Filesystem\Filesystem();
		}
		return self::$instance;
	}

}