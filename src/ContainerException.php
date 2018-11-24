<?php

namespace Dashifen\Container;

use Dashifen\Exception\Exception;

class ContainerException extends Exception {

	// normally, Dashifen\Exception constant values start at one, since the
	// parent class uses zero as the "UNKNOWN_ERROR" constant value.  but,
	// since we expect children to extend this further, we'll let them start
	// at one and use negative one and two here instead.

	const UNKNOWN_PROPERTY = -1;
	const UNKNOWN_SETTER = -2;
	const INVALID_VALUE = -3;
}