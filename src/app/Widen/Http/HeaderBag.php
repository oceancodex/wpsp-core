<?php

namespace WPSPCORE\App\Widen\Http;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Case-insensitive header container: $request->headers->get('X-Foo').
 * Keys are stored lower-cased with dashes; each header holds a list of values.
 */
class HeaderBag implements IteratorAggregate, Countable {

	/** @var array<string, string[]> */
	protected array $headers = [];

	public function __construct(array $headers = []) {
		foreach ($headers as $key => $values) {
			$this->set((string)$key, $values);
		}
	}

	protected static function normalize(string $key): string {
		return strtolower(str_replace('_', '-', $key));
	}

	/** All headers as [name => string[]], or the values of one header. */
	public function all(?string $key = null): array {
		if ($key !== null) return $this->headers[static::normalize($key)] ?? [];
		return $this->headers;
	}

	public function keys(): array {
		return array_keys($this->headers);
	}

	public function replace(array $headers = []): void {
		$this->headers = [];
		$this->add($headers);
	}

	public function add(array $headers): void {
		foreach ($headers as $key => $values) {
			$this->set((string)$key, $values);
		}
	}

	/** First value of the header, or $default. */
	public function get(string $key, ?string $default = null): ?string {
		$values = $this->all($key);
		return $values ? (isset($values[0]) ? (string)$values[0] : $default) : $default;
	}

	public function set(string $key, string|array|null $values, bool $replace = true): void {
		$key    = static::normalize($key);
		$values = $values === null ? [null] : array_values((array)$values);

		if ($replace === true || !isset($this->headers[$key])) {
			$this->headers[$key] = $values;
		}
		else {
			$this->headers[$key] = array_merge($this->headers[$key], $values);
		}
	}

	public function has(string $key): bool {
		return array_key_exists(static::normalize($key), $this->headers);
	}

	public function contains(string $key, string $value): bool {
		return in_array($value, $this->all($key), true);
	}

	public function remove(string $key): void {
		unset($this->headers[static::normalize($key)]);
	}

	public function getIterator(): Traversable {
		return new ArrayIterator($this->headers);
	}

	public function count(): int {
		return count($this->headers);
	}

}