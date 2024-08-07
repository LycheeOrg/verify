<?php

namespace LycheeVerify\Facades;

use Illuminate\Support\Facades\Facade;
use LycheeVerify\Verify;

/**
 * @see LycheeVerify\Verify
 */
class VerifyFacade extends Facade
{
	protected static function getFacadeAccessor(): string
	{
		return Verify::class;
	}
}
