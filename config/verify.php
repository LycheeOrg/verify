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
		ValidateSupporter::class => 'ef1a42701af6dc36e052556a0ee1c762394f9428',
		ValidatePro::class => '482b48f1a026684b6c1754e45ca180ffc52483ff',
		ValidateSignature::class => '5a8a855d4b59c44c298daa66801c79f2aba20492',
		Verify::class => 'e5a8ebb4878c0fd3387ec0cb2f28d9caa463e5bb',
		VerifySupporterStatus::class => '6358c45ed0414c1e2697e0881238659fa6221bed',
		VerifyProStatus::class => '212e6ada794587ee8e2b81cf76e243d134a7e823',
		VerifyServiceProvider::class => '923b63b15d25e69b95ed1d5ec1c82ba57f1a7d74',
	],
];
