<?php

namespace WPSPCORE\Cache;

class Cache {

	public static function getItemValue($key) {
		return self::getItem($key)->get();
	}

	public static function getItem($key): \Symfony\Component\Cache\CacheItem {
		$key = self::getCacheKey($key);
		return Adapter::getInstance()->getItem($key);
	}

	public static function set($key, $callback) {
		self::delete($key);
		return self::get($key, $callback);
	}

	public static function get($key, $callback) {
		$key = self::getCacheKey($key);
		return Adapter::getInstance()->get($key, $callback);
	}

	public static function delete($key): bool {
		$key = self::getCacheKey($key);
		return Adapter::getInstance()->delete($key);
	}

	public static function reset(): void {
		Adapter::getInstance()->reset();
	}

	public static function clear($prefix = null): void {
		if ($prefix) {
			Adapter::getInstance()->clear($prefix);
		}
		else {
			Adapter::getInstance()->clear();
		}
	}

	private static function getCacheKey($key): string {
		return _dbTablePrefix() . $key;
	}

}