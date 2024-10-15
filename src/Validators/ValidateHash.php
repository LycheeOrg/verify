<?php

namespace LycheeVerify\Validators;

use Illuminate\Support\Facades\Hash;
use LycheeVerify\Contract\Status;
use LycheeVerify\Contract\ValidatorInterface;

/**
 * This is the validator for supporters.
 */
class ValidateHash implements ValidatorInterface
{
	private string $hash;

	public function __construct(#[\SensitiveParameter] ?string $hash = null)
	{
		$this->hash = $hash ?? '$2y$10$Bo9bqC34tQr.hEJ1qZZNBO.dkJRoEiLeZpXxlsYaSlaKi/dRyCyea';
	}

	/**
	 * Validate whether the static license key provided matches with the hash.
	 */
	public function validate(string $verifiable, string $license): bool
	{
		if ($license === '') {
			return false;
		}

		return Hash::check($license, $this->hash);
	}

	/**
	 * If the hash passes, we grant the user the supporter edition.
	 *
	 * @return Status::SUPPORTER_EDITION
	 */
	public function grant(): Status
	{
		return Status::SUPPORTER_EDITION;
	}
}
