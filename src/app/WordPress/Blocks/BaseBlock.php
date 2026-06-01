<?php

namespace WPSPCORE\App\WordPress\Blocks;

use Illuminate\Support\Facades\File;
use WPSPCORE\BaseInstances;

abstract class BaseBlock extends BaseInstances {

	public $name 		= null;
	public $blockPath   = null;
	public $args		= [];

	/*
	 *
	 */

	public function afterConstruct() {
		$this->overrideName($this->extraParams['full_path']);
	}

	/*
	 *
	 */

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
			$blockPath = $this->blockPath ?? $this->funcs->_getResourcesPath('/views/blocks/build/' . $name);

			if (File::exists($blockPath)) {
				if (method_exists($this, 'render')) {
					$this->args['render_callback'] = function($attributes, $content, $block) {
						return $this->render($attributes, $content, $block);
					};
				}

				register_block_type($blockPath, $this->args);
			}
		}
	}

}