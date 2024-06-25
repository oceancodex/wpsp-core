<?php

namespace WPSPCORE\Base;

use WPSPCORE\Http\HttpFoundation;
use WPSP\Funcs;

abstract class BaseRewriteFrontPage extends HttpFoundation {

	public mixed $path                 = null;
	public mixed $rewriteIdent         = null;
	public mixed $useTemplate          = false;
	public mixed $rewriteFrontPageName = 'rewrite-front-pages';

	public function __construct() {
		parent::__construct();
	}

	/*
	 *
	 */

	public function init($path): void {
		// Rewrite rule.
		add_action('init', function() use ($path) {
			// Prepare string matches.
			preg_match('/\(.+?\)/iu', $path, $groupMatches);
			$stringMatches = '';
			if (!empty($groupMatches)) {
				foreach ($groupMatches as $groupMatchKey => $groupMatch) {
					$stringMatches .= '&' . config('app.short_name') . '_rewrite_group_' . ($groupMatchKey + 1) . '=$matches[' . ($groupMatchKey + 1) . ']';
				}
			}
			if ($this->rewriteIdent) {
				$stringMatches .= '&' . config('app.short_name') . '_rewrite_ident=' . $this->rewriteIdent;
			}
			add_rewrite_rule($path, 'index.php?post_type=page&pagename=' . $this->rewriteFrontPageName . '&is_rewrite=true' . $stringMatches, 'top');
		});

		if (!is_admin()) {
			// Access URL that match rewrite rule.
			add_action('wp', function() use ($path) {
				$requestPath = trim(self::$request->getPathInfo(), '/');
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
				return Funcs::instance()->getResourcesPath() . '/views/modules/web/rewrite-front-pages/layout/base.blade.php';
			});
		}
	}

}