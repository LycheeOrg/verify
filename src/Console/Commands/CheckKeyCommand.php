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
	 *
	 * @var string
	 */
	protected $signature = 'verify:check-key';

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
			$key = $this->secret('License key');
		} else {
			$stream = $this->input instanceof StreamableInputInterface ? $this->input->getStream() : null;
			$key = stream_get_contents($stream ?? STDIN);
		}

		return new \SensitiveParameterValue(is_string($key) ? trim($key) : '');
	}
}
