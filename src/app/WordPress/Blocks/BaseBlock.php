<?php

namespace WPSPCORE\app\WordPress\Blocks;

use Illuminate\Support\Facades\File;
use WPSPCORE\BaseInstances;

abstract class BaseBlock extends BaseInstances {

	public $blockFolder = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->overrideBlockFolder($this->extraParams['full_path']);
	}

	/*
	 *
	 */

	private function overrideBlockFolder($blockFolder = null) {
		if ($blockFolder && !$this->blockFolder) {
			$this->blockFolder = $blockFolder;
		}
	}

	/*
	 *
	 */

	public function init($blockFolder = null) {
		$blockFolder = $this->blockFolder ?? $blockFolder;
		if ($blockFolder) {
			$blockBuildPath = $this->funcs->_getResourcesPath('/views/blocks/build/' . $blockFolder);
			if (File::exists($blockBuildPath)) {
				register_block_type($blockBuildPath);
			}
		}
	}

}