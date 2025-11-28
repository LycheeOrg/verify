<?php

use LycheeVerify\Http\Middleware\VerifyProStatus;
use LycheeVerify\Http\Middleware\VerifySupporterStatus;
use LycheeVerify\Validators\ValidatePro;
use LycheeVerify\Validators\ValidateSignature;
use LycheeVerify\Validators\ValidateSupporter;
use LycheeVerify\Verify;
use LycheeVerify\VerifyServiceProvider;

return [
	'validation' => [
		ValidateSupporter::class => '882f8890242f77ee8dd8b84c6511c82e32858c12',
		ValidatePro::class => '676b945ac8649a099ced12bfbab87b11c5c0daad',
		ValidateSignature::class => '5a8a855d4b59c44c298daa66801c79f2aba20492',
		Verify::class => 'e5a8ebb4878c0fd3387ec0cb2f28d9caa463e5bb',
		VerifySupporterStatus::class => '6358c45ed0414c1e2697e0881238659fa6221bed',
		VerifyProStatus::class => '212e6ada794587ee8e2b81cf76e243d134a7e823',
		VerifyServiceProvider::class => '927a8f3c811fc82cb8a0ac2667c06e7d292c3633',
	],
];
