<?php

namespace WPSPCORE\App\Widen\Lite\Http;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use UnexpectedValueException;

/**
 * Symfony-style parameter container used for $request->query, ->request,
 * ->cookies, ->attributes and ->server.
 */
class ParameterBag implements IteratorAggregate, Countable {

	protected array $parameters = [];

	public function __construct(array $parameters = []) {
		$this->parameters = $parameters;
	}

	/** All parameters, or the array stored under $key. */
	public function all(?string $key = null): array {
		if ($key === null) return $this->parameters;

		$value = $this->parameters[$key] ?? [];
		if (!is_array($value)) {
			throw new UnexpectedValueException(sprintf('Unexpected value for parameter "%s": expecting "array", got "%s".', $key, get_debug_type($value)));
		}
		return $value;
	}

	public function keys(): array {
		return array_keys($this->parameters);
	}

	public function replace(array $parameters = []): void {
		$this->parameters = $parameters;
	}

	public function add(array $parameters = []): void {
		$this->parameters = array_replace($this->parameters, $parameters);
	}

	public function get(string $key, mixed $default = null): mixed {
		return array_key_exists($key, $this->parameters) ? $this->parameters[$key] : $default;
	}

	public function set(string $key, mixed $value): void {
		$this->parameters[$key] = $value;
	}

	public function has(string $key): bool {
		return array_key_exists($key, $this->parameters);
	}

	public function remove(string $key): void {
		unset($this->parameters[$key]);
	}

	public function getAlpha(string $key, string $default = ''): string {
		return preg_replace('/[^[:alpha:]]/', '', (string)$this->get($key, $default));
	}

	public function getAlnum(string $key, string $default = ''): string {
		return preg_replace('/[^[:alnum:]]/', '', (string)$this->get($key, $default));
	}

	public function getDigits(string $key, string $default = ''): string {
		return preg_replace('/[^[:digit:]]/', '', (string)$this->get($key, $default));
	}

	public function getString(string $key, string $default = ''): string {
		return (string)$this->get($key, $default);
	}

	public function getInt(string $key, int $default = 0): int {
		return (int)$this->get($key, $default);
	}

	public function getBoolean(string $key, bool $default = false): bool {
		return $this->filter($key, $default, FILTER_VALIDATE_BOOLEAN);
	}

	public function getEnum(string $key, string $class, ?\BackedEnum $default = null): ?\BackedEnum {
		$value = $this->get($key);
		if ($value === null || !function_exists('enum_exists') || !enum_exists($class) || !method_exists($class, 'tryFrom')) {
			return $default;
		}
		return $class::tryFrom($value) ?? $default;
	}

	public function filter(string $key, mixed $default = null, int $filter = FILTER_DEFAULT, array|int $options = []): mixed {
		$value = $this->get($key, $default);

		if (!is_array($options) && $options) $options = ['flags' => $options];
		if (is_array($value) && !isset($options['flags'])) $options['flags'] = FILTER_REQUIRE_ARRAY;

		return filter_var($value, $filter, $options);
	}

	public function getIterator(): Traversable {
		return new ArrayIterator($this->parameters);
	}

	public function count(): int {
		return count($this->parameters);
	}
}