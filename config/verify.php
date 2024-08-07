<?php

use LycheeVerify\Http\Middleware\VerifySupporterStatus;
use LycheeVerify\Validators\ValidateHash;
use LycheeVerify\Validators\ValidateSignature;
use LycheeVerify\Verify;
use LycheeVerify\VerifyServiceProvider;

return [
	'validation' => [
		ValidateHash::class => 'c34da4a4523e54f303c23cd22eaf252f74ed7965',
		ValidateSignature::class => 'a3d4081247b4f56c8aeb7c6b192415700e27acc0',
		Verify::class => 'cda79b50522e9189aa928a5f471ebc20a049db76',
		VerifySupporterStatus::class => '6358c45ed0414c1e2697e0881238659fa6221bed',
		VerifyServiceProvider::class => '927a8f3c811fc82cb8a0ac2667c06e7d292c3633',
	],
];
