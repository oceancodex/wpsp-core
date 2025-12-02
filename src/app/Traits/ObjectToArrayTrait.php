<?php

namespace WPSPCORE\App\Traits;

trait ObjectToArrayTrait {

	public function toArray() {
		return get_object_vars($this);
	}

}