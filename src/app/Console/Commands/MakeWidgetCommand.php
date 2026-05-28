<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeWidgetCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:widget
        {id_base? : The id base of the widget.}
        {--view : Create view files for this widget}';

	protected $description = 'Create a new widget. | Eg: php artisan make:widget custom_widget --view';

	protected $help = 'This command allows you to create a widget.';

	public function handle() {
		/**
		 * ---
		 * Funcs.
		 * ---
		 */
		$this->funcs = $this->getLaravel()->make("funcs");
		$mainPath    = $this->funcs->mainPath;

		/**
		 * ---
		 * Khai báo, hỏi và kiểm tra.
		 * ---
		 */
		$id_base = $this->argument('id_base');

		// Nếu không khai báo, hãy hỏi.
		if (!$id_base) {
			$id_base = $this->ask('Please enter the id base of the widget (Eg: custom_widget)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($id_base)) {
				$this->error('Missing id base for the widget. Please try again.');
				exit;
			}

			// Nếu có câu trả lời, hãy tiếp tục hỏi.
			$createView = $this->confirm('Do you want to create view files for this widget?', false);
		}

		// Kiểm tra chuỗi hợp lệ.
		$this->validateClassName($id_base);

		// Chuẩn bị thêm các biến để sử dụng.
		$createView  = $createView ?? $this->option('view');

		// Kiểm tra tồn tại.
		$classPath = $mainPath . '/app/WordPress/Widgets/' . $id_base . '.php';
		$formViewPath  = $mainPath . '/resources/views/widgets/' . $id_base . '/form.blade.php';
		$widgetViewPath  = $mainPath . '/resources/views/widgets/' . $id_base . '/widget.blade.php';

		if (File::exists($classPath)) {
			$this->error('Widget: "' . $id_base . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class & Views.
		 * ---
		 */
		if ($createView) {
			$formView = File::get(__DIR__ . '/../Views/Widgets/form.view');
			$formView = str_replace(
				['{{ id_base }}', '{{ name }}'],
				[$id_base, $id_base],
				$formView
			);

			$widgetView = File::get(__DIR__ . '/../Views/Widgets/widget.view');
			$widgetView = str_replace(
				['{{ id_base }}', '{{ name }}'],
				[$id_base, $id_base],
				$widgetView
			);

			File::ensureDirectoryExists(dirname($formViewPath));
			File::ensureDirectoryExists(dirname($widgetViewPath));

			File::put($formViewPath, $formView);
			File::put($widgetViewPath, $widgetView);

			$stub = File::get(__DIR__ . '/../Stubs/Widgets/widget-view.stub');
		}
		else {
			$stub = File::get(__DIR__ . '/../Stubs/Widgets/widget.stub');
		}

		$stub = str_replace(
			['{{ className }}', '{{ id_base }}', '{{ name }}'],
			[$id_base, $id_base, $id_base],
			$stub
		);

		$stub = $this->replaceNamespaces($stub);

		File::ensureDirectoryExists(dirname($classPath));
		File::put($classPath, $stub);

		/**
		 * ---
		 * Function.
		 * ---
		 */
		$func = File::get(__DIR__ . '/../Funcs/Widgets/widget.func');
		$func = str_replace(
			['{{ id_base }}', '{{ name }}'],
			[$id_base, $id_base],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/Widgets/widget.use');
		$use = str_replace(
			['{{ id_base }}', '{{ name }}'],
			[$id_base, $id_base],
			$use
		);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('Widgets', 'widgets', $func, $use);


		// Done.
		$this->info('Created new widget: "' . $id_base . '"');

		exit;
	}

}
