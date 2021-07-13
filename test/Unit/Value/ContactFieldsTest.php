<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Unit\Value;

use GSteel\Listless\Exception\InvalidArgument;
use GSteel\Listless\Octopus\Exception\AssertionFailed;
use GSteel\Listless\Octopus\Value\ContactFields;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContactFieldsTest extends TestCase
{
    /** @return array<string, mixed[]> */
    public function invalidValueProvider(): array
    {
        return [
            'Float' => [0.1],
            'Object' => [new stdClass()],
            'Array' => [['foo']],
        ];
    }

    /**
     * @param mixed $value
     *
     * @dataProvider invalidValueProvider
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function testInvalidParameterTypesAreExceptional($value): void
    {
        $input = ['foo' => $value];
        $this->expectException(InvalidArgument::class);
        ContactFields::fromArray($input);
    }

    public function testAnEmptyArrayIsAcceptable(): ContactFields
    {
        $fields = ContactFields::fromArray([]);
        self::assertEquals([], $fields->getArrayCopy());

        return $fields;
    }

    /** @depends testAnEmptyArrayIsAcceptable */
    public function testThatSettingAPropertyReturnsANewInstance(ContactFields $fields): void
    {
        $copy = $fields->set('foo', 'bar');
        self::assertNotSame($fields, $copy);
        self::assertEquals([], $fields->getArrayCopy());
        self::assertEquals(['foo' => 'bar'], $copy->getArrayCopy());
    }

    /** @depends testAnEmptyArrayIsAcceptable */
    public function testThatHasBehavesAsExpected(ContactFields $fields): void
    {
        $copy = $fields->set('foo', 'bar');
        self::assertFalse($fields->has('foo'));
        self::assertTrue($copy->has('foo'));
        self::assertNull($fields->get('foo'));
        self::assertEquals('bar', $copy->get('foo'));
    }

    public function testThatFieldsWithEmptyNamesAreAcceptable(): void
    {
        $fields = ContactFields::fromArray([
            ContactFields::FIELD_NAME_FIRST_NAME => null,
            ContactFields::FIELD_NAME_LAST_NAME => null,
        ]);

        self::assertNull($fields->firstName());
        self::assertNull($fields->lastName());
    }

    public function testThatNameAccessorsReturnTheExpectedValues(): void
    {
        $fields = ContactFields::fromArray([
            ContactFields::FIELD_NAME_FIRST_NAME => 'Hairy',
            ContactFields::FIELD_NAME_LAST_NAME => 'Styles',
        ]);

        self::assertEquals('Hairy', $fields->firstName());
        self::assertEquals('Styles', $fields->lastName());
    }

    /** @psalm-suppress InvalidScalarArgument */
    public function testIntegerKeysAreExceptional(): void
    {
        $this->expectException(AssertionFailed::class);
        ContactFields::fromArray([0 => 'Foo']);
    }
}
