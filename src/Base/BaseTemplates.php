<?php

namespace WPSPCORE\Base;

use WPSPCORE\Http\HttpFoundation;
use WPSP\Funcs;

abstract class BaseTemplates extends HttpFoundation {

	public mixed $templateName  = null;
	public mixed $templateLabel = null;
	public mixed $templatePath  = null;
	public mixed $mainPath      = null;

	public function __construct($templateName = null, $mainPath = null) {
		parent::__construct();
		$this->templateName = $templateName;
		$this->mainPath     = $mainPath;
		$this->customProperties();
		$this->templateInclude();
	}

	/*
	 *
	 */

	public function init($templateName = null): void {
		if ($this->templateName) {
			add_filter('theme_page_templates', function($templates) {
				$templateName = $this->templateName;
				if ($this->templatePath) {
					$templateName .= '|' . preg_replace('/\/|\\\/iu', '%%slash%%', $this->templatePath);
				}
				else {
					$templateName .= '.php';
				}
				$templates[$templateName] = $this->templateLabel ?? config('app.short_name') . ' - Custom template';
				return $templates;
			});
		}
	}

	/*
	 *
	 */

	abstract public function customProperties();

	/*
	 *
	 */

	private function templateInclude(): void {
		if ($this->mainPath) {
			add_filter('template_include', function($template) {
				global $post;
				if ($post) {
					$seletedTemplate = get_post_meta($post->ID, '_wp_page_template', true);
					if ($seletedTemplate) {
						$seletedTemplate     = explode('|', $seletedTemplate);
						$seletedTemplateName = $seletedTemplate[0] ?? null;
						$seletedTemplatePath = $seletedTemplate[1] ?? null;
						if ($seletedTemplateName == $this->templateName || $seletedTemplateName == $this->templateName . '.php') {
							if ($seletedTemplatePath) {
								$seletedTemplatePath = preg_replace('/%%slash%%/iu', '/', $seletedTemplatePath);
							}
							$filePath = $seletedTemplatePath ?? Funcs::instance()->getResourcesPath() . '/views/modules/web/templates/' . $seletedTemplateName;
							if (file_exists($filePath)) {
								return $filePath;
							}
							elseif (config('app.debug')) {
								echo '<pre>';
								print_r('Template file not found: ' . $filePath);
								echo '</pre>';
							}
						}
					}
				}
				return $template;
			});
		}
	}

}