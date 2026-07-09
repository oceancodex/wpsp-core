<?php

namespace WPSPCORE\App\Routes;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\Response;
use WPSP\App\Exceptions\HttpException;

trait RouteTrait {
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
	 * @param string $currentClass Tên class middleware cần kiểm tra.
	 * @param mixed  $allMiddlewares Danh sách middleware của pipeline.
	 *
	 * @return bool Trả về true nếu middleware hiện tại là middleware cuối cùng,
	 *              ngược lại trả về false.
	 */
	public function isLastMiddleware($currentClass, $allMiddlewares) {
		if (!is_array($allMiddlewares)) {
			return false;
		}

		// Lọc chỉ lấy key dạng số (0,1,2...)
		$middlewares = [];
		foreach ($allMiddlewares as $key => $value) {
			if (is_int($key)) {
				$middlewares[$key] = $value;
			}
		}

		if (empty($middlewares)) {
			return false;
		}

		// Lấy phần tử cuối cùng
		$last = end($middlewares);

		// dạng: [ 'ClassName', 'handle' ]
		if (is_array($last) && isset($last[0]) && $last[0] === $currentClass) {
			return true;
		}

		return false;
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
	 * - Closure
	 * - ClassName::class
	 * - [ClassName::class, 'method']
	 *
	 * Giá trị trả về của middleware:
	 *
	 * - true  : PASS
	 * - false : FAIL
	 * - Response có status < 400 : PASS
	 * - Response có status >= 400 : FAIL
	 *
	 * Thông tin block hiện tại sẽ được truyền vào tham số `$args`
	 * với key `current_block_middleware`.
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
	 * @param mixed $request Request hiện tại. Nếu null sẽ tự động lấy từ container.
	 * @param array $args Dữ liệu bổ sung được truyền vào middleware.
	 *
	 * @return bool Trả về true nếu toàn bộ middleware đều PASS, ngược lại false.
	 */
	public function isPassedMiddleware($middlewares = [], $request = null, $args = []) {
		// Set route resolver.
		$this->request->setRouteResolver(function() use (&$args) {
			return $args['route'] ?? null;
		});

		/** @var \Illuminate\Foundation\Application $app */
		$app     = $this->funcs->_getApplication();
		$request = $request ?? $this->request ?? $app->make('request');

		// Không có middleware → pass
		if (empty($middlewares)) {
			return true;
		}

		/**
		 * Chuẩn hoá 1 middleware "lá" (Closure / string / [class, method]) thành dạng runtime.
		 */
		$normalizeLeaf = function($mw, $args) {
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
		};

		/**
		 * Chạy 1 middleware đã normalize, trả về true (PASS) / false (FAIL).
		 */
		$runOne = function($mw) use ($app, $request) {
			$next = function() {
				return new Response('OK', 200);
			};

			$runThrottle = function($mw, $request) use ($app, $next) {
				$middleware = $app->make(
					\Illuminate\Routing\Middleware\ThrottleRequests::class
				);

				$parts = explode(':', $mw['value'], 2);

				$parameters = [];
				if (isset($parts[1])) {
					$parameters = explode(',', $parts[1]);
				}

				try {
					return $middleware->handle($request, $next, ...$parameters);
				}
				catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $e) {
					if ($this->funcs->_wantsJson()) {
						$response = $this->funcs->_response(false, $e->getMessage(), 429);
						$response = new JsonResponse($response, 429);
						return $response->send();
					}
					wp_die($e->getMessage(), '429 - Too Many Requests.', [
						'back_link' => true,
						'response'  => 429,
					]);
				}
				catch (\Exception $e) {
					if ($this->funcs->_wantsJson()) {
						$response = $this->funcs->_response(false, $e->getMessage(), 500);
						$response = new JsonResponse($response, 500);
						return $response->send();
					}
					wp_die($e->getMessage(), '500 - Internal Server Error.', [
						'back_link' => true,
						'response'  => 500,
					]);
				}
			};

			if ($mw['type'] === 'throttle') {
				$res = $runThrottle($mw, $request);
			}
			elseif ($mw['type'] === 'closure') {
				$res = call_user_func($mw['closure'], $request, $next);
			}
			else {
				$class  = $mw['class'];
				$method = $mw['method'];

				if (!class_exists($class)) {
					return false;
				}

				$instance = $app->make($class);

				if (!method_exists($instance, $method)) {
					$method = 'handle';
				}

				$res = $app->call([$instance, $method], [
					'request' => $request,
					'next'    => $next,
					'args'    => $mw['args'] ?? null,
				]);
			}

			if ($res instanceof Response) {
				return $res->getStatusCode() < 400;
			}

			if (is_bool($res)) return $res;

			return true;
		};

		/**
		 * Đánh giá đệ quy 1 node: có thể là middleware lá, hoặc 1 block con lồng nhau
		 * (mảng có key 'relation').
		 */
		$evaluateNode = function($node, $args) use (&$evaluateNode, &$evaluateBlock, $normalizeLeaf, $runOne) {
			if (is_array($node) && array_key_exists('relation', $node)) {
				return $evaluateBlock($node, $args);
			}

			$normalized = $normalizeLeaf($node, $args);

			// Dạng không hợp lệ → bỏ qua, coi như PASS để không chặn nhầm route.
			if ($normalized === null) {
				return true;
			}

			return $runOne($normalized);
		};

		/**
		 * Đánh giá 1 block theo relation (mặc định AND nếu không khai báo).\
		 * Các phần tử con có thể là middleware lá hoặc block con lồng nhau — đệ quy vô hạn cấp.
		 */
		$evaluateBlock = function($block, $args) use (&$evaluateNode) {
			$relation = 'AND';
			if (isset($block['relation'])) {
				$relation = strtoupper($block['relation']);
			}

			$args['current_block_middleware'] = $block;

			$children = [];
			foreach ($block as $key => $value) {
				if ($key === 'relation') continue;
				$children[] = $value;
			}

			if (empty($children)) {
				return true;
			}

			if ($relation === 'OR') {
				foreach ($children as $child) {
					if ($evaluateNode($child, $args)) return true;
				}
				return false;
			}

			// AND
			foreach ($children as $child) {
				if (!$evaluateNode($child, $args)) return false;
			}
			return true;
		};

		// Mỗi phần tử trong $middlewares là 1 block top-level. Route PASS khi TẤT CẢ block PASS.
		foreach ($middlewares as $blockMiddleware) {
			if (!$evaluateBlock($blockMiddleware, $args)) {
				return false;
			}
		}

		return true;
	}

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
	 * Chuẩn bị callback cho các function đặc biệt, ví dụ: add_menu_page()\
	 * Sử dụng hàm này khi cần gọi "Callback Dependencies Injection" trong các class callback của Route.\
	 * Ví dụ:
	 * - Route::get('/my-page', [MyClass::class, 'myMethod']);
	 *
	 * Lúc này myMethod được gọi với DI tự động.\
	 * Nhưng trong myMethod chúng ta lại muốn gọi tiếp method khác, ví dụ: $this->secondMethod()
	 * Nếu không sử dụng hàm này, thì secondMethod() sẽ không được "Dependencies Injection".
	 */
	public function prepareCallbackFunction($method, $path, $fullPath, $class = null, $args = []): \Closure {
		return function() use ($method, $path, $fullPath, $class, $args) {
			$requestPath = ltrim($this->request->getRequestUri(), '/\\');

			// build callback [instance, method]
			$callback = [$class ?? $this, $method];

			if (!isset($args['route'])) {
				$args['route'] = $this->extraParams['route'] ?? null;
			}

			// build params
			$callParams = $this->buildParametersForCallable($callback, $path, $fullPath, $requestPath, $args);

			// call
			return $this->resolveAndCall($callback, $callParams);
		};
	}

	/**
	 * Build params for callable (route callback).\
	 * Hàm này rất phức tạp, xử lý rất nhiều trường hợp params của method.\
	 * Bao gồm:
	 * - Detect callback type
	 * - Reflection callback signature
	 * - Regex route matching
	 * - Ajax route compatibility
	 * - Fallback param build khi route không match
	 * - Request resolving
	 * - Regex capture parsing
	 * - Request source aggregation
	 * - Primitive param binding
	 * - Eloquent model binding
	 * - Metadata injection
	 * - Request → route parameter bridging
	 */
	public function getCallParams($path, $fullPath, $requestPath, $callbackOrClass, $method = null, $args = [], $wpParams = []) {
		$originalRequestPath = $requestPath;
		$httpMethod          = $this->request->getMethod();
		$params              = $this->request->all();

		// NEW: detect closure
		if ($callbackOrClass instanceof \Closure) {
			$reflection = new \ReflectionFunction($callbackOrClass);
			$class      = null;
			$method     = null;
		}
		else {
			$class      = $callbackOrClass;
			$reflection = new \ReflectionMethod($class, $method);
		}

		// Match pattern: KHÔNG escape path vì path đã là regex pattern (có thể chứa (?P<name>...))
		// Nếu $path có ^ hoặc $ thì vẫn dùng như vậy; nếu không có, ta match toàn chuỗi.
		$forceRegex = $args['route']->args['force_regex'] ?? false;
		$regexPath = $this->funcs->_regexPath($fullPath, $forceRegex);
		$pattern   = '#' . $regexPath . '#iu';

		$passed = false;

		// Nếu nơi gọi hàm này là route "Ajaxs" với method POST, check match action và path.
		if (@preg_match('/Ajaxs$/', static::class)) {
			if ($httpMethod === 'POST') {
				$passed = isset($params['action']) && $params['action'] === $fullPath;
			}
		}

		/**
		 * Nếu nơi gọi hàm là "Actions" hoặc "Filters", tự động passed.\
		 * Bởi vì add_action và add_filter không có request.
		 */
		if (@preg_match('/Actions$|Filters$/', static::class)) {
//			$passed = $path == $fullPath;
			$requestPath = $fullPath;
		}

		/**
		 * Kiểm tra $path có khớp với request path hiện tại không?\
		 * Mục đích để chỉ thực sự chạy khi đang truy cập trực tiếp "path" hoặc "fullPath"\
		 * Tránh tình trạng đang ở URL khác lại thực thi các code bên dưới là không cần thiết.
		 */
		if (
			!empty($regexPath) &&
			(
				@preg_match($pattern, $requestPath, $matches)
				|| @preg_match('#' . $fullPath . '#iu', $requestPath, $matches)
				|| $fullPath == $requestPath
			)
		) {
			$passed = true;
		}

		if (!$passed) {
			// Build all params as null for primitive args
			$callParams = [];

			foreach ($reflection->getParameters() as $param) {
				$name = $param->getName();
				$type = $param->getType();

				// Nếu type là class → container sẽ inject sau
				if ($className = $this->getClassFromType($type)) {
					/**
					 * Nếu method đang xử lý là "__wpspConstruct" và type của param\
					 * là một class hợp lệ, tự động tạo properties cho class đang xử lý.
					 */
					if ($method == '__wpspConstruct' && $name && class_exists($className)) {
						try {
							$nextClass = new $className($this->mainPath, $this->rootNamespace, $this->prefixEnv, $this->extraParams);
							@$this->{$name} = $nextClass;
						}
						catch (\Exception $e) {}
					}
					continue;
				}

				// Primitive → NULL
				$callParams[$param->getName()] = null;
			}

			// Thêm các giá trị hệ thống
			$callParams['path']            = $path;
			$callParams['path_regex']      = $this->funcs->_regexPath($path);
			$callParams['full_path']       = $fullPath;
			$callParams['full_path_regex'] = $this->funcs->_regexPath($fullPath);
			$callParams['request_path']    = $requestPath;

			foreach ($args as $argKey => $argValue) {
				$callParams[$argKey] = $argValue;
			}

			return $callParams;
		}

		// Lấy container / request
		$app = $this->funcs->_getApplication();
		$baseRequest = $this->request ?? ($app->bound('request') ? $app->make('request') : Request::capture());

		// Named groups: keys là tên (PHP returns associative entries for named groups)
		$named = array_filter($matches, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);

		// Positional captures (1..n)
		$positional = [];
		foreach ($matches as $k => $v) {
			if (is_int($k) && $k > 0) $positional[] = $v;
		}

		// Request sources
		$query = $baseRequest->query->all();      // GET params
		$post  = $baseRequest->request->all();    // POST params
		$attr  = $baseRequest->attributes->all(); // attributes

		$callParams   = [];
		$posIndex     = 0;
		$runtimeIndex = 0;

		foreach ($reflection->getParameters() as $param) {
			$name = $param->getName();
			$type = $param->getType();

			/**
			 * Model binding & auto define class properties with DI.
			 */
			$className = $this->getClassFromType($type);
			if ($className) {

				/**
				 * Nếu method đang xử lý là "__wpspConstruct" và type của param\
				 * là một class hợp lệ, tự động tạo properties cho class đang xử lý.
				 */
				if ($method == '__wpspConstruct' && $name && class_exists($className)) {
					try {
						$nextClass = new $className($this->mainPath, $this->rootNamespace, $this->prefixEnv, $this->extraParams);
						@$this->{$name} = $nextClass;
					}
					catch (\Exception $e) {}
				}

				// Nếu type là Eloquent Model => tự binding
				if (is_subclass_of($className, \Illuminate\Database\Eloquent\Model::class)) {
					// Lấy id từ path / query
					$modelId = null;

					// Ưu tiên named group (?P<user_id>)
					if (array_key_exists($name, $named)) {
						$modelId = $named[$name];
					}
					elseif (array_key_exists($name, $query)) {
						$modelId = $query[$name];
					}
					elseif (array_key_exists($name, $post)) {
						$modelId = $post[$name];
					}
					elseif (array_key_exists($name, $args)) {
						$modelId = $args[$name];
					}

					// Nếu có ID → binding
					if (!empty($modelId)) {
						try {
							$callParams[$name] = $className::query()->findOrFail($modelId);
						}
						catch (\Exception $exception) {
							do_action($this->funcs->_getAppShortName() . '_model_not_found', $className, $modelId, $exception);
							wp_die($exception->getMessage(), $exception->getMessage(), [
								'back_link' => true,
							]);
						}
					}
					else {
						// Không có id nhưng param optional → default / null
						if ($param->isDefaultValueAvailable()) {
							$defaultValue = $param->getDefaultValue();
							try {
								$callParams[$name] = $className::query()->findOrFail($defaultValue);
							}
							catch (\Exception $e) {
								$callParams[$name] = $defaultValue;
							}
						}
						else {
							$callParams[$name] = null;
						}
					}

					continue; // xong param model-binding
				}
				// Còn lại hãy xử lý param tiếp theo.
				else {
					continue;
				}
			}

			$value = null;

			// 1) Nếu có named capture trùng tên param -> ưu tiên
			if (array_key_exists($name, $named)) {
				$value = $named[$name];
			}
			// 2) attributes (request attributes)
			elseif (array_key_exists($name, $attr)) {
				$value = $attr[$name];
			}
			// 3) POST (body)
			elseif (array_key_exists($name, $post)) {
				$value = $post[$name];
			}
			// 4) Query string
			elseif (array_key_exists($name, $query)) {
				$value = $query[$name];
			}
			// 5) Positional capture fallback
			elseif (isset($positional[$posIndex])) {
				$value = $positional[$posIndex++];
			}
			// 6) Default value from signature
			elseif ($param->isDefaultValueAvailable()) {
				$value = $param->getDefaultValue();
			}
			// 7) Tự động thêm WP Params. Ví dụ add_action('save_post') có 3 đối số mà WP cho phép dùng: $post_id, $post, $update. Tại đây sẽ đưa các đối số đó vào $callParams để DI.
			elseif (isset($wpParams[$runtimeIndex])) {
				$value = $wpParams[$runtimeIndex];
				$runtimeIndex++;
			}
			// 8) else null

			// Nếu là string, decode URL-encoded values (an toàn)
			if (is_string($value)) {
				$value = urldecode($value);
			}

			$callParams[$name] = $value;
		}

		// Thêm các thuộc tính vào params.
		$callParams['path']            = $path;
		$callParams['path_regex']      = $this->funcs->_regexPath($path);
		$callParams['full_path']       = $fullPath;
		$callParams['full_path_regex'] = $this->funcs->_regexPath($fullPath);
		$callParams['request_path']    = $requestPath;

		foreach ($args as $argKey => $argValue) {
			if (!isset($callParams[$argKey])) $callParams[$argKey] = $argValue;
		}

		// Ngoài các params lấy từ signature (primitive params),
		// ta cũng muốn expose ALL named captures (dù method không khai báo param cụ thể)
		// — giúp bạn có thể lấy $routeParams['endpoint'] trong middleware hoặc log.
		foreach ($named as $k => $v) {
			if (!array_key_exists($k, $callParams)) {
				$callParams[$k] = is_string($v) ? urldecode($v) : $v;
			}
		}

		/**
		 * Đưa tham số route vào request để có thể truyền vào callback.\
		 * Ví dụ:
		 * - /wpsp/posts/{id}
		 *
		 * Tronng callback có thể gọi:
		 *
		 * public function posts(Request \$request) {\
		 * ㅤ\$id = $request->route('id');\
		 * }
		 */
		if (
			!empty($regexPath) &&
			(
				@preg_match($pattern, $originalRequestPath, $matches)
				|| @preg_match('#' . $fullPath . '#iu', $originalRequestPath, $matches)
			)
		) {
			if (
				$path !== 'wpsp' &&
				isset($args['route'])
				&& $httpMethod == strtoupper($args['route']->method)
				&& (
					@preg_match('/' . $args['route']->fullPathRegex . '$/iu', $originalRequestPath)
					|| @preg_match('/' . $args['route']->fullPathRegex . '/iu', $originalRequestPath)
					|| @preg_match('/' . $args['route']->fullPath . '/iu', $originalRequestPath)
					|| @preg_match($args['route']->fullPathRegex, $originalRequestPath)
				)
			) {
				$args['route']->parameters = $callParams;
				$this->request->setRouteResolver(function() use ($args) {
					return $args['route'];
				});
			}
		}

		return $callParams;
	}

	/**
	 * Lấy tên class từ một ReflectionType.
	 *
	 * Hàm này được sử dụng để xác định class cần được khởi tạo tự động
	 * từ khai báo kiểu dữ liệu (type declaration) của tham số hoặc phương thức.
	 *
	 * Chỉ các kiểu đối tượng (class/interface) mới được trả về. Các kiểu
	 * dựng sẵn của PHP như string, int, bool, float, array... sẽ bị bỏ qua.
	 *
	 * Đối với Union Type, hàm sẽ trả về class đầu tiên tìm thấy.
	 *
	 * Ví dụ:
	 * - LoggerInterface      => "LoggerInterface"
	 * - string               => null
	 * - Logger|NullLogger    => "Logger"
	 *
	 * @param \ReflectionType|null $type Kiểu dữ liệu cần phân tích.
	 *
	 * @return string|null Tên class/interface nếu tìm thấy, ngược lại trả về null.
	 */
	protected function getClassFromType(\ReflectionType|null $type): ?string {
		if (!$type) {
			return null;
		}

		if ($type instanceof \ReflectionNamedType) {
			return $type->isBuiltin()
				? null
				: $type->getName();
		}

		if ($type instanceof \ReflectionUnionType) {

			foreach ($type->getTypes() as $t) {

				if (
					$t instanceof \ReflectionNamedType &&
					!$t->isBuiltin()
				) {
					return $t->getName();
				}
			}
		}

		return null;
	}

	/**
	 * Beauty method của resolveAndCall với call = false.\
	 * Mục đích để trả về Closure chứa callback đã được resolve Dependency Injection.
	 */
	public function resolveCallback($callback, $callParams = []) {
		return $this->resolveAndCall($callback, $callParams, false);
	}

	/**
	 * Call callback với Dependency Injection.\
	 * Bắt buộc phải có "callParams" để resolve Dependency Injection.\
	 * "callParams" có thể được chuẩn bị bằng method getCallParams().
	 */
	public function resolveAndCall($callback, $callParams = [], $call = true) {
		/** @var \Illuminate\Container\Container|\Illuminate\Foundation\Application $container */
		$container = $this->funcs->_getApplication();

		// Set container và facade theo mỗi lần gọi callback.
		Container::setInstance($container);
		Facade::setFacadeApplication($container);
		Model::setConnectionResolver($container['db']);
		Model::setEventDispatcher($container['events']);

		if (!$call) {
			return function(...$wpParams) use ($container, $callback, $callParams) {
				return $container->call($callback, $callParams);
			};
		}

		return $container->call($callback, $callParams);
	}

	/**
	 * Trả về callback với Dependency Injection.\
	 * Tự động hoàn toàn.
	 */
	public function autoResolveCallback($path, $fullPath, $requestPath, $callbackOrClass, $method = null, $args = []) {
		return $this->autoResolveAndCall($path, $fullPath, $requestPath, $callbackOrClass, $method, $args, false);
	}

	/**
	 * Gọi callback với Dependency Injection.\
	 * Tự động hoàn toàn.
	 */
	public function autoResolveAndCall($path, $fullPath, $requestPath, $callbackOrClass, $method = null, $args = [], $call = true) {
		$class    = is_array($callbackOrClass) ? $callbackOrClass[0] : $callbackOrClass;
		$method   = $method ?? (is_array($callbackOrClass) ? ($callbackOrClass[1] ?? null) : null);
		$method   = $method ?? '__instanceConstruct';

		if ($class && $method && method_exists($class, $method)) {
			$callback = $this->prepareCallbackFunction($method, $path, $fullPath, $class, $args);
			$params   = $this->getCallParams($path, $fullPath, $requestPath, $callbackOrClass, $method, $args);
			return $this->resolveAndCall($callback, $params, $call);
		}
		return null;
	}

	/*
	 *
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
	public function buildParametersForCallable($callback, $path, $fullPath, $requestPath, $args = []) {
		[$class, $method] = $this->normalizeCallback($callback);
		return $this->getCallParams($path, $fullPath, $requestPath, $class, $method, $args);
	}

}