<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Unit\Value;

use GSteel\Listless\Octopus\Exception\AssertionFailed;
use GSteel\Listless\Octopus\Value\FieldDefinition;
use GSteel\Listless\Octopus\Value\FieldType;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_map;

class FieldDefinitionTest extends TestCase
{
    /** @return array<string, string|null> */
    private function validPayload(): array
    {
        return [
            'tag' => 'tag',
            'label' => 'label',
            'type' => (string) FieldType::text()->getValue(),
            'fallback' => null,
        ];
    }

    /** @return array<string, array{0: string, 1: mixed}> */
    public function mutantProvider(): array
    {
        return [
            'Tag is null' => ['tag', null],
            'Label is null' => ['label', null],
            'Type is null' => ['type', null],
            'Tag is not a string' => ['tag', 1],
            'Label is not a string' => ['label', 1],
            'Type is not a string' => ['type', 1],
            'fallback is a number' => ['fallback', 1],
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
        FieldDefinition::fromArray($payload);
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
        FieldDefinition::fromArray($payload);
    }
}
