<?php

namespace LycheeVerify;

use Illuminate\Support\ServiceProvider;

class VerifyServiceProvider extends ServiceProvider
{
	public const CONFIG = __DIR__ . '/../config/verify.php';

	/**
	 * Register the service provider.
	 *
	 * @return void
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function register()
	{
		$this->mergeConfigFrom(static::CONFIG, 'verify');

		$this->app->bind('verify', function () {
			return new \LycheeVerify\Verify(); // Replace with your actual instantiation logic.
		});
	}

	public function boot(): void
	{
	}
}
