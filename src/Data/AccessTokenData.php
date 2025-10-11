<?php

namespace WPSPCORE\Data;

use Symfony\Component\HttpClient\HttpClient;
use WPSPCORE\Base\BaseData;

class AccessTokenData extends BaseData {

	public $access_token;
	public $token_type;
	public $expires_in;

	public function __construct() {
		$this->prepareAccessTokenData();
	}

	public function getAccessToken() {
		return $this->access_token;
	}

	public function getTokenType() {
		return $this->token_type;
	}

	public function getExpiresIn() {
		return $this->expires_in;
	}

	public function setAccessToken($access_token) {
		$this->access_token = $access_token;
	}

	public function setTokenType($token_type) {
		$this->token_type = $token_type;
	}

	public function setExpiresIn($expires_in) {
		$this->expires_in = $expires_in;
	}

	/*
	 *
	 */

	public function prepareAccessTokenData() {
		$response = HttpClient::create()->request('POST', 'https://domain.com/oauth/token', [
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
				'verify'       => false,
			],
			'body'    => json_encode([
				'grant_type'    => 'client_credentials',
				'client_id'     => 3,
				'scope'         => '*',
				'client_secret' => 'XXXXXXXXXXXXXXXXXXXX',
			]),
		])->getContent();
		$response = json_decode($response, true);
		$this->setAccessToken($response['access_token']);
		$this->setTokenType($response['token_type']);
		$this->setExpiresIn($response['expires_in']);
	}

}