<?php

namespace WPSPCORE\App\WordPress\ThemeTemplates;

use WPSPCORE\BaseInstances;

abstract class BaseThemeTemplates extends BaseInstances {

	public $name              = null;
	public $label             = null;
	public $path              = null;
	public $post_types        = [];

	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->overrideCallbackFunction($this->extraParams['callback_function'] ?? null);
		$this->overrideName($this->extraParams['full_path']);
		$this->templateInclude();
	}

	/*
	 *
	 */

	private function overrideCallbackFunction($callback_function = null) {
		if ($callback_function && $this->callback_function === null) {
			$this->callback_function = $callback_function;
		}
	}

	private function overrideName($name = null) {
		if ($name && !$this->name) {
			$this->name = $name;
		}
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

	/*
	 *
	 */

	/**
	 * Thêm theme template vào danh sách template có sẵn của WordPress
	 *
	 * Method này sử dụng filter của WordPress để đăng ký một template tùy chỉnh
	 * vào danh sách các template có sẵn. Template sẽ xuất hiện trong dropdown
	 * "Template" khi chỉnh sửa post/page trong WordPress admin.
	 *
	 * @param string      $name     Tên của template (tên file hoặc tên tùy chỉnh)
	 * @param string|null $postType Loại post type cần áp dụng template (page, post, custom post type...). Null nếu áp dụng cho tất cả.
	 */
	public function addThemeTemplate($name, $postType = null) {
		if ($postType) {
			$postType = $postType . '_';
		}

		add_filter('theme_' . $postType . 'templates', function($templates) use ($name) {
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

	/**
	 * Sử dụng filter `template_include` để hiển thị giao diện post/page\
	 * theo theme template được chọn. Tại sao phải vậy?\
	 * Theme template được sử dụng trong theme với cấu trúc:\
	 * .../wp-content/themes/my-theme/{template_name}.php\
	 * Nhưng đây là môi trường plugin, vậy nên cần phải sử dụng hook để "ép" giao diện\
	 * hiển thị cho post/page theo template đuợc chọn.
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
							$filePath = $seletedTemplatePath ?? $this->funcs->_getResourcesPath('/views/theme-templates/' . $seletedTemplateName);
							if (file_exists($filePath)) {
								return $filePath;
							}
							elseif ($this->funcs->_config('app.debug')) {
								echo '<pre style="background:white;z-index:9999;position:relative;color:red;">'; print_r('Template file not found: ' . $filePath); echo '</pre>';
							}
						}
					}
				}
				return $template;
			});
		}
	}

}