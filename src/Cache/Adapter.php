<?php

namespace WPSPCORE\Cache;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use WPSPCORE\Base\BaseInstances;

class Adapter extends BaseInstances {

	public function init(): DoctrineDbalAdapter {
		$cacheConfigs     = include($this->funcs->_getConfigPath() . '/cache.php');
		$connectionParams = $cacheConfigs['stores'][$cacheConfigs['default']];
		$connection       = DriverManager::getConnection($connectionParams);
		return new DoctrineDbalAdapter(
			$connection,
			$cacheConfigs['prefix'],
			0,
			['db_table' => $this->funcs->_getDBTablePrefix() . 'cm_cache_items']
		);
	}

}