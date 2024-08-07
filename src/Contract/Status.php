<?php

namespace LycheeVerify\Contract;

/**
 * Defines the 3 levels of installation of Lychee.
 */
enum Status: string
{
	case FREE_EDITION = 'free';
	case SUPPORTER_EDITION = 'se';
	case PLUS_EDITION = 'plus';
}