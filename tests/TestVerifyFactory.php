<?php

namespace LycheeVerify\Tests;

use LycheeVerify\Contract\VerifyFactory;
use LycheeVerify\Contract\VerifyInterface;
use LycheeVerify\Verify;

class TestVerifyFactory implements VerifyFactory
{
	public function __construct(
		#[\SensitiveParameter] private ?string $hash_supporter = null,
	) {}

	public function make(#[\SensitiveParameter] string $license_key): VerifyInterface
	{
		return new Verify(license_key: $license_key, hash_supporter: $this->hash_supporter);
	}
}
