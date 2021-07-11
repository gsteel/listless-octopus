<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Value;

use MyCLabs\Enum\Enum;

/**
 * @psalm-immutable
 * @psalm-template T of string
 * @template-extends Enum<T>
 */
final class SubscriptionStatus extends Enum
{
    private const SUBSCRIBED = 'SUBSCRIBED';
    private const PENDING = 'PENDING';
    private const UNSUBSCRIBED = 'UNSUBSCRIBED';

    public static function subscribed(): self
    {
        return new self(self::SUBSCRIBED);
    }

    public static function unsubscribed(): self
    {
        return new self(self::UNSUBSCRIBED);
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }
}
