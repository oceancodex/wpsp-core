<?php

namespace WPSPCORE\App\WordPress\ThemeTemplates;

use WPSPCORE\BaseInstances;

abstract class BaseThemeTemplates extends BaseInstances {

	public $name              = null;
	public $label             = null;
	public $path              = null;
	public $post_types        = [];
	public $callback_function = null;

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'] ?? null;
		$this->overrideName($this->extraParams['name']);
		$this->templateInclude();
	}

	/*
	 *
	 */

	public function init($name = null) {
		$name = $this->name ?? $name;
		if ($name) {
			if (is_array($this->post_types)) {
				if (!empty($this->post_types)) {
					foreach ($this->post_types as $post_type) {
						$this->addThemeTemplate($name, $post_type);
					}
				}
				else {
					$this->addThemeTemplate($name);
				}
			}
			elseif (is_string($this->post_types) && $postType = $this->post_types) {
				$this->addThemeTemplate($name, $postType);
			}
		}
	}

	public function addThemeTemplate($name, $postType = null) {
		if ($postType) $postType = $postType . '_';
		add_filter('theme_'.$postType.'templates', function($templates) use ($name) {
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

	/*
	 *
	 */

	protected function overrideName($name = null) {
		if ($name && !$this->name) {
			$this->name = $name;
		}
	}

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
							$filePath = $seletedTemplatePath ?? $this->funcs->_getResourcesPath('/views/modules/theme-templates/' . $seletedTemplateName);
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