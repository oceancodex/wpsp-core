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

	public function afterConstruct(): void {
		$this->store = $this->funcs->_env('CACHE_STORE', true);
	}

	public function init($store = null, $connectionParams = null) {
		if ($store) $this->store = $store;
		$cacheConfigs     = include($this->funcs->_getConfigPath() . '/cache.php');
		$cachePrefix      = $cacheConfigs['prefix'];
		$connectionParams = $connectionParam ?? $cacheConfigs['stores'][$this->store];

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
			$conns    = [];
			$user     = $connectionParams['sasl'][0];
			$password = $connectionParams['sasl'][1];
			foreach ($connectionParams['servers'] as $server) {
				if ($user || $password) {
					$conns[] = 'memcached://'
						. $user
						. ':' . $password
						. '@' . $server['host']
						. ':' . $server['port']
						. '?weight=' . $server['weight'];
				}
				else {
					$conns[] = 'memcached://'
						. $server['host']
						. ':' . $server['port']
						. '?weight=' . $server['weight'];
				}
			}
			$connection = MemcachedAdapter::createConnection($conns);
			$adapter    = new MemcachedAdapter(
				$connection,
				$this->funcs->_getAppShortName(),
				0
			);
		}

		return $adapter;
	}

}