<?php

namespace WPSPCORE\Cache;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use WPSPCORE\Base\BaseInstances;

class Cache extends BaseInstances {

	/**
	 * @var DoctrineDbalAdapter|FilesystemAdapter|MemcachedAdapter|RedisAdapter|null $adapter
	 */
	public $adapter          = null;
	public $store            = null;
	public $connectionParams = null;

	/*
	 *
	 */

	public function prepare(): ?self {
		$this->adapter = (new Adapter(
			$this->funcs->_getMainPath(),
			$this->funcs->_getRootNamespace(),
			$this->funcs->_getPrefixEnv()
		))->init($this->store, $this->connectionParams);
		return $this;
	}

	/*
	 *
	 */

	public function global(): void {
		$globalCache = $this->funcs->_getAppShortName();
		$globalCache = $globalCache . '_cache';
		global ${$globalCache};
		${$globalCache} = $this;
	}

	/*
	 *
	 */

	public function _getItem($key) {
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

	public function _delete($key) {
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