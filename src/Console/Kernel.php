<?php

namespace WPSPCORE\Console;

use Symfony\Component\Console\Application;

class Kernel {

	public static function commands(Application $application): void {
		$application->add(new \WPSPCORE\Console\Commands\MakeAdminPageCommand());
		$application->add(new \WPSPCORE\Console\Commands\MakeControllerCommand());
		$application->add(new \WPSPCORE\Console\Commands\MakeEntityCommand());
		$application->add(new \WPSPCORE\Console\Commands\MakeMetaBoxCommand());
		$application->add(new \WPSPCORE\Console\Commands\MakeMiddlewareCommand());
		$application->add(new \WPSPCORE\Console\Commands\MakeMigrationCommand());
		$application->add(new \WPSPCORE\Console\Commands\MakeModelCommand());
		$application->add(new \WPSPCORE\Console\Commands\MakePostTypeCommand());
		$application->add(new \WPSPCORE\Console\Commands\MakeRewriteFrontPageCommand());
		$application->add(new \WPSPCORE\Console\Commands\MakeShortcodeCommand());
		$application->add(new \WPSPCORE\Console\Commands\MakeTaxonomyCommand());
		$application->add(new \WPSPCORE\Console\Commands\MakeTemplateCommand());
	}

}
