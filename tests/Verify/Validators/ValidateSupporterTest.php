<?php

namespace LycheeVerify\Tests\Verify\Validators;

use LycheeVerify\Tests\Constants;
use LycheeVerify\Tests\TestCase;
use LycheeVerify\Validators\ValidateSupporter;

class ValidateSupporterTest extends TestCase
{
	public function testValidHash(): void
	{
		$crypto = new ValidateSupporter(Constants::HASH);
		$signature = Constants::HASH_KEY;
		$verifiable = '';
		self::assertTrue($crypto->validate($verifiable, $signature));
	}

	public function testInvalidHash(): void
	{
		$crypto = new ValidateSupporter(Constants::HASH);
		$signature = 'random stuff';
		$verifiable = '';
		self::assertFalse($crypto->validate($verifiable, $signature));
	}
}
