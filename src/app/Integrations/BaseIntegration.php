<?php

namespace WPSPCORE\App\Integrations;

use WPSPCORE\BaseInstances;

class BaseIntegration extends BaseInstances {

	public $activate = true;

	/*
	 *
	 */

	public function getActivate() {
		return $this->activate;

	}

}