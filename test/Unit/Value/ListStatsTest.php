<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Unit\Value;

use GSteel\Listless\Octopus\Exception\AssertionFailed;
use GSteel\Listless\Octopus\Value\ListStats;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_map;

class ListStatsTest extends TestCase
{
    /** @return array<string, int> */
    private function validPayload(): array
    {
        return [
            'pending' => 1,
            'subscribed' => 2,
            'unsubscribed' => 3,
        ];
    }

    /** @return array<string, array{0: string, 1: mixed}> */
    public function mutantProvider(): array
    {
        return [
            'pending is null' => ['pending', null],
            'subscribed is null' => ['subscribed', null],
            'unsubscribed is null' => ['unsubscribed', null],
            'pending is not an int' => ['pending', 'foo'],
            'subscribed is not an int' => ['subscribed', 'foo'],
            'unsubscribed is not an int' => ['unsubscribed', 'foo'],
        ];
    }

    /**
     * @param mixed $mutatedValue
     *
     * @psalm-suppress MixedAssignment, MixedArgumentTypeCoercion
     * @dataProvider mutantProvider
     */
    public function testMutants(string $key, $mutatedValue): void
    {
        $payload = $this->validPayload();
        $payload[$key] = $mutatedValue;
        $this->expectException(AssertionFailed::class);
        ListStats::fromArray($payload);
    }

    /** @psalm-return list<string[]> */
    public function keyProvider(): array
    {
        return array_map(static function (string $key): array {
            return [$key];
        }, array_keys($this->validPayload()));
    }

    /** @dataProvider keyProvider */
    public function testAllKeysMustExist(string $key): void
    {
        $payload = $this->validPayload();
        unset($payload[$key]);
        $this->expectException(AssertionFailed::class);
        ListStats::fromArray($payload);
    }
}
