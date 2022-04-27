<?php

declare(strict_types=1);

namespace ListInterop\Octopus\Test\Unit\Value;

use ListInterop\Octopus\Exception\AssertionFailed;
use ListInterop\Octopus\Exception\InvalidArgument;
use ListInterop\Octopus\Value\FieldDefinition;
use ListInterop\Octopus\Value\FieldType;
use ListInterop\Octopus\Value\MailingList;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_map;

class MailingListTest extends TestCase
{
    private MailingList $list;

    protected function setUp(): void
    {
        parent::setUp();
        $this->list = MailingList::fromArray($this->validPayload());
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'id' => '1a354de7-e25a-11eb-96f5-06b4794bee2a',
            'name' => 'Example List',
            'double_opt_in' => false,
            'fields' => [
                [
                    'tag' => 'EmailAddress',
                    'type' => 'TEXT',
                    'label' => 'Email address',
                    'fallback' => null,
                ],
                [
                    'tag' => 'FirstName',
                    'type' => 'TEXT',
                    'label' => 'First name',
                    'fallback' => null,
                ],
                [
                    'tag' => 'LastName',
                    'type' => 'TEXT',
                    'label' => 'Last name',
                    'fallback' => null,
                ],
                [
                    'tag' => 'ArbitraryText',
                    'type' => 'TEXT',
                    'label' => 'Arbitrary Text',
                    'fallback' => 'Foo',
                ],
                [
                    'tag' => 'NumericValue',
                    'type' => 'NUMBER',
                    'label' => 'Numeric Value',
                    'fallback' => '42',
                ],
            ],
            'counts' => [
                'pending' => 5,
                'subscribed' => 6,
                'unsubscribed' => 7,
            ],
            'created_at' => '2021-01-01T02:03:04+01:30',
        ];
    }

    public function testThatAValidPayloadWillReturnAMailingList(): void
    {
        self::assertEquals('Example List', $this->list->name());
        self::assertFalse($this->list->isDoubleOptIn());
        self::assertEquals('2021-01-01 02:03:04', $this->list->createdAt()->format('Y-m-d H:i:s'));
    }

    public function testTheStatsHaveTheExpectedValues(): void
    {
        self::assertEquals(5, $this->list->stats()->pending());
        self::assertEquals(6, $this->list->stats()->subscribed());
        self::assertEquals(7, $this->list->stats()->unsubscribed());
    }

    public function testThatAnExceptionIsThrownAccessingANonExistentField(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('"not-there"');
        $this->list->field('not-there');
    }

    public function testThatAKnownFieldCanBeRetrieved(): void
    {
        $field = $this->list->field('NumericValue');
        self::assertTrue($field->type()->equals(FieldType::number()));
        self::assertEquals('Numeric Value', $field->label());
        self::assertEquals('42', $field->fallback());
    }

    public function testThatAllFieldsCanBeRetrieved(): void
    {
        self::assertContainsOnlyInstancesOf(FieldDefinition::class, $this->list->fields());
    }

    /** @return array<string, array{0:string, 1: mixed}> */
    public function mutantValues(): array
    {
        return [
            'ID is null' => ['id', null],
            'ID is not a string' => ['id', 1],
            'Name is null' => ['name', null],
            'Name is not a string' => ['name', 1],
            'Opt in is not a bool' => ['double_opt_in', 1],
            'fields are null' => ['fields', null],
            'fields are not an array' => ['fields', 1],
            'counts are null' => ['counts', null],
            'counts are not an array' => ['counts', 1],
            'date is null' => ['created_at', null],
            'date is not a string' => ['created_at', 1],
            'date is invalid string' => ['created_at', 'foo'],
        ];
    }

    /**
     * @param mixed $mutatedValue
     *
     * @dataProvider mutantValues
     */
    public function testAssertions(string $key, $mutatedValue): void
    {
        $payload = $this->validPayload();
        /** @psalm-suppress MixedAssignment */
        $payload[$key] = $mutatedValue;
        $this->expectException(AssertionFailed::class);
        MailingList::fromArray($payload);
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
        MailingList::fromArray($payload);
    }
}
