<?php

namespace LycheeVerify;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
	use VerifyTrait;
	private string $config_email;
	private string $license_key;
	private ValidateSignature $validateSignature;
	private ValidateSupporter $validateSupporter;
	private ValidatePro $validatePro;

	private ?Status $status;

	private bool $initialized = false;

	public function __construct(
		#[\SensitiveParameter] ?string $config_email = null,
		#[\SensitiveParameter] ?string $license_key = null,
		#[\SensitiveParameter] ?string $public_key = null,
		#[\SensitiveParameter] ?string $hash_supporter = null,
		#[\SensitiveParameter] ?string $hash_pro = null,
	) {
		$this->validateSignature = new ValidateSignature($public_key);
		$this->validatePro = new ValidatePro($hash_pro);
		$this->validateSupporter = new ValidateSupporter($hash_supporter);
		$this->init($config_email, $license_key);
	}

	/**
	 * To avoid crashing when the database does not exists we
	 * add some safeties.
	 *
	 * @param string|null $config_email
	 * @param string|null $license_key
	 *
	 * @return bool
	 */
	private function init(
		#[\SensitiveParameter] ?string $config_email = null,
		#[\SensitiveParameter] ?string $license_key = null,
	): bool {
		if ($config_email !== null || $license_key !== null) {
			// If both values are provided, no need to check the database
			$this->config_email = $config_email ?? '';
			$this->license_key = $license_key ?? '';
			$this->initialized = true;

			return true;
		}

		// Validate that the database is ready
		if (!Schema::hasTable('configs')) {
			return false;
		}

		// Load the necessary config entries
		$this->config_email = DB::table('configs')->where('key', 'email')->first()?->value ?? ''; // @phpstan-ignore-line
		$this->license_key = DB::table('configs')->where('key', 'license_key')->first()?->value ?? ''; // @phpstan-ignore-line
		$this->initialized = true;

		return true;
	}

	/**
	 * Check if the installation is verified.
	 *
	 * @return Status
	 */
	public function get_status(): Status
	{
		if (!$this->initialized && !$this->init()) {
			return Status::FREE_EDITION;
		}

		return $this->status ??= $this->resolve_status();
	}

	/**
	 * Private resolver for status.
	 *
	 * @return Status
	 */
	private function resolve_status(): Status
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
	 * Reset the cached status.
	 *
	 * @return void
	 */
	public function reset_status(): void
	{
		$this->status = null;
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
}
