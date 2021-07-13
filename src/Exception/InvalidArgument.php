<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Exception;

use GSteel\Listless\Octopus\Value\MailingList;
use InvalidArgumentException;

use function sprintf;

final class InvalidArgument extends InvalidArgumentException implements Exception
{
    private const ERROR_FIELD_UNDEFINED = 1;

    public static function mailingListFieldNameDoesNotExist(MailingList $list, string $name): self
    {
        return new self(sprintf(
            'The field "%s" does not exist for the mailing list "%s"',
            $name,
            $list->name()
        ), self::ERROR_FIELD_UNDEFINED);
    }
}
