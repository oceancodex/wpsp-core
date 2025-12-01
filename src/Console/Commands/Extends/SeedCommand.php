<?php

namespace WPSPCORE\Console\Commands\Extends;

use WPSPCORE\Console\Commands\Extends\Database\Seeds\Funcs;

class SeedCommand extends \Illuminate\Database\Console\Seeds\SeedCommand {

	protected function getSeeder() {
		$class = $this->input->getArgument('class') ?? $this->input->getOption('class');

		// Lấy namespace root động từ plugin hiện tại
		$rootNamespace = Funcs::instance()->_getRootNamespace();

		// Nếu chưa có root prefix, tự thêm
		if (strpos($class, $rootNamespace) !== 0) {
			$class = "\\{$rootNamespace}\\{$class}";
		}

		return $this->laravel->make($class)
			->setContainer($this->laravel)
			->setCommand($this);
	}

}
