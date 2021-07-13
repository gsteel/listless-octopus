<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Unit\Stub;

use GSteel\Listless\Assert;
use GSteel\Listless\EmailAddress;

use function strtolower;

/**
 * @psalm-immutable
 */
final class CaseSensitiveEmail implements EmailAddress
{
    private string $email;

    public function __construct(string $email)
    {
        Assert::email($email);
        $this->email = $email;
    }

    public function __toString(): string
    {
        return $this->email;
    }

    public function toString(): string
    {
        return $this->email;
    }

    public function displayName(): ?string
    {
        return null;
    }

    public function isEqualTo(EmailAddress $other): bool
    {
        return strtolower($this->email) === strtolower($other->toString());
    }
}
