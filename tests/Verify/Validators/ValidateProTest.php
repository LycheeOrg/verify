<?php

namespace LycheeVerify\Tests\Verify\Validators;

use LycheeVerify\Tests\Constants;
use LycheeVerify\Tests\TestCase;
use LycheeVerify\Validators\ValidatePro;

class ValidateProTest extends TestCase
{
	public function testValidHash(): void
	{
		$crypto = new ValidatePro(Constants::HASH2);
		$signature = Constants::HASH2_KEY;
		$verifiable = '';
		self::assertTrue($crypto->validate($verifiable, $signature));
	}

	public function testInvalidHash(): void
	{
		$crypto = new ValidatePro(Constants::HASH2);
		$signature = 'random stuff';
		$verifiable = '';
		self::assertFalse($crypto->validate($verifiable, $signature));
	}
}
