<?php

namespace LycheeVerify;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use LycheeVerify\Contract\Status;
use LycheeVerify\Contract\VerifyFactory;

class Rotation
{
	public const CACHE_KEY = 'verify.rotation.next_retry';

	public function __construct(
		private VerifyFactory $verifyFactory,
	) {}

	public function rotate(): ?string
	{
		/** @var string $api_key */
		$api_key = config('verify.keygen_api_key', '');
		if ($api_key === '') {
			return null;
		}

		if (Cache::has(self::CACHE_KEY)) {
			return null;
		}

		/** @var string $url */
		$url = config('verify.keygen_url', '');
		$response = Http::withToken($api_key)->get($url);

		if ($response->failed()) {
			Cache::put(self::CACHE_KEY, true, now()->addDay());

			return null;
		}

		/** @var array{message?: string, value?: string|null, tier?: string|null} $data */
		$data = $response->json();

		if (isset($data['message']) || !isset($data['value']) || !isset($data['tier']) || $data['tier'] === 'none') {
			Cache::put(self::CACHE_KEY, true, now()->addDay());

			return null;
		}

		$license_key = $data['value'];

		$verify = $this->verifyFactory->make($license_key);
		if ($verify->get_status() === Status::FREE_EDITION) {
			Cache::put(self::CACHE_KEY, true, now()->addDay());

			return null;
		}

		if (Schema::hasTable('configs')) {
			DB::table('configs')->where('key', 'license_key')->update(['value' => $license_key]);
		}

		return $license_key;
	}
}
