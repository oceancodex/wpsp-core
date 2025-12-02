<?php

namespace WPSPCORE\App\WP\RewriteFrontPages;

use WPSPCORE\App\BaseInstances;

abstract class BaseRewriteFrontPage extends BaseInstances {

	public $path                     = null;
	public $rewriteIdent             = null;
	public $useTemplate              = false;
	public $rewriteFrontPageSlug     = 'rewrite-front-pages';
	public $rewriteFrontPagePostType = 'page';
	public $callback_function        = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overridePath($this->extraParams['path']);
		$this->customProperties();
	}

	/*
	 *
	 */

	public function init($path = null) {
		$path = $this->path ?? $path;
		if ($path) {
			// Prepare string matches.
			preg_match_all('/\(.+?\)/iu', $path, $groupMatches);
			$stringMatches = '';
			if (!empty($groupMatches) && !empty($groupMatches[0])) {
				foreach ($groupMatches[0] as $groupMatchKey => $groupMatch) {
					$stringMatches .= '&' . $this->funcs->_config('app.short_name') . '_rewrite_group_' . ($groupMatchKey + 1) . '=$matches[' . ($groupMatchKey + 1) . ']';
				}
			}
			if ($this->rewriteIdent) {
				$stringMatches .= '&' . $this->funcs->_config('app.short_name') . '_rewrite_ident=' . $this->rewriteIdent;
			}

			// Rewrite rule.
			add_rewrite_rule($path, 'index.php?post_type=' . $this->rewriteFrontPagePostType . '&pagename=' . $this->rewriteFrontPageSlug . '&is_rewrite=true' . $stringMatches, 'top');

			$requestPath = trim($this->request->getPathInfo(), '/\\');

			// Fix "404" for custom permalinks.
			add_action('parse_request', function($wp) use ($path, $requestPath, $stringMatches) {
				try {
					$matched = preg_match('/' . $this->funcs->_regexPath($path) . '/iu', $requestPath);
					if (!$matched) {
						$matched = preg_match('/' . $path . '/iu', $requestPath);
					}
				}
				catch (\Throwable $e) {
					$matched = false;
				}

				if (!$matched) return;

				$stringMatches = ltrim($stringMatches, '&');
				parse_str($stringMatches, $stringMatchesArr);

				unset($wp->query_vars['attachment']);
				unset($wp->query_vars['page']);
				unset($wp->query_vars['name']);

				$wp->query_vars['is_rewrite'] = true;
//					$wp->query_vars['page']       = $this->rewriteFrontPageSlug;
				$wp->query_vars['pagename']   = $this->rewriteFrontPageSlug;
				$wp->query_vars['post_type']  = $this->rewriteFrontPagePostType;

				foreach ($stringMatchesArr as $stringMatchesArrKey => $stringMatchesArrValue) {
					$wp->query_vars[$stringMatchesArrKey] = $stringMatchesArrValue;
				}
			}, 10);

			if (!is_admin()) {
				// Access URL that match rewrite rule.
				add_action('wp', function() use ($path, $requestPath) {
					try {
						$matched = preg_match('/' . $this->funcs->_regexPath($path) . '/iu', $requestPath);
						if (!$matched) {
							$matched = preg_match('/' . $path . '/iu', $requestPath);
						}
					}
					catch (\Throwable $e) {
						$matched = false;
					}

					if (!$matched) return;

					$this->maybeNoTemplate();
//					$this->{$this->callback_function}();
					$this->callWithDependencies($this->callback_function);
				});
			}
		}
	}

	/*
	 *
	 */

	private function overridePath($path = null) {
		if ($path && !$this->path) {
			$this->path = $path;
		}
	}

	/*
	 *
	 */

	public function maybeNoTemplate() {
		if (!$this->useTemplate) {
			add_filter('template_include', function($template) {
				return $this->funcs->_getResourcesPath('/views/modules/rewrite-front-pages/layout/base.blade.php');
			});
		}
	}

	private function callWithDependencies($method) {
		// Lấy container/app & request
		$app = $this->funcs->getApplication() ?? (\Illuminate\Foundation\Application::getInstance() ?? null);
		$baseRequest = $app && $app->bound('request') ? $app->make('request') : ($this->request ?? \Illuminate\Http\Request::capture());

		// Chuẩn hóa request path (loại query string, trim)
		$requestPath = preg_replace('/\?.*$/', '', $this->request->getPathInfo() ?? '');
		$requestPath = trim($requestPath, '/\\');

		// Cố gắng match requestPath với pattern $this->path để lấy captures
		$matches = [];
		if ($this->path && @preg_match('/' . $this->path . '/iu', $requestPath, $matches) === 1) {
			// ok
		}
		// Nếu không match (ví dụ gọi từ nơi khác) -> thử lấy từ query vars (WP rewrite groups)
		if (empty($matches)) {
			// build matches from query vars if available (fallback)
			$matches = [];
			// example keys set earlier: {shortname}_rewrite_group_1 ... _2 ...
			$short = $this->funcs->_config('app.short_name') ?? 'app';
			for ($i = 1; $i <= 20; $i++) {
				$key = $short . '_rewrite_group_' . $i;
				$val = get_query_var($key, null);
				if ($val !== null && $val !== '') {
					// fill numeric index as preg_match would: 1..n
					$matches[$i] = $val;
				}
			}
			// also support an ident var
			$identKey = $short . '_rewrite_ident';
			$identVal = get_query_var($identKey, null);
			if ($identVal !== null && $identVal !== '') {
				$matches[$identKey] = $identVal;
			}
		}

		// Build named groups (non-integer keys) and positional list
		$named = [];
		$positional = [];
		foreach ($matches as $k => $v) {
			if (!is_int($k)) {
				$named[$k] = $v;
			} else {
				if ($k > 0) $positional[] = $v;
			}
		}
		$posIndex = 0;

		// Reflection of the method on $this
		$reflector = new \ReflectionMethod($this, $method);
		$finalArgs = [];

		foreach ($reflector->getParameters() as $param) {
			$paramName = $param->getName();
			$paramType = $param->getType();
			$value = null;

			// 1) If parameter type is a class (non-builtin) -> let container handle (resolve)
			if ($paramType && !$paramType->isBuiltin()) {
				$paramClass = $paramType->getName();

				// Special-case: if they ask for Illuminate\Http\Request, pass current request
				if ($paramClass === \Illuminate\Http\Request::class || is_subclass_of($paramClass, \Illuminate\Http\Request::class)) {
					// prefer $baseRequest (which may be FormRequest bound earlier)
					$value = $baseRequest;
					$finalArgs[] = $value;
					continue;
				}

				// Otherwise try container make (if possible)
				try {
					if ($app) {
						$value = $app->make($paramClass);
						$finalArgs[] = $value;
						continue;
					}
				} catch (\Throwable $e) {
					// failover to leaving null -> container->call will still attempt to resolve later,
					// but for Reflection invocation we must pass something (use null)
					$finalArgs[] = null;
					continue;
				}
			}

			// 2) If named capture exists matching param name -> use it
			if (array_key_exists($paramName, $named)) {
				$value = $named[$paramName];
				// decode urlencoded capture
				if (is_string($value)) $value = urldecode($value);
				$finalArgs[] = $value;
				continue;
			}

			// 3) If request attributes (Symfony style) contain param -> use it
			$attributes = $baseRequest->attributes->all();
			if (array_key_exists($paramName, $attributes)) {
				$value = $attributes[$paramName];
				$finalArgs[] = $value;
				continue;
			}

			// 4) POST body (request->request)
			$post = $baseRequest->request->all();
			if (array_key_exists($paramName, $post)) {
				$value = $post[$paramName];
				$finalArgs[] = $value;
				continue;
			}

			// 5) Query string (GET)
			$query = $baseRequest->query->all();
			if (array_key_exists($paramName, $query)) {
				$value = $query[$paramName];
				$finalArgs[] = $value;
				continue;
			}

			// 6) Positional fallback from captures
			if (isset($positional[$posIndex])) {
				$value = $positional[$posIndex++];
				if (is_string($value)) $value = urldecode($value);
				$finalArgs[] = $value;
				continue;
			}

			// 7) Default value if available
			if ($param->isDefaultValueAvailable()) {
				$finalArgs[] = $param->getDefaultValue();
				continue;
			}

			// 8) Nothing matched -> null
			$finalArgs[] = null;
		}

		// Invoke the method on $this with the resolved arguments
		return $reflector->invokeArgs($this, $finalArgs);
	}

}