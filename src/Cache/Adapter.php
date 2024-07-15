<?php

namespace WPSPCORE\Cache;

use Doctrine\DBAL\DriverManager;
use Illuminate\Support\Str;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use WPSPCORE\Base\BaseInstances;

class Adapter extends BaseInstances {

	private string $store;

	public function afterInstanceConstruct(): void {
		$this->store = $this->funcs->_env('CACHE_STORE', true);
	}

	public function init(): DoctrineDbalAdapter|FilesystemAdapter|MemcachedAdapter|RedisAdapter|null {
		$cacheConfigs     = include($this->funcs->_getConfigPath() . '/cache.php');
		$cachePrefix      = $cacheConfigs['prefix'];
		$connectionParams = $cacheConfigs['stores'][$this->store];

		$adapter = null;

		if ($this->store == 'database') {
			$connection = DriverManager::getConnection($connectionParams);
			$adapter    = new DoctrineDbalAdapter(
				$connection,
				Str::slug($cacheConfigs['prefix']),
				0,
				['db_table' => $this->funcs->_getDBTablePrefix() . 'cm_cache_items']
			);
		}
		elseif ($this->store == 'file') {
			$adapter = new FilesystemAdapter(
				$this->funcs->_getAppShortName(),
				0,
				$connectionParams['path']
			);
		}
		elseif ($this->store == 'redis') {
			$connection = RedisAdapter::createConnection(
				'redis://' . $connectionParams['password'] . '@' . $connectionParams['host'] . ':' . $connectionParams['port'],
			);
			$adapter    = new RedisAdapter(
				$connection,
				$this->funcs->_getAppShortName(),
				0
			);
		}
		elseif ($this->store == 'memcached') {
			$connection = MemcachedAdapter::createConnection(
				'memcached://'
				. $connectionParams['sasl'][0]
				. ':' . $connectionParams['sasl'][1]
				. '@' . $connectionParams['servers']['host']
				. ':' . $connectionParams['servers']['port']
				. '?weight=' . $connectionParams['servers']['weight'],
			);
			$adapter = new MemcachedAdapter(
				$connection,
                $this->funcs->_getAppShortName(),
                0
			);
		}

		return $adapter;
	}

}