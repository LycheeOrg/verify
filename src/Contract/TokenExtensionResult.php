<?php

namespace LycheeVerify\Contract;

use Illuminate\Support\Carbon;

class TokenExtensionResult
{
	private function __construct(
		public readonly bool $success,
		public readonly ?string $message,
		public readonly ?int $id,
		public readonly ?string $name,
		/** @var string[]|null */
		public readonly ?array $scopes,
		public readonly ?Carbon $expires_at,
	) {
	}

	/**
	 * @param string[] $scopes
	 */
	public static function ok(
		int $id,
		string $name,
		array $scopes,
		Carbon $expires_at,
	): self {
		return new self(
			success: true,
			message: null,
			id: $id,
			name: $name,
			scopes: $scopes,
			expires_at: $expires_at,
		);
	}

	public static function fail(string $message): self
	{
		return new self(
			success: false,
			message: $message,
			id: null,
			name: null,
			scopes: null,
			expires_at: null,
		);
	}
}
