<?php

namespace LycheeVerify\Http\Middleware;

use Illuminate\Http\Request;
use LycheeVerify\Contract\Status;
use LycheeVerify\Contract\VerifyException;
use LycheeVerify\Exceptions\SupporterOnlyOperationException;
use LycheeVerify\Verify;

/**
 * This class checks whether the use on a supporter installation.
 * If it is not, then the request is aborted.
 */
class VerifyProStatus
{
	private Verify $verify;

	public function __construct(?Verify $verify)
	{
		$this->verify = $verify ?? new Verify();
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param Request  $request the incoming request to serve
	 * @param \Closure $next    the next operation to be applied to the
	 *                          request
	 *
	 * @return mixed
	 *
	 * @throws VerifyException
	 */
	public function handle(Request $request, \Closure $next, string $required_status): mixed
	{
		$required_status = Status::tryFrom($required_status) ?? Status::PRO_EDITION;

		if ($this->verify->check($required_status)) {
			return $next($request);
		}

		throw new SupporterOnlyOperationException(Status::PRO_EDITION);
	}
}
