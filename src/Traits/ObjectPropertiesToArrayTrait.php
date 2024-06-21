<?php

namespace OCBPCORE\Traits;

trait ObjectPropertiesToArrayTrait {

	public function toArray(): array {
		return get_object_vars($this);
	}

}