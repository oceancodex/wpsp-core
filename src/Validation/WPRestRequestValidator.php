<?php

namespace WPSPCORE\Validation;

use Illuminate\Validation\ValidationException;
use WPSPCORE\Validation\Validation;

class WPRestRequestValidator {

	/**
	 * Validate WP_REST_Request
	 *
	 * @param \WP_REST_Request $request
	 * @param array $rules
	 * @param array $messages
	 * @param array $customAttributes
	 * @return array
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public static function validate(\WP_REST_Request $request, array $rules, array $messages = [], array $customAttributes = []) {
		$data = $request->get_params();

		return Validation::validate($data, $rules, $messages, $customAttributes);
	}

	/**
	 * Validate and return validator instance
	 *
	 * @param \WP_REST_Request $request
	 * @param array $rules
	 * @param array $messages
	 * @param array $customAttributes
	 * @return \Illuminate\Validation\Validator
	 */
	public static function validator(\WP_REST_Request $request, array $rules, array $messages = [], array $customAttributes = []) {
		$data = $request->get_params();

		return Validation::make($data, $rules, $messages, $customAttributes);
	}

	/**
	 * Check if validation passes
	 *
	 * @param \WP_REST_Request $request
	 * @param array $rules
	 * @param array $messages
	 * @param array $customAttributes
	 * @return bool
	 */
	public static function passes(\WP_REST_Request $request, array $rules, array $messages = [], array $customAttributes = []) {
		try {
			self::validate($request, $rules, $messages, $customAttributes);
			return true;
		} catch (ValidationException $e) {
			return false;
		}
	}

	/**
	 * Get validation errors
	 *
	 * @param \WP_REST_Request $request
	 * @param array $rules
	 * @param array $messages
	 * @param array $customAttributes
	 * @return array
	 */
	public static function errors(\WP_REST_Request $request, array $rules, array $messages = [], array $customAttributes = []) {
		try {
			self::validate($request, $rules, $messages, $customAttributes);
			return [];
		} catch (ValidationException $e) {
			return $e->errors();
		}
	}

}