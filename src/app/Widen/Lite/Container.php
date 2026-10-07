<?php

namespace WPSPCORE\App\Widen\Lite;

/**
 * Service container - mô phỏng Illuminate\Container\Container bằng PHP thuần.
 *
 * bind / bindIf / singleton / singletonIf / scoped / scopedIf / instance / alias
 * extend / rebinding / tag / tagged / resolving / afterResolving
 * make / makeWith / get / has / bound / resolved / factory / call / wrap / build
 * ArrayAccess: $app['funcs'], $app['funcs'] = ...; magic: $app->funcs
 */
class Container implements \ArrayAccess {

	/** @var static|null */
	protected static $instance;

	/** abstract => ['concrete' => Closure|string, 'shared' => bool] */
	protected $bindings = [];

	/** abstract => object */
	protected $instances = [];

	/** abstract[] - singleton theo vòng đời (forgetScopedInstances() để xoá). */
	protected $scopedInstances = [];

	/** alias => abstract */
	protected $aliases = [];

	/** abstract => [alias, ...] */
	protected $abstractAliases = [];

	/** abstract => true */
	protected $resolved = [];

	/** abstract => Closure[] */
	protected $extenders = [];

	/** tag => abstract[] */
	protected $tags = [];

	/** abstract => Closure[] */
	protected $reboundCallbacks = [];

	protected $globalResolvingCallbacks      = [];
	protected $globalAfterResolvingCallbacks = [];
	protected $resolvingCallbacks            = [];
	protected $afterResolvingCallbacks       = [];

	/** Class đang build / abstract đang resolve (phát hiện vòng lặp). */
	protected $buildStack     = [];
	protected $resolvingStack = [];

	/** Stack tham số truyền vào make(). */
	protected $with = [];

	/*
	 * ---
	 * Global instance.
	 * ---
	 */

	public static function getInstance() {
		return static::$instance ??= new static;
	}

	public static function setInstance(?Container $container = null) {
		return static::$instance = $container;
	}

	/*
	 * ---
	 * Đăng ký.
	 * ---
	 */

	/**
	 * $concrete: null (dùng chính $abstract) | string (class / abstract khác) | Closure($app, array $parameters)
	 */
	public function bind($abstract, $concrete = null, $shared = false) {
		$this->dropStaleInstances($abstract);

		$concrete ??= $abstract;

		if (!$concrete instanceof \Closure && !is_string($concrete)) {
			throw new \InvalidArgumentException('Binding concrete must be a Closure, a class name or null.');
		}

		$this->bindings[$abstract] = ['concrete' => $concrete, 'shared' => (bool)$shared];

		if ($this->resolved($abstract)) {
			$this->rebound($abstract);
		}

		return $this;
	}

	public function bindIf($abstract, $concrete = null, $shared = false) {
		return $this->bound($abstract) ? $this : $this->bind($abstract, $concrete, $shared);
	}

	public function singleton($abstract, $concrete = null) {
		return $this->bind($abstract, $concrete, true);
	}

	public function singletonIf($abstract, $concrete = null) {
		return $this->bound($abstract) ? $this : $this->singleton($abstract, $concrete);
	}

	/**
	 * Singleton nhưng bị xoá khi gọi forgetScopedInstances() (vd: mỗi request/job).
	 */
	public function scoped($abstract, $concrete = null) {
		$this->scopedInstances[] = $abstract;
		return $this->singleton($abstract, $concrete);
	}

	public function scopedIf($abstract, $concrete = null) {
		return $this->bound($abstract) ? $this : $this->scoped($abstract, $concrete);
	}

	/**
	 * Đăng ký object có sẵn (luôn shared).
	 */
	public function instance($abstract, $instance) {
		$this->removeAbstractAlias($abstract);

		$isBound = $this->bound($abstract);

		unset($this->aliases[$abstract]);

		$this->instances[$abstract] = $instance;
		$this->resolved[$abstract]  = true;

		if ($isBound) {
			$this->rebound($abstract);
		}

		return $instance;
	}

	public function alias($abstract, $alias) {
		if ($alias === $abstract) {
			throw new \LogicException("[{$abstract}] is aliased to itself.");
		}

		$this->removeAbstractAlias($alias);

		$this->aliases[$alias]              = $abstract;
		$this->abstractAliases[$abstract][] = $alias;

		return $this;
	}

	/**
	 * Bọc / chỉnh sửa object sau khi resolve: extend('mailer', fn($mailer, $app) => new Decorated($mailer))
	 */
	public function extend($abstract, \Closure $closure) {
		$abstract = $this->getAlias($abstract);

		if (isset($this->instances[$abstract])) {
			$this->instances[$abstract] = $closure($this->instances[$abstract], $this);
			$this->rebound($abstract);
		}
		else {
			$this->extenders[$abstract][] = $closure;
			if ($this->resolved($abstract)) {
				$this->rebound($abstract);
			}
		}

		return $this;
	}

	public function getExtenders($abstract) {
		return $this->extenders[$this->getAlias($abstract)] ?? [];
	}

	public function forgetExtenders($abstract) {
		unset($this->extenders[$this->getAlias($abstract)]);
	}

	/**
	 * Callback chạy khi abstract được bind/instance lại: rebinding('request', fn($app, $request) => ...)
	 */
	public function rebinding($abstract, \Closure $callback) {
		$this->reboundCallbacks[$abstract = $this->getAlias($abstract)][] = $callback;

		return $this->bound($abstract) ? $this->make($abstract) : null;
	}

	protected function rebound($abstract) {
		if (empty($this->reboundCallbacks[$abstract])) return;

		$instance = $this->make($abstract);
		foreach ($this->reboundCallbacks[$abstract] as $callback) {
			$callback($this, $instance);
		}
	}

	public function tag($abstracts, $tags) {
		$tags = is_array($tags) ? $tags : array_slice(func_get_args(), 1);

		foreach ($tags as $tag) {
			foreach ((array)$abstracts as $abstract) {
				$this->tags[$tag][] = $abstract;
			}
		}

		return $this;
	}

	public function tagged($tag) {
		return array_map(function($abstract) {
			return $this->make($abstract);
		}, $this->tags[$tag] ?? []);
	}

	/**
	 * resolving(Closure) => mọi object; resolving('abstract', Closure) => object cụ thể.
	 * Callback nhận ($object, $app).
	 */
	public function resolving($abstract, ?\Closure $callback = null) {
		if ($abstract instanceof \Closure) {
			$this->globalResolvingCallbacks[] = $abstract;
		}
		else {
			$this->resolvingCallbacks[$this->getAlias($abstract)][] = $callback;
		}
		return $this;
	}

	public function afterResolving($abstract, ?\Closure $callback = null) {
		if ($abstract instanceof \Closure) {
			$this->globalAfterResolvingCallbacks[] = $abstract;
		}
		else {
			$this->afterResolvingCallbacks[$this->getAlias($abstract)][] = $callback;
		}
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
	 * $parameters: ghi đè tham số constructor / closure, theo tên, vị trí hoặc tên class.
	 */
	public function make($abstract, array $parameters = []) {
		return $this->resolve($abstract, $parameters);
	}

	public function makeWith($abstract, array $parameters = []) {
		return $this->make($abstract, $parameters);
	}

	public function get($id) {
		return $this->make($id);
	}

	public function factory($abstract) {
		return function() use ($abstract) {
			return $this->make($abstract);
		};
	}

	protected function resolve($abstract, array $parameters = []) {
		$abstract = $this->getAlias($abstract);

		if (isset($this->instances[$abstract]) && !$parameters) {
			return $this->instances[$abstract];
		}

		if (in_array($abstract, $this->resolvingStack, true)) {
			throw new ContainerException('Circular dependency detected: ' . implode(' -> ', $this->resolvingStack) . ' -> ' . $abstract);
		}

		$this->resolvingStack[] = $abstract;
		$this->with[]           = $parameters;

		try {
			$concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;

			if ($concrete instanceof \Closure) {
				$object = $concrete($this, $parameters);
			}
			elseif ($concrete === $abstract || $this->getAlias($concrete) === $abstract) {
				// singleton('funcs', Funcs::class) + alias('funcs', Funcs::class) => build trực tiếp.
				$object = $this->build($concrete);
			}
			else {
				// interface => class, hoặc chuỗi binding.
				$object = $this->make($concrete, $parameters);
			}

			foreach ($this->extenders[$abstract] ?? [] as $extender) {
				$object = $extender($object, $this);
			}
		}
		finally {
			array_pop($this->with);
			array_pop($this->resolvingStack);
		}

		if ($this->isShared($abstract) && !$parameters) {
			$this->instances[$abstract] = $object;
		}

		$this->fireResolvingCallbacks($abstract, $object);

		$this->resolved[$abstract] = true;

		return $object;
	}

	protected function fireResolvingCallbacks($abstract, $object) {
		$this->fireCallbackArray($object, $this->globalResolvingCallbacks);
		$this->fireCallbackArray($object, $this->callbacksForType($abstract, $object, $this->resolvingCallbacks));

		$this->fireCallbackArray($object, $this->globalAfterResolvingCallbacks);
		$this->fireCallbackArray($object, $this->callbacksForType($abstract, $object, $this->afterResolvingCallbacks));
	}

	protected function callbacksForType($abstract, $object, array $callbacksPerType) {
		$results = [];
		foreach ($callbacksPerType as $type => $callbacks) {
			if ($type === $abstract || (is_object($object) && $object instanceof $type)) {
				array_push($results, ...$callbacks);
			}
		}
		return $results;
	}

	protected function fireCallbackArray($object, array $callbacks) {
		foreach ($callbacks as $callback) {
			$callback($object, $this);
		}
	}

	/**
	 * Tạo object từ tên class, tự inject dependency của constructor.
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

		$constructor = $reflector->getConstructor();
		if ($constructor === null) {
			return new $concrete;
		}

		$this->buildStack[] = $concrete;

		try {
			$args = $this->resolveDependencies($constructor->getParameters(), $this->lastParameterOverride());
		}
		finally {
			array_pop($this->buildStack);
		}

		return $reflector->newInstanceArgs($args);
	}

	/**
	 * Gọi callable với dependency injection.
	 *
	 * Hỗ trợ: Closure, 'function', [$object, 'method'], [Class::class, 'method'],
	 * 'Class@method', 'Class::method', object/class có __invoke, Class + $defaultMethod.
	 */
	public function call($callback, array $parameters = [], $defaultMethod = null) {
		if (is_string($callback)) {
			if (strpos($callback, '@') !== false) {
				$callback = explode('@', $callback, 2);
			}
			elseif (strpos($callback, '::') !== false) {
				$callback = explode('::', $callback, 2);
			}
			elseif (class_exists($callback)) {
				$callback = [$callback, $defaultMethod ?? '__invoke'];
			}
		}

		if (is_array($callback)) {
			$reflector = new \ReflectionMethod($callback[0], $callback[1]);
			if (is_string($callback[0]) && !$reflector->isStatic()) {
				$callback[0] = $this->make($callback[0]);
			}
		}
		elseif (is_object($callback) && !$callback instanceof \Closure) {
			$reflector = new \ReflectionMethod($callback, '__invoke');
		}
		else {
			$reflector = new \ReflectionFunction($callback);
		}

		return $callback(...$this->resolveDependencies($reflector->getParameters(), $parameters));
	}

	/**
	 * Closure gọi call() trễ.
	 */
	public function wrap($callback, array $parameters = []) {
		return function() use ($callback, $parameters) {
			return $this->call($callback, $parameters);
		};
	}

	/**
	 * @param \ReflectionParameter[] $parameters
	 */
	protected function resolveDependencies(array $parameters, array $overrides = []) {
		$args = [];

		foreach ($parameters as $index => $parameter) {
			$name = $parameter->getName();

			// 1. Ghi đè theo tên hoặc vị trí.
			$key = array_key_exists($name, $overrides) ? $name : (array_key_exists($index, $overrides) ? $index : null);
			if ($key !== null) {
				if ($parameter->isVariadic()) {
					array_push($args, ...array_values((array)$overrides[$key]));
					break;
				}
				$args[] = $overrides[$key];
				continue;
			}

			if ($parameter->isVariadic()) {
				break;
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
					if ($parameter->allowsNull()) {
						$args[] = null;
						continue;
					}
					throw $e;
				}
			}

			// 3. Giá trị mặc định.
			if ($parameter->isDefaultValueAvailable()) {
				$args[] = $parameter->getDefaultValue();
				continue;
			}

			$function = $parameter->getDeclaringFunction()->getName();
			$where    = $parameter->getDeclaringClass() ? $parameter->getDeclaringClass()->getName() . '::' . $function : $function;

			throw new ContainerException("Unresolvable dependency resolving [\${$name}] in {$where}().");
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

	public function forgetScopedInstances() {
		foreach ($this->scopedInstances as $scoped) {
			unset($this->instances[$scoped]);
		}
	}

	public function flush() {
		$this->bindings                      = [];
		$this->instances                     = [];
		$this->scopedInstances               = [];
		$this->aliases                       = [];
		$this->abstractAliases               = [];
		$this->resolved                      = [];
		$this->extenders                     = [];
		$this->tags                          = [];
		$this->reboundCallbacks              = [];
		$this->resolvingCallbacks            = [];
		$this->afterResolvingCallbacks       = [];
		$this->globalResolvingCallbacks      = [];
		$this->globalAfterResolvingCallbacks = [];
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