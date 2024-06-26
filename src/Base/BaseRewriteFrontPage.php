<?php

namespace WPSPCORE\Base;

use WPSP\Funcs;

abstract class BaseRewriteFrontPage extends BaseInstances {

	public mixed $path                 = null;
	public mixed $rewriteIdent         = null;
	public mixed $useTemplate          = false;
	public mixed $rewriteFrontPageName = 'rewrite-front-pages';

	/*
	 *
	 */

	public function init($path): void {
		// Rewrite rule.
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
		add_rewrite_rule($path, 'index.php?post_type=page&pagename=' . $this->rewriteFrontPageName . '&is_rewrite=true' . $stringMatches, 'top');

		if (!is_admin()) {
			// Access URL that match rewrite rule.
			add_action('wp', function() use ($path) {
				$requestPath = trim($this->request->getPathInfo(), '/');
				if (preg_match('/' . $path . '/iu', $requestPath)) {
					$this->maybeNoTemplate();
					$this->access();
				}
			});
		}
	}

	/*
	 *
	 */

	abstract public function access();

	/*
	 *
	 */

	public function maybeNoTemplate(): void {
		if (!$this->useTemplate) {
			add_filter('template_include', function($template) {
				return Funcs::instance()->_getResourcesPath() . '/views/modules/web/rewrite-front-pages/layout/base.blade.php';
			});
		}
	}

}