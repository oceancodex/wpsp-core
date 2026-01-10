<?php

namespace WPSPCORE\App\View\Directives;

use Illuminate\View\Compilers\BladeCompiler;

class adminpagemetaboxes extends BaseDirective {

	use DirectiveTrait;

	/*
	 *
	 */

	public function register(BladeCompiler $bladeCompiler) {
		$bladeCompiler->directive('adminpagemetaboxes', function($expression) {
			$expression = explode(',', $expression);

//			$adminPageMenuClass      = $expression[0] ?? null;
			$adminPageMetaboxName    = $expression[0] ?? null;
			$adminPageMetaboxPageNow = $expression[1] ?? null;
			$adminPageMenuArgs       = $expression[2] ?? null;

//			if ($adminPageMenuClass) {
//				$adminPageMenuClass = preg_replace('/^(\s*?)\'|^(\s*?)\"|\'(\s*?)$|\"(\s*?)$/', '', $adminPageMenuClass);
//			}

			if ($adminPageMetaboxName) {
				$adminPageMetaboxName = preg_replace('/^(\s*?)\'|^(\s*?)\"|\'(\s*?)$|\"(\s*?)$/', '', $adminPageMetaboxName);
			}

			if ($adminPageMetaboxPageNow) {
				$adminPageMetaboxPageNow = preg_replace('/^(\s*?)\'|^(\s*?)\"|\'(\s*?)$|\"(\s*?)$/', '', $adminPageMetaboxPageNow);
			}

			if ($adminPageMenuArgs) {
				$adminPageMenuArgs = trim($adminPageMenuArgs);
				$adminPageMenuArgs = $this->arrayStringToArray($adminPageMenuArgs);
//				$adminPageMenuArgs = json_encode($adminPageMenuArgs);
			}

			$expression = json_encode([
//				'admin_page_menu_class'      => $adminPageMenuClass,
				'admin_page_metabox_name'    => $adminPageMetaboxName,
				'admin_page_metabox_pagenow' => $adminPageMetaboxPageNow,
				'admin_page_metabox_args'    => $adminPageMenuArgs,
			]);

			return $this->adminpagemetaboxes($expression);
		});
	}

	/*
	 *
	 */

	public function adminpagemetaboxes($expression) {
		$mainPath = $this->mainPath;
		$rootNamespace = $this->rootNamespace;
		$prefixEnv = $this->prefixEnv;
		return "<?php
					\$__adminMetaboxJsonConfigs = '$expression';
					echo \\WPSPCORE\\App\\View\\Directives\\adminpagemetaboxes::render(\$__adminMetaboxJsonConfigs, '$rootNamespace');
					?>";
	}

	/*
	 *
	 */

	public static function render($jsonConfigs = null, $rootNamespace = null) {
		if ($jsonConfigs && $rootNamespace) {
			$jsonConfigs = json_decode($jsonConfigs, true);

//			$adminPageMenuClass      = $jsonConfigs['admin_page_menu_class'] ?? null;
			$adminPageMetaboxName    = $jsonConfigs['admin_page_metabox_name'] ?? null;
			$adminPageMetaboxPageNow = $jsonConfigs['admin_page_metabox_pagenow'] ?? null;

			if ($adminPageMetaboxName) {
				$adminPageMenuArgs = $jsonConfigs['admin_page_metabox_args'] ?? null;

				/** @var \WPSPCORE\Funcs|\WPSP\Funcs $funcs */
				$funcs = '\\' . $rootNamespace . '\\Funcs';
				$routeMap = $funcs::instance()->getRouteMap();
				$route = $routeMap->getRoute('AdminPageMetaboxes', $adminPageMetaboxName);

				if ($route) {
					$adminPageMetaboxCallbackClass = $route['route_data']->callback[0] ?? null;

					if ($adminPageMetaboxCallbackClass) {
						$adminPageMetaboxCallbackClass = '\\' . $adminPageMetaboxCallbackClass;
						$adminPageMetaboxes = (new $adminPageMetaboxCallbackClass())->adminPageMetaboxes();
					}

					echo '<pre style="background:white;z-index:9999;position:relative">'; print_r($adminPageMetaboxName); echo '</pre>';
					echo '<pre style="background:white;z-index:9999;position:relative">'; print_r($adminPageMenuArgs); echo '</pre>';
				}
			}

		}

		return '123';
	}

}