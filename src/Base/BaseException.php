<?php
namespace WPSPCORE\Base;

class BaseException extends \Exception {

	public function __construct($message = "", $code = 0, $previous = null) {
		if (method_exists($this, 'beforeInstanceConstruct')) {
			$this->beforeInstanceConstruct();
		}
		parent::__construct($message, $this->code ?? $code, $previous);
	}

}