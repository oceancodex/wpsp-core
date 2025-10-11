<?php

namespace WPSPCORE\Base;

use WPSPCORE\Data\TaxonomyData;
use WPSPCORE\Traits\ObjectPropertiesToArrayTrait;

abstract class BaseTaxonomy extends BaseInstances {

	use ObjectPropertiesToArrayTrait;

	public $taxonomy          = null;
	public $args              = null;
	public $object_type       = 'post';     // The post type which the taxonomy will be associated with.
	public $callback_function = null;
	public $custom_properties = null;

	/*
	 *
	 */

	public function __construct($mainPath = null, $rootNamespace = null, $prefixEnv = null, $taxonomy = null, $callback_function = null, $custom_properties = null) {
		parent::__construct($mainPath, $rootNamespace, $prefixEnv);
		$this->callback_function = $callback_function;
		$this->custom_properties = $custom_properties;
		$this->overrideTaxonomy($taxonomy);
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