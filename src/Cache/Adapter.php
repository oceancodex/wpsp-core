<?php

namespace WPSPCORE\Cache;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use WPSP\Funcs;

class Adapter {

	public static ?DoctrineDbalAdapter $instance = null;

	public static function getInstance(): DoctrineDbalAdapter {
		if (self::$instance == null) {
			self::$instance = self::initCacheAdapter();
		}
		return self::$instance;
	}

	public static function initCacheAdapter(): DoctrineDbalAdapter {
		$cacheConfigs     = include(Funcs::instance()->getConfigPath() . ('/cache.php'));
		$connectionParams = $cacheConfigs['stores'][$cacheConfigs['default']];
		$connection       = DriverManager::getConnection($connectionParams);
		return new DoctrineDbalAdapter(
			$connection,
			$cacheConfigs['prefix'],
			0,
			['db_table' => _dbTablePrefix() . 'cm_cache_items']
		);
	}

}