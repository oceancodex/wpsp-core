<?php

namespace WPSPCORE\Traits;

trait GroupRoutesTrait {

	public function group($callback, $middlewares = null) {
		if ($this->isPassedMiddleware($middlewares, $this->request)) {
			$callback();
		}
	}

}