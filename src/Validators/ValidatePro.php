<?php

namespace LycheeVerify\Validators;

use LycheeVerify\Contract\Status;
use LycheeVerify\Contract\ValidatorInterface;

/**
 * This is the validator for supporters.
 */
class ValidatePro implements ValidatorInterface
{
	private string $hash;

	public function __construct(#[\SensitiveParameter] ?string $hash = null)
	{
		$this->hash = $hash ?? '2a8223d64f471a9ff5cd5b3afcd9f7e9bd30917d77e186ab211ff9bb76949580';
	}

	/**
	 * Validate whether the static license key provided matches with the hash.
	 */
	public function validate(string $verifiable, string $license): bool
	{
		if ($license === '') {
			return false;
		}

		// We could use Hash::check here, but it is WAY too costly: 150ms per call.
		return hash('sha3-256', $license) === $this->hash;
	}

	/**
	 * If the hash passes, we grant the user the supporter edition.
	 *
	 * @return Status::PRO_EDITION
	 */
	public function grant(): Status
	{
		return Status::PRO_EDITION;
	}
}
