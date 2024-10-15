<?php

namespace LycheeVerify\Validators;

use Illuminate\Support\Facades\Log;
use LycheeVerify\Contract\Status;
use LycheeVerify\Contract\ValidatorInterface;
use function Safe\base64_decode;
use Safe\Exceptions\OpensslException;
use function Safe\openssl_pkey_get_public;
use function Safe\openssl_verify;

/**
 * This is the validator for the premium users.
 */
class ValidateSignature implements ValidatorInterface
{
	private string $public_key;

	public function __construct(#[\SensitiveParameter] ?string $public_key = null)
	{
		$this->public_key = $public_key ?? "-----BEGIN PUBLIC KEY-----\nMFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEcFqncMi8yaAYvqk9nXx1Cl3dQseN\nWAISTnuW67XBopIemOgbbd6PHRPjmuaVrsd/tXT7NX9aikKpAGrgJI5yQw==\n-----END PUBLIC KEY-----";
	}

	/**
	 * Validate whether the cryptographic signature (license) is valid with the hard coded public key.
	 */
	public function validate(string $verifiable, string $license): bool
	{
		if ($license === '' || $verifiable === '') {
			return false;
		}

		try {
			$publicKey = openssl_pkey_get_public($this->public_key);

			if (openssl_verify($verifiable, base64_decode($license), $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
				Log::error('Signature is invalid.');

				return false;
			}

			return true;
		} catch (OpensslException $e) {
			Log::error('Something went wrong in the verification. ' . $e->getMessage());

			return false;
		}
	}

	/**
	 * If the signature passes, we grant the user the plus edition.
	 *
	 * @return Status::PLUS_EDITION
	 */
	public function grant(): Status
	{
		return Status::PLUS_EDITION;
	}
}
