<?php

namespace LycheeVerify\Contract;

interface VerifyInterface
{
	/**
	 * Check if the installation is verified.
	 *
	 * @return Status
	 */
	public function get_status(): Status;

	/**
	 * Check the status of the installation and validate.
	 *
	 * @param Status $required_status (default to SUPPORTER_EDITION)
	 *
	 * @return bool
	 */
	public function check(Status $required_status = Status::SUPPORTER_EDITION): bool;

	/**
	 * Returns true if the user is a supporter (or pro/signature user).
	 *
	 * @return bool
	 */
	public function is_supporter(): bool;

	/**
	 * Return true of the user is a pro user (or signature user).
	 *
	 * @return bool
	 */
	public function is_pro(): bool;

	/**
	 * Return true if the user is a signature user.
	 *
	 * @return bool
	 */
	public function is_signature(): bool;
}
