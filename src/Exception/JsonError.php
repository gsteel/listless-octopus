<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Exception;

use JsonException;
use RuntimeException;

use function sprintf;

final class JsonError extends RuntimeException implements Exception
{
    /** @var string|null */
    private $jsonString;

    public static function onDecode(string $payload, JsonException $error): self
    {
        $instance = new self(
            sprintf('JSON Decode Failure: %s', $error->getMessage()),
            (int) $error->getCode(),
            $error
        );
        $instance->jsonString = $payload;

        return $instance;
    }

    public static function onEncode(JsonException $error): self
    {
        return new self(
            sprintf('JSON Encode Failure: %s', $error->getMessage()),
            (int) $error->getCode(),
            $error
        );
    }

    public function jsonString(): string
    {
        if (! $this->jsonString) {
            throw new BadMethodCall('This error was not caused during decode');
        }

        return $this->jsonString;
    }
}
