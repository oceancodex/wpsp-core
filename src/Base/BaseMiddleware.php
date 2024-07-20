<?php

namespace WPSPCORE\Base;

use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use WP_REST_Request;

abstract class BaseMiddleware extends BaseInstances {

	/**
	 * @param HttpFoundationRequest|WP_REST_Request $request
	 *
	 * @return bool
	 */
	abstract public function handle($request): bool;

}