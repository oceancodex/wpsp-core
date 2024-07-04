<?php

namespace WPSPCORE\Traits;

trait WebRouteTrait {

	public function init(): void {
		// Add rewrite query vars.
		$this->filter('query_vars', function($query_vars) {
			$query_vars[] = 'is_rewrite';
			$query_vars[] = $this->funcs->_config('app.short_name') . '_rewrite_ident';
			for ($i = 1; $i <= 10; $i++) {
				$query_vars[] = $this->funcs->_config('app.short_name') . '_rewrite_group_'. $i;
			}
			return $query_vars;
		}, true, null, null, 10, 1);

		// Change "Check for updates" link text.
		$this->filter('puc_manual_check_link-' . $this->funcs->_getTextDomain(), function($text) {
			return $this->funcs->_trans('messages.check_for_updates');
		}, true, null, null, 10, 1);

		$this->apis();
		$this->meta_boxes();
		$this->templates();
		$this->shortcodes();
		$this->post_types();
		$this->taxonomies();

		$this->actions();
		$this->filters();
		$this->hooks();
	}

	/*
     *
     */

	public function apis() {}
	public function templates() {}
	public function meta_boxes() {}
	public function shortcodes() {}
	public function post_types() {}
	public function taxonomies() {}
	public function actions() {}
	public function filters() {}
	public function hooks() {}

	/*
	 *
	 */

	public function get($path, $callback, $useInitClass = false, $classArgs = [], $middleware = null): void {
		if (!wp_doing_ajax() && $this->isPassedMiddleware($middleware, $this->request)) {
			$classArgs = array_merge([$path], $classArgs ?? []);
			$classArgs = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $classArgs);
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($path) : $callback;
		}
	}

	public function post($path, $callback, $useInitClass = false, $classArgs = [], $middleware = null): void {
		if (!wp_doing_ajax() && $this->request->isMethod('POST')) {
			$requestPath = trim($this->request->getPathInfo(), '/\\');
			if (
				($this->request->get('page') == $path || preg_match('/' . $path . '/iu', $requestPath))
				&& $this->isPassedMiddleware($middleware, $this->request)
			) {
				$classArgs = array_merge([$path], $classArgs ?? []);
				$classArgs = array_merge([
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv()
				], $classArgs);
				$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
				isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($path) : $callback;
			}
		}
	}

	/*
	 *
	 */

	public function hook($type, $hook, $callback, $useInitClass = false, $classArgs = [], $middleware = null, $priority = 10, $argsNumber = 0): void {
		if ($this->isPassedMiddleware($middleware, $this->request)) {
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			if ($type == 'action') {
				add_action($hook, $callback, $priority, $argsNumber);
			}
			elseif ($type == 'filter') {
				add_filter($hook, $callback, $priority, $argsNumber);
			}
		}
	}

	public function action($hook, $callback, $useInitClass = false, $classArgs = [], $middleware = null, $priority = 10, $argsNumber = 0): void {
		$this->hook('action', $hook, $callback, $useInitClass, $classArgs, $middleware, $priority, $argsNumber);
	}

	public function filter($hook, $callback, $useInitClass = false, $classArgs = [], $middleware = null, $priority = 10, $argsNumber = 0): void {
        $this->hook('filter', $hook, $callback, $useInitClass, $classArgs, $middleware, $priority, $argsNumber);
    }

	/*
	 *
	 */

	public function template($name, $callback, $useInitClass = false, $classArgs = [], $middleware = null, $priority = 10, $argsNumber = 0): void {
		if ($this->isPassedMiddleware($middleware, $this->request)) {
			$classArgs = array_merge([$name], $classArgs ?? []);
			$classArgs = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $classArgs);
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($name) : $callback;
		}
	}

	public function meta_box($id, $callback, $useInitClass = false, $classArgs = [], $middleware = null, $priority = 10, $argsNumber = 0): void {
		if ($this->isPassedMiddleware($middleware, $this->request)) {
			$classArgs = array_merge([$id], $classArgs ?? []);
			$classArgs = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $classArgs);
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			add_action('add_meta_boxes', $callback, $priority, $argsNumber);
		}
	}

	public function taxonomy($taxonomy, $callback, $useInitClass = false, $classArgs = [], $middleware = null, $priority = 10, $argsNumber = 0): void {
		if ($this->isPassedMiddleware($middleware, $this->request)) {
			$classArgs = array_merge([$taxonomy], $classArgs ?? []);
			$classArgs = array_merge([
				$this->funcs->_getMainPath(),
				$this->funcs->_getRootNamespace(),
				$this->funcs->_getPrefixEnv()
			], $classArgs);
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($taxonomy) : $callback;
		}
	}

	public function shortcode($shortcode, $callback, $useInitClass = false, $classArgs = [], $middleware = null): void {
		if ($this->isPassedMiddleware($middleware, $this->request)) {
			$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
			add_shortcode($shortcode, $callback);
		}
	}

	public function post_type($postType, $callback, $useInitClass = false, $classArgs = [], $middleware = null): void {
		if ($this->isPassedMiddleware($middleware, $this->request)) {
			if (is_array($callback)) {
				$classArgs = array_merge([$postType], $classArgs ?? []);
				$classArgs = array_merge([
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv()
				], $classArgs);
				$callback = $this->prepareCallback($callback, $useInitClass, $classArgs);
				isset($callback[0]) && isset($callback[1]) ? $callback[0]->{$callback[1]}($postType) : $callback;
			}
			elseif (is_callable($callback)) {
				$callback();
			}
		}
	}

}