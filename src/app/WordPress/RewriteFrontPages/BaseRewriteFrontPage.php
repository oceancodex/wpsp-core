<?php

namespace WPSPCORE\App\WordPress\RewriteFrontPages;

use WPSPCORE\App\Routes\RouteTrait;
use WPSPCORE\BaseInstances;

abstract class BaseRewriteFrontPage extends BaseInstances {

	use RouteTrait;

	public $path                     = null;
	public $fullPath                 = null;
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
		$this->overrideFullPath($this->extraParams['full_path']);
	}

	/*
	 *
	 */

	public function init($path = null, $fullPath = null) {
		$path     = $this->path ?? $path;
		$fullPath = $this->fullPath ?? $fullPath;

		if ($path && $fullPath) {
			// Prepare string matches.
			preg_match_all('/\(.+?\)/iu', $this->funcs->_regexPath($fullPath), $groupMatches);
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
			add_rewrite_rule('^' . $this->funcs->_regexPath($fullPath) . '\/?$', 'index.php?post_type=' . $this->rewriteFrontPagePostType . '&pagename=' . $this->rewriteFrontPageSlug . '&is_rewrite=true' . $stringMatches, 'top');

			$requestPath = trim($this->request->getPathInfo(), '/\\');

			// Fix "404" for custom permalinks.
			add_action('parse_request', function($wp) use ($fullPath, $requestPath, $stringMatches) {
				try {
					$matched = preg_match('/^' . $this->funcs->_regexPath($fullPath) . '$/iu', $requestPath);
					if (!$matched) {
						$matched = preg_match('/^' . $fullPath . '$/iu', $requestPath);
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
//				$wp->query_vars['page']       = $this->rewriteFrontPageSlug;
				$wp->query_vars['pagename']   = $this->rewriteFrontPageSlug;
				$wp->query_vars['post_type']  = $this->rewriteFrontPagePostType;

				foreach ($stringMatchesArr as $stringMatchesArrKey => $stringMatchesArrValue) {
					$wp->query_vars[$stringMatchesArrKey] = $stringMatchesArrValue;
				}
			}, 999999999);

			// Access URL that match rewrite rule.
			if (!is_admin()) {
				try {
					$matched = preg_match('/^' . $this->funcs->_regexPath($fullPath) . '$/iu', $requestPath);
					if (!$matched) {
						$matched = preg_match('/^' . $fullPath . '$/iu', $requestPath);
					}
				}
				catch (\Throwable $e) {
					$matched = false;
				}

				if (!$matched) return;

				$this->maybeNoTemplate();
				$callback   = $this->prepareCallbackFunction($this->callback_function, $path, $fullPath);
				$callParams = $this->getCallParams($path, $fullPath, $requestPath, $this, $this->callback_function);
				$this->resolveAndCall($callback, $callParams);
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

	private function overrideFullPath($fullPath = null) {
		if ($fullPath && !$this->fullPath) {
			$this->fullPath = $fullPath;
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

}