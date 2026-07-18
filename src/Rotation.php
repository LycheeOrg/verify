<?php

namespace LycheeVerify;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use LycheeVerify\Contract\RotationResult;
use LycheeVerify\Contract\Status;
use LycheeVerify\Contract\VerifyFactory;

class Rotation
{
	public const CACHE_KEY = 'verify.rotation.next_retry';

	public function __construct(
		private VerifyFactory $verifyFactory,
	) {
	}

	public function rotate(): RotationResult
	{
		/** @var string $api_key */
		$api_key = config('verify.keygen_api_key', '');
		if ($api_key === '') {
			return RotationResult::fail('No API key configured.');
		}

		if (Cache::has(self::CACHE_KEY)) {
			return RotationResult::fail('Retry timeout active.');
		}

		/** @var string $url */
		$url = config('verify.keygen_url', '');
		$response = Http::withToken($api_key)->get($url . '/licenses/me');

		if ($response->serverError()) {
			Cache::put(self::CACHE_KEY, true, now()->addDay());

			return RotationResult::fail('HTTP request failed with status ' . $response->status() . '.');
		}

		/** @var array{message?: string, value?: string|null, tier?: string|null} $data */
		$data = $response->json();

		if (isset($data['message'])) {
			Cache::put(self::CACHE_KEY, true, now()->addDay());

			return RotationResult::fail($data['message']);
		}

		if (!isset($data['value']) || !isset($data['tier'])) {
			Cache::put(self::CACHE_KEY, true, now()->addDay());

			return RotationResult::fail('No valid license in response.');
		}

		$license_key = $data['value'];

		$verify = $this->verifyFactory->make($license_key);
		if ($verify->get_status() === Status::FREE_EDITION) {
			Cache::put(self::CACHE_KEY, true, now()->addDay());

			return RotationResult::fail('Fetched key failed local validation.');
		}

		if (Schema::hasTable('configs')) {
			DB::table('configs')->where('key', 'license_key')->update(['value' => $license_key]);
		}

		return RotationResult::ok();
	}
}
