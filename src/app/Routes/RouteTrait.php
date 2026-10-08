<?php

namespace WPSPCORE\App\Routes;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\Response;

trait RouteTrait {
	/*
	 * =====================================================================
	 * MIDDLEWARE
	 * =====================================================================
	 */

	/**
	 * Kiểm tra middleware hiện tại có phải là middleware cuối cùng trong pipeline hay không.
	 *
	 * Hàm chỉ xét các phần tử có key dạng số trong danh sách middleware,
	 * bỏ qua các phần tử cấu hình khác có key dạng chuỗi.
	 *
	 * Middleware được coi là middleware cuối cùng khi phần tử cuối cùng
	 * trong danh sách có dạng:
	 *
	 * [
	 *     TênClassMiddleware::class,
	 *     'handle'
	 * ]
	 *
	 * và tên class trùng với giá trị của tham số $currentClass.
	 *
	 * @param string $currentClass   Tên class middleware cần kiểm tra.
	 * @param mixed  $allMiddlewares Danh sách middleware của pipeline.
	 *
	 * @return bool
	 */
	public function isLastMiddleware($currentClass, $allMiddlewares) {
		if (!is_array($allMiddlewares)) {
			return false;
		}

		// Lọc chỉ lấy key dạng số (0,1,2...)
		$middlewares = array_filter($allMiddlewares, 'is_int', ARRAY_FILTER_USE_KEY);

		if (empty($middlewares)) {
			return false;
		}

		// Lấy phần tử cuối cùng
		$last = end($middlewares);

		// dạng: [ 'ClassName', 'handle' ]
		return is_array($last) && isset($last[0]) && $last[0] === $currentClass;
	}

	/**
	 * Kiểm tra xem route hiện tại có vượt qua toàn bộ middleware hay không.
	 *
	 * Middleware được tổ chức thành nhiều "block middleware".
	 * Mỗi block có thể chứa một hoặc nhiều middleware và có thể định nghĩa
	 * quan hệ đánh giá thông qua key `relation`:
	 *
	 * - AND: tất cả middleware trong block phải PASS.
	 * - OR : chỉ cần một middleware trong block PASS.
	 *
	 * Route chỉ được coi là PASS khi tất cả các block middleware đều PASS.
	 *
	 * Middleware hỗ trợ các định dạng:
	 *
	 * - Closure                       → function($request, $next, $args)
	 * - ClassName::class
	 * - [ClassName::class, 'method']
	 * - 'throttle:60,1' / ['throttle:api']
	 *
	 * Giá trị trả về của middleware:
	 *
	 * - true / false                        : PASS / FAIL
	 * - \WP_Error                           : FAIL
	 * - Response status >= 400              : FAIL
	 * - Response 3xx mà KHÔNG gọi $next     : FAIL (middleware đang chặn + redirect, vd: redirect login)
	 * - Response còn lại / null             : PASS
	 *
	 * Thông tin block hiện tại sẽ được truyền vào `$args['current_block_middleware']`.
	 *
	 * Ví dụ:
	 *
	 * [
	 *     [
	 *         'relation' => 'AND',
	 *         AuthMiddleware::class,
	 *         VerifiedMiddleware::class,
	 *     ],
	 *     [
	 *         'relation' => 'OR',
	 *         AdminMiddleware::class,
	 *         ManagerMiddleware::class,
	 *     ],
	 * ]
	 *
	 * Trong ví dụ trên:
	 * - AuthMiddleware và VerifiedMiddleware đều phải PASS.
	 * - AdminMiddleware hoặc ManagerMiddleware chỉ cần một PASS.
	 *
	 * @param array $middlewares Danh sách middleware block cần kiểm tra.
	 * @param mixed $request     Request hiện tại. Nếu null sẽ tự động lấy từ container.
	 * @param array $args        Dữ liệu bổ sung được truyền vào middleware.
	 *
	 * @return bool Trả về true nếu toàn bộ middleware đều PASS, ngược lại false.
	 */
	public function isPassedMiddleware($middlewares = [], $request = null, $args = []) {
		// Không có middleware → pass
		if (empty($middlewares)) {
			return true;
		}

		$app     = $this->currentApp();
		$request = $request ?? $this->resolveCurrentRequest();
		$args    = (array)$args;

		foreach ($middlewares as $blockMiddleware) {
			$passed = is_array($blockMiddleware)
				? $this->evaluateMiddlewareBlock($blockMiddleware, $args, $request, $app)
				: $this->evaluateMiddlewareNode($blockMiddleware, $args, $request, $app); // top-level dạng string / Closure

			if (!$passed) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Đánh giá 1 node: middleware lá hoặc block con (mảng có key 'relation').
	 */
	protected function evaluateMiddlewareNode($node, array $args, $request, $app): bool {
		if (is_array($node) && array_key_exists('relation', $node)) {
			return $this->evaluateMiddlewareBlock($node, $args, $request, $app);
		}

		$mw = $this->normalizeMiddlewareLeaf($node, $args);

		// Dạng không hợp lệ → bỏ qua, coi như PASS để không chặn nhầm route.
		if ($mw === null) {
			return true;
		}

		return $this->runMiddlewareLeaf($mw, $request, $app);
	}

	/**
	 * Đánh giá 1 block theo relation (mặc định AND).
	 */
	protected function evaluateMiddlewareBlock(array $block, array $args, $request, $app): bool {
		$relation = strtoupper((string)($block['relation'] ?? 'AND'));
		unset($block['relation']);

		if (empty($block)) {
			return true;
		}

		$args['current_block_middleware'] = $block;

		if ($relation === 'OR') {
			foreach ($block as $child) {
				if ($this->evaluateMiddlewareNode($child, $args, $request, $app)) return true;
			}
			return false;
		}

		// AND
		foreach ($block as $child) {
			if (!$this->evaluateMiddlewareNode($child, $args, $request, $app)) return false;
		}
		return true;
	}

	/**
	 * Chuẩn hoá 1 middleware "lá" (Closure / string / [class, method]) thành dạng runtime.
	 */
	protected function normalizeMiddlewareLeaf($mw, array $args): ?array {
		if ($mw instanceof \Closure) {
			return ['type' => 'closure', 'closure' => $mw, 'args' => $args];
		}

		if (is_array($mw) && isset($mw[0]) && is_string($mw[0])) {
			if (str_starts_with($mw[0], 'throttle')) {
				return ['type' => 'throttle', 'value' => $mw[0], 'args' => $args];
			}
			return ['type' => 'class', 'class' => $mw[0], 'method' => $mw[1] ?? 'handle', 'args' => $args];
		}

		if (is_string($mw)) {
			if (str_starts_with($mw, 'throttle')) {
				return ['type' => 'throttle', 'value' => $mw, 'args' => $args];
			}
			return ['type' => 'class', 'class' => $mw, 'method' => 'handle', 'args' => $args];
		}

		return null;
	}

	/**
	 * Chạy 1 middleware đã normalize, trả về true (PASS) / false (FAIL).
	 */
	protected function runMiddlewareLeaf(array $mw, $request, $app): bool {
		if ($mw['type'] === 'throttle') {
			return $this->runThrottleMiddleware($mw['value'], $request, $app);
		}

		// Theo dõi middleware có gọi $next hay không → phân biệt "cho qua" và "chặn + redirect".
		$nextCalled = false;
		$next       = function() use (&$nextCalled) {
			$nextCalled = true;
			return new Response('OK', 200);
		};

		if ($mw['type'] === 'closure') {
			// Tham số thừa được PHP bỏ qua nếu closure không khai báo $args.
			$res = ($mw['closure'])($request, $next, $mw['args']);
		}
		else {
			$class = $mw['class'];

			if (!class_exists($class)) {
				return false;
			}

			$instance = ($app && method_exists($app, 'make'))
				? $app->make($class)
				: $this->manualMakeClass($class);

			$method = method_exists($instance, $mw['method']) ? $mw['method'] : 'handle';
			$params = [
				'request' => $request,
				'next'    => $next,
				'args'    => $mw['args'] ?? null,
			];

			$res = ($app && method_exists($app, 'call'))
				? $app->call([$instance, $method], $params)
				: $this->manualResolveAndCall([$instance, $method], $params);
		}

		return $this->isPassedResult($res, $nextCalled);
	}

	/**
	 * Quy đổi kết quả trả về của middleware thành PASS (true) / FAIL (false).
	 */
	protected function isPassedResult($res, bool $nextCalled = true): bool {
		if (is_bool($res)) {
			return $res;
		}

		if ($res instanceof \WP_Error) {
			return false;
		}

		if ($res instanceof Response) {
			if ($res->getStatusCode() >= 400) {
				return false;
			}

			// Redirect mà không đi qua $next = middleware đang chặn request.
			if ($res->isRedirection() && !$nextCalled) {
				return false;
			}

			return true;
		}

		return true;
	}

	/**
	 * Chạy ThrottleRequests của Laravel.\
	 * Mỗi signature (vd: 'throttle:60,1') chỉ hit limiter đúng 1 lần / request,
	 * kết quả được cache trong request attributes (đúng vòng đời request, không phụ thuộc closure).
	 */
	protected function runThrottleMiddleware(string $value, $request, $app): bool {
		$cacheKey = '_wpsp_throttle.' . $value;

		if ($request && $request->attributes->has($cacheKey)) {
			return (bool)$request->attributes->get($cacheKey);
		}

		$parts      = explode(':', $value, 2);
		$parameters = (isset($parts[1]) && $parts[1] !== '') ? explode(',', $parts[1]) : [];

		try {
			/** @var ThrottleRequests $middleware */
			$middleware = $app->make(ThrottleRequests::class);
			$response   = $middleware->handle($request, fn() => new Response('OK', 200), ...$parameters);

			// Header X-RateLimit-* được Laravel gắn vào response giả → chuyển ra response thật.
			if ($response instanceof Response) {
				$this->forwardResponseHeaders($response, ['X-RateLimit-Limit', 'X-RateLimit-Remaining']);
			}

			$passed = $this->isPassedResult($response);
		}
		catch (ThrottleRequestsException $e) {
			$this->abortWithStatus($e->getMessage(), 429, '429 - Too Many Requests.', $e->getHeaders());
		}
		catch (\Throwable $e) {
			$this->abortWithStatus($this->safeErrorMessage($e), 500, '500 - Internal Server Error.');
		}

		$request?->attributes->set($cacheKey, $passed);

		return $passed;
	}

	/*
	 * =====================================================================
	 * HTTP HELPERS
	 * =====================================================================
	 */

	/**
	 * Dừng request với status code: JSON nếu client muốn JSON, ngược lại wp_die().\
	 * Luôn dừng hẳn để không có output nào bị nối thêm sau response lỗi.
	 */
	protected function abortWithStatus(string $message, int $status, string $title, array $headers = []): never {
		if ($this->funcs->_wantsJson()) {
			(new JsonResponse($this->funcs->_response(false, $message, $status), $status, $headers))->send();
			exit;
		}

		if (!headers_sent()) {
			foreach ($headers as $name => $value) {
				header($name . ': ' . (is_array($value) ? implode(', ', $value) : $value));
			}
		}

		wp_die($message, $title, [
			'back_link' => true,
			'response'  => $status,
		]);

		exit;
	}

	/**
	 * Chép một số header từ response Symfony ra output thật.
	 */
	protected function forwardResponseHeaders(Response $response, array $only): void {
		if (headers_sent()) {
			return;
		}

		foreach ($only as $name) {
			if ($response->headers->has($name)) {
				header($name . ': ' . $response->headers->get($name));
			}
		}
	}

	/**
	 * Không lộ message exception nội bộ ra ngoài khi không bật WP_DEBUG.
	 */
	protected function safeErrorMessage(\Throwable $e): string {
		return (defined('WP_DEBUG') && WP_DEBUG) ? $e->getMessage() : 'Internal Server Error.';
	}

	/**
	 * Lấy application/container hiện tại của plugin.
	 *
	 * @return \Illuminate\Foundation\Application|\Illuminate\Container\Container|null
	 */
	protected function currentApp() {
		return method_exists($this->funcs, '_getApplication') ? $this->funcs->_getApplication() : null;
	}

	/**
	 * Lấy request hiện tại: $this->request → container → Request::capture().
	 */
	protected function resolveCurrentRequest() {
		if (!empty($this->request)) {
			return $this->request;
		}

		$app = $this->currentApp();

		return ($app && $app->bound('request')) ? $app->make('request') : Request::capture();
	}

	/*
	 * =====================================================================
	 * CALLBACK PREPARATION
	 * =====================================================================
	 */

	/**
	 * Chuẩn bị callback cho route trước khi thực thi.
	 *
	 * Hỗ trợ các dạng callback:
	 *
	 * - Closure
	 * - [ClassName::class, 'method']
	 *
	 * Nếu callback là Closure, hàm sẽ trả về nguyên bản.
	 * Nếu callback là mảng chứa tên class và method, một instance của class
	 * sẽ được khởi tạo bằng các tham số truyền vào thông qua `$constructParams`,
	 * sau đó trả về dưới dạng callable `[object, method]`.
	 *
	 * Ví dụ:
	 *
	 * prepareRouteCallback(function () {});
	 *
	 * prepareRouteCallback([
	 *     UserController::class,
	 *     'index'
	 * ]);
	 *
	 * Nếu callback không thuộc các định dạng được hỗ trợ,
	 * RuntimeException sẽ được ném ra.
	 *
	 * @param mixed $callback Callback cần chuẩn hóa.
	 * @param array $constructParams Các tham số truyền vào constructor của class.
	 *
	 * @return callable Callback đã được chuẩn hóa và sẵn sàng để thực thi.
	 *
	 * @throws \RuntimeException Khi callback không hợp lệ.
	 */
	public function prepareRouteCallback($callback, $constructParams = []) {
		if ($callback instanceof \Closure) {
			return $callback;
		}

		if (is_array($callback)) {
			$class = new $callback[0](...($constructParams ?? []));
			return [$class, $callback[1] ?? null];
		}

		throw new \RuntimeException("Invalid callback");
	}

	/**
	 * Chuẩn bị callback cho các function đặc biệt, ví dụ: add_menu_page(), add_action()...\
	 * Sử dụng hàm này khi cần gọi "Callback Dependencies Injection" trong các class callback của Route.\
	 * Ví dụ:
	 * - Route::get('/my-page', [MyClass::class, 'myMethod']);
	 *
	 * Lúc này myMethod được gọi với DI tự động.\
	 * Nhưng trong myMethod chúng ta lại muốn gọi tiếp method khác, ví dụ: $this->secondMethod()
	 * Nếu không sử dụng hàm này, thì secondMethod() sẽ không được "Dependencies Injection".
	 *
	 * Closure trả về nhận các đối số WordPress truyền vào (hook args) và map chúng vào signature.
	 */
	public function prepareCallbackFunction($method, $path, $fullPath, $class = null, $args = []): \Closure {
		return function(...$wpParams) use ($method, $path, $fullPath, $class, $args) {
			$requestPath = ltrim($this->request->getRequestUri(), '/\\');

			// Nếu truyền tên class thay vì instance, tự khởi tạo class với DI.
			$targetInstance = $class;
			if (is_string($class) && class_exists($class)) {
				$container      = $this->currentApp();
				$targetInstance = $container ? $container->make($class) : $this->manualMakeClass($class);
			}

			$callback = [$targetInstance ?? $this, $method];

			if (!isset($args['route'])) {
				$args['route'] = $this->extraParams['route'] ?? null;
			}

			$callParams = $this->buildParametersForCallable($callback, $path, $fullPath, $requestPath, $args, $wpParams);

			return $this->resolveAndCall($callback, $callParams);
		};
	}

	/**
	 * Build params for callable (route callback).\
	 * Bao gồm:
	 * - Detect callback type + Reflection signature (có cache)
	 * - Regex route matching / Ajax route compatibility
	 * - Fallback param build khi route không match
	 * - WordPress hook args (add_action / add_filter) theo vị trí
	 * - Regex capture parsing (named + positional)
	 * - Request source aggregation (attributes, POST, GET)
	 * - Primitive param binding
	 * - Eloquent route model binding (resolveRouteBinding → hỗ trợ getRouteKeyName)
	 * - Metadata injection
	 * - Request → route parameter bridging
	 */
	public function getCallParams($path, $fullPath, $requestPath, $callbackOrClass, $method = null, $args = [], $wpParams = []) {
		if ($callbackOrClass instanceof \Closure) {
			$method = null;
		}

		$parameters  = $this->reflectCallable($callbackOrClass, $method)->getParameters();
		$hookArgs    = $this->mapHookArgs($parameters, (array)$wpParams);
		$calledClass = static::class;

		// Path đã là regex pattern (có thể chứa (?P<name>...)) → KHÔNG escape.
		$forceRegex = $args['route']->args['force_regex'] ?? false;
		$regexPath  = $this->funcs->_regexPath($fullPath, $forceRegex);
		$matches    = [];
		$passed     = false;

		// Route "Ajaxs" với method POST → so khớp action.
		if (str_ends_with($calledClass, 'Ajaxs') && $this->request->getMethod() === 'POST') {
			$passed = ($this->request->all()['action'] ?? null) === $fullPath;
		}

		// "Actions" / "Filters" không có request path → luôn coi như đang ở đúng path.
		if (str_ends_with($calledClass, 'Actions') || str_ends_with($calledClass, 'Filters')) {
			$requestPath = $fullPath;
		}

		// Chỉ thực sự bind dữ liệu khi đang truy cập đúng "path" / "fullPath".
		if (
			!empty($regexPath)
			&& (
				@preg_match('#' . $regexPath . '#iu', $requestPath, $matches)
				|| @preg_match('#' . $fullPath . '#iu', $requestPath, $matches)
				|| $fullPath == $requestPath
			)
		) {
			$passed = true;
		}

		$meta = [
			'path'            => $path,
			'path_regex'      => $this->funcs->_regexPath($path),
			'full_path'       => $fullPath,
			'full_path_regex' => $this->funcs->_regexPath($fullPath),
			'request_path'    => $requestPath,
		];

		/*
		 * ----- Route KHÔNG khớp: primitive = null, class để container inject -----
		 */
		if (!$passed) {
			$callParams = [];

			foreach ($parameters as $param) {
				$name = $param->getName();

				if (array_key_exists($name, $hookArgs)) {
					$callParams[$name] = $hookArgs[$name];
					continue;
				}

				if ($className = $this->getClassFromType($param->getType())) {
					$special = $this->resolveSpecialClassParam($name, $className, $method);
					if ($special !== null) {
						$callParams[$name] = $special;
					}
					continue;
				}

				$callParams[$name] = null;
			}

			// Thứ tự ghi đè giữ như bản cũ: params < meta < args.
			return array_merge($callParams, $meta, $args, $this->variadicHookArgs($hookArgs));
		}

		/*
		 * ----- Route khớp: bind đầy đủ -----
		 */
		$baseRequest = $this->resolveCurrentRequest();

		$named      = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
		$positional = array_values(array_filter($matches, fn($k) => is_int($k) && $k > 0, ARRAY_FILTER_USE_KEY));

		$query = $baseRequest->query->all();      // GET
		$post  = $baseRequest->request->all();    // POST / JSON body
		$attr  = $baseRequest->attributes->all(); // attributes

		$callParams = [];
		$posIndex   = 0;

		foreach ($parameters as $param) {
			$name = $param->getName();

			// 0) Hook args của WordPress luôn ưu tiên cao nhất, không urldecode.
			if (array_key_exists($name, $hookArgs)) {
				$callParams[$name] = $hookArgs[$name];
				continue;
			}

			$className = $this->getClassFromType($param->getType());

			if ($className) {
				$special = $this->resolveSpecialClassParam($name, $className, $method);
				if ($special !== null) {
					$callParams[$name] = $special;
					continue;
				}

				// Eloquent route model binding
				if (is_subclass_of($className, Model::class)) {
					$modelId           = $named[$name] ?? $query[$name] ?? $post[$name] ?? $args[$name] ?? null;
					$callParams[$name] = $this->bindEloquentModel($className, $modelId, $param);
				}

				// Các class khác → container inject.
				continue;
			}

			// 1) named capture → 2) attributes → 3) POST → 4) GET → 5) positional → 6) default
			if (array_key_exists($name, $named)) {
				$value = $named[$name];
			}
			elseif (array_key_exists($name, $attr)) {
				$value = $attr[$name];
			}
			elseif (array_key_exists($name, $post)) {
				$value = $post[$name];
			}
			elseif (array_key_exists($name, $query)) {
				$value = $query[$name];
			}
			elseif (isset($positional[$posIndex])) {
				$value = $positional[$posIndex++];
			}
			elseif ($param->isDefaultValueAvailable()) {
				$value = $param->getDefaultValue();
			}
			else {
				$value = null;
			}

			$callParams[$name] = is_string($value) ? urldecode($value) : $value;
		}

		// Meta luôn ghi đè.
		$callParams = array_merge($callParams, $meta);

		// Args chỉ bổ sung, không ghi đè giá trị đã có.
		foreach ($args as $argKey => $argValue) {
			if (!isset($callParams[$argKey])) {
				$callParams[$argKey] = $argValue;
			}
		}

		// Expose toàn bộ named captures (kể cả khi method không khai báo param) cho middleware / log.
		foreach ($named as $k => $v) {
			if (!array_key_exists($k, $callParams)) {
				$callParams[$k] = is_string($v) ? urldecode($v) : $v;
			}
		}

		// Set parameters cho route.
		if (isset($args['route']) && $args['route'] instanceof RouteData) {
			$routeParameters = $callParams;
			unset($routeParameters['route']);
			$args['route']->parameters = $routeParameters;
		}

		// Phần dư của hook args cho tham số variadic (key số → container append vào cuối).
		foreach ($this->variadicHookArgs($hookArgs) as $value) {
			$callParams[] = $value;
		}

		return $callParams;
	}

	/**
	 * Xử lý các tham số kiểu class "đặc biệt":
	 * - Request (kể cả class con) → request hiện tại.
	 * - "__wpspConstruct" → tự khởi tạo class và gán thành property.
	 *
	 * @return mixed|null null nếu không thuộc trường hợp đặc biệt (để container inject).
	 */
	protected function resolveSpecialClassParam(string $name, string $className, $method) {
		if ($requestInstance = $this->resolveRequestForType($className)) {
			return $requestInstance;
		}

		if ($method === '__wpspConstruct' && class_exists($className)) {
			try {
				$instance = new $className($this->mainPath, $this->rootNamespace, $this->prefixEnv, $this->extraParams);
				@$this->{$name} = $instance;
				return $instance;
			}
			catch (\Throwable $e) {
			}
		}

		return null;
	}

	/**
	 * Route model binding theo chuẩn Laravel (resolveRouteBinding → tôn trọng getRouteKeyName()).
	 * Không tìm thấy → do_action "{app}_model_not_found" rồi trả 404 (JSON hoặc wp_die).
	 */
	protected function bindEloquentModel(string $className, $value, \ReflectionParameter $param) {
		if ($value !== null && $value !== '') {
			$model = (new $className)->resolveRouteBinding($value);

			if ($model) {
				return $model;
			}

			$exception = (new ModelNotFoundException())->setModel($className, [$value]);
			do_action($this->funcs->_getAppShortName() . '_model_not_found', $className, $value, $exception);

			$this->abortWithStatus($exception->getMessage(), 404, '404 - Not Found.');
		}

		if (!$param->isDefaultValueAvailable()) {
			return null;
		}

		$default = $param->getDefaultValue();

		// Default null → không cần query DB.
		if ($default === null || $default === '') {
			return $default;
		}

		return (new $className)->resolveRouteBinding($default) ?? $default;
	}

	/**
	 * Map đối số WordPress hook (add_action / add_filter) vào signature theo thứ tự.
	 *
	 * - Param không type / type builtin → nhận đối số hiện tại.
	 * - Param type class → chỉ nhận nếu đối số là instance của class đó (vd: \WP_Post $post),
	 *   ngược lại để container DI (vd: MyService $service) và KHÔNG tiêu thụ đối số.
	 * - Param nullable nhận được null từ WP → nhận null.
	 * - Param variadic → nhận toàn bộ phần còn lại (trả về với key số).
	 *
	 * @param \ReflectionParameter[] $parameters
	 *
	 * @return array<string|int, mixed>
	 */
	protected function mapHookArgs(array $parameters, array $wpParams): array {
		if (empty($wpParams)) {
			return [];
		}

		$wpParams = array_values($wpParams);
		$total    = count($wpParams);
		$mapped   = [];
		$i        = 0;

		foreach ($parameters as $param) {
			if ($i >= $total) {
				break;
			}

			if ($param->isVariadic()) {
				foreach (array_slice($wpParams, $i) as $rest) {
					$mapped[] = $rest;
				}
				break;
			}

			$value     = $wpParams[$i];
			$className = $this->getClassFromType($param->getType());

			if ($className !== null) {
				if ($value instanceof $className || ($value === null && $param->allowsNull())) {
					$mapped[$param->getName()] = $value;
					$i++;
				}
				continue;
			}

			$mapped[$param->getName()] = $value;
			$i++;
		}

		return $mapped;
	}

	/**
	 * Lấy phần hook args dành cho tham số variadic (các key dạng số).
	 */
	protected function variadicHookArgs(array $hookArgs): array {
		return array_values(array_filter($hookArgs, 'is_int', ARRAY_FILTER_USE_KEY));
	}

	/**
	 * Reflection cho callable, cache theo Class::method (Closure không cache được).
	 */
	protected function reflectCallable($callbackOrClass, $method = null): \ReflectionFunctionAbstract {
		static $cache = [];

		if ($callbackOrClass instanceof \Closure) {
			return new \ReflectionFunction($callbackOrClass);
		}

		if (is_array($callbackOrClass)) {
			$method          = $callbackOrClass[1] ?? $method;
			$callbackOrClass = $callbackOrClass[0];
		}

		$class = is_object($callbackOrClass) ? get_class($callbackOrClass) : $callbackOrClass;

		return $cache[$class . '::' . $method] ??= new \ReflectionMethod($class, $method);
	}

	/*
	 * =====================================================================
	 * MANUAL DI (khi không có container)
	 * =====================================================================
	 */

	/**
	 * Tự động Resolve Dependency Injection dựa trên Reflection khi không có Laravel Container.
	 */
	protected function manualResolveAndCall($callback, array $callParams = []) {
		if ($callback instanceof \Closure) {
			$reflection = new \ReflectionFunction($callback);
			$instance   = null;
		}
		elseif (is_array($callback)) {
			[$classOrInstance, $method] = $callback;

			$instance   = is_object($classOrInstance) ? $classOrInstance : $this->manualMakeClass($classOrInstance);
			$reflection = $this->reflectCallable($instance, $method);
		}
		else {
			throw new \InvalidArgumentException("Unsupported callback type for manual DI.");
		}

		$resolvedArgs = [];

		foreach ($reflection->getParameters() as $param) {
			// Variadic → nhận toàn bộ giá trị key số còn lại trong $callParams.
			if ($param->isVariadic()) {
				foreach (array_filter($callParams, 'is_int', ARRAY_FILTER_USE_KEY) as $value) {
					$resolvedArgs[] = $value;
				}
				break;
			}

			$paramName = $param->getName();
			$className = $this->getClassFromType($param->getType());

			// 1. Đã có sẵn trong $callParams
			if (array_key_exists($paramName, $callParams)) {
				$resolvedArgs[] = $callParams[$paramName];
				continue;
			}

			// 2. Class type-hint
			if ($className && class_exists($className)) {
				if ($requestInstance = $this->resolveRequestForType($className)) {
					$resolvedArgs[] = $requestInstance;
					continue;
				}

				foreach ($callParams as $argVal) {
					if ($argVal instanceof $className) {
						$resolvedArgs[] = $argVal;
						continue 2;
					}
				}

				$resolvedArgs[] = $this->manualMakeClass($className);
				continue;
			}

			// 3. Default value / 4. null
			$resolvedArgs[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
		}

		return $instance === null
			? $reflection->invokeArgs($resolvedArgs)
			: $reflection->invokeArgs($instance, $resolvedArgs);
	}

	/**
	 * Tự động tạo Instance của một Class và Inject các Dependency vào Constructor (Manual Instantiation).
	 */
	protected function manualMakeClass(string $className) {
		if (!class_exists($className)) {
			throw new \RuntimeException("Class {$className} does not exist.");
		}

		$reflector = new \ReflectionClass($className);

		if (!$reflector->isInstantiable()) {
			throw new \RuntimeException("Class {$className} is not instantiable.");
		}

		$constructor = $reflector->getConstructor();

		if (is_null($constructor)) {
			return new $className();
		}

		$constructorParams = [];
		foreach ($constructor->getParameters() as $param) {
			$typeClass = $this->getClassFromType($param->getType());

			if ($typeClass && class_exists($typeClass)) {
				$constructorParams[] = $this->resolveRequestForType($typeClass) ?? $this->manualMakeClass($typeClass);
			}
			elseif ($param->isDefaultValueAvailable()) {
				$constructorParams[] = $param->getDefaultValue();
			}
			else {
				$constructorParams[] = null;
			}
		}

		return $reflector->newInstanceArgs($constructorParams);
	}

	/*
	 * =====================================================================
	 * ROUTE / REQUEST
	 * =====================================================================
	 */

	/**
	 * Đưa route hiện tại vào request để callback có thể gọi $request->route('id').
	 */
	public function setRouteResolver() {
		$route = $this->funcs->_getRouteManager()->currentRoute() ?? null;

		if (!$route || !in_array($route->type, ['AdminPages', 'Apis', 'Ajaxs', 'FrontPages', 'RewriteFrontPages'], true)) {
			return;
		}

		if (!method_exists($this->request, 'setRouteResolver')) {
			return;
		}

		if ($this->request->getMethod() !== strtoupper($route->method)) {
			return;
		}

		$requestPath = ltrim($this->request->getRequestUri(), '/\\');
		$fullPath    = $route->fullPath;
		$regexPath   = $this->funcs->_regexPath($fullPath, $route->args['force_regex'] ?? false);

		if (
			empty($regexPath)
			|| !(
				@preg_match('#' . $regexPath . '#iu', $requestPath)
				|| @preg_match('#' . $fullPath . '#iu', $requestPath)
			)
		) {
			return;
		}

		// Bản "…$/iu" cũ là tập con của bản không neo bên dưới nên đã bỏ.
		if (
			@preg_match('/' . $route->fullPathRegex . '/iu', $requestPath)
			|| @preg_match('/' . $fullPath . '/iu', $requestPath)
			|| @preg_match($route->fullPathRegex, $requestPath)
		) {
			$this->request->setRouteResolver(fn() => $route);
		}
	}

	/**
	 * Nếu $className là một Request (WPSP Widen / Illuminate / Symfony, kể cả class con)
	 * thì trả về request hiện tại; ngược lại trả về null.
	 *
	 * - Type là class cha của request hiện tại → trả về chính instance đó.
	 * - Type là class con → createFrom() để chép toàn bộ dữ liệu sang (cache theo request + class).
	 */
	protected function resolveRequestForType(string $className) {
		$isRequestType = is_a($className, \WPSPCORE\App\Widen\Http\Request::class, true)
			|| is_a($className, Request::class, true)
			|| is_a($className, \Symfony\Component\HttpFoundation\Request::class, true);

		if (!$isRequestType) {
			return null;
		}

		$current = $this->request;
		if (!$current) {
			$app     = $this->currentApp();
			$current = ($app && $app->bound('request')) ? $app->make('request') : null;
		}

		if (!$current) {
			return null;
		}

		if ($current instanceof $className) {
			return $current;
		}

		static $copies = [];
		$key = spl_object_id($current) . '|' . $className;

		if (!isset($copies[$key])) {
			try {
				$copies[$key] = $className::createFrom($current);
			}
			catch (\Throwable $e) {
				return null;
			}
		}

		return $copies[$key];
	}

	/**
	 * Lấy tên class từ một ReflectionType (bỏ qua builtin; union type → class đầu tiên).
	 */
	protected function getClassFromType(\ReflectionType|null $type): ?string {
		if ($type instanceof \ReflectionNamedType) {
			return $type->isBuiltin() ? null : $type->getName();
		}

		if ($type instanceof \ReflectionUnionType) {
			foreach ($type->getTypes() as $t) {
				if ($t instanceof \ReflectionNamedType && !$t->isBuiltin()) {
					return $t->getName();
				}
			}
		}

		return null;
	}

	/*
	 * =====================================================================
	 * RESOLVE & CALL
	 * =====================================================================
	 */

	/**
	 * Beauty method của resolveAndCall với call = false.\
	 * Trả về Closure chứa callback đã được resolve Dependency Injection.
	 */
	public function resolveCallback($callback, $callParams = []) {
		return $this->resolveAndCall($callback, $callParams, false);
	}

	/**
	 * Call callback với Dependency Injection.\
	 * "callParams" có thể được chuẩn bị bằng method getCallParams().
	 *
	 * Khi $call = false: trả về Closure nhận hook args của WordPress,
	 * container/facade được đồng bộ TẠI THỜI ĐIỂM GỌI (không phải lúc tạo closure).
	 */
	public function resolveAndCall($callback, $callParams = [], $call = true, $method = null) {
		if (!$call) {
			return function(...$wpParams) use ($callback, $callParams) {
				return $this->invokeResolved($callback, $this->withHookArgs($callback, (array)$callParams, $wpParams));
			};
		}

		return $this->invokeResolved($callback, (array)$callParams);
	}

	/**
	 * Gọi callback qua container (nếu có) hoặc manual resolver.
	 */
	protected function invokeResolved($callback, array $callParams) {
		$container = $this->currentApp();

		if ($container) {
			$this->syncContainerState($container);
			return $container->call($callback, $callParams);
		}

		return $this->manualResolveAndCall($callback, $callParams);
	}

	/**
	 * Gộp hook args của WordPress vào $callParams theo signature của callback.
	 */
	protected function withHookArgs($callback, array $callParams, array $wpParams): array {
		if (empty($wpParams)) {
			return $callParams;
		}

		try {
			$reflection = $this->reflectCallable($callback);
		}
		catch (\Throwable $e) {
			return $callParams;
		}

		return array_merge($callParams, $this->mapHookArgs($reflection->getParameters(), $wpParams));
	}

	/**
	 * Đồng bộ Container / Facade / Eloquent về container của plugin hiện tại.\
	 * Chỉ ghi khi thực sự khác, và xoá cache Facade khi đổi container
	 * (Facade::$resolvedInstance là static dùng chung → nếu không xoá sẽ trả về service của plugin trước).
	 */
	protected function syncContainerState($container): void {
		if ($container instanceof Container) {
			Container::setInstance($container);
		}

		if (Facade::getFacadeApplication() !== $container) {
			Facade::clearResolvedInstances();
			Facade::setFacadeApplication($container);
		}

		if (method_exists($container, 'bound') && $container->bound('db')) {
			$db = $container->make('db');
			if (Model::getConnectionResolver() !== $db) {
				Model::setConnectionResolver($db);
			}

			if ($container->bound('events')) {
				$events = $container->make('events');
				if (Model::getEventDispatcher() !== $events) {
					Model::setEventDispatcher($events);
				}
			}
		}
	}

	/**
	 * Trả về callback với Dependency Injection. Tự động hoàn toàn.
	 */
	public function autoResolveCallback($path, $fullPath, $requestPath, $callbackOrClass, $method = null, $args = []) {
		return $this->autoResolveAndCall($path, $fullPath, $requestPath, $callbackOrClass, $method, $args, false);
	}

	/**
	 * Gọi callback với Dependency Injection. Tự động hoàn toàn.
	 */
	public function autoResolveAndCall($path, $fullPath, $requestPath, $callbackOrClass, $method = null, $args = [], $call = true) {
		$class  = is_array($callbackOrClass) ? $callbackOrClass[0] : $callbackOrClass;
		$method = $method ?? (is_array($callbackOrClass) ? ($callbackOrClass[1] ?? null) : null);
		$method = $method ?? '__instanceConstruct';

		if ($class && method_exists($class, $method)) {
			$callback = $this->prepareCallbackFunction($method, $path, $fullPath, $class, $args);
			return $this->resolveAndCall($callback, [], $call, $method);
		}

		return null;
	}

	/*
	 * =====================================================================
	 * UTILS
	 * =====================================================================
	 */

	/**
	 * Chuẩn hóa callback để trả về [class, method].
	 */
	public function normalizeCallback($callback) {
		if ($callback instanceof \Closure) {
			return [null, $callback];
		}

		if (is_array($callback) && is_object($callback[0]) && is_string($callback[1])) {
			return [$callback[0], $callback[1]];
		}

		throw new \RuntimeException("Invalid callback format");
	}

	/**
	 * Build params for callable (route callback).
	 */
	public function buildParametersForCallable($callback, $path, $fullPath, $requestPath, $args = [], $wpParams = []) {
		[$class, $method] = $this->normalizeCallback($callback);

		// Closure: normalizeCallback trả [null, Closure] → reflect chính Closure.
		if ($class === null) {
			return $this->getCallParams($path, $fullPath, $requestPath, $method, null, $args, $wpParams);
		}

		return $this->getCallParams($path, $fullPath, $requestPath, $class, $method, $args, $wpParams);
	}

}