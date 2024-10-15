<?php

use LycheeVerify\Http\Middleware\VerifySupporterStatus;
use LycheeVerify\Validators\ValidateHash;
use LycheeVerify\Validators\ValidateSignature;
use LycheeVerify\Verify;
use LycheeVerify\VerifyServiceProvider;

return [
	'validation' => [
		ValidateHash::class => 'e2511ed0f1adc865c7c8b40bec19d656323d81f6',
		ValidateSignature::class => '1bae28471b402e73ddad2ea871b15835954822c3',
		Verify::class => '6dd9c193b7505dff9d4ba0f19f0e2b3a3171d83a',
		VerifySupporterStatus::class => '6358c45ed0414c1e2697e0881238659fa6221bed',
		VerifyServiceProvider::class => '927a8f3c811fc82cb8a0ac2667c06e7d292c3633',
	],
];
