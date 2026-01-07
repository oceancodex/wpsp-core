<?php

namespace WPSPCORE\App\WordPress\AdminPageMetaboxes;

use WPSPCORE\App\Routes\RouteTrait;
use WPSPCORE\BaseInstances;

abstract class BaseAdminPageMetabox extends BaseInstances {

	use RouteTrait;

	public function afterConstruct() {}

}