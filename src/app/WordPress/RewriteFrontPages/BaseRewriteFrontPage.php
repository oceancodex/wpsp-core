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

	public function init($path = null, $fullPath = null) {
		$path     = $this->path ?? $path;
		$fullPath = $this->fullPath ?? $fullPath;

		// Prepare regex path.
		$regexPrefix = '^';
		$regexSuffix = '$';
		$regexPath   = $this->funcs->_regexPath($fullPath);
		$regexPath   = !str_starts_with($regexPath, $regexPrefix) ? $regexPrefix . $regexPath : $regexPath;
		$regexPath   = !str_ends_with($regexPath, $regexSuffix) ? $regexPath . $regexSuffix : $regexPath;

		$appShortName = $this->funcs->_config('app.short_name');

		if ($path && $fullPath) {
			// Prepare string matches.
			preg_match_all('/\(.+?\)/iu', $regexPath, $groupMatches);

			$stringMatches = '';

			if (!empty($groupMatches) && !empty($groupMatches[0])) {
				foreach ($groupMatches[0] as $groupMatchKey => $groupMatch) {
					$stringMatches .= '&' . $appShortName . '_rewrite_group_' . ($groupMatchKey + 1) . '=$matches[' . ($groupMatchKey + 1) . ']';
				}
			}

			if ($this->rewriteIdent) {
				$stringMatches .= '&' . $appShortName . '_rewrite_ident=' . $this->rewriteIdent;
			}

			// Rewrite rule.
			add_rewrite_rule($regexPath, 'index.php?post_type=' . $this->rewriteFrontPagePostType . '&pagename=' . $this->rewriteFrontPageSlug . '&is_rewrite=true' . $stringMatches, 'top');

			$requestPath = ltrim($this->request->getPathInfo(), '/\\');

			if (!preg_match('/' . $regexPath . '/iu', $requestPath, $matches)) return;

			// Fix "404" for custom permalinks.
			add_action('parse_request', function($wp) use ($fullPath, $requestPath, $regexPath, $stringMatches, $matches, $appShortName) {
				unset($wp->query_vars['attachment']);
				unset($wp->query_vars['page']);
				unset($wp->query_vars['name']);

				$wp->query_vars['is_rewrite'] = true;
//				$wp->query_vars['page']       = $this->rewriteFrontPageSlug;
				$wp->query_vars['pagename']   = $this->rewriteFrontPageSlug;
				$wp->query_vars['post_type']  = $this->rewriteFrontPagePostType;

				foreach ($matches as $k => $v) {
					if ($k === 0) continue;
					$wp->query_vars[$appShortName . '_rewrite_group_' . $k] = $v;
				}
			}, 9999999999);

			// Access URL that match rewrite rule.
			if (!is_admin()) {
				// Cần phải hook vào 'wp' để có thể truy cập được global $post.
				add_action('wp', function() use ($path, $fullPath, $requestPath) {
					$this->maybeNoTemplate();
					$callback = $this->prepareCallbackFunction($this->callback_function, $path, $fullPath);
					$this->resolveAndCall($callback);
				});
			}
		}
	}

	/*
	 *
	 */

	public function maybeNoTemplate() {
		if (!$this->useTemplate) {
			add_filter('template_include', function($template) {
				return $this->funcs->_getResourcesPath('/views/rewrite-front-pages/layout/base.blade.php');
			});
		}
	}

}