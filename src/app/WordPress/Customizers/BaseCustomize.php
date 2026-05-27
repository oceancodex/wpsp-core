<?php

namespace WPSPCORE\App\WordPress\Customizers;

use WPSPCORE\BaseInstances;

abstract class BaseCustomize extends BaseInstances {

	public  $name                = null;
	public  $callback_function   = null;

	private $calledControlAssets = false;
	private $calledPreviewAssets = false;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overrideName($this->extraParams['full_path']);
	}

	/*
	 *
	 */

	public function init($name = null) {
//		$callback  = $this->callback_function ? [$this, $this->callback_function] : null;
		$name = $this->name ?? $name;
		if ($name) {
			$this->registerCustomize($name);
			$this->controlAssets();
			$this->previewAssets();
		}
	}

	public function registerCustomize($name = null) {
		add_action('customize_register', function(\WP_Customize_Manager $wpCustomizeManager) use ($name) {
			$this->panels($wpCustomizeManager);
			$this->sections($wpCustomizeManager);
			$this->settings($wpCustomizeManager);
			$this->controls($wpCustomizeManager);
		}, 9999999999);
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

	abstract public function panels(\WP_Customize_Manager $wpCustomizeManager);

	abstract public function sections(\WP_Customize_Manager $wpCustomizeManager);

	abstract public function controls(\WP_Customize_Manager $wpCustomizeManager);

	abstract public function settings(\WP_Customize_Manager $wpCustomizeManager);

	/*
	 *
	 */

	public function controlAssets() {
		if ($this->calledControlAssets) return;

		add_action('customize_controls_enqueue_scripts', function() {
			$this->controlStyles();
			$this->controlScripts();
			$this->controlLocalizeScripts();
		});

		$this->calledControlAssets = true;
	}

	public function controlStyles() {}

	public function controlScripts() {}

	public function controlLocalizeScripts() {}

	/*
	 *
	 */

	public function previewAssets() {
		if ($this->calledPreviewAssets) return;

		add_action('customize_preview_init', function() {
			$this->previewStyles();
			$this->previewScripts();
			$this->previewLocalizeScripts();
		});

		$this->calledPreviewAssets = true;
	}

	public function previewStyles() {}

	public function previewScripts() {}

	public function previewLocalizeScripts() {}

}