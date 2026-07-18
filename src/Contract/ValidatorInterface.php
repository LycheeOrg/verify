<?php

namespace LycheeVerify\Contract;

/**
 * Interface to check whther a license key is valid or not.
 */
interface ValidatorInterface
{
	/**
	 * Given a license key and a verifiable string, check whether the license key is valid or not.
	 *
	 * @param string                                  $verifiable
	 * @param string|\SensitiveParameterValue<string> $license
	 *
	 * @return bool
	 */
	public function validate(string $verifiable, string|\SensitiveParameterValue $license): bool;

	/**
	 * Defines the status granted by the Validator in case of success.
	 *
	 * @return Status
	 */
	public function grant(): Status;
}
