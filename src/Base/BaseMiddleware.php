<?php

namespace WPSPCORE\Base;

use WPSPCORE\Objects\Http\HttpFoundation;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use WP_REST_Request;

abstract class BaseMiddleware extends HttpFoundation {

	abstract public function handle(HttpFoundationRequest|WP_REST_Request $request): bool;

}