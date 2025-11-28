<?php

namespace WPSPCORE\WP\ListTables;

use WPSPCORE\Traits\BaseInstancesTrait;

abstract class BaseListTable extends \WP_List_Table {

	use BaseInstancesTrait;

	public $defaultOrder    = 'asc';
	public $defaultOrderBy  = 'id';
	public $removeQueryVars = [];

	public function __construct($args = [], $mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		parent::__construct($args);
		$this->baseInstanceConstruct($mainPath, $rootNamespace, $prefixEnv, $extraParams);
		$this->removeQueryVars();
		$this->customProperties();
	}

	public function removeQueryVars() {
		if (isset($_REQUEST['action']) && $_REQUEST['action'] < 0 && isset($_REQUEST['action2']) && $_REQUEST['action2'] < 0
			|| !isset($_REQUEST['action']) && !isset($_REQUEST['action2']) && isset($_REQUEST['_wpnonce'])) {
			wp_safe_redirect(remove_query_arg($this->removeQueryVars, stripslashes($_SERVER['REQUEST_URI'])));
			exit;
		}
	}

	public function customProperties() {}

}