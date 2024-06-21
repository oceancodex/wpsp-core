<?php

namespace OCBPCORE\Objects\Env;

use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;

class Env {

	private static bool $isInitialized = false;

	public static function init($envDir): void {
		if (static::$isInitialized) return;
		if (method_exists(Dotenv::class, 'createImmutable')) {
			$dotEnv = Dotenv::createImmutable($envDir);
		}
		else {
			$repository = RepositoryBuilder::createWithNoAdapters()
//			                               ->addAdapter(EnvConstAdapter::class)
//			                               ->addWriter(PutenvAdapter::class)
			                               ->immutable()
			                               ->make();
			$dotEnv     = Dotenv::create($repository, $envDir);
		}
		$dotEnv->safeLoad();
		$dotEnv->required('APP_ENV')->allowedValues(['local', 'dev', 'production'])->notEmpty();
		static::$isInitialized = true;
	}

	public static function get(string $varName, $default = ''): string {
		return getenv($varName) ?: $_SERVER[$varName] ?? ($_ENV[$varName] ?? $default);
	}

}
