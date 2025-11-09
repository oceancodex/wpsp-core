<?php

namespace WPSPCORE\Validation;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Factory;
use WPSPCORE\Base\BaseInstances;

/**
 * Validation.
 * @property \Illuminate\Validation\Factory|null        $factory
 * @property \Illuminate\Translation\Translator|null    $translator
 * @property \WPSPCORE\Database\Eloquent|null           $eloquent
 */
class Validation extends BaseInstances {

	public $factory      = null;
	public $translator   = null;
	public $eloquent     = null;
	public $langPaths    = [];

	public function setupTranslator() {
		if (!$this->translator) {
			$langPaths = !empty($this->langPaths) ? $this->langPaths : [
				__DIR__ . '/../lang',
			];

			// Lấy path đầu tiên làm mặc định
			$defaultPath = is_array($langPaths) ? reset($langPaths) : $langPaths;

			$loader = new FileLoader(new Filesystem(), $defaultPath);

			// Nếu có nhiều path, thêm namespace thủ công
			if (is_array($langPaths)) {
				foreach ($langPaths as $namespace => $path) {
					if ($path !== $defaultPath) {
						$loader->addNamespace(is_string($namespace) ? $namespace : 'extra', $path);
					}
				}
			}

			$locale = function_exists('get_locale') ? get_locale() : 'en';
			$this->translator = new Translator($loader, $locale);
		}
	}

	public function setupPresenceVerifier() {
		if ($this->eloquent && $this->eloquent->getCapsule()) {
			$db = $this->eloquent->getCapsule()->getDatabaseManager();
			$presenceVerifier = new DatabasePresenceVerifier($db);
			$this->factory->setPresenceVerifier($presenceVerifier);
		}
	}

	/*
	 *
	 */

	public function setLangPaths($paths) {
		$this->langPaths  = $paths;
		$this->translator = null;
		$this->factory    = null;
	}

	public function setEloquentForPresenceVerifier($eloquent) {
		$this->eloquent = $eloquent;

		// Reinitialize if factory already exists
		if ($this->factory && $eloquent && $eloquent->getCapsule()) {
			$db = $eloquent->getCapsule()->getDatabaseManager();
			$presenceVerifier = new DatabasePresenceVerifier($db);
			$this->factory->setPresenceVerifier($presenceVerifier);
		}
	}

	/*
	 *
	 */

	public function initFactory() {
		if (!$this->factory) {
			// Setup translator
			$this->setupTranslator();

			// Create validation factory
			$this->factory = new Factory($this->translator, Container::getInstance());

			// Setup database presence verifier for exists/unique rules
			$this->setupPresenceVerifier();
		}
	}

	public function make(array $data, array $rules, array $messages = [], array $customAttributes = []) {
		return $this->factory->make($data, $rules, $messages, $customAttributes);
	}

	public function extend($rule, $extension, $message = null) {
		$this->factory->extend($rule, $extension, $message);
	}

	public function factory() {
		return $this->factory;
	}

	public function replacer($rule, $replacer) {
		$this->factory->replacer($rule, $replacer);
	}

	public function validate(array $data, array $rules, array $messages = [], array $customAttributes = []) {
		$validation = $this->make($data, $rules, $messages, $customAttributes);
		return $validation->validate();
	}

	public function extendImplicit($rule, $extension, $message = null) {
		$this->factory->extendImplicit($rule, $extension, $message);
	}

}