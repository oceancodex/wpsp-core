<?php

namespace WPSPCORE\Base;

abstract class BaseTemplates extends BaseInstances {

	public $name  = null;
	public $label = null;
	public $path  = null;

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $name = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->name = $name;
		$this->customProperties();
		$this->templateInclude();
	}

	/*
	 *
	 */

	public function init($name = null) {
		if ($this->name) {
			add_filter('theme_page_templates', function($templates) {
				$name = $this->name;
				if ($this->path) {
					$name .= '|' . preg_replace('/\/|\\\/iu', '%%slash%%', $this->path);
				}
				else {
					$name .= '.php';
				}
				$templates[$name] = $this->label ?? $this->funcs->_config('app.short_name') . ' - Custom template';
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

	private function templateInclude() {
		if ($this->mainPath) {
			add_filter('template_include', function($template) {
				global $post;
				if ($post) {
					$seletedTemplate = get_post_meta($post->ID, '_wp_page_template', true);
					if ($seletedTemplate) {
						$seletedTemplate     = explode('|', $seletedTemplate);
						$seletedTemplateName = $seletedTemplate[0] ?? null;
						$seletedTemplatePath = $seletedTemplate[1] ?? null;
						if ($seletedTemplateName == $this->name || $seletedTemplateName == $this->name . '.php') {
							if ($seletedTemplatePath) {
								$seletedTemplatePath = preg_replace('/%%slash%%/iu', '/', $seletedTemplatePath);
							}
							$filePath = $seletedTemplatePath ?? $this->funcs->_getResourcesPath('/views/modules/templates/' . $seletedTemplateName);
							if (file_exists($filePath)) {
								return $filePath;
							}
							elseif ($this->funcs->_config('app.debug')) {
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