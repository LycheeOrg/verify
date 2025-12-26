<?php

namespace LycheeVerify\Validators;

use LycheeVerify\Contract\Status;
use LycheeVerify\Contract\ValidatorInterface;

/**
 * This is the validator for supporters.
 */
class ValidateSupporter implements ValidatorInterface
{
	private string $hash;

	public function __construct(#[\SensitiveParameter] ?string $hash = null)
	{
		$this->hash = $hash ?? 'a1d156bc04660d6e34c248e9efc8619fefbe163f691559c7ecc02d695463d100';
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
	 * @return Status::SUPPORTER_EDITION
	 */
	public function grant(): Status
	{
		return Status::SUPPORTER_EDITION;
	}
}
