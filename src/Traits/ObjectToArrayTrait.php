<?php

namespace WPSPCORE\Traits;

trait ObjectToArrayTrait {

	public function toArray() {
		return get_object_vars($this);
	}

}