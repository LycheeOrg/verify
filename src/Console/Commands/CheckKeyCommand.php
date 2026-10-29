<?php

namespace LycheeVerify\Console\Commands;

use Illuminate\Console\Command;
use LycheeVerify\Contract\Status;
use LycheeVerify\Validators\ValidatePro;
use LycheeVerify\Validators\ValidateSupporter;
use function Safe\json_encode;
use function Safe\stream_get_contents;
use Symfony\Component\Console\Input\StreamableInputInterface;

class CheckKeyCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * The key is deliberately not accepted as an argument: process arguments
	 * are visible to other local users (e.g. via ps or /proc).
	 * The catch-all argument only exists so that legacy invocations are rejected
	 * by us, instead of Symfony echoing the key in its "unexpected argument" error.
	 *
	 * @var string
	 */
	protected $signature = 'verify:check-key {legacy?* : Not supported, the key is read from a hidden prompt or standard input}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Check the sponsorship level for a license key (read from a hidden prompt, or from standard input with --no-interaction)';

	/**
	 * Execute the console command.
	 */
	public function handle(): int
	{
		if ($this->argument('legacy') !== []) {
			$this->error('Passing the license key as an argument is not supported, as it is visible to other users. Enter it at the prompt, or pipe it to standard input with --no-interaction.');

			return Command::FAILURE;
		}

		$key = $this->readKey();

		if ($key->getValue() === '') {
			$this->error('No key provided. Please provide a license key to check.');

			return Command::FAILURE;
		}

		$validateSupporter = new ValidateSupporter();
		$validatePro = new ValidatePro();

		// We use a dummy verifiable string for checking
		$verifiable = json_encode(['url' => '', 'email' => '']);

		// Check Pro edition first (highest tier)
		if ($validatePro->validate($verifiable, $key)) {
			$this->info('Sponsorship Level: ' . Status::PRO_EDITION->value . ' (Pro Edition)');

			return Command::SUCCESS;
		}

		// Check Supporter edition
		if ($validateSupporter->validate($verifiable, $key)) {
			$this->info('Sponsorship Level: ' . Status::SUPPORTER_EDITION->value . ' (Supporter Edition)');

			return Command::SUCCESS;
		}

		// Key doesn't match any known tier
		$this->warn('Sponsorship Level: ' . Status::FREE_EDITION->value . ' (No valid sponsorship found)');

		return Command::FAILURE;
	}

	/**
	 * Read the license key without exposing it in argv or echoing it to the terminal.
	 *
	 * @return \SensitiveParameterValue<string>
	 */
	private function readKey(): \SensitiveParameterValue
	{
		if ($this->input->isInteractive()) {
			// No visible fallback: fail rather than echo the key if input cannot be hidden.
			$key = $this->secret('License key', false);
		} else {
			$stream = $this->input instanceof StreamableInputInterface ? $this->input->getStream() : null;
			$stream ??= STDIN;
			// Nothing was piped in: report the missing key instead of waiting for EOF on the terminal.
			$key = $this->isTerminal($stream) ? '' : stream_get_contents($stream);
		}

		return new \SensitiveParameterValue(is_string($key) ? trim($key) : '');
	}

	/**
	 * Not Safe\stream_isatty(), which throws whenever the stream is not a TTY.
	 * posix_isatty() warns on streams without a file descriptor (e.g. php://memory), hence the STDIO check.
	 *
	 * @param resource $stream
	 */
	private function isTerminal($stream): bool
	{
		return function_exists('posix_isatty') &&
			stream_get_meta_data($stream)['stream_type'] === 'STDIO' &&
			posix_isatty($stream);
	}
}
