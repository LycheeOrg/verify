<?php

namespace LycheeVerify;

use Illuminate\Support\Facades\DB;
use LycheeVerify\Contract\Status;
use LycheeVerify\Contract\VerifyInterface;
use LycheeVerify\Exceptions\SupporterOnlyOperationException;
use LycheeVerify\Validators\ValidatePro;
use LycheeVerify\Validators\ValidateSignature;
use LycheeVerify\Validators\ValidateSupporter;

use function Safe\json_encode;
use function Safe\preg_replace;

class Verify implements VerifyInterface
{
	private string $config_email;
	private string $license_key;
	private ValidateSignature $validateSignature;
    private ValidateSupporter $validateSupporter;
	private ValidatePro $validatePro;

	public function __construct(
		#[\SensitiveParameter] ?string $config_email = null,
		#[\SensitiveParameter] ?string $license_key = null,
		#[\SensitiveParameter] ?string $public_key = null,
		#[\SensitiveParameter] ?string $hash_supporter = null,
		#[\SensitiveParameter] ?string $hash_pro = null,
	) {
		$this->config_email = $config_email ?? DB::table('configs')->where('key', 'email')->first()?->value ?? ''; // @phpstan-ignore-line
		$this->license_key = $license_key ?? DB::table('configs')->where('key', 'license_key')->first()?->value ?? ''; // @phpstan-ignore-line
		$this->validateSignature = new ValidateSignature($public_key);
		$this->validatePro = new ValidatePro($hash_pro);
        $this->validateSupporter = new ValidateSupporter($hash_supporter);
	}

	/**
	 * Check if the installation is verified.
	 *
	 * @return Status
	 */
	public function get_status(): Status
	{
		$base = json_encode(['url' => config('app.url'), 'email' => $this->config_email]);

		if ($this->validateSupporter->validate($base, $this->license_key)) {
			return $this->validateSupporter->grant();
		}

		if ($this->validatePro->validate($base, $this->license_key)) {
			return $this->validatePro->grant();
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
        return match ($status) {
            Status::SIGNATURE_EDITION => true,
            Status::PRO_EDITION => in_array($required_status, [Status::PRO_EDITION, Status::SUPPORTER_EDITION], true),
            Status::SUPPORTER_EDITION => in_array($required_status, [Status::SUPPORTER_EDITION], true),
            default => false,
        };
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
	public function is_pro(): bool
	{
		return $this->check(Status::PRO_EDITION);
	}

    /**
     * Return true if the user is a signature user
     *
     * @return bool
     */
    public function is_signature(): bool
    {
        return $this->check(Status::SIGNATURE_EDITION);
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
        /** @var array<class-string,string>|null $checks */
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
