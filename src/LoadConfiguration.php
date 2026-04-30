<?php

namespace WPSPCORE;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;

class LoadConfiguration extends \Illuminate\Foundation\Bootstrap\LoadConfiguration {

	protected function loadConfigurationFiles(Application $app, Repository $repository) {
		global $wpspApplicationInstanceConfig;

		$files = $this->getConfigurationFiles($app);

		$items = [];

		foreach ($files as $key => $path) {
			$items[$key] = require $path;
		}

		// 👉 Đây là toàn bộ config trước khi set vào container
		// Bạn có thể debug / modify tại đây
//		dump($items);

		foreach ($items as $key => $value) {
			$repository->set($key, $value);
		}
	}

}