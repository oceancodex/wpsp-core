<?php

namespace WPSPCORE\Base;

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

			// Fix "404" for custom permalinks.
			add_action('parse_request', function($wp) use ($path, $stringMatches) {
				if (preg_match('/' . $path . '/iu', $this->request->getUri())) {
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
				}
			}, 10);

			if (!is_admin()) {
				// Access URL that match rewrite rule.
				add_action('wp', function() use ($path) {
					$requestPath = trim($this->request->getPathInfo(), '/\\');
					if (preg_match('/' . $path . '/iu', $requestPath)) {
						$this->maybeNoTemplate();
						$this->{$this->callback_function}(); // $this->index();
					}
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

//	abstract public function access();

	abstract public function customProperties();

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