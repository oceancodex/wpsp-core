<?php

namespace WPSPCORE\Components\Taxonomies;

use WPSPCORE\Base\BaseInstances;
use WPSPCORE\Traits\ObjectToArrayTrait;

abstract class BaseTaxonomy extends BaseInstances {

	use ObjectToArrayTrait;

	public $taxonomy    = null;
	public $object_type = 'post';     // The post type which the taxonomy will be associated with.
	public $args        = null;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $extraParams = []) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv, $extraParams);
		$this->overrideTaxonomy($extraParams['taxonomy']);
		$this->prepareArguments();
		$this->customProperties();
		$this->prepareArguments($this->args);
	}

	/*
	 *
	 */

	public function init($taxonomy = null) {
		$taxonomy = $this->taxonomy ?? $taxonomy;
		if ($taxonomy) {
			register_taxonomy($taxonomy, $this->object_type, $this->args);
		}
	}

	/*
	 *
	 */

	protected function overrideTaxonomy($taxonomy = null) {
		if ($taxonomy && !$this->taxonomy) {
			$this->taxonomy = $taxonomy;
		}
	}

	protected function prepareArguments($args = null) {
		$this->args = new TaxonomyData($this, $args);
		foreach ($this->toArray() as $key => $value) {
			if (property_exists($this->args, $key)) {
				$this->args->{$key} = $value;
//				unset($this->args->{$key});
			}
			if (array_key_exists($key, $this->args->labels) && !$args) {
				$this->args->labels[$key] = $value;
				unset($this->args->{$key});
			}
		}
		unset($this->args->args);
	}

	/*
	 *
	 */

	abstract public function customProperties();

}