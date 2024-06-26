<?php
namespace WPSPCORE\Base;

abstract class BaseRoute extends BaseInstances {

	public ?array $initClasses = null;

	/*
	 *
	 */

	public function isPassedMiddleware($middlewares = null, $request = null) {
		$passed = true;
		if (!empty($middlewares)) {
			$relation = strtolower($middlewares['relation'] ?? 'and');
			if (isset($middlewares['relation'])) unset($middlewares['relation']);
			foreach ($middlewares as $middleware) {
				$class  = $middleware[0];
				$method = $middleware[1];
				$passed = (new $class())->$method($request ?? $this->request);
				if (!$passed && (!$relation || $relation == 'and')) break;
				if ($passed && $relation == 'or') break;
			}
		}
		return $passed;
	}

	public function prepareCallback($callback, $useInitClass = false, $classArgs = []): array|\Closure {

		// If callback is a closure.
		if ($callback instanceof \Closure) {
			return $callback;
		}

		// If callback is an array with class and method.
		if (is_array($callback)) {
			if ($useInitClass) {
				$class = $this->getInitClass($callback[0], $useInitClass, $classArgs);
			}
			else {
				$class = new $callback[0](...$classArgs ?? []);
			}
			return [$class, $callback[1]];
		}

		// If callback is a string.
		return function() use ($callback) {
			return $callback;
		};

	}

	public function prepareClass($callback, $useInitClass = false, $classArgs = []) {
		if ($useInitClass) {
			$class = $this->getInitClass($callback[0], $useInitClass, $classArgs);
		}
		else {
			$class = $callback[0](...$classArgs ?? []);
		}
		return $class;
	}

	/*
	 *
	 */

	private function getInitClasses(): ?array {
		return $this->initClasses;
	}

	private function setInitClasses($initClasses): void {
		$this->initClasses = $initClasses;
	}

	private function getInitClass($className, $addInitClass = false, $classArgs = []) {
		$initClass = $this->getInitClasses()[$className] ?? null;
		if (!$initClass) {
			$initClass = new $className(...$classArgs ?? []);
			if ($addInitClass) $this->addInitClass($className, $initClass);
		}
		return $initClass;
	}

	private function addInitClass($className, $classInstance): void {
		$initClasses             = $this->getInitClasses();
		$initClasses[$className] = $classInstance;
		$this->setInitClasses($initClasses);
	}

}