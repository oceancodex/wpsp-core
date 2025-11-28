<?php

namespace WPSPCORE\Http\Middleware;

use WPSPCORE\BaseInstances;

abstract class BaseMiddleware extends BaseInstances {

	/**
	 * @var $request \Symfony\Component\HttpFoundation\Request|\WP_REST_Request
	 */
	abstract public function handle($request);

}