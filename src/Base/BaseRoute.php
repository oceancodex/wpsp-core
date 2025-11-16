<?php

namespace WPSPCORE\Base;

use Illuminate\Pipeline\Pipeline;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseRoute extends BaseInstances {

	public $initClasses = null;

	/*
	 *
	 */

	public function isPassedMiddleware($middlewares = null, $request = null): bool {
		// Không có middleware -> pass
		if (empty($middlewares)) {
			return true;
		}

		// Lấy relation (AND/OR), mặc định AND
		$relation = 'AND';
		if (isset($middlewares['relation'])) {
			$relation = strtoupper((string)$middlewares['relation']);
			unset($middlewares['relation']);
		}

		// Chuẩn hoá middleware: mỗi item thành một "callable descriptor"
		// descriptor có thể là:
		// - ['type' => 'class', 'class' => ClassName, 'method' => 'handle']
		// - ['type' => 'closure', 'closure' => Closure]
		$normalized = [];
		foreach ($middlewares as $m) {
			if ($m instanceof \Closure) {
				$normalized[] = ['type' => 'closure', 'closure' => $m];
				continue;
			}

			if (is_string($m)) {
				$normalized[] = ['type' => 'class', 'class' => $m, 'method' => 'handle'];
				continue;
			}

			if (is_array($m)) {
				// [Class, method?] or nested structure
				if (isset($m[0]) && $m[0] instanceof \Closure) {
					// closure inside array
					$normalized[] = ['type' => 'closure', 'closure' => $m[0]];
				}
				elseif (isset($m[0]) && is_string($m[0])) {
					$method       = isset($m[1]) && is_string($m[1]) ? $m[1] : 'handle';
					$normalized[] = ['type' => 'class', 'class' => $m[0], 'method' => $method];
				}
				// else ignore invalid entry
			}
		}

		// Lấy request & app
		$app     = $this->funcs->getApplication();
		$request = $app->make('request');

		// Helper: chạy 1 middleware descriptor, trả về chuẩn ['ok' => bool, 'response' => Response|null]
		$runOne = function($desc) use ($request, $app) {
			// $next giả: middleware gọi $next($request) => được coi là "pass" -> trả Response 200
			$next = function($req = null) {
				return new Response('', 200);
			};

			try {
				if ($desc['type'] === 'closure') {
					$res = call_user_func($desc['closure'], $request, $next);
				}
				elseif ($desc['type'] === 'class') {
					$class  = $desc['class'];
					$method = $desc['method'] ?? 'handle';

					// nếu class không tồn tại, coi như fail
					if (!class_exists($class)) {
						return ['ok' => false, 'response' => null];
					}

					// 🚀 Quan trọng: dùng Container để tự động Dependency Injection
					try {
						$instance = $app->make($class);
					} catch (\Throwable $e) {
						return ['ok' => false, 'response' => null];
					}

					// nếu method không tồn tại, cố gọi handle, nếu không có -> fail
					if (!method_exists($instance, $method)) {
						if (method_exists($instance, 'handle')) {
							$res = $instance->handle($request, $next);
						}
						else {
							return ['ok' => false, 'response' => null];
						}
					}
					else {
						$res = $instance->$method($request, $next);
					}
				}
				else {
					return ['ok' => false, 'response' => null];
				}
			}
			catch (\Throwable $e) {
				// lỗi khi chạy middleware => coi là fail
				return ['ok' => false, 'response' => null];
			}

			// Chuẩn hóa kết quả:
			// - Nếu là Symfony Response (Illuminate Response kế thừa) -> check status
			// - Nếu là boolean true -> coi là pass
			// - Nếu là boolean false -> coi là fail
			// - Nếu là null -> coi là pass (nếu middleware gọi $next và không trả gì)
			if ($res instanceof Response) {
				$status = (int)$res->getStatusCode();
				return ['ok' => ($status < 400), 'response' => $res];
			}

			if (is_bool($res)) {
				return ['ok' => $res === true, 'response' => null];
			}

			if ($res === null) {
				// mặc định coi là pass (nhiều middleware PHP cũ không return, nhưng gọi $next internally)
				return ['ok' => true, 'response' => null];
			}

			// Trường hợp trả string/other -> coi là pass (hoặc bạn có thể đổi thành fail)
			return ['ok' => true, 'response' => null];
		};

		// Logic OR: chỉ cần 1 pass => pass toàn bộ
		if ($relation === 'OR') {
			foreach ($normalized as $desc) {
				$r = $runOne($desc);
				if ($r['ok'] === true) {
					return true; // pass sớm
				}
			}
			return false; // tất cả fail
		}

		// Logic AND: tất cả phải pass
		foreach ($normalized as $desc) {
			$r = $runOne($desc);
			if ($r['ok'] !== true) {
				return false; // có 1 fail -> fail ngay
			}
		}

		// Tất cả pass
		return true;
	}

	public function prepareCallback($callback, $useInitClass = false, $constructParams = []) {

		// If callback is a closure.
		if ($callback instanceof \Closure) {
			return $callback;
		}

		// If callback is an array with class and method.
		if (is_array($callback)) {
			if ($useInitClass) {
				$class = $this->getInitClass($callback[0], $useInitClass, $constructParams);
			}
			else {
				$class = new $callback[0](...$constructParams ?? []);
			}
			return [$class, $callback[1] ?? null];
		}

		// If callback is a string.
		return function() use ($callback) {
			return $callback;
		};

	}

	public function prepareClass($callback, $useInitClass = false, $constructParams = []) {
		if ($useInitClass) {
			$class = $this->getInitClass($callback[0], $useInitClass, $constructParams);
		}
		else {
			$class = new $callback[0](...$constructParams ?? []);
		}
		return $class;
	}

	public function resolveAndCall($callback, array $routeParams = []) {
		// 🔹 Lấy container Laravel từ Application hoặc fallback
		$app = $this->funcs->getApplication();
		$container = $app ?? (\Illuminate\Foundation\Application::getInstance() ?? null);

		if (!$container) {
			throw new \RuntimeException('Container instance not found.');
		}

		[$classOrInstance, $method] = $callback;

		// 🔹 Resolve instance controller
		$instance = is_object($classOrInstance)
			? $classOrInstance
			: $container->make($classOrInstance);

		// 🔹 Tự động inject FormRequest nếu có
		$reflection = new \ReflectionMethod($instance, $method);
		$baseRequest = $container->bound('request')
			? $container->make('request')
			: \Illuminate\Http\Request::capture();

		foreach ($reflection->getParameters() as $param) {
			$type = $param->getType();
			if ($type && !$type->isBuiltin()) {
				$paramClass = $type->getName();

				// Inject FormRequest (nếu có)
				if (is_subclass_of($paramClass, \Illuminate\Foundation\Http\FormRequest::class)) {
					$formRequest = $paramClass::createFromBase($baseRequest);
					$formRequest->setContainer($container);
					$formRequest->setRedirector($container->make(\Illuminate\Routing\Redirector::class));
					if (method_exists($formRequest, 'validateResolved')) {
						$formRequest->validateResolved();
					}
					$container->instance($paramClass, $formRequest);
				}
			}
		}

		// 🔹 Gọi thông qua Container::call() để Laravel tự inject linh hoạt
		return $container->call([$instance, $method], $routeParams);
	}

	public function defineMark($name) {
		$prefix = $this->funcs->_getRootNamespace();
		$markStr = $prefix . '_IS_' . strtoupper($name);
		return !defined($markStr) && define($markStr, true);
	}


	/*
	 *
	 */

	private function getInitClasses() {
		return $this->initClasses;
	}

	private function setInitClasses($initClasses) {
		$this->initClasses = $initClasses;
	}

	private function getInitClass($className, $addInitClass = false, $constructParams = []) {
		$initClass = $this->getInitClasses()[$className] ?? null;
		if (!$initClass) {
			$initClass = new $className(...$constructParams ?? []);
			if ($addInitClass) $this->addInitClass($className, $initClass);
		}
		return $initClass;
	}

	private function addInitClass($className, $classInstance) {
		$initClasses             = $this->getInitClasses();
		$initClasses[$className] = $classInstance;
		$this->setInitClasses($initClasses);
	}

	/*
	 *
	 */

	public function customProperties() {}

}