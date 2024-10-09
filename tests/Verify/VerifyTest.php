<?php

namespace LycheeVerify\Tests\Verify;

use LycheeVerify\Contract\Status;
use LycheeVerify\Exceptions\SupporterOnlyOperationException;
use LycheeVerify\Tests\Constants;
use LycheeVerify\Tests\TestCase;
use LycheeVerify\Verify;
use function Safe\preg_replace;

class VerifyTest extends TestCase
{
	public function testVerifyDefault(): void
	{
		$verify = new Verify();
		self::assertEquals($verify->get_status(), Status::FREE_EDITION);

		self::assertFalse($verify->check());
		self::assertTrue($verify->check(Status::FREE_EDITION));
		self::assertFalse($verify->check(Status::SUPPORTER_EDITION));
		self::assertFalse($verify->check(Status::PLUS_EDITION));
		self::assertFalse($verify->is_supporter()); // User is not recognised as supporter.
		self::assertFalse($verify->is_plus()); // User is not recognised as plus.

		$this->assertThrows(fn () => $verify->authorize(Status::SUPPORTER_EDITION), SupporterOnlyOperationException::class, 'supporters');
		$this->assertThrows(fn () => $verify->authorize(Status::PLUS_EDITION), SupporterOnlyOperationException::class, 'plus');
	}

	public function testVerifySupporterDefault(): void
	{
		$verify = new Verify(
			license_key: Constants::HASH_KEY,
			hash: Constants::HASH,
		);
		self::assertEquals($verify->get_status(), Status::SUPPORTER_EDITION);

		self::assertTrue($verify->check());
		self::assertTrue($verify->check(Status::FREE_EDITION));
		self::assertTrue($verify->check(Status::SUPPORTER_EDITION));
		self::assertFalse($verify->check(Status::PLUS_EDITION));
		self::assertTrue($verify->is_supporter()); // User is recognised as supporter.
		self::assertFalse($verify->is_plus()); // User is not recognised as plus.

		$this->assertThrows(fn () => $verify->authorize(Status::PLUS_EDITION), SupporterOnlyOperationException::class, 'plus');
	}

	public function testVerifyPlus(): void
	{
		$verify = new Verify(
			config_email: Constants::EMAIL_TEST,
			license_key: Constants::LICENSE_JSON,
			public_key: Constants::PUBLIC_EC_KEY,
		);
		self::assertEquals($verify->get_status(), Status::PLUS_EDITION);
		self::assertTrue($verify->check());
		self::assertTrue($verify->check(Status::FREE_EDITION));
		self::assertTrue($verify->check(Status::SUPPORTER_EDITION));
		self::assertTrue($verify->check(Status::PLUS_EDITION));
		self::assertTrue($verify->is_supporter()); // user is recognised as supporter.
		self::assertTrue($verify->is_plus()); // user is recognised as plus.
	}

	public function testVerifyValidate(): void
	{
		$verify = new Verify();

		// Check config before executing validation
		$checks = config('verify.validation');
		foreach ($checks as $class => $value) {
			$file = (new \ReflectionClass($class))->getFileName();
			if ($file === false || !file_exists($file)) {
				self::fail(sprintf('Validation failed for %s: file not found', $class));
			}
			// this necessary because stupid line endings in Windows.
			/** @var string $content */
			$content = file_get_contents($file);  // @phpstan-ignore-line
			$content = preg_replace('~\R~u', "\n", $content);
			if (sha1($content) !== $value) {
				self::fail(sprintf("Validation failed for %s: expected '%s'", $class, sha1($content)));
			}
		}

		self::assertTrue($verify->validate());
	}
}
