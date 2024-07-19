<?php

namespace WPSPCORE\Cache;

use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use WPSPCORE\Base\BaseInstances;

class RateLimiter extends BaseInstances {

	protected ?string $key              = null;
	protected ?string $store            = null;
	protected ?array  $connectionParams = null;
	protected ?object $adapter          = null;
	protected ?array  $limiters         = null;

	/*
	 *
	 */

	public function prepare(): RateLimiter {
		$configs = $this->funcs->_config('rate-limiter');
		if (!$this->adapter) {
			$this->adapter = (new Adapter(
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			))->init($this->store, $this->connectionParams);
		}
		foreach ($configs as $configKey => $configData) {
			$this->limiters[$configKey] = (new RateLimiterFactory(
				$configData,
				new CacheStorage($this->adapter)
			))->create($this->getKey());
		}
		return $this;
	}

	/*
	 *
	 */

	public function global(): void {
		$globalRateLimiter = $this->funcs->_getAppShortName();
		$globalRateLimiter = $globalRateLimiter . '_rate_limiter';
		global ${$globalRateLimiter};
		${$globalRateLimiter} = $this;
	}

	/*
	 *
	 */

	public function setKey($key = null): void {
		if ($key) $this->key = $key;
	}

	public function getKey(): ?string {
		return $this->key ?? $this->request->getClientIp();
	}

}