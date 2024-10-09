<?php

use LycheeVerify\Http\Middleware\VerifySupporterStatus;
use LycheeVerify\Validators\ValidateHash;
use LycheeVerify\Validators\ValidateSignature;
use LycheeVerify\Verify;
use LycheeVerify\VerifyServiceProvider;

return [
	'validation' => [
		ValidateHash::class => '283d910da171808f650011e41d5c23c743e90e39',
		ValidateSignature::class => '71627830f8485d02dc48a59e2aa28cc9dea6fe3b',
		Verify::class => 'b9e4048f3543734262582553d2202f9ef5d39b1f',
		VerifySupporterStatus::class => '6358c45ed0414c1e2697e0881238659fa6221bed',
		VerifyServiceProvider::class => '927a8f3c811fc82cb8a0ac2667c06e7d292c3633',
	],
];
