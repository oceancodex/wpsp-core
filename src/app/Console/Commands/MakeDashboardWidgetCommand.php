<?php

namespace WPSPCORE\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use WPSPCORE\App\Console\Traits\CommandsTrait;

class MakeDashboardWidgetCommand extends Command {

	use CommandsTrait;

	protected $signature = 'make:dashboard-widget
        {widget_id? : The id of the dashboard widget.}
        {--view : Create view files for this dashboard widget}';

	protected $description = 'Create a new dashboard widget. | Eg: php artisan make:dashboard-widget custom_dashboard_widget --view';

	protected $help = 'This command allows you to create a dashboard widget.';

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
		$widget_id = $this->argument('widget_id');

		// Nếu không khai báo, hãy hỏi.
		if (!$widget_id) {
			$widget_id = $this->ask('Please enter the id of the dashboard widget (Eg: custom_dashboard_widget)');

			// Nếu không có câu trả lời, hãy thoát.
			if (empty($widget_id)) {
				$this->error('Missing id for the dashboard widget. Please try again.');
				exit;
			}

			// Nếu có câu trả lời, hãy tiếp tục hỏi.
			$createView = $this->confirm('Do you want to create view files for this dashboard widget?', false);
		}

		// Chuẩn bị thêm các biến để sử dụng.
		$className  = preg_replace('/[^A-Za-z0-9_]/', '_', $widget_id);
		$createView = $createView ?? $this->option('view');

		// Kiểm tra chuỗi hợp lệ.
//		$this->validateClassName($className);

		// Kiểm tra tồn tại.
		$classPath      = $mainPath . '/app/WordPress/DashboardWidgets/' . $className . '.php';
		$widgetViewPath = $mainPath . '/resources/views/dashboard-widgets/' . $widget_id . '.blade.php';

		if (File::exists($classPath)) {
			$this->error('Widget: "' . $widget_id . '" already exists! Please try again.');
			exit;
		}

		/**
		 * ---
		 * Class & Views.
		 * ---
		 */
		if ($createView) {
			$widgetView = File::get(__DIR__ . '/../Views/DashboardWidgets/dashboard-widget.view');
			$widgetView = str_replace(
				['{{ class_name }}', '{{ widget_id }}'],
				[$className, $widget_id],
				$widgetView
			);

			File::ensureDirectoryExists(dirname($widgetViewPath));
			File::put($widgetViewPath, $widgetView);

			$stub = File::get(__DIR__ . '/../Stubs/DashboardWidgets/dashboard-widget-view.stub');
		}
		else {
			$stub = File::get(__DIR__ . '/../Stubs/DashboardWidgets/dashboard-widget.stub');
		}

		$stub = str_replace(
			['{{ class_name }}', '{{ widget_id }}'],
			[$className, $widget_id],
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
		$func = File::get(__DIR__ . '/../Funcs/DashboardWidgets/dashboard-widget.func');
		$func = str_replace(
			['{{ class_name }}', '{{ widget_id }}'],
			[$className, $widget_id],
			$func
		);

		/**
		 * ---
		 * Use.
		 * ---
		 */
		$use = File::get(__DIR__ . '/../Uses/DashboardWidgets/dashboard-widget.use');
		$use = str_replace(
			['{{ class_name }}', '{{ widget_id }}'],
			[$className, $widget_id],
			$use
		);
		$use = $this->replaceNamespaces($use);

		/**
		 * ---
		 * Thêm class vào route.
		 * ---
		 */
		$this->addClassToRoute('DashboardWidgets', 'dashboard_widgets', $func, $use);


		// Done.
		$this->info('Created new dashboard widget: "' . $widget_id . '"');

		exit;
	}

}
