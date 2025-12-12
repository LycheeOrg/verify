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
		self::assertFalse($verify->check(Status::PRO_EDITION));
		self::assertFalse($verify->check(Status::SIGNATURE_EDITION));
		self::assertFalse($verify->is_supporter()); // User is not recognised as supporter.
		self::assertFalse($verify->is_pro()); // User is not recognised as pro.

		$this->assertThrows(fn () => $verify->authorize(Status::SUPPORTER_EDITION), SupporterOnlyOperationException::class, 'supporters');
		$this->assertThrows(fn () => $verify->authorize(Status::PRO_EDITION), SupporterOnlyOperationException::class, 'pro');
	}

	public function testVerifySupporter(): void
	{
		$verify = new Verify(
			license_key: Constants::HASH_KEY,
			hash_supporter: Constants::HASH,
			hash_pro: Constants::HASH2,
		);
		self::assertEquals($verify->get_status(), Status::SUPPORTER_EDITION);

		self::assertTrue($verify->check());
		self::assertTrue($verify->check(Status::FREE_EDITION));
		self::assertTrue($verify->check(Status::SUPPORTER_EDITION));
		self::assertFalse($verify->check(Status::PRO_EDITION));
		self::assertFalse($verify->check(Status::SIGNATURE_EDITION));
		self::assertTrue($verify->is_supporter()); // User is recognised as supporter.
		self::assertFalse($verify->is_pro()); // User is not recognised as pro.
		self::assertFalse($verify->is_signature()); // User is not recognised as signature.

		$this->assertThrows(fn () => $verify->authorize(Status::PRO_EDITION), SupporterOnlyOperationException::class, 'pro');
	}

	public function testVerifyPro(): void
	{
		$verify = new Verify(
			license_key: Constants::HASH2_KEY,
			hash_supporter: Constants::HASH,
			hash_pro: Constants::HASH2,
		);
		self::assertEquals($verify->get_status(), Status::PRO_EDITION);

		self::assertTrue($verify->check());
		self::assertTrue($verify->check(Status::FREE_EDITION));
		self::assertTrue($verify->check(Status::SUPPORTER_EDITION));
		self::assertTrue($verify->check(Status::PRO_EDITION));
		self::assertFalse($verify->check(Status::SIGNATURE_EDITION));
		self::assertTrue($verify->is_supporter()); // User is recognised as supporter.
		self::assertTrue($verify->is_pro()); // User is recognised as pro.
		self::assertFalse($verify->is_signature()); // User is not recognised as signature.
	}

	public function testVerifySignature(): void
	{
		$verify = new Verify(
			config_email: Constants::EMAIL_TEST,
			license_key: Constants::LICENSE_JSON,
			public_key: Constants::PUBLIC_EC_KEY,
		);
		self::assertEquals($verify->get_status(), Status::SIGNATURE_EDITION);
		self::assertTrue($verify->check());
		self::assertTrue($verify->check(Status::FREE_EDITION));
		self::assertTrue($verify->check(Status::SUPPORTER_EDITION));
		self::assertTrue($verify->check(Status::PRO_EDITION));
		self::assertTrue($verify->check(Status::SIGNATURE_EDITION));
		self::assertTrue($verify->is_supporter()); // user is recognised as supporter.
		self::assertTrue($verify->is_pro()); // user is recognised as pro.
		self::assertTrue($verify->is_signature()); // User is not recognised as signature.
	}

	public function testVerifyValidate(): void
	{
		$verify = new Verify();

		// Check config before executing validation
		/** @var array<class-string,string> $checks */
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
