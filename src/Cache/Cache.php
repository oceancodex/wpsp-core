<?php

namespace WPSPCORE\Cache;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use WPSPCORE\Base\BaseInstances;

class Cache extends BaseInstances {

	private DoctrineDbalAdapter|FilesystemAdapter|MemcachedAdapter|RedisAdapter|null $adapter = null;

	/*
	 *
	 */

	public function afterConstruct(): void {
		$this->adapter = (new Adapter($this->mainPath, $this->rootNamespace, $this->prefixEnv))->init();
	}

	/*
	 *
	 */

	public function _getItem($key): \Symfony\Component\Cache\CacheItem|string {
		$key = $this->_getCacheKey($key);
		try {
			return $this->adapter->getItem($key);
		}
		catch (InvalidArgumentException $e) {
			return $e->getMessage();
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	public function _getCacheKey($key): string {
		return $this->funcs->_getDBTablePrefix() . $key;
	}

	public function _getItemValue($key) {
		return $this->_getItem($key)->get();
	}

	/*
	 *
	 */

	public function _set($key, $callback) {
		$this->_delete($key);
		return $this->_get($key, $callback);
	}

	public function _get($key, $callback) {
		$key = $this->_getCacheKey($key);
		try {
			return $this->adapter->get($key, $callback);
		}
		catch (InvalidArgumentException $e) {
			return $e->getMessage();
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	public function _delete($key): bool|string {
		$key = $this->_getCacheKey($key);
		try {
			return $this->adapter->delete($key);
		}
		catch (InvalidArgumentException $e) {
			return $e->getMessage();
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	public function _reset(): void {
		$this->adapter->reset();
	}

	public function _clear($prefix = null): void {
		if ($prefix) {
			$this->adapter->clear($prefix);
		}
		else {
			$this->adapter->clear();
		}
	}

}