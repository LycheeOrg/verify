<?php

namespace LycheeVerify;

use Illuminate\Support\Facades\DB;
use LycheeVerify\Contract\Status;
use LycheeVerify\Exceptions\SupporterOnlyOperationException;
use LycheeVerify\Validators\ValidateHash;
use LycheeVerify\Validators\ValidateSignature;
use function Safe\json_encode;
use function Safe\preg_replace;

class Verify
{
	private string $config_email;
	private string $license_key;
	private ValidateSignature $validateSignature;
	private ValidateHash $validateHash;

	public function __construct(
		#[\SensitiveParameter] ?string $config_email = null,
		#[\SensitiveParameter] ?string $license_key = null,
		#[\SensitiveParameter] ?string $public_key = null,
		#[\SensitiveParameter] ?string $hash = null,
	) {
		$this->config_email = $config_email ?? DB::table('configs')->where('key', 'email')->first()?->value ?? ''; // @phpstan-ignore-line
		$this->license_key = $license_key ?? DB::table('configs')->where('key', 'license_key')->first()?->value ?? ''; // @phpstan-ignore-line
		$this->validateSignature = new ValidateSignature($public_key);
		$this->validateHash = new ValidateHash($hash);
	}

	/**
	 * Check if the installation is verified.
	 *
	 * @return Status
	 */
	public function get_status(): Status
	{
		$base = json_encode(['url' => config('app.url'), 'email' => $this->config_email]);

		if ($this->validateHash->validate($base, $this->license_key)) {
			return $this->validateHash->grant();
		}

		if ($this->config_email !== '' && $this->validateSignature->validate($base, $this->license_key)) {
			return $this->validateSignature->grant();
		}

		return Status::FREE_EDITION;
	}

	/**
	 * Check the status of the installation and validate.
	 *
	 * @param Status $required_status (default to SUPPORTER_EDITION)
	 *
	 * @return bool
	 */
	public function check(Status $required_status = Status::SUPPORTER_EDITION): bool
	{
		if ($required_status === Status::FREE_EDITION) {
			return true;
		}

		$status = $this->get_status();
		if ($status === Status::PLUS_EDITION) {
			return true;
		}

		if ($status === Status::SUPPORTER_EDITION && $required_status === Status::SUPPORTER_EDITION) {
			return true;
		}

		return false;
	}

	/**
	 * Returns true if the user is a supporter (or plus registered user).
	 *
	 * @return bool
	 */
	public function is_supporter(): bool
	{
		return $this->check(Status::SUPPORTER_EDITION);
	}

	/**
	 * Return true of the user is a plus registered user.
	 *
	 * @return bool
	 */
	public function is_plus(): bool
	{
		return $this->check(Status::PLUS_EDITION);
	}

	/**
	 * Authorize the operation if the installation is verified.
	 * Otherwise throw an exception.
	 *
	 * @param Status $required_status (default to SUPPORTER_EDITION)
	 *
	 * @return void
	 *
	 * @throws SupporterOnlyOperationException
	 */
	public function authorize(Status $required_status = Status::SUPPORTER_EDITION): void
	{
		if (!$this->check($required_status)) {
			throw new SupporterOnlyOperationException($required_status);
		}
	}

	/**
	 * Fork depending whether the installation is verified or not.
	 *
	 * @template T
	 *
	 * @param T|\Closure(): T $valIfTrue       what happens or Value if we features are enabled
	 * @param T|\Closure(): T $valIfFalse      what happens or Value if we features are disabled
	 * @param Status          $required_status
	 *
	 * @return T
	 */
	public function when(mixed $valIfTrue, mixed $valIfFalse, Status $required_status = Status::SUPPORTER_EDITION): mixed
	{
		$retValue = $this->check($required_status) ? $valIfTrue : $valIfFalse;

		return is_callable($retValue) ? $retValue() : $retValue;
	}

	/**
	 * Validate installation.
	 *
	 * @return bool
	 */
	public function validate(): bool
	{
		$checks = config('verify.validation');
		if ($checks === null || count($checks) === 0) {
			return false;
		}

		foreach ($checks as $class => $value) {
			$file = (new \ReflectionClass($class))->getFileName();
			if ($file === false || !file_exists($file)) {
				return false;
			}
			// this necessary because stupid line endings in Windows.
			/** @var string $content */
			$content = file_get_contents($file);  // @phpstan-ignore-line
			$content = preg_replace('~\R~u', "\n", $content);
			if (sha1($content) !== $value) {
				return false;
			}
		}

		return true;
	}
}
