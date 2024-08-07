<?php

namespace LycheeVerify\Tests;

use LycheeVerify\VerifyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
	protected function setUp(): void
	{
		parent::setUp();
	}

	protected function getPackageProviders($app)
	{
		return [
			VerifyServiceProvider::class,
		];
	}

	public function getEnvironmentSetUp($app)
	{
		/** @disregard */
		config()->set('database.default', 'testing');

		$migration = include __DIR__ . '/../database/migrations/create_configs_table.php';
		$migration->up();
	}
}
