<?php

namespace WPSPCORE\Console;

use WPSPCORE\Console\Commands\MakeAdminPageCommand;
use WPSPCORE\Console\Commands\MakeAjaxCommand;
use WPSPCORE\Console\Commands\MakeAPICommand;
use WPSPCORE\Console\Commands\MakeCommandCommand;
use WPSPCORE\Console\Commands\MakeControllerCommand;
use WPSPCORE\Console\Commands\MakeEntityCommand;
use WPSPCORE\Console\Commands\MakeEventCommand;
use WPSPCORE\Console\Commands\MakeExceptionCommand;
use WPSPCORE\Console\Commands\MakeListenerCommand;
use WPSPCORE\Console\Commands\MakeListTableCommand;
use WPSPCORE\Console\Commands\MakeMetaBoxCommand;
use WPSPCORE\Console\Commands\MakeMiddlewareCommand;
use WPSPCORE\Console\Commands\MakeMigrationCommand;
use WPSPCORE\Console\Commands\MakeModelCommand;
use WPSPCORE\Console\Commands\MakeNavLocationCommand;
use WPSPCORE\Console\Commands\MakeNavMenuCommand;
use WPSPCORE\Console\Commands\MakePostTypeColumnCommand;
use WPSPCORE\Console\Commands\MakePostTypeCommand;
use WPSPCORE\Console\Commands\MakeRequestCommand;
use WPSPCORE\Console\Commands\MakeRewriteFrontPageCommand;
use WPSPCORE\Console\Commands\MakeRoleCommand;
use WPSPCORE\Console\Commands\MakeScheduleCommand;
use WPSPCORE\Console\Commands\MakeSeederCommand;
use WPSPCORE\Console\Commands\MakeShortcodeCommand;
use WPSPCORE\Console\Commands\MakeTaxonomyColumnCommand;
use WPSPCORE\Console\Commands\MakeTaxonomyCommand;
use WPSPCORE\Console\Commands\MakeTemplateCommand;
use WPSPCORE\Console\Commands\MakeUserMetaBoxCommand;
use WPSPCORE\Console\Commands\MigrationDiffCommand;
use WPSPCORE\Console\Commands\MigrationMigrateCommand;
use WPSPCORE\Console\Commands\QueueFailedCommand;
use WPSPCORE\Console\Commands\QueueWorkCommand;
use WPSPCORE\Console\Commands\RouteRemapCommand;
use WPSPCORE\Console\Commands\RouteWatchCommand;

class Kernel {

	public static function initCommands($mainPath, $rootNamespace, $prefixEnv, $extraParams = []) {
		$application = $extraParams['application'] ?? null;

		if (!$application) return;

		$commands = [
			MakeAdminPageCommand::class,
			MakeAjaxCommand::class,
			MakeAPICommand::class,
			MakeCommandCommand::class,
			MakeControllerCommand::class,
			MakeEntityCommand::class,
			MakeEventCommand::class,
			MakeExceptionCommand::class,
			MakeListenerCommand::class,
			MakeListTableCommand::class,
			MakeMetaBoxCommand::class,
			MakeMiddlewareCommand::class,
			class_exists('\WPSPCORE\Migration\Migration') ? MakeMigrationCommand::class : null,
			class_exists('\WPSPCORE\Database\Eloquent') ? MakeModelCommand::class : null,
			MakeNavLocationCommand::class,
			MakeNavMenuCommand::class,
			MakePostTypeColumnCommand::class,
			MakePostTypeCommand::class,
			MakeRequestCommand::class,
			MakeRewriteFrontPageCommand::class,
			MakeRoleCommand::class,
			MakeScheduleCommand::class,
			MakeSeederCommand::class,
			MakeShortcodeCommand::class,
			MakeTaxonomyColumnCommand::class,
			MakeTaxonomyCommand::class,
			MakeTemplateCommand::class,
			MakeUserMetaBoxCommand::class,

			RouteRemapCommand::class,
			RouteWatchCommand::class,

			QueueWorkCommand::class,
			QueueFailedCommand::class,

			class_exists('\WPSPCORE\Migration\Migration') ? MigrationDiffCommand::class : null,
			class_exists('\WPSPCORE\Migration\Migration') ? MigrationMigrateCommand::class : null,
		];

		foreach ($commands as $command) {
			if ($command) {
				$application->add(new $command($mainPath, $rootNamespace, $prefixEnv, $extraParams));
			}
		}
	}

}
