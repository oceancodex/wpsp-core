<?php

namespace OCBPCORE\Objects\Updater;

use YahnisElsts\PluginUpdateChecker\v5p4\PucFactory;

class Updater {

	public static function init(): ?\YahnisElsts\PluginUpdateChecker\v5p4\Plugin\PluginInfo {

		// Disable SSL verification.
		add_filter('puc_request_info_options-' . config('app.short_name'), function ($options) {
			$options['sslverify'] = false;
			return $options;
		});

		try {
			$updateChecker = PucFactory::buildUpdateChecker(
				OCBP_PUBLIC_URL . '/plugin.json',
				OCBP_PLUGIN_FILE_PATH,
				OCBP_TEXT_DOMAIN,
			);

			return $updateChecker->requestInfo();
		}
		catch (\Exception $e) {
			return null;
		}

	}

}