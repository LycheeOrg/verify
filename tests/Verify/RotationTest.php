<?php

namespace LycheeVerify\Tests\Verify;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use LycheeVerify\DefaultVerifyFactory;
use LycheeVerify\Rotation;
use LycheeVerify\Tests\Constants;
use LycheeVerify\Tests\TestCase;
use LycheeVerify\Tests\TestVerifyFactory;

class RotationTest extends TestCase
{
	private const KEYGEN_URL = 'https://keygen.lycheeorg.dev/api';

	private function makeRotation(#[\SensitiveParameter] ?string $hash_supporter = null): Rotation
	{
		$factory = $hash_supporter !== null
			? new TestVerifyFactory($hash_supporter)
			: new DefaultVerifyFactory();

		return new Rotation($factory);
	}

	public function testNoApiKey(): void
	{
		config()->set('verify.keygen_api_key', '');
		Http::fake();

		$rotation = $this->makeRotation();
		$result = $rotation->rotate();

		self::assertFalse($result->success);
		self::assertEquals('No API key configured.', $result->message);
		Http::assertNothingSent();
	}

	public function testCacheTimeoutActive(): void
	{
		config()->set('verify.keygen_api_key', 'some-key');
		config()->set('verify.keygen_url', self::KEYGEN_URL);
		Cache::put(Rotation::CACHE_KEY, true, now()->addDay());
		Http::fake();

		$rotation = $this->makeRotation();
		$result = $rotation->rotate();

		self::assertFalse($result->success);
		self::assertEquals('Retry timeout active.', $result->message);
		Http::assertNothingSent();
	}

	public function testHttpFailure(): void
	{
		config()->set('verify.keygen_api_key', 'some-key');
		config()->set('verify.keygen_url', self::KEYGEN_URL);
		Http::fake([self::KEYGEN_URL . '/licenses/me' => Http::response('', 500)]);

		$rotation = $this->makeRotation();
		$result = $rotation->rotate();

		self::assertFalse($result->success);
		self::assertEquals('HTTP request failed with status 500.', $result->message);
		self::assertTrue(Cache::has(Rotation::CACHE_KEY));
	}

	public function testUnauthenticated(): void
	{
		config()->set('verify.keygen_api_key', 'bad-key');
		config()->set('verify.keygen_url', self::KEYGEN_URL);
		Http::fake([self::KEYGEN_URL . '/licenses/me' => Http::response(['message' => 'Unauthenticated.'], 401)]);

		$rotation = $this->makeRotation();
		$result = $rotation->rotate();

		self::assertFalse($result->success);
		self::assertEquals('Unauthenticated.', $result->message);
		self::assertTrue(Cache::has(Rotation::CACHE_KEY));
	}

	public function testNullValueTierNone(): void
	{
		config()->set('verify.keygen_api_key', 'some-key');
		config()->set('verify.keygen_url', self::KEYGEN_URL);
		Http::fake([self::KEYGEN_URL . '/licenses/me' => Http::response([
			'type' => null,
			'value' => null,
			'email' => null,
			'tier' => 'none',
			'sponsorship_active' => false,
		])]);

		$rotation = $this->makeRotation();
		$result = $rotation->rotate();

		self::assertFalse($result->success);
		self::assertEquals('No valid license in response.', $result->message);
		self::assertTrue(Cache::has(Rotation::CACHE_KEY));
		self::assertEmpty(DB::table('configs')->where('key', 'license_key')->first()->value ?? '');
	}

	public function testKeyFailsLocalValidation(): void
	{
		config()->set('verify.keygen_api_key', 'some-key');
		config()->set('verify.keygen_url', self::KEYGEN_URL);
		Http::fake([self::KEYGEN_URL . '/licenses/me' => Http::response([
			'type' => null,
			'value' => 'INVALID-KEY-THAT-WONT-PASS',
			'email' => null,
			'tier' => 'se',
			'sponsorship_active' => true,
		])]);

		DB::table('configs')->insert(['key' => 'license_key', 'value' => '', 'cat' => 'config', 'type_range' => 'string', 'is_secret' => true, 'description' => '']);

		$rotation = $this->makeRotation(hash_supporter: Constants::HASH);
		$result = $rotation->rotate();

		self::assertFalse($result->success);
		self::assertEquals('Fetched key failed local validation.', $result->message);
		self::assertTrue(Cache::has(Rotation::CACHE_KEY));
		self::assertEquals('', DB::table('configs')->where('key', 'license_key')->first()->value); // @phpstan-ignore-line
	}

	public function testSuccessfulRotation(): void
	{
		config()->set('verify.keygen_api_key', 'valid-key');
		config()->set('verify.keygen_url', self::KEYGEN_URL);
		Http::fake([self::KEYGEN_URL . '/licenses/me' => Http::response([
			'type' => null,
			'value' => Constants::HASH_KEY,
			'email' => null,
			'tier' => 'se',
			'sponsorship_active' => true,
		])]);

		DB::table('configs')->insert(['key' => 'license_key', 'value' => '', 'cat' => 'config', 'type_range' => 'string', 'is_secret' => true, 'description' => '']);

		$rotation = $this->makeRotation(hash_supporter: Constants::HASH);
		$result = $rotation->rotate();

		self::assertTrue($result->success);
		self::assertNull($result->message);
		self::assertEquals(Constants::HASH_KEY, DB::table('configs')->where('key', 'license_key')->first()->value); // @phpstan-ignore-line
		self::assertFalse(Cache::has(Rotation::CACHE_KEY));
	}
}
