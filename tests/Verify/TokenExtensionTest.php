<?php

namespace LycheeVerify\Tests\Verify;

use Illuminate\Support\Facades\Http;
use LycheeVerify\Tests\TestCase;
use LycheeVerify\TokenExtension;

class TokenExtensionTest extends TestCase
{
	private const KEYGEN_URL = 'https://keygen.lycheeorg.dev/api';

	public function testNoApiKey(): void
	{
		config()->set('verify.keygen_api_key', '');
		Http::fake();

		$extension = new TokenExtension();
		$result = $extension->extend();

		self::assertFalse($result->success);
		self::assertEquals('No API key configured.', $result->message);
		Http::assertNothingSent();
	}

	public function testHttpFailure(): void
	{
		config()->set('verify.keygen_api_key', 'some-key');
		config()->set('verify.keygen_url', self::KEYGEN_URL);
		Http::fake([self::KEYGEN_URL . '/tokens/extend' => Http::response('', 500)]);

		$extension = new TokenExtension();
		$result = $extension->extend();

		self::assertFalse($result->success);
		self::assertEquals('HTTP request failed with status 500.', $result->message);
	}

	public function testUnauthenticated(): void
	{
		config()->set('verify.keygen_api_key', 'bad-key');
		config()->set('verify.keygen_url', self::KEYGEN_URL);
		Http::fake([self::KEYGEN_URL . '/tokens/extend' => Http::response(['message' => 'Unauthenticated.'], 401)]);

		$extension = new TokenExtension();
		$result = $extension->extend();

		self::assertFalse($result->success);
		self::assertEquals('Unauthenticated.', $result->message);
	}

	public function testSuccessfulExtension(): void
	{
		config()->set('verify.keygen_api_key', 'valid-key');
		config()->set('verify.keygen_url', self::KEYGEN_URL);
		Http::fake([self::KEYGEN_URL . '/tokens/extend' => Http::response([
			'id' => 2,
			'name' => 'testing',
			'scopes' => ['licenses:read'],
			'last_used_at' => '2026-06-21T09:19:08+00:00',
			'expires_at' => '2027-06-21T09:18:23+00:00',
			'created_at' => '2026-06-21T09:18:23+00:00',
		])]);

		$extension = new TokenExtension();
		$result = $extension->extend();

		self::assertTrue($result->success);
		self::assertNull($result->message);
		self::assertEquals(2, $result->id);
		self::assertEquals('testing', $result->name);
		self::assertEquals(['licenses:read'], $result->scopes);
		self::assertNotNull($result->expires_at);
		self::assertEquals('2027-06-21T09:18:23+00:00', $result->expires_at->toIso8601String());
	}
}
