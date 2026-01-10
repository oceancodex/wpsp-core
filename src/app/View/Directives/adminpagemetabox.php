<?php

namespace WPSPCORE\App\View\Directives;

use Illuminate\View\Compilers\BladeCompiler;

class adminpagemetabox extends BaseDirective {

	use DirectiveTrait;

	public function register(BladeCompiler $bladeCompiler) {
		$bladeCompiler->directive('adminpagemetabox', function($expression) {
			$expression = explode(',', $expression);

			$adminPageMenuClass   = $expression[0] ?? null;
			$adminPageMetaboxName = $expression[1] ?? null;
			$adminPageMenuArgs    = $expression[2] ?? null;

			if ($adminPageMenuClass) {
				$adminPageMenuClass = preg_replace('/^(\s*?)\'|^(\s*?)\"|\'(\s*?)$|\"(\s*?)$/', '', $adminPageMenuClass);
			}

			if ($adminPageMetaboxName) {
				$adminPageMetaboxName = preg_replace('/^(\s*?)\'|^(\s*?)\"|\'(\s*?)$|\"(\s*?)$/', '', $adminPageMetaboxName);
			}

			if ($adminPageMenuArgs) {
				$adminPageMenuArgs = trim($adminPageMenuArgs);
				$adminPageMenuArgs = $this->arrayStringToArray($adminPageMenuArgs);
//				$adminPageMenuArgs = json_encode($adminPageMenuArgs);
			}

			$expression = json_encode([
				'admin_page_menu_class'   => $adminPageMenuClass,
				'admin_page_metabox_name' => $adminPageMetaboxName,
				'admin_page_metabox_args' => $adminPageMenuArgs,
			]);

			return $this->adminpagemetabox($expression);
		});
	}

	/*
	 *
	 */

	public function adminpagemetabox($expression) {
		return "<?php
					\$__adminMetaboxJsonConfigs = '$expression';
					echo \\WPSPCORE\\App\\View\\Directives\\adminpagemetabox::render(
						\$__adminMetaboxJsonConfigs
					);
				?>";
	}

	/*
	 *
	 */

	public static function render($jsonConfigs = null) {
		if ($jsonConfigs) {
			$jsonConfigs = json_decode($jsonConfigs, true);

			$adminPageMenuClass   = $jsonConfigs['admin_page_menu_class'] ?? null;
			$adminPageMetaboxName = $jsonConfigs['admin_page_metabox_name'] ?? null;

			if ($adminPageMenuClass && $adminPageMetaboxName) {
				$adminPageMenuArgs = $jsonConfigs['admin_page_metabox_args'] ?? null;
			}

			echo '<pre style="background:white;z-index:9999;position:relative">'; print_r($adminPageMenuClass); echo '</pre>';
			echo '<pre style="background:white;z-index:9999;position:relative">'; print_r($adminPageMetaboxName); echo '</pre>';
			echo '<pre style="background:white;z-index:9999;position:relative">'; print_r($adminPageMenuArgs); echo '</pre>';

		}

		return '123';
	}

}