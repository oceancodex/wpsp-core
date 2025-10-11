<?php

namespace WPSPCORE\Traits;

trait ObjectPropertiesToArrayTrait {

	public function toArray() {
		return get_object_vars($this);
	}

}