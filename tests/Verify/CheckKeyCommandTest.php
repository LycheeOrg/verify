<?php

namespace LycheeVerify\Tests\Verify;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Testing\PendingCommand;
use LycheeVerify\Console\Commands\CheckKeyCommand;
use LycheeVerify\Tests\TestCase;
use function Safe\fopen;
use function Safe\fwrite;
use function Safe\rewind;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CheckKeyCommandTest extends TestCase
{
	private const INVALID_KEY = 'AAAAA-BBBBB-CCCCC-DDDDD-EEEEE';

	public function testKeyIsReadFromHiddenPrompt(): void
	{
		$this->checkKey()
			->expectsQuestion('License key', self::INVALID_KEY)
			->expectsOutputToContain('No valid sponsorship found')
			->doesntExpectOutputToContain(self::INVALID_KEY)
			->assertExitCode(1);
	}

	public function testEmptyKeyIsRejected(): void
	{
		$this->checkKey()
			->expectsQuestion('License key', '')
			->expectsOutputToContain('No key provided')
			->assertExitCode(1);
	}

	public function testKeyPassedAsArgumentIsRejectedWithoutEchoingIt(): void
	{
		// Go through ArgvInput and the console kernel, as a real CLI invocation would.
		$input = new ArgvInput(['artisan', 'verify:check-key', self::INVALID_KEY]);
		$output = new BufferedOutput();

		self::assertSame(1, app(Kernel::class)->handle($input, $output));
		$display = $output->fetch();
		self::assertStringContainsString('Passing the license key as an argument is not supported', $display);
		self::assertStringNotContainsString(self::INVALID_KEY, $display);
	}

	public function testKeyIsReadFromStdinWhenNotInteractive(): void
	{
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, self::INVALID_KEY . "\n");
		rewind($stream);

		$input = new ArrayInput([]);
		$input->setInteractive(false);
		$input->setStream($stream);
		$output = new BufferedOutput();

		$command = app(CheckKeyCommand::class);
		$command->setLaravel(app());

		self::assertSame(1, $command->run($input, $output));
		$display = $output->fetch();
		self::assertStringContainsString('No valid sponsorship found', $display);
		self::assertStringNotContainsString(self::INVALID_KEY, $display);
	}

	/**
	 * @param array<string,string> $parameters
	 */
	private function checkKey(array $parameters = []): PendingCommand
	{
		return new PendingCommand($this, app(), 'verify:check-key', $parameters);
	}
}
