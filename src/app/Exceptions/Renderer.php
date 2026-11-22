<?php

namespace WPSPCORE\App\Exceptions;

use Illuminate\Http\Request;
use WPSPCORE\App\Routes\RouteManager;
use WPSPCORE\Funcs;

class Renderer extends \Illuminate\Foundation\Exceptions\Renderer\Renderer {

	/**
	 * Render the given exception as an HTML string.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Throwable               $throwable
	 *
	 * @return string
	 */
	public function render(Request $request, \Throwable $throwable, ?Funcs $funcs = null, ?RouteManager $routeManager = null) {
		$flattenException = $this->bladeMapper->map(
			$this->htmlErrorRenderer->render($throwable),
		);

		$exception = new Exception($flattenException, $request, $this->listener, $this->basePath, $routeManager);

		$exceptionAsMarkdown = $this->viewFactory->make('laravel-exceptions-renderer::markdown', [
			'funcs'     => $funcs,
			'exception' => $exception,
		])->render();

		return $this->viewFactory->make('laravel-exceptions-renderer::show', [
			'funcs'               => $funcs,
			'exception'           => $exception,
			'exceptionAsMarkdown' => $exceptionAsMarkdown,
		])->render();
	}

}
