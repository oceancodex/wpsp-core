<?php

namespace WPSPCORE\Base;

use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use WP_REST_Request;

abstract class BaseMiddleware extends BaseInstances {

	abstract public function handle(HttpFoundationRequest|WP_REST_Request $request): bool;

}