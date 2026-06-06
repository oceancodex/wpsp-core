<?php

namespace WPSPCORE\App\WordPress\Blocks;

use Illuminate\Support\Facades\File;
use WPSPCORE\BaseInstances;

abstract class BaseBlock extends BaseInstances {

	public  $name      = null;
	public  $blockPath = null;
	public  $args      = [];

	private $path      = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->overrideName($this->extraParams['full_path']);
		$this->path = $this->extraParams['path'];
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
		$requestPath = ltrim($this->request->getRequestUri(), '/\\');
		$name = $this->name ?? $name;

		if ($name) {
			$blockPath = $this->blockPath ?? $this->funcs->_getResourcesPath('/views/blocks/build/' . $name);

			if (File::exists($blockPath)) {
				if (method_exists($this, 'render')) {
					$this->args['render_callback'] = function($attributes, $content, $block) use ($requestPath) {
						return $this->autoResolveAndCall(
							$this->path,
							$this->name,
							$requestPath,
							$this,
							'render',
							[
								'attributes' => $attributes,
								'content' => $content,
								'block' => $block
							]
						);
					};
				}

				register_block_type($blockPath, $this->args);
			}
		}
	}

}