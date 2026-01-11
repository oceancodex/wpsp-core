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
			$adminPageMetaBoxName    = $expression[0] ?? null;
			$adminPageMetaBoxPageNow = $expression[1] ?? null;
			$adminPageMenuArgs       = $expression[2] ?? null;

//			if ($adminPageMenuClass) {
//				$adminPageMenuClass = preg_replace('/^(\s*?)\'|^(\s*?)\"|\'(\s*?)$|\"(\s*?)$/', '', $adminPageMenuClass);
//			}

			if ($adminPageMetaBoxName) {
				$adminPageMetaBoxName = preg_replace('/^(\s*?)\'|^(\s*?)\"|\'(\s*?)$|\"(\s*?)$/', '', $adminPageMetaBoxName);
			}

			if ($adminPageMetaBoxPageNow) {
				$adminPageMetaBoxPageNow = preg_replace('/^(\s*?)\'|^(\s*?)\"|\'(\s*?)$|\"(\s*?)$/', '', $adminPageMetaBoxPageNow);
			}

			if ($adminPageMenuArgs) {
				$adminPageMenuArgs = trim($adminPageMenuArgs);
				$adminPageMenuArgs = $this->arrayStringToArray($adminPageMenuArgs);
//				$adminPageMenuArgs = json_encode($adminPageMenuArgs);
			}

			$expression = json_encode([
//				'admin_page_menu_class'      => $adminPageMenuClass,
				'admin_page_metabox_name'    => $adminPageMetaBoxName,
				'admin_page_metabox_pagenow' => $adminPageMetaBoxPageNow,
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
					\$__adminMetaBoxJsonConfigs = '$expression';
					echo \\WPSPCORE\\App\\View\\Directives\\adminpagemetaboxes::render(\$__adminMetaBoxJsonConfigs, '$rootNamespace');
					?>";
	}

	/*
	 *
	 */

	public static function render($jsonConfigs = null, $rootNamespace = null) {
		if ($jsonConfigs && $rootNamespace) {
			$jsonConfigs = json_decode($jsonConfigs, true);

//			$adminPageMenuClass      = $jsonConfigs['admin_page_menu_class'] ?? null;
			$adminPageMetaBoxName    = $jsonConfigs['admin_page_metabox_name'] ?? null;
			$adminPageMetaBoxPageNow = $jsonConfigs['admin_page_metabox_pagenow'] ?? null;

			if ($adminPageMetaBoxName) {
				$adminPageMenuArgs = $jsonConfigs['admin_page_metabox_args'] ?? null;

				/** @var \WPSPCORE\Funcs|\WPSP\Funcs $funcs */
				$funcs = '\\' . $rootNamespace . '\\Funcs';
				$routeMap = $funcs::instance()->getRouteMap();
				$route = $routeMap->getRoute('AdminPageMetaBoxes', $adminPageMetaBoxName);

				if ($route) {
					$adminPageMetaBoxCallbackClass = $route['route_data']->callback[0] ?? null;

					if ($adminPageMetaBoxCallbackClass) {
						$adminPageMetaBoxCallbackClass = '\\' . $adminPageMetaBoxCallbackClass;
						$adminPageMetaBoxes = (new $adminPageMetaBoxCallbackClass())->adminPageMetaBoxes();
					}

					echo '<pre style="background:white;z-index:9999;position:relative">'; print_r($adminPageMetaBoxName); echo '</pre>';
					echo '<pre style="background:white;z-index:9999;position:relative">'; print_r($adminPageMenuArgs); echo '</pre>';
				}
			}

		}

		return '123';
	}

}