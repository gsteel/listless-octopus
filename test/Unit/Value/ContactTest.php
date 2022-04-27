<?php

declare(strict_types=1);

namespace ListInterop\Octopus\Test\Unit\Value;

use Generator;
use ListInterop\Octopus\Exception\AssertionFailed;
use ListInterop\Octopus\Value\Contact;
use ListInterop\Octopus\Value\SubscriptionStatus;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function assert;
use function is_string;

class ContactTest extends TestCase
{
    /** @return array<array-key, mixed> */
    private function validPayload(): array
    {
        return [
            'id' => 'foo',
            'email_address' => 'me@example.com',
            'fields' => ['Key' => 'Value'],
            'status' => SubscriptionStatus::unsubscribed()->getValue(),
            'created_at' => '2020-01-01T01:02:03+01:30',
        ];
    }

    public function testThatWhatGoesInMustComeOut(): void
    {
        $contact = Contact::fromArray($this->validPayload());

        self::assertEquals('foo', $contact->id());
        self::assertEquals('me@example.com', $contact->emailAddress()->toString());
        self::assertEquals(['Key' => 'Value'], $contact->fields()->getArrayCopy());
        self::assertTrue(SubscriptionStatus::unsubscribed()->equals($contact->status()));
        self::assertEquals('2020-01-01 01:02:03', $contact->createdAt()->format('Y-m-d H:i:s'));
    }

    /** @return Generator<string, array{0: string}> */
    public function expectedKeyProvider(): Generator
    {
        $payload = $this->validPayload();
        foreach (array_keys($payload) as $key) {
            assert(is_string($key));

            yield $key => [$key];
        }
    }

    /** @dataProvider expectedKeyProvider */
    public function testThatAnyMissingKeysWillCauseAnException(string $key): void
    {
        $payload = $this->validPayload();
        unset($payload[$key]);
        $this->expectException(AssertionFailed::class);
        Contact::fromArray($payload);
    }

    /** @return array<string, array{0:string, 1: mixed}> */
    public function invalidTypeProvider(): array
    {
        return [
            'Integer ID' => ['id', 1],
            'Null ID' => ['id', null],
            'Integer Email' => ['email_address', 1],
            'Array Email' => ['email_address', ['foo']],
            'Null Email' => ['email_address', null],
            'Invalid Status String' => ['status', 'foo'],
            'Integer Status' => ['status', 1],
            'Array Status' => ['status', ['foo']],
            'Null Status' => ['status', null],
            'Integer Keys in Custom Fields' => ['fields', [1 => 'foo']],
            'Custom fields not array' => ['fields', 1],
            'Date not a string' => ['created_at', 1],
            'Invalid Date String' => ['created_at', 'foo'],
        ];
    }

    /**
     * @param mixed $value
     *
     * @dataProvider invalidTypeProvider
     * @psalm-suppress MixedAssignment
     */
    public function testPayloadTypesAreAsserted(string $key, $value): void
    {
        $payload = $this->validPayload();
        $payload[$key] = $value;
        $this->expectException(AssertionFailed::class);
        Contact::fromArray($payload);
    }
}
