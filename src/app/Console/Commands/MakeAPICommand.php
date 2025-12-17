<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeAPICommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:api
        {path? : The path of the API end point.}
        {--method= : The method of the API end point.}
        {--namespace= : The namespace of the API end point.}
        {--ver= : The version of the API end point.}';

	protected $description = 'Create a new API end point.               | Eg: bin/wpsp make:api my-api-endpoint --method=POST --namespace=wpsp --ver=v1';

	public function handle() {
		$this->funcs = $this->getLaravel()->make('funcs');

		$path = $this->argument('path');

		// Ask interactively if missing
		if (!$path) {
			$path = $this->ask('Please enter the path of the API end point');

			$method    = $this->ask('Please enter the method of the API end point (blank is "GET")');
			$namespace = $this->ask('Please enter the namespace of the API end point (blank is "' . $this->funcs->_getAppShortName() . '")');
			$version   = $this->ask('Please enter the version of the API end point (blank is "v1")');

			if (empty($path)) {
				$this->error('Missing path for the API end point. Please try again.');
				exit;
			}
		}
		else {
			$method    = $this->option('method');
			$namespace = $this->option('namespace');
			$version   = $this->option('ver');
		}

		// Normalize
		$pathSlugify = Str::slug($path);
		$name        = $path;
		$nameSlugify = Str::slug($name, '_');

		$method    = strtolower($method ?: '');
		$namespace = $namespace ?: null;
		$version   = $version ?: null;

		// FUNC template
		if ($namespace) {
			if ($version) {
				$func = File::get(__DIR__ . '/../Funcs/APIs/api-namespace-version.func');
			}
			else {
				$func = File::get(__DIR__ . '/../Funcs/APIs/api-namespace.func');
			}
		}
		else {
			$func = File::get(__DIR__ . '/../Funcs/APIs/api.func');
		}
		$func = str_replace(
			['{{ name }}', '{{ name_slugify }}', '{{ path }}', '{{ path_slugify }}', '{{ method }}', '{{ namespace }}', '{{ version }}'],
			[$name, $nameSlugify, $path, $pathSlugify, $method, $namespace, $version],
			$func
		);

		// USE template
		$use = File::get(__DIR__ . '/../Uses/APIs/api.use');
		$use = str_replace(
			['{{ name }}', '{{ name_slugify }}', '{{ path }}', '{{ path_slugify }}', '{{ method }}', '{{ namespace }}', '{{ version }}'],
			[$name, $nameSlugify, $path, $pathSlugify, $method, $namespace, $version],
			$use
		);

		$use = $this->replaceNamespaces($use);

		// Add to route list
		$this->addClassToRoute('Apis', 'apis', $func, $use);

		// Done
		$this->info("Created new API end point: {$path}");

		exit;
	}

}
