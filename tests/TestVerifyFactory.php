<?php

namespace LycheeVerify\Tests;

use LycheeVerify\Contract\VerifyFactory;
use LycheeVerify\Contract\VerifyInterface;
use LycheeVerify\Verify;

class TestVerifyFactory implements VerifyFactory
{
	/** @var \SensitiveParameterValue<?string> */
	private \SensitiveParameterValue $hash_supporter;

	public function __construct(
		#[\SensitiveParameter] ?string $hash_supporter = null,
	) {
		$this->hash_supporter = new \SensitiveParameterValue($hash_supporter);
	}

	public function make(#[\SensitiveParameter] string $license_key): VerifyInterface
	{
		return new Verify(license_key: $license_key, hash_supporter: $this->hash_supporter->getValue());
	}
}
