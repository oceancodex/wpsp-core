<?php

namespace WPSPCORE\Validation;

use WPSPCORE\Validation\Validation;

class WPDBValidation {

	/**
	 * Validate WPDB data before insert/update
	 *
	 * @param array $data
	 * @param array $rules
	 * @param array $messages
	 * @param array $customAttributes
	 * @return array
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public static function validate(array $data, array $rules, array $messages = [], array $customAttributes = []) {
		return Validation::validate($data, $rules, $messages, $customAttributes);
	}

	/**
	 * Validate and insert data into WPDB table
	 *
	 * @param string $table
	 * @param array $data
	 * @param array $rules
	 * @param array $format
	 * @return int|false
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public static function insert($table, array $data, array $rules, array $format = null) {
		global $wpdb;

		$validated = self::validate($data, $rules);

		return $wpdb->insert($table, $validated, $format);
	}

	/**
	 * Validate and update data in WPDB table
	 *
	 * @param string $table
	 * @param array $data
	 * @param array $where
	 * @param array $rules
	 * @param array $format
	 * @param array $whereFormat
	 * @return int|false
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public static function update($table, array $data, array $where, array $rules, array $format = null, array $whereFormat = null) {
		global $wpdb;

		$validated = self::validate($data, $rules);

		return $wpdb->update($table, $validated, $where, $format, $whereFormat);
	}

}