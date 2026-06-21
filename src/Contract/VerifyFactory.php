<?php

namespace LycheeVerify\Contract;

interface VerifyFactory
{
	public function make(#[\SensitiveParameter] string $license_key): VerifyInterface;
}
