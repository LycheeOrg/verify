<?php

namespace LycheeVerify;

use LycheeVerify\Contract\VerifyFactory;
use LycheeVerify\Contract\VerifyInterface;

class DefaultVerifyFactory implements VerifyFactory
{
	public function make(#[\SensitiveParameter] string $license_key): VerifyInterface
	{
		return new Verify(license_key: $license_key);
	}
}
