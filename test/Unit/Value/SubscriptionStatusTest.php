<?php

declare(strict_types=1);

namespace ListInterop\Octopus\Test\Unit\Value;

use Generator;
use ListInterop\Octopus\Value\SubscriptionStatus;
use PHPUnit\Framework\TestCase;

class SubscriptionStatusTest extends TestCase
{
    /**
     * @return Generator<string, array{0: string, 1: SubscriptionStatus}>
     */
    public function dataProvider(): Generator
    {
        yield 'Subscribed' => [
            'SUBSCRIBED',
            SubscriptionStatus::subscribed(),
        ];

        yield 'Pending' => [
            'PENDING',
            SubscriptionStatus::pending(),
        ];

        yield 'Unsubscribed' => [
            'UNSUBSCRIBED',
            SubscriptionStatus::unsubscribed(),
        ];
    }

    /** @dataProvider dataProvider */
    public function testEnum(string $value, SubscriptionStatus $expected): void
    {
        self::assertTrue(
            $expected->equals(new SubscriptionStatus($value))
        );
    }
}
