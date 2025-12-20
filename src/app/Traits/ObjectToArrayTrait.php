<?php

namespace WPSPCORE\App\Traits;

trait ObjectToArrayTrait {

	public function toArray() {
		unset($this->funcs);
		unset($this->request);
		return get_object_vars($this);
	}

}