<?php

namespace LycheeVerify\Exceptions;

use LycheeVerify\Contract\Status;

final class SupporterOnlyOperationException extends BaseVerifyException
{
	/**
	 * Constructor.
	 */
	public function __construct(Status $status = Status::SUPPORTER_EDITION)
	{
		$users = match ($status) {
			Status::PLUS_EDITION => 'plus users',
			default => 'supporters',
		};
		parent::__construct(402, sprintf('This operation is reserved to the %s of LycheeOrg.', $users), null);
	}
}