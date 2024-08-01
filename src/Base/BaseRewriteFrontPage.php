<?php

namespace WPSPCORE\Base;

abstract class BaseRewriteFrontPage extends BaseInstances {

	public $path                 = null;
	public $rewriteIdent         = null;
	public $useTemplate          = false;
	public $rewriteFrontPageSlug = 'rewrite-front-pages';

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $path = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->overridePath($path);
		$this->customProperties();
	}

	/*
	 *
	 */

	public function init($path = null): void {
		if ($path) {
			// Prepare string matches.
			preg_match('/\(.+?\)/iu', $path, $groupMatches);
			$stringMatches = '';
			if (!empty($groupMatches)) {
				foreach ($groupMatches as $groupMatchKey => $groupMatch) {
					$stringMatches .= '&' . $this->funcs->_config('app.short_name') . '_rewrite_group_' . ($groupMatchKey + 1) . '=$matches[' . ($groupMatchKey + 1) . ']';
				}
			}
			if ($this->rewriteIdent) {
				$stringMatches .= '&' . $this->funcs->_config('app.short_name') . '_rewrite_ident=' . $this->rewriteIdent;
			}

			// Rewrite rule.
			add_rewrite_rule($path, 'index.php?post_type=page&pagename=' . $this->rewriteFrontPageSlug . '&is_rewrite=true' . $stringMatches, 'top');

			if (!is_admin()) {
				// Access URL that match rewrite rule.
				add_action('wp', function() use ($path) {
					$requestPath = trim($this->request->getPathInfo(), '/\\');
					if (preg_match('/' . $path . '/iu', $requestPath)) {
						$this->maybeNoTemplate();
						$this->access();
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

	abstract public function access();

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