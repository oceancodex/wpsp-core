<?php

namespace WPSPCORE\App\Http\Requests;

use Illuminate\Foundation\Auth\EmailVerificationRequest as Request;

class EmailVerificationRequest extends Request {

	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize() {
		dd($this->route('hash'));
		return true;
	}

}
