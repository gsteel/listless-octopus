<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Util;

use GSteel\Listless\Octopus\Exception\UnexpectedValue;
use Webmozart\Assert\Assert as WebmozartAssert;

final class Assert extends WebmozartAssert
{
    /**
     * @param string $message
     *
     * @inheritDoc
     * @psalm-pure
     */
    protected static function reportInvalidArgument($message): void
    {
        throw new UnexpectedValue($message);
    }
}
