<?php

namespace LycheeVerify;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use LycheeVerify\Contract\TokenExtensionResult;

class TokenExtension
{
	public function extend(): TokenExtensionResult
	{
		/** @var string $api_key */
		$api_key = config('verify.keygen_api_key', '');
		if ($api_key === '') {
			return TokenExtensionResult::fail('No API key configured.');
		}

		/** @var string $url */
		$url = config('verify.keygen_url', '');
		$response = Http::withToken($api_key)->patch($url . '/tokens/extend');

		if ($response->serverError()) {
			return TokenExtensionResult::fail('HTTP request failed with status ' . $response->status() . '.');
		}

		/** @var array{message?: string, id?: int, name?: string, scopes?: string[], last_used_at?: string, expires_at?: string, created_at?: string} $data */
		$data = $response->json();

		if (isset($data['message'])) {
			return TokenExtensionResult::fail($data['message']);
		}

		if (!isset($data['id']) || !isset($data['name']) || !isset($data['scopes']) || !isset($data['expires_at'])) {
			return TokenExtensionResult::fail('Incomplete response from server.');
		}

		return TokenExtensionResult::ok(
			id: $data['id'],
			name: $data['name'],
			scopes: $data['scopes'],
			expires_at: Carbon::parse($data['expires_at']),
		);
	}
}
