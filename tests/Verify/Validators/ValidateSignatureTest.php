<?php

namespace LycheeVerify\Tests\Verify\Validators;

use LycheeVerify\Tests\Constants;
use LycheeVerify\Tests\TestCase;
use LycheeVerify\Validators\ValidateSignature;

class ValidateSignatureTest extends TestCase
{
	public function testValidSignature(): void
	{
		$crypto = new ValidateSignature(Constants::PUBLIC_EC_KEY);
		$signature = Constants::LICENSE;
		$verifiable = Constants::VERIFIABLE;
		self::assertTrue($crypto->validate($verifiable, $signature));
	}

	public function testInvalidSignature(): void
	{
		$crypto = new ValidateSignature(Constants::PUBLIC_EC_KEY);
		$signature = 'MEUCIQD/WoyAdTdU9HfHAMoiZR5VoAOsWl10oSgLXbyMICBQ+wIgOhnc/YqvzCluPilZifF9TylmVkHQElraXN6KmiCu9r2==';
		$verifiable = Constants::VERIFIABLE;
		self::assertFalse($crypto->validate($verifiable, $signature));
	}

	public function testInvalidVerifiable(): void
	{
		$crypto = new ValidateSignature(Constants::PUBLIC_EC_KEY);
		$signature = Constants::LICENSE;
		$verifiable = '"this is not a test"';
		self::assertFalse($crypto->validate($verifiable, $signature));
	}
}
