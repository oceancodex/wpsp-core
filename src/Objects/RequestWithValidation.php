<?php

namespace WPSPCORE\Objects;

use Symfony\Component\HttpFoundation\Request;
use WPSPCORE\Validation\Traits\ValidatesRequestTrait;

class RequestWithValidation extends Request {

//	use ValidatesRequestTrait;

	/**
	 * @var \WPSPCORE\Validation\Validation|null
	 */
	public $validation = null;

}