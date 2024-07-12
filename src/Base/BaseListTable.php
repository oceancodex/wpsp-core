<?php

namespace WPSPCORE\Base;

abstract class BaseListTable extends \WP_List_Table {

	public ?string $defaultOrder   = 'asc';
	public ?string $defaultOrderBy = 'id';
	public ?array  $removeQueryVars = [];

	public function __construct($args = []) {
		parent::__construct($args);
		$this->customProperties();
        $this->removeQueryVars();
	}

	public function removeQueryVars(): void {
		if (isset($_REQUEST['action']) && $_REQUEST['action'] < 0 && isset($_REQUEST['action2']) && $_REQUEST['action2'] < 0
			|| !isset($_REQUEST['action']) && !isset($_REQUEST['action2']) && isset($_REQUEST['_wpnonce'])) {
			wp_safe_redirect(remove_query_arg($this->removeQueryVars, stripslashes($_SERVER['REQUEST_URI'])));
			exit;
		}
	}

	public function customProperties() {}


}