<?php

use LycheeVerify\Http\Middleware\VerifyProStatus;
use LycheeVerify\Http\Middleware\VerifySupporterStatus;
use LycheeVerify\Validators\ValidatePro;
use LycheeVerify\Validators\ValidateSignature;
use LycheeVerify\Validators\ValidateSupporter;
use LycheeVerify\Verify;
use LycheeVerify\VerifyServiceProvider;
use LycheeVerify\VerifyTrait;

return [
	'validation' => [
		ValidateSupporter::class => 'a12d601f9f22a6f326901f0c4deb3cdeb5d6cc47',
		ValidatePro::class => '50a93c3e54cbd8ec502cb574ac236bc4e99194be',
		ValidateSignature::class => '5a8a855d4b59c44c298daa66801c79f2aba20492',
		Verify::class => '1026070973fa233fc087ed706aa4b9b50bd37843',
		VerifySupporterStatus::class => '6358c45ed0414c1e2697e0881238659fa6221bed',
		VerifyProStatus::class => '212e6ada794587ee8e2b81cf76e243d134a7e823',
		VerifyServiceProvider::class => '923b63b15d25e69b95ed1d5ec1c82ba57f1a7d74',
		VerifyTrait::class => 'aa1536689e09b43ed762a99a80fa04502cfe0d68',
	],
];
