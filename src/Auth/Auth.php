<?php
namespace WPSPCORE\Auth;

use WPSPCORE\Base\BaseInstances;

abstract class Auth extends BaseInstances {

	public $auth;

	public function setAuth() {
		$this->auth = $this->funcs->getApplication('auth');
	}

	public function getAuth() {
		return $this->auth;
	}

}