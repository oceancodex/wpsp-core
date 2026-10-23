<?php

namespace WPSPCORE\App\Http\Controllers;

use WPSPCORE\App\Traits\InstanceMiddlewareTrait;
use WPSPCORE\BaseInstances;

abstract class BaseController extends BaseInstances {

	use InstanceMiddlewareTrait;

}