<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeAPICommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:api
        {path? : The path of the API endpoint.}
        {--method= : The method of the API endpoint.}
        {--namespace= : The namespace of the API endpoint.}
        {--ver= : The version of the API endpoint.}';

	protected $description = 'Create a new API endpoint. | Eg: php artisan make:api my-api-endpoint --method=POST --namespace=wpsp --ver=v1';

	public function handle() {
		/**
		 * ---
		 * Funcs.
		 * ---
		 */
		$this->funcs = $this->getLaravel()->make('funcs');

		/**
		 * ---
		 * Khai báo, hỏi và kiểm tra.
		 * ---
		 */
		$path = $this->argument('path');

		// Nếu không khai báo, hãy hỏi.
		if (!$path) {
			$path = $this->ask('Please enter the path of the API endpoint (Eg: custom-endpoint)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($path)) {
				$this->error('Missing path for the API endpoint. Please try again.');
				exit;
			}

			// Nếu có câu trả lời, hãy tiếp tục hỏi.
			$method    = $this->ask('Please enter the method of the API endpoint (Eg: GET, POST or get, post,...)', 'GET');
			$namespace = $this->ask('Please enter the namespace of the API endpoint (Eg: wpsp, custom-namespace,...', $this->funcs->_getAppShortName());
			$version   = $this->ask('Please enter the version of the API endpoint (Eg: v1, v2,...)', 'v1');
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateSlug($path, 'path');

		// Chuẩn bị thêm các biến để sử dụng.
		$name      = Str::slug(str_replace('-', '_', $path), '_');
		$method    = strtolower($method ?? $this->option('method') ?: 'GET');
		$namespace = $namespace ?? $this->option('namespace') ?: null;
		$version   = $version ?? $this->option('ver') ?: null;

		// Không cần validate "name", vì command này yêu cầu "path" mà path có thể chứa "-".
		// $name sẽ được slugify từ "path" ra.

		/**
		 * ---
		 * Function.
		 * ---
		 */
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
			['{{ name }}', '{{ path }}', '{{ method }}', '{{ namespace }}', '{{ version }}'],
			[$name, $path, $method, $namespace, $version],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/APIs/api.use');
		$use = str_replace(
			['{{ name }}', '{{ path }}', '{{ method }}', '{{ namespace }}', '{{ version }}'],
			[$name, $path, $method, $namespace, $version],
			$use
		);

		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('Apis', 'apis', $func, $use);

		// Done.
		$this->info("Created new API endpoint: {$path}");

		exit;
	}

}
