<?php

namespace LycheeVerify\Console\Commands;

use Illuminate\Console\Command;
use LycheeVerify\Contract\Status;
use LycheeVerify\Validators\ValidatePro;
use LycheeVerify\Validators\ValidateSupporter;
use function Safe\json_encode;

class CheckKeyCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'verify:check-key {key : The license key to check}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Check the sponsorship level for a given license key';

	/**
	 * Execute the console command.
	 */
	public function handle(): int
	{
		if (!$this->hasArgument('key')) {
			$this->error('No key provided. Please provide a license key to check.');

			return Command::FAILURE;
		}

		if (!is_string($this->argument('key'))) {
			$this->error('Invalid key format. The key must be a string.');

			return Command::FAILURE;
		}

		$key = $this->argument('key');

		$validateSupporter = new ValidateSupporter();
		$validatePro = new ValidatePro();

		// We use a dummy verifiable string for checking
		$verifiable = json_encode(['url' => '', 'email' => '']);

		// Check Pro edition first (highest tier)
		if ($validatePro->validate($verifiable, $key)) {
			$this->info('Key: ' . $key);
			$this->info('Sponsorship Level: ' . Status::PRO_EDITION->value . ' (Pro Edition)');

			return Command::SUCCESS;
		}

		// Check Supporter edition
		if ($validateSupporter->validate($verifiable, $key)) {
			$this->info('Key: ' . $key);
			$this->info('Sponsorship Level: ' . Status::SUPPORTER_EDITION->value . ' (Supporter Edition)');

			return Command::SUCCESS;
		}

		// Key doesn't match any known tier
		$this->warn('Key: ' . $key);
		$this->warn('Sponsorship Level: ' . Status::FREE_EDITION->value . ' (No valid sponsorship found)');

		return Command::FAILURE;
	}
}
