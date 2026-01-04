<?php

namespace WPSPCORE\App\View\Directives;

use Illuminate\View\Compilers\BladeCompiler;
use WPSPCORE\App\View\DirectiveTrait;

class adminpagemetabox {

	use DirectiveTrait;

	public static function register(BladeCompiler $bladeCompiler) {
		$bladeCompiler->directive('adminpagemetabox', function($expression) {
			$expression = explode(',', $expression);

			$adminPageMetaboxId = $expression[0] ?? null;
			$adminPageMenuClass = $expression[1] ?? null;
			$adminPageMenuArgs  = $expression[2] ?? null;

			if ($adminPageMetaboxId) {
				$adminPageMetaboxId = preg_replace('/^(\s*?)\'|^(\s*?)\"|\'(\s*?)$|\"(\s*?)$/', '', $adminPageMetaboxId);
			}

			if ($adminPageMenuClass) {
				$adminPageMenuClass = preg_replace('/^(\s*?)\'|^(\s*?)\"|\'(\s*?)$|\"(\s*?)$/', '', $adminPageMenuClass);
			}

			if ($adminPageMenuArgs) {
				$adminPageMenuArgs = trim($adminPageMenuArgs);
				$adminPageMenuArgs = static::arrayStringToArray($adminPageMenuArgs);
				$adminPageMenuArgs = json_encode($adminPageMenuArgs);
			}

			$expression = json_encode([
				'admin_page_metabox_id'   => $adminPageMetaboxId,
				'admin_page_menu_class'   => $adminPageMenuClass,
				'admin_page_metabox_args' => $adminPageMenuArgs,
			]);

			return static::adminpagemetabox($expression);
		});
		$bladeCompiler->directive('endadminpagemetabox', function($expression) {
			return static::endadminpagemetabox($expression);
		});
	}

	/*
	 *
	 */

	public static function adminpagemetabox($expression) {
		return "<?php
					\$__adminMetaboxJsonConfigs = '$expression';
					ob_start();
				?>";
	}

	public static function endadminpagemetabox() {
		return "<?php
					\$__content = ob_get_clean();
					echo \\WPSPCORE\\App\\View\\Directives\\adminpagemetabox::render(
						\$__content,
						\$__adminMetaboxJsonConfigs
					);
				?>";
	}

	/*
	 *
	 */

	public static function render($content = null, $jsonConfigs = null) {

		if ($jsonConfigs) {
			$jsonConfigs        = json_decode($jsonConfigs, true);
			$adminPageMetaboxId = $jsonConfigs['admin_page_metabox_id'] ?? null;
			$adminPageMenuClass = $jsonConfigs['admin_page_menu_class'] ?? null;
			$adminPageMenuArgs  = $jsonConfigs['admin_page_metabox_args'] ?? null;

			if ($adminPageMenuClass) {
				return
			}

		}

		return $content;
	}

}