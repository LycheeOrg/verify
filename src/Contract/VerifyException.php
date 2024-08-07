<?php

namespace LycheeVerify\Contract;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

interface VerifyException extends \Throwable, HttpExceptionInterface
{
}
