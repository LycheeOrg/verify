<?php

namespace LycheeVerify\Validators;

use Illuminate\Support\Facades\Hash;
use LycheeVerify\Contract\Status;
use LycheeVerify\Contract\Validator;

/**
 * This is the validator for supporters.
 */
class ValidateHash implements Validator
{
	private string $hash;

	public function __construct(#[\SensitiveParameter] ?string $hash = null)
	{
		// ! TODO change me when we are shipping for real.
		$this->hash = $hash ?? '$2y$10$TBBCJeXOa10Y5WwLU.yeQ.4fQS/BujBknIvISyhlPzp.LU9jWIH2W';
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
