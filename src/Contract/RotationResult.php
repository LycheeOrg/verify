<?php

namespace LycheeVerify\Contract;

class RotationResult
{
	private function __construct(
		public readonly bool $success,
		public readonly ?string $message,
	) {
	}

	public static function ok(): self
	{
		return new self(success: true, message: null);
	}

	public static function fail(string $message): self
	{
		return new self(success: false, message: $message);
	}
}
