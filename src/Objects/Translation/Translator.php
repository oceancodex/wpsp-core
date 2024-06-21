<?php

namespace OCBPCORE\Objects\Translation;

class Translator {

	public static function init() {
//		add_action('init', function () {
		load_plugin_textdomain(OCBP_TEXT_DOMAIN, false, OCBP_TEXT_DOMAIN . '/core/resources/lang/');
//		});
	}

}