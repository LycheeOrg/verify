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
	'keygen_api_key' => env('KEYGEN_API_KEY', ''),
	'keygen_url' => env('KEYGEN_URL', 'https://keygen.lycheeorg.dev/api'),

	'validation' => [
		ValidateSupporter::class => 'bcd0281afd7a8c4e19be3ea3a16860f4fe81556b',
		ValidatePro::class => '26e178bcb4264101281ff4adb51d05a821b021f6',
		ValidateSignature::class => '8c1c664c1ed9a98452f5af10b1a2a69c03b81d66',
		Verify::class => '4ae4a13beb075b695536d91478f8e55f5226e51a',
		VerifySupporterStatus::class => '6358c45ed0414c1e2697e0881238659fa6221bed',
		VerifyProStatus::class => '212e6ada794587ee8e2b81cf76e243d134a7e823',
		VerifyServiceProvider::class => '923b63b15d25e69b95ed1d5ec1c82ba57f1a7d74',
		VerifyTrait::class => 'aa1536689e09b43ed762a99a80fa04502cfe0d68',
	],
];
