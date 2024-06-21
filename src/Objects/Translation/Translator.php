<?php

namespace WPSPCORE\Objects\Translation;

class Translator {

	public static function init() {
//		add_action('init', function () {
		load_plugin_textdomain(WPSP_TEXT_DOMAIN, false, WPSP_TEXT_DOMAIN . '/core/resources/lang/');
//		});
	}

}