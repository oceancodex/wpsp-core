<?php

namespace WPSPCORE\Listeners;

use Doctrine\Common\EventSubscriber;
use Doctrine\Migrations\Events;

class MigrationListener implements EventSubscriber {

	public function getSubscribedEvents() {
		return [
			Events::onMigrationsMigrating,
			Events::onMigrationsMigrated,
			Events::onMigrationsVersionExecuting,
			Events::onMigrationsVersionExecuted,
			Events::onMigrationsVersionSkipped,
		];
	}

	public function onMigrationsMigrating($args) {
		// ...
	}

	public function onMigrationsMigrated($args) {
		// ...
	}

	public function onMigrationsVersionExecuting($args) {
		// ...
	}

	public function onMigrationsVersionExecuted($args) {
		// ...
	}

	public function onMigrationsVersionSkipped($args) {
		// ...
	}

}