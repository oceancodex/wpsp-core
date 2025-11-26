<?php
namespace WPSPCORE\Exceptions;

use WPSPCORE\Base\BaseInstances;

class Handler extends BaseInstances {

	public $dontReport = [];
	public $dontFlash  = [
		'current_password',
		'password',
		'password_confirmation',
	];

	protected $existsExceptionHandler = null;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv, $extraParams);
		$this->existsExceptionHandler = $extraParams['exists_exception_handler'] ?? null;
	}

	/*
	 *
	 */

	public function render(\Throwable $e) {
		// Kiểm tra xem exception có method render() không
		if (method_exists($e, 'render')) {
			try {
				$result = $e->render();

				// Nếu render() trả về giá trị hoặc đã echo, return
				if ($result !== null) {
					echo $result;
					exit;
				}

				// Nếu render() đã echo và exit, code sẽ không chạy đến đây
				return;
			}
			catch (\Throwable $renderException) {
				// Nếu render() gặp lỗi, fallback sang Ignition
				$this->fallbackToIgnition($e);
			}
		}
	}

	public function report(\Throwable $e) {
		if ($this->shouldntReport($e)) {
			return;
		}

		if (method_exists($e, 'report')) {
			return $e->report();
		}
	}

	/*
	 *
	 */

	public function register() {
		//
	}

	public function shouldReport(\Throwable $e) {
		return !$this->shouldntReport($e);
	}

	public function shouldntReport(\Throwable $e) {
		foreach ($this->dontReport as $type) {
			if ($e instanceof $type) {
				return true;
			}
		}

		return false;
	}

	public function wantsJson() {
		return $this->expectsJson();
	}

	public function expectsJson() {
		return static::$funcs->_expectsJson();
	}

	public function prepareResponse(\Throwable $e) {
		if ($this->expectsJson()) {
			$this->prepareJsonResponse($e);
			exit;
		}

		wp_die(
			'<h1>ERROR: 500</h1><p>' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . '</p>',
			'ERROR: 500',
			[
				'response'  => 500,
				'back_link' => true,
			]
		);
	}

	public function prepareJsonResponse(\Throwable $e) {
		$data = ['message' => $e->getMessage()];

		if (static::$funcs->env('APP_DEBUG', true) == 'true') {
			$data['exception'] = get_class($e);
			$data['file']      = $e->getFile();
			$data['line']      = $e->getLine();
			$data['trace']     = $e->getTrace();
		}

		wp_send_json($data, 500);
		exit;
	}

	public function redirectBack($params = []) {
		$redirectUrl = wp_get_raw_referer() ?: admin_url();

		foreach ($params as $key => $value) {
			$redirectUrl = add_query_arg($key, $value, $redirectUrl);
		}

		wp_safe_redirect($redirectUrl);
		exit;
	}

	public function fallbackToIgnition(\Throwable $e) {
		$app = static::$funcs->getApplication();

		// 1) Nếu Laravel 12+ Renderer class tồn tại và container có thể make nó
		if (class_exists(\Illuminate\Foundation\Exceptions\Renderer\Renderer::class) && $app && $app->bound(\Illuminate\Foundation\Exceptions\Renderer\Renderer::class)) {
			try {
				// Resolve request (nếu không có, tự tạo Request::capture())
				$request = null;
				if ($app->bound('request')) {
					$request = $app->make('request');
				}
				else {
					$request = \Illuminate\Http\Request::capture();
				}

				// Ask container to build Renderer with its dependencies
				$renderer = $app->make(\Illuminate\Foundation\Exceptions\Renderer\Renderer::class);

				// render() expects (Request, Throwable) and returns a Symfony Response or string
				$response = $renderer->render($request, $e);

				// If response is a Response object, getContent()
				if (is_object($response) && method_exists($response, 'getContent')) {
					$content = $response->getContent();
					$status  = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 500;

					if (trim((string)$content) !== '') {
						http_response_code($status);
						echo $content;
						exit;
					}
					else {
						// Log for debugging why renderer returned empty
						error_log('[WPSP] Renderer returned empty content for exception: ' . get_class($e) . ': ' . $e->getMessage());
					}
				}
				// If it's a string, print it
				elseif (is_string($response) && trim($response) !== '') {
					echo $response;
					exit;
				}
			}
			catch (\Throwable $renderEx) {
				// Log renderer failure then continue to fallback
				error_log('[WPSP] Renderer threw: ' . $renderEx->getMessage());
			}
		}

		// 2) Nếu có Ignition -> dùng Ignition
		if (class_exists('\Spatie\LaravelIgnition\Ignition')) {
			try {
				\Spatie\LaravelIgnition\Ignition::make()
					->shouldDisplayException(true)
					->register()
					->renderException($e);
				exit;
			}
			catch (\Throwable $ignEx) {
				error_log('[WPSP] Ignition threw: ' . $ignEx->getMessage());
				// fallthrough
			}
		}

		// 3) Nếu tồn tại handler trước đó thì gọi lại
		if ($this->existsExceptionHandler && is_callable($this->existsExceptionHandler)) {
			try {
				call_user_func($this->existsExceptionHandler, $e);
				return;
			}
			catch (\Throwable $hEx) {
				error_log('[WPSP] Previous exception handler threw: ' . $hEx->getMessage());
			}
		}

		// 4) Cuối cùng fallback về prepareResponse (wp_die / json)
		$this->prepareResponse($e);
	}


	/*
	 *
	 */

	protected function handleAuthenticationException(\Throwable $e) {
		status_header(401);

		$message    = $e->getMessage();
		$guards     = method_exists($e, 'guards') ? $e->guards() : [];
		$redirectTo = method_exists($e, 'redirectTo') ? $e->redirectTo() : null;

		/**
		 * Với request AJAX hoặc REST API.
		 */

		if ($this->wantsJson()) {
			wp_send_json([
				'success' => false,
				'data'    => null,
				'errors'  => [
					[
						'type'   => 'AuthenticationException',
						'guards' => $guards,
					],
				],
				'message' => $message,
			], 401);
			exit;
		}

		/**
		 * Với request thông thường.
		 */

		// Redirect.
		if ($redirectTo) {
			wp_redirect($redirectTo);
			exit;
		}

		// Sử dụng view.
		try {
			echo static::$funcs->view('errors.401', [
				'message' => $message,
			]);
			exit;
		}
		catch (\Throwable $viewException) {
		}

		// Sử dụng wp_die.
		wp_die(
			'<h1>ERROR: 401 - Chưa xác thực</h1><p>' . $message . '</p>',
			'ERROR: 401 - Chưa xác thực',
			[
				'response'  => 401,
				'back_link' => true,
			]
		);
	}

	protected function handleAuthorizationException(\Throwable $e) {
		status_header(403);

		$message = $e->getMessage();

		/**
		 * Với request AJAX hoặc REST API.
		 */
		if ($this->wantsJson()) {
			wp_send_json([
				'success' => false,
				'data'    => null,
				'errors'  => [
					[
						'type' => 'AuthorizationException',
					],
				],
				'message' => $message,
			], 403);
			exit;
		}

		/**
		 * Với request thông thường.
		 */

		// Sử dụng view.
		try {
			echo static::$funcs->view('errors.403', [
				'message' => $message,
			]);
			exit;
		}
		catch (\Throwable $viewException) {
		}

		// Sử dụng wp_die.
		wp_die(
			'<h1>ERROR: 403 - Truy cập bị từ chối</h1><p>' . $message . '</p>',
			'ERROR: 403 - Truy cập bị từ chối',
			[
				'response'  => 403,
				'back_link' => true,
			]
		);
	}

	protected function handleHttpException(\Throwable $e) {
		$statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
		$message    = $e->getMessage();
		$headers    = method_exists($e, 'getHeaders') ? $e->getHeaders() : [];

		status_header($statusCode);

		// Set headers bổ sung.
		foreach ($headers as $key => $value) {
			if (!headers_sent()) {
				header("{$key}: {$value}");
			}
		}

		/**
		 * Với request AJAX hoặc REST API.
		 */

		if ($this->wantsJson()) {
			wp_send_json([
				'success' => false,
				'data'    => null,
				'errors'  => [
					[
						'type' => 'HttpException',
					],
				],
				'message' => $message,
			], $statusCode);
			exit;
		}

		/**
		 * Với request thông thường.
		 */

		// Sử dụng view.
		try {
			$viewName     = "errors.{$statusCode}";
			$viewInstance = static::$funcs->_viewInstance();

			if ($viewInstance->exists($viewName)) {
				echo static::$funcs->view($viewName, [
					'message' => $message,
					'code'    => $statusCode,
					'status'  => 'Lỗi HTTP',
				]);
				exit;
			}

			if ($viewInstance->exists('errors.default')) {
				echo static::$funcs->view('errors.default', [
					'message' => $message,
					'code'    => $statusCode,
					'status'  => 'Lỗi HTTP',
				]);
				exit;
			}
		}
		catch (\Throwable $viewException) {
		}

		// Sử dụng wp_die.
		wp_die(
			'<h1>ERROR: ' . $statusCode . ' - Lỗi HTTP</h1><p>' . $message . '</p>',
			'ERROR: ' . $statusCode . ' - Lỗi HTTP',
			[
				'response'  => $statusCode,
				'back_link' => true,
			]
		);
	}

	protected function handleValidationException(\Throwable $e) {
		status_header(422);

		/**
		 * Với request AJAX hoặc REST API.
		 */
		if ($this->wantsJson()) {
			wp_send_json([
				'success' => false,
				'data'    => null,
				'errors'  => $e->validator->errors()->messages(),
				'message' => $e->getMessage(),
			], 422);
			exit;
		}

		/**
		 * Với request thông thường.
		 */

		// Debug mode.
		if (static::$funcs->_isDebug()) {
			$this->fallbackToIgnition($e);
		}

		// Production mode.
		else {
			// Lấy danh sách lỗi.
			$errors = $e->validator->errors()->all();

			// Tạo danh sách lỗi HTML.
			$errorList = '<ul>';
			foreach ($errors as $error) {
				$errorList .= '<li>' . $error . '</li>';
			}
			$errorList .= '</ul>';

			// Sử dụng view.
			try {
				echo static::$funcs->view('errors.default', [
					'message'      => 'Vui lòng kiểm tra lại dữ liệu theo thông tin bên dưới:',
					'code'         => 422,
					'errorMessage' => $errorList,
					'status'       => 'Dữ liệu không hợp lệ',
				]);
				exit;
			}
			catch (\Throwable $viewException) {
			}

			// Sử dụng wp_die.
			wp_die(
				'<h1>ERROR: 422 - Dữ liệu không hợp lệ</h1><p>' . $errorList . '</p>',
				'ERROR: 422 - Dữ liệu không hợp lệ',
				[
					'response'  => 422,
					'back_link' => true,
				]
			);
		}
	}

	protected function handleModelNotFoundException(\Throwable $e) {
		status_header(404);

		$message   = $e->getMessage();
		$modelName = method_exists($e, 'getModelName') ? $e->getModelName() : null;

		/**
		 * Với request AJAX hoặc REST API.
		 */
		if ($this->wantsJson()) {
			wp_send_json([
				'success' => false,
				'data'    => null,
				'errors'  => [
					[
						'type'  => 'ModelNotFoundException',
						'model' => $modelName,
					],
				],
				'message' => $message,
			], 404);
			exit;
		}

		/**
		 * Với request thông thường.
		 */

		// Sử dụng view.
		try {
			echo static::$funcs->view('errors.model-not-found', [
				'message' => $message,
				'model'   => $modelName,
			]);
			exit;
		}
		catch (\Throwable $viewException) {
		}

		// Sử dụng wp_die.
		wp_die(
			'<h1>ERROR: 404 - Không tìm thấy bản ghi</h1><p>' . esc_html($message) . '</p>',
			'ERROR: 404 - Không tìm thấy bản ghi',
			[
				'response'  => 404,
				'back_link' => true,
			]
		);
	}

	protected function handleQueryException(\Throwable $e) {
		status_header(500);

		global $wpdb;

		$message  = $e->getMessage();
		$sql      = method_exists($e, 'getSql') ? $e->getSql() : null;
		$bindings = method_exists($e, 'getBindings') ? $e->getBindings() : [];

		/**
		 * Với request AJAX hoặc REST API.
		 */
		if ($this->wantsJson()) {

			// Debug mode.
			if (static::$funcs->isDebug()) {
				wp_send_json([
					'success' => false,
					'data'    => null,
					'errors'  => [
						[
							'type'     => 'QueryException',
							'sql'      => $sql,
							'bindings' => $bindings,
							'error'    => $wpdb->last_error ?? null,
						],
					],
					'message' => $message,
				], 500);
			}

			// Production mode.
			else {
				wp_send_json([
					'success' => false,
					'data'    => null,
					'errors'  => [
						[
							'type'  => 'QueryException',
							'error' => $wpdb->last_error ?? null,
						],
					],
					'message' => $message,
				], 500);
			}

			exit;
		}

		/**
		 * Với request thông thường.
		 */

		// Debug mode.
		if (static::$funcs->isDebug()) {
			// Sử dụng view.
			try {
				echo static::$funcs->view('errors.query', [
					'message'  => $message,
					'sql'      => $sql ?? null,
					'bindings' => $bindings ?? [],
					'error'    => $wpdb->last_error ?? null,
				]);
				exit;
			}
			catch (\Throwable $viewException) {
			}

			// Sử dụng wp_die.
			wp_die(
				'<h1>ERROR: 500 - Lỗi truy vấn cơ sở dữ liệu</h1><p>' . $message . '</p>',
				'ERROR: 500 - Lỗi truy vấn cơ sở dữ liệu',
				[
					'response'  => 500,
					'back_link' => true,
				]
			);
		}

		// Production mode.
		else {
			// Sử dụng view.
			try {
				echo static::$funcs->view('errors.query', [
					'message' => $message,
					'error'   => $wpdb->last_error ?? null,
				]);
				exit;
			}
			catch (\Throwable $viewException) {
			}

			// Sử dụng wp_die.
			wp_die(
				'<h1>ERROR: 500 - Lỗi truy vấn cơ sở dữ liệu</h1><p>' . $message . '</p>',
				'ERROR: 500 - Lỗi truy vấn cơ sở dữ liệu',
				[
					'response'  => 500,
					'back_link' => true,
				]
			);
		}
	}

}