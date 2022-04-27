<?php

declare(strict_types=1);

namespace ListInterop\Octopus\Value;

use MyCLabs\Enum\Enum;

/**
 * @psalm-immutable
 */
final class FieldType extends Enum
{
    private const TYPE_TEXT = 'TEXT';
    private const TYPE_NUMBER = 'NUMBER';

    public static function text(): self
    {
        return new self(self::TYPE_TEXT);
    }

    public static function number(): self
    {
        return new self(self::TYPE_NUMBER);
    }
}
