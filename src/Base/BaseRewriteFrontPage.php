<?php

namespace WPSPCORE\Base;

abstract class BaseRewriteFrontPage extends BaseInstances {

	public mixed $path                     = null;
	public mixed $rewriteIdent             = null;
	public mixed $useTemplate              = false;
	public mixed $rewriteFrontPageSlug     = 'rewrite-front-pages';
	public mixed $rewriteFrontPagePostType = 'page';
	public mixed $callback_function        = null;
	public mixed $custom_properties        = null;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $path = null, $callback_function = null, $custom_properties = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->callback_function = $callback_function;
		$this->custom_properties = $custom_properties;
		$this->overridePath($path);
		$this->customProperties();
	}

	/*
	 *
	 */

	public function init($path = null): void {
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

			add_action('parse_request', function($wp) use ($path, $stringMatches) {
				if (preg_match('/' . $path . '/iu', $wp->request)) {
					$stringMatches = ltrim($stringMatches, '&');
					parse_str($stringMatches, $stringMatchesArr);

					unset($wp->query_vars['attachment']);

					$wp->query_vars['is_rewrite'] = true;
					$wp->query_vars['pagename']   = $this->rewriteFrontPageSlug;
					$wp->query_vars['post_type']  = $this->rewriteFrontPagePostType;

					foreach ($stringMatchesArr as $stringMatchesArrKey => $stringMatchesArrValue) {
						$wp->query_vars[$stringMatchesArrKey] = $stringMatchesArrValue;
					}
				}
			}, 9999);

			if (!is_admin()) {
				// Access URL that match rewrite rule.
				add_action('wp', function() use ($path) {
					$requestPath = trim($this->request->getPathInfo(), '/\\');
					if (preg_match('/' . $path . '/iu', $requestPath)) {
						$this->maybeNoTemplate();
						$this->{$this->callback_function}(); // $this->access();
					}
				});
			}
		}
	}

	/*
	 *
	 */

	private function overridePath($path = null): void {
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

	public function maybeNoTemplate(): void {
		if (!$this->useTemplate) {
			add_filter('template_include', function($template) {
				return $this->funcs->_getResourcesPath('/views/modules/rewrite-front-pages/layout/base.blade.php');
			});
		}
	}

}