<?php

namespace WPSPCORE\Base;

abstract class BaseListTable extends \WP_List_Table {

	public ?string $defaultOrder   = null;
	public ?string $defaultOrderby = null;
	public ?array  $removeQueryVar = [];

	public function __construct($args = []) {
		parent::__construct($args);
        $this->removeQueryVar();
	}

	public function removeQueryVar(): void {
		if (isset($_REQUEST['action']) && $_REQUEST['action'] < 0 && isset($_REQUEST['action2']) && $_REQUEST['action2'] < 0
			|| !isset($_REQUEST['action']) && !isset($_REQUEST['action2']) && isset($_REQUEST['_wpnonce'])) {
			wp_safe_redirect(remove_query_arg($this->removeQueryVar, stripslashes($_SERVER['REQUEST_URI'])));
			exit;
		}
	}


}