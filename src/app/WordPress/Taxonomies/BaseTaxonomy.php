<?php

namespace WPSPCORE\App\WordPress\Taxonomies;

use WPSPCORE\App\Traits\ObjectToArrayTrait;
use WPSPCORE\BaseInstances;

abstract class BaseTaxonomy extends BaseInstances {

	use ObjectToArrayTrait;

	public $taxonomy          = null;
	public $object_type       = 'post';     // The post type which the taxonomy will be associated with.
	public $args              = null;
	public $callback_function = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overrideTaxonomy($this->extraParams['full_path']);
		$this->prepareArguments();
	}

	public function afterInstanceConstruct() {
		$this->prepareArgumentsAfterCustomProperties();
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

	protected function prepareArguments() {
		$this->args = new TaxonomyData($this);

		foreach ($this->toArray() as $key => $value) {
			if (property_exists($this->args, $key)) {
				$this->args->{$key} = $value;
			}
			if (array_key_exists($key, $this->args->labels)) {
				$this->args->labels[$key] = $value;
			}
		}
	}

	protected function prepareArgumentsAfterCustomProperties() {
		foreach ($this->toArray() as $key => $value) {
			if (property_exists($this->args, $key)) {
				$this->args->{$key} = $value;
			}
			if (array_key_exists($key, $this->args->labels)) {
				$this->args->labels[$key] = $value;
			}
		}

		// Unset taxonomy from args.
		unset($this->args->taxonomy);
		unset($this->args->previousArgs);
	}

}