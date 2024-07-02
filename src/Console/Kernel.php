<?php

namespace WPSPCORE\Console;

use Symfony\Component\Console\Application;
use WPSPCORE\Console\Commands\MakeAdminPageCommand;
use WPSPCORE\Console\Commands\MakeAjaxCommand;
use WPSPCORE\Console\Commands\MakeAPICommand;
use WPSPCORE\Console\Commands\MakeControllerCommand;
use WPSPCORE\Console\Commands\MakeEntityCommand;
use WPSPCORE\Console\Commands\MakeListTableCommand;
use WPSPCORE\Console\Commands\MakeMetaBoxCommand;
use WPSPCORE\Console\Commands\MakeMiddlewareCommand;
use WPSPCORE\Console\Commands\MakeMigrationCommand;
use WPSPCORE\Console\Commands\MakeModelCommand;
use WPSPCORE\Console\Commands\MakePostTypeCommand;
use WPSPCORE\Console\Commands\MakeRewriteFrontPageCommand;
use WPSPCORE\Console\Commands\MakeShortcodeCommand;
use WPSPCORE\Console\Commands\MakeTaxonomyCommand;
use WPSPCORE\Console\Commands\MakeTemplateCommand;

class Kernel {

	public static function initCommands(Application $application, $mainPath, $rootNamespace, $prefixEnv): void {
		$commands = [
			MakeAdminPageCommand::class,
			MakeAjaxCommand::class,
			MakeAPICommand::class,
			MakeControllerCommand::class,
            MakeEntityCommand::class,
            MakeListTableCommand::class,
            MakeMetaBoxCommand::class,
            MakeMiddlewareCommand::class,
            MakeMigrationCommand::class,
			MakeModelCommand::class,
            MakePostTypeCommand::class,
            MakeRewriteFrontPageCommand::class,
            MakeShortcodeCommand::class,
            MakeTaxonomyCommand::class,
			MakeTemplateCommand::class,
		];
		foreach ($commands as $command) {
			$application->add(new $command(null, $mainPath, $rootNamespace, $prefixEnv));
		}
	}

}
