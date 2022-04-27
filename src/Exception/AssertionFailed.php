<?php

declare(strict_types=1);

namespace ListInterop\Octopus\Exception;

use UnexpectedValueException;

final class AssertionFailed extends UnexpectedValueException implements Exception
{
}
