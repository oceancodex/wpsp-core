<?php

namespace OCBPCORE\Objects\Data;

use OCBPCORE\Base\BaseData;
use Symfony\Component\HttpClient\HttpClient;

class AccessTokenData extends BaseData {
	public string $access_token;
	public string $token_type;
	public int    $expires_in;

	public function __construct() {
		$this->prepareAccessTokenData();
    }

	public function getAccessToken(): string {
		return $this->access_token;
	}

	public function getTokenType(): string {
		return $this->token_type;
	}

	public function getExpiresIn(): int {
		return $this->expires_in;
	}

	public function setAccessToken($access_token): void {
		$this->access_token = $access_token;
	}

	public function setTokenType($token_type): void {
		$this->token_type = $token_type;
	}

	public function setExpiresIn($expires_in): void {
		$this->expires_in = $expires_in;
	}

	/*
	 *
	 */

	public function prepareAccessTokenData(): void {
		$response = HttpClient::create()->request('POST', 'https://domain.com/oauth/token', [
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
				'verify'       => false,
			],
			'body' => json_encode([
				'grant_type'    => 'client_credentials',
				'client_id'     => 3,
				'scope'         => '*',
				'client_secret' => 'XXXXXXXXXXXXXXXXXXXX',
			])
		])->getContent();
		$response = json_decode($response, true);
		$this->setAccessToken($response['access_token']);
		$this->setTokenType($response['token_type']);
		$this->setExpiresIn($response['expires_in']);
	}

}