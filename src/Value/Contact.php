<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Value;

use DateTimeImmutable;
use DateTimeInterface;
use GSteel\Listless\EmailAddress as EmailAddressContract;
use GSteel\Listless\Octopus\Util\Assert;
use GSteel\Listless\Value\EmailAddress;

final class Contact
{
    /** @var string */
    private $id;
    /** @var EmailAddressContract */
    private $address;
    /** @var SubscriptionStatus */
    private $status;
    /** @var DateTimeImmutable */
    private $createdAt;
    /** @var ContactFields */
    private $data;

    private function __construct(
        string $id,
        EmailAddressContract $address,
        SubscriptionStatus $status,
        DateTimeImmutable $createdAt,
        ContactFields $data
    ) {
        $this->id = $id;
        $this->address = $address;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->data = $data;
    }

    /**
     * @internal
     *
     * @param array<array-key, mixed> $data
     *
     * @psalm-suppress DocblockTypeContradiction
     */
    public static function fromArray(array $data): self
    {
        $keys = [
            'id',
            'email_address',
            'fields',
            'status',
            'created_at',
        ];
        foreach ($keys as $key) {
            Assert::keyExists($data, $key);
        }

        /** @psalm-var SubscriptionStatus<string> $status */
        $status = $data['status'];

        Assert::string($data['id']);
        Assert::string($data['email_address']);
        Assert::true(SubscriptionStatus::isValid($status));
        Assert::string($data['created_at']);
        Assert::isArray($data['fields']);

        /** @psalm-var array<string, string|int|null> $fields */
        $fields = $data['fields'];

        $createdAt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $data['created_at']);
        Assert::isInstanceOf($createdAt, DateTimeImmutable::class);

        return new self(
            $data['id'],
            EmailAddress::fromString($data['email_address']),
            new SubscriptionStatus($status),
            $createdAt,
            ContactFields::fromArray($fields)
        );
    }

    public function status(): SubscriptionStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function emailAddress(): EmailAddressContract
    {
        return $this->address;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function fields(): ContactFields
    {
        return $this->data;
    }
}
