<?php

namespace WPSPCORE\App\Traits;

use Illuminate\Routing\ControllerMiddlewareOptions;

/**
 * InstanceMiddlewareTrait — controller middleware kiểu Laravel cho page class của WPSP
 * (BaseAdminPage, BaseRewriteFrontPage...).
 *
 * Giống Illuminate\Routing\Controller::middleware() / getMiddleware():
 *
 *     public function customProperties() {
 *         $this->middleware('can:update,user')->only('edit', 'update');
 *         $this->middleware(AuthenticationMiddleware::class)->except('index');
 *
 *         // Dùng cùng AuthorizesRequests:
 *         $this->authorizeResource(UsersModel::class, 'user');
 *     }
 *
 * RouteTrait::authorizeControllerAction() đọc getMiddleware() và chạy các middleware áp
 * cho ACTION của route (index / show / create / store / edit / update / destroy...) ngay
 * trước khi gọi action. Method vòng đời (customProperties, init...) không bị chặn.
 *
 * LƯU Ý: KHÔNG dùng trait này cho class route (BaseRoute và con của nó): BaseRoute xử lý
 * Route::middleware(...) qua __call(), một method middleware() thật sẽ chặn cơ chế đó.
 */
trait InstanceMiddlewareTrait {

	/**
	 * [['middleware' => string|Closure|array, 'options' => ['only' => [...], 'except' => [...]]], ...]
	 */
	protected array $instanceMiddleware = [];

	/**
	 * Đăng ký controller middleware.
	 *
	 * @param \Closure|array|string $middleware
	 * @param array                 $options ['only' => [...]] / ['except' => [...]]
	 */
	public function middleware($middleware, array $options = []) {
		// (array)$closure = [] → Closure / string phải bọc thủ công.
		$middlewares = ($middleware instanceof \Closure || is_string($middleware)) ? [$middleware] : (array)$middleware;

		foreach ($middlewares as $m) {
			$this->instanceMiddleware[] = [
				'middleware' => $m,
				'options'    => &$options,
			];
		}

		// ->only() / ->except() ghi vào $options (truyền theo tham chiếu) của các entry vừa thêm.
		return new ControllerMiddlewareOptions($options);
	}

	/**
	 * Toàn bộ controller middleware đã đăng ký.
	 */
	public function getMiddleware() {
		return $this->instanceMiddleware;
	}

}
