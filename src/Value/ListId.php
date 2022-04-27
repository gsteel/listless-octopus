<?php

declare(strict_types=1);

namespace ListInterop\Octopus\Value;

use ListInterop\ListId as ListIdContract;
use ListInterop\Octopus\Util\Assert;

/**
 * @psalm-immutable
 */
final class ListId implements ListIdContract
{
    private string $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function fromString(string $id): self
    {
        Assert::notEmpty($id);

        return new self($id);
    }

    public function toString(): string
    {
        return $this->id;
    }

    public function isEqualTo(ListIdContract $other): bool
    {
        return $this->id === $other->toString();
    }
}
