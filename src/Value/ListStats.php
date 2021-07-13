<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Value;

use GSteel\Listless\Octopus\Exception\AssertionFailed;
use GSteel\Listless\Octopus\Util\Assert;

/**
 * @psalm-immutable
 */
final class ListStats
{
    private int $pending;
    private int $subscribed;
    private int $unsubscribed;

    private function __construct(
        int $pending,
        int $subscribed,
        int $unsubscribed
    ) {
        $this->pending = $pending;
        $this->subscribed = $subscribed;
        $this->unsubscribed = $unsubscribed;
    }

    /**
     * @param array<string, int> $values
     *
     * @throws AssertionFailed if any of the data provided in invalid.
     */
    public static function fromArray(array $values): self
    {
        Assert::keyExists($values, 'pending');
        Assert::keyExists($values, 'subscribed');
        Assert::keyExists($values, 'unsubscribed');
        Assert::allInteger($values);

        return new self(
            $values['pending'],
            $values['subscribed'],
            $values['unsubscribed']
        );
    }

    public function pending(): int
    {
        return $this->pending;
    }

    public function subscribed(): int
    {
        return $this->subscribed;
    }

    public function unsubscribed(): int
    {
        return $this->unsubscribed;
    }
}
