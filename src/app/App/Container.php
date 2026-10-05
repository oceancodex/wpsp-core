<?php

namespace WPSPCORE\App\App;

/**
 * Service container tối giản - mô phỏng Illuminate\Container\Container bằng PHP thuần.
 *
 * - bind($abstract, $concrete = null, $shared = false)
 * - singleton($abstract, $concrete = null)
 * - instance($abstract, $instance)
 * - alias($abstract, $alias)
 * - make($abstract, array $parameters = [])
 * - call($callback, array $parameters = [])
 * - Tự động inject dependency qua type-hint (autowiring).
 * - Hỗ trợ ArrayAccess: $app['funcs'], $app['funcs'] = ...
 */
class Container implements \ArrayAccess {

	/** @var static */
	protected static $instance;

	/** abstract => ['concrete' => Closure|string, 'shared' => bool] */
	protected $bindings = [];

	/** abstract => object (singleton đã resolve hoặc instance()) */
	protected $instances = [];

	/** alias => abstract */
	protected $aliases = [];

	/** abstract => [alias, ...] */
	protected $abstractAliases = [];

	/** abstract => true */
	protected $resolved = [];

	/** Các class đang được build (phát hiện vòng lặp). */
	protected $buildStack = [];

	/** Các abstract đang được resolve (chặn vòng lặp binding/alias). */
	protected $resolving = [];

	/** Stack tham số truyền vào make(). */
	protected $with = [];

	/*
	 * ---
	 * Global instance.
	 * ---
	 */

	public static function getInstance() {
		if (!static::$instance) {
			static::$instance = new static;
		}
		return static::$instance;
	}

	public static function setInstance(Container $container = null) {
		return static::$instance = $container;
	}

	/*
	 * ---
	 * Đăng ký.
	 * ---
	 */

	/**
	 * Đăng ký binding.
	 *
	 * $concrete có thể là:
	 * - null: dùng chính $abstract làm class.
	 * - string: tên class sẽ được build (hoặc một abstract khác).
	 * - Closure: function ($app, array $parameters) { return ...; }
	 */
	public function bind($abstract, $concrete = null, $shared = false) {
		$this->dropStaleInstances($abstract);

		if ($concrete === null) {
			$concrete = $abstract;
		}

		if (!$concrete instanceof \Closure && !is_string($concrete)) {
			throw new \InvalidArgumentException('Binding concrete must be a Closure, a class name or null.');
		}

		$this->bindings[$abstract] = [
			'concrete' => $concrete,
			'shared'   => (bool)$shared,
		];

		return $this;
	}

	public function bindIf($abstract, $concrete = null, $shared = false) {
		if (!$this->bound($abstract)) {
			$this->bind($abstract, $concrete, $shared);
		}
		return $this;
	}

	/**
	 * Binding dùng chung: chỉ tạo 1 lần, các lần make() sau trả về cùng object.
	 */
	public function singleton($abstract, $concrete = null) {
		return $this->bind($abstract, $concrete, true);
	}

	public function singletonIf($abstract, $concrete = null) {
		if (!$this->bound($abstract)) {
			$this->singleton($abstract, $concrete);
		}
		return $this;
	}

	/**
	 * Đăng ký một object có sẵn (luôn shared).
	 */
	public function instance($abstract, $instance) {
		$this->removeAbstractAlias($abstract);

		unset($this->aliases[$abstract]);

		$this->instances[$abstract] = $instance;
		$this->resolved[$abstract]  = true;

		return $instance;
	}

	/**
	 * Đặt tên khác cho một abstract: alias('funcs', Funcs::class)
	 * => make(Funcs::class) trả về cùng kết quả với make('funcs').
	 */
	public function alias($abstract, $alias) {
		if ($alias === $abstract) {
			throw new \LogicException("[{$abstract}] is aliased to itself.");
		}

		$this->removeAbstractAlias($alias);

		$this->aliases[$alias]              = $abstract;
		$this->abstractAliases[$abstract][] = $alias;

		return $this;
	}

	/*
	 * ---
	 * Truy vấn.
	 * ---
	 */

	public function bound($abstract) {
		return isset($this->bindings[$abstract])
			|| isset($this->instances[$abstract])
			|| $this->isAlias($abstract);
	}

	public function has($id) {
		return $this->bound($id);
	}

	public function resolved($abstract) {
		$abstract = $this->getAlias($abstract);
		return isset($this->resolved[$abstract]) || isset($this->instances[$abstract]);
	}

	public function isShared($abstract) {
		return isset($this->instances[$abstract])
			|| (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared']);
	}

	public function isAlias($name) {
		return isset($this->aliases[$name]);
	}

	/**
	 * Lấy abstract gốc của một alias (đi theo chuỗi alias).
	 */
	public function getAlias($abstract) {
		$seen = [];
		while (isset($this->aliases[$abstract])) {
			if (isset($seen[$abstract])) {
				throw new \LogicException("Circular alias detected for [{$abstract}].");
			}
			$seen[$abstract] = true;
			$abstract        = $this->aliases[$abstract];
		}
		return $abstract;
	}

	public function getBindings() {
		return $this->bindings;
	}

	/*
	 * ---
	 * Resolve.
	 * ---
	 */

	/**
	 * Lấy object từ container.
	 *
	 * $parameters: tham số ghi đè cho constructor / closure, theo tên hoặc vị trí.
	 * Eg: $app->make(Mailer::class, ['host' => 'smtp.local'])
	 */
	public function make($abstract, array $parameters = []) {
		return $this->resolve($abstract, $parameters);
	}

	public function get($id) {
		return $this->resolve($id);
	}

	/**
	 * Trả về closure để resolve sau (lazy).
	 */
	public function factory($abstract) {
		return function() use ($abstract) {
			return $this->make($abstract);
		};
	}

	protected function resolve($abstract, array $parameters = []) {
		$abstract = $this->getAlias($abstract);

		// Singleton/instance đã có và không có tham số ghi đè => trả về luôn.
		if (isset($this->instances[$abstract]) && !$parameters) {
			return $this->instances[$abstract];
		}

		if (in_array($abstract, $this->resolving, true)) {
			throw new ContainerException('Circular dependency detected: ' . implode(' -> ', $this->resolving) . ' -> ' . $abstract);
		}

		$this->resolving[] = $abstract;
		$this->with[]      = $parameters;

		try {
			$concrete = isset($this->bindings[$abstract])
				? $this->bindings[$abstract]['concrete']
				: $abstract;

			if ($concrete instanceof \Closure) {
				$object = $concrete($this, $parameters);
			}
			elseif ($concrete === $abstract || $this->getAlias($concrete) === $abstract) {
				// singleton('funcs', Funcs::class) + alias('funcs', Funcs::class)
				// => concrete trỏ ngược về chính abstract qua alias, build trực tiếp.
				$object = $this->build($concrete);
			}
			else {
				// Binding trỏ sang abstract khác (interface => class, hoặc chuỗi binding).
				$object = $this->make($concrete, $parameters);
			}
		}
		finally {
			array_pop($this->with);
			array_pop($this->resolving);
		}

		if ($this->isShared($abstract) && !$parameters) {
			$this->instances[$abstract] = $object;
		}

		$this->resolved[$abstract] = true;

		return $object;
	}

	/**
	 * Tạo object từ tên class, tự động inject dependency của constructor.
	 */
	public function build($concrete) {
		if ($concrete instanceof \Closure) {
			return $concrete($this, $this->lastParameterOverride());
		}

		if (!class_exists($concrete) && !interface_exists($concrete)) {
			throw new ContainerException("Target class [{$concrete}] does not exist.");
		}

		$reflector = new \ReflectionClass($concrete);

		if (!$reflector->isInstantiable()) {
			$message = "Target [{$concrete}] is not instantiable";
			if ($this->buildStack) {
				$message .= ' while building [' . implode(', ', $this->buildStack) . ']';
			}
			throw new ContainerException($message . '.');
		}

		if (in_array($concrete, $this->buildStack, true)) {
			throw new ContainerException('Circular dependency detected: ' . implode(' -> ', $this->buildStack) . ' -> ' . $concrete);
		}

		$this->buildStack[] = $concrete;

		try {
			$constructor = $reflector->getConstructor();

			if ($constructor === null) {
				return new $concrete;
			}

			$args = $this->resolveDependencies($constructor->getParameters(), $this->lastParameterOverride());

			return $reflector->newInstanceArgs($args);
		}
		finally {
			array_pop($this->buildStack);
		}
	}

	/**
	 * Gọi một callable và tự inject dependency cho tham số.
	 *
	 * Hỗ trợ: Closure, 'function', [$object, 'method'], [Class::class, 'method'],
	 * 'Class@method', 'Class::staticMethod', object có __invoke.
	 */
	public function call($callback, array $parameters = []) {
		if (is_string($callback) && strpos($callback, '@') !== false) {
			$callback = explode('@', $callback, 2);
		}
		elseif (is_string($callback) && strpos($callback, '::') !== false) {
			$callback = explode('::', $callback, 2);
		}

		if (is_array($callback) && is_string($callback[0])) {
			$method = new \ReflectionMethod($callback[0], $callback[1]);
			if (!$method->isStatic()) {
				$callback[0] = $this->make($callback[0]);
			}
		}

		if (is_array($callback)) {
			$reflector = new \ReflectionMethod($callback[0], $callback[1]);
		}
		elseif (is_object($callback) && !$callback instanceof \Closure) {
			$reflector = new \ReflectionMethod($callback, '__invoke');
		}
		else {
			$reflector = new \ReflectionFunction($callback);
		}

		$args = $this->resolveDependencies($reflector->getParameters(), $parameters);

		return call_user_func_array($callback, $args);
	}

	/**
	 * @param \ReflectionParameter[] $parameters
	 */
	protected function resolveDependencies(array $parameters, array $overrides = []) {
		$args = [];

		foreach ($parameters as $index => $parameter) {
			$name = $parameter->getName();

			// 1. Ghi đè theo tên hoặc vị trí.
			if (array_key_exists($name, $overrides)) {
				$args[] = $overrides[$name];
				continue;
			}
			if (array_key_exists($index, $overrides)) {
				$args[] = $overrides[$index];
				continue;
			}

			// 2. Type-hint là class => resolve từ container.
			$class = $this->getParameterClassName($parameter);

			if ($class !== null) {
				if (array_key_exists($class, $overrides)) {
					$args[] = $overrides[$class];
					continue;
				}

				try {
					$args[] = $this->make($class);
					continue;
				}
				catch (ContainerException $e) {
					if ($parameter->isDefaultValueAvailable()) {
						$args[] = $parameter->getDefaultValue();
						continue;
					}
					if ($parameter->allowsNull() && !$parameter->isVariadic()) {
						$args[] = null;
						continue;
					}
					throw $e;
				}
			}

			// 3. Giá trị mặc định / variadic.
			if ($parameter->isVariadic()) {
				break;
			}
			if ($parameter->isDefaultValueAvailable()) {
				$args[] = $parameter->getDefaultValue();
				continue;
			}

			$where = $parameter->getDeclaringClass()
				? $parameter->getDeclaringClass()->getName() . '::' . $parameter->getDeclaringFunction()->getName() . '()'
				: $parameter->getDeclaringFunction()->getName() . '()';

			throw new ContainerException("Unresolvable dependency resolving [\${$name}] in {$where}.");
		}

		return $args;
	}

	protected function getParameterClassName(\ReflectionParameter $parameter) {
		$type = $parameter->getType();

		if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
			return null;
		}

		$name = $type->getName();

		if (($name === 'self' || $name === 'static') && $parameter->getDeclaringClass()) {
			return $parameter->getDeclaringClass()->getName();
		}

		return $name;
	}

	protected function lastParameterOverride() {
		return $this->with ? end($this->with) : [];
	}

	/*
	 * ---
	 * Dọn dẹp.
	 * ---
	 */

	public function forgetInstance($abstract) {
		unset($this->instances[$this->getAlias($abstract)]);
	}

	public function forgetInstances() {
		$this->instances = [];
	}

	public function flush() {
		$this->bindings        = [];
		$this->instances       = [];
		$this->aliases         = [];
		$this->abstractAliases = [];
		$this->resolved        = [];
	}

	protected function dropStaleInstances($abstract) {
		unset($this->instances[$abstract], $this->aliases[$abstract]);
	}

	protected function removeAbstractAlias($searched) {
		if (!isset($this->aliases[$searched])) {
			return;
		}

		foreach ($this->abstractAliases as $abstract => $aliases) {
			foreach ($aliases as $index => $alias) {
				if ($alias === $searched) {
					unset($this->abstractAliases[$abstract][$index]);
				}
			}
		}
	}

	/*
	 * ---
	 * ArrayAccess & magic.
	 * ---
	 */

	#[\ReturnTypeWillChange]
	public function offsetExists($key) {
		return $this->bound($key);
	}

	#[\ReturnTypeWillChange]
	public function offsetGet($key) {
		return $this->make($key);
	}

	#[\ReturnTypeWillChange]
	public function offsetSet($key, $value) {
		$this->bind($key, $value instanceof \Closure ? $value : function() use ($value) {
			return $value;
		});
	}

	#[\ReturnTypeWillChange]
	public function offsetUnset($key) {
		unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
	}

	public function __get($key) {
		return $this[$key];
	}

	public function __set($key, $value) {
		$this[$key] = $value;
	}

}
