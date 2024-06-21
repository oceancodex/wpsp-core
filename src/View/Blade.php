<?php

namespace WPSPCORE\View;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use WPSPCORE\Objects\Cache\Cache;

class Blade {

	public array         $viewPaths;
	public string        $cachePath;
	public Container     $container;
	public Factory       $instance;
	public static ?Blade $BLADE = null;

	function __construct(array $viewPaths, string $cachePath) {
		$this->viewPaths = $viewPaths;
		$this->cachePath = $cachePath;

		$this->container = new Container();
		$this->instance  = $this->createFactory();
	}

	protected function createFactory(): Factory {
		$fs         = new Filesystem();
		$dispatcher = new Dispatcher($this->container);

		// Create a view factory that is capable of rendering PHP and Blade templates
		$viewResolver  = new EngineResolver();
		$bladeCompiler = new BladeCompiler($fs, $this->cachePath);

		$viewResolver->register('blade', function() use ($bladeCompiler) {
			return new CompilerEngine($bladeCompiler);
		});

		$viewFinder  = new FileViewFinder($fs, $this->viewPaths);
		$viewFactory = new Factory($viewResolver, $viewFinder, $dispatcher);
		$viewFactory->setContainer($this->container);
		$this->container->instance(\Illuminate\Contracts\View\Factory::class, $viewFactory);
		$this->container->instance(BladeCompiler::class, $bladeCompiler);

		// Share variables to all views.
		$viewFactory->share('settings', Cache::getItemValue(config('app.short_name') . '_settings'));

		return $viewFactory;
	}

	public function view(): Factory {
		return $this->instance;
	}

}