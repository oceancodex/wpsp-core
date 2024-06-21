<?php

namespace OCBPCORE\Console;

use Symfony\Component\Console\Application;

class Kernel {

	public static function commands(Application $application): void {
		$application->add(new \OCBPCORE\Console\Commands\MakeAdminPageCommand());
		$application->add(new \OCBPCORE\Console\Commands\MakeControllerCommand());
		$application->add(new \OCBPCORE\Console\Commands\MakeEntityCommand());
		$application->add(new \OCBPCORE\Console\Commands\MakeMetaBoxCommand());
		$application->add(new \OCBPCORE\Console\Commands\MakeMiddlewareCommand());
		$application->add(new \OCBPCORE\Console\Commands\MakeMigrationCommand());
		$application->add(new \OCBPCORE\Console\Commands\MakeModelCommand());
		$application->add(new \OCBPCORE\Console\Commands\MakePostTypeCommand());
		$application->add(new \OCBPCORE\Console\Commands\MakeRewriteFrontPageCommand());
		$application->add(new \OCBPCORE\Console\Commands\MakeShortcodeCommand());
		$application->add(new \OCBPCORE\Console\Commands\MakeTaxonomyCommand());
		$application->add(new \OCBPCORE\Console\Commands\MakeTemplateCommand());
	}

}
