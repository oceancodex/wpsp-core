<?php

namespace WPSPCORE\App\Integrations\LaravelIgnition\ErrorPage;

class ErrorPageViewModel extends \Spatie\Ignition\ErrorPage\ErrorPageViewModel {

	public function updateConfigEndpoint(): string {
		return 'https://wpsp.local/_ignition/update-config';
	}

}
