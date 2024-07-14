<?php

namespace WPSPCORE\Base;

use WPSPCORE\Data\TaxonomyData;
use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

abstract class BaseTaxonomy extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public mixed $taxonomy    = null;
	public mixed $args        = null;
	public mixed $object_type = 'post';     // The post type which the taxonomy will be associated with.

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $taxonomy = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->overrideTaxonomy($taxonomy);
		$this->prepareArguments();
		$this->customProperties();
		$this->prepareArguments($this->args);
	}

	/*
	 *
	 */

	public function init($taxonomy = null): void {
		if ($this->taxonomy) {
			register_taxonomy($this->taxonomy, $this->object_type, $this->args);
		}
	}

	/*
	 *
	 */

	protected function overrideTaxonomy($taxonomy = null): void {
		if ($taxonomy && !$this->taxonomy) {
			$this->taxonomy = $taxonomy;
		}
	}

	protected function prepareArguments($args = null): void {
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