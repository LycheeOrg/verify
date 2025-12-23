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

	/**
	 * Fork depending whether the installation is verified or not.
	 *
	 * @template T
	 *
	 * @param T|\Closure(): T $valIfTrue       what happens or Value if we features are enabled
	 * @param T|\Closure(): T $valIfFalse      what happens or Value if we features are disabled
	 * @param Status          $required_status
	 *
	 * @return T
	 */
	public function when(mixed $valIfTrue, mixed $valIfFalse, Status $required_status = Status::SUPPORTER_EDITION): mixed;
}
