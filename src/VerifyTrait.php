<?php

namespace LycheeVerify;

use LycheeVerify\Contract\Status;

trait VerifyTrait
{
	abstract public function get_status(): Status;

	/**
	 * Check the status of the installation and validate.
	 *
	 * @param Status $required_status (default to SUPPORTER_EDITION)
	 *
	 * @return bool
	 */
	public function check(Status $required_status = Status::SUPPORTER_EDITION): bool
	{
		if ($required_status === Status::FREE_EDITION) {
			return true;
		}

		$status = $this->get_status();

		return match ($status) {
			Status::SIGNATURE_EDITION => true,
			Status::PRO_EDITION => in_array($required_status, [Status::PRO_EDITION, Status::SUPPORTER_EDITION], true),
			Status::SUPPORTER_EDITION => in_array($required_status, [Status::SUPPORTER_EDITION], true),
			default => false,
		};
	}

	/**
	 * Returns true if the user is a supporter (or plus registered user).
	 *
	 * @return bool
	 */
	public function is_supporter(): bool
	{
		return $this->check(Status::SUPPORTER_EDITION);
	}

	/**
	 * Return true of the user is a plus registered user.
	 *
	 * @return bool
	 */
	public function is_pro(): bool
	{
		return $this->check(Status::PRO_EDITION);
	}

	/**
	 * Return true if the user is a signature user.
	 *
	 * @return bool
	 */
	public function is_signature(): bool
	{
		return $this->check(Status::SIGNATURE_EDITION);
	}
}
