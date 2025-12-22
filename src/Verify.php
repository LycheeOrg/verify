<?php

namespace LycheeVerify;

use Illuminate\Support\Facades\DB;
use LycheeVerify\Contract\Status;
use LycheeVerify\Contract\VerifyInterface;
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
