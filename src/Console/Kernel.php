<?php

namespace WPSPCORE\Console;

use Symfony\Component\Console\Application;

class Kernel {

	public static function initCommands(Application $application, $mainPath, $rootNamespace, $prefixEnv): void {
		$commands = [
			'MakeAdminPageCommand',
			'MakeControllerCommand',
            'MakeEntityCommand',
            'MakeMetaBoxCommand',
            'MakeMiddlewareCommand',
            'MakeMigrationCommand',
			'MakeModelCommand',
            'MakePostTypeCommand',
            'MakeRewriteFrontPageCommand',
            'MakeShortcodeCommand',
            'MakeTaxonomyCommand',
			'MakeTemplateCommand',
		];
		foreach ($commands as $command) {
			$command = '\WPSPCORE\Console\Commands\\' . $command;
			$application->add(new $command($mainPath, $rootNamespace, $prefixEnv));
		}
	}

}
