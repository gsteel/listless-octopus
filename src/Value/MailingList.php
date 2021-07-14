<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Value;

use DateTimeImmutable;
use DateTimeInterface;
use GSteel\Listless\ListId;
use GSteel\Listless\MailingList as MailingListInterface;
use GSteel\Listless\Octopus\Exception\AssertionFailed;
use GSteel\Listless\Octopus\Exception\InvalidArgument;
use GSteel\Listless\Octopus\Util\Assert;
use GSteel\Listless\Octopus\Value\ListId as ID;

use function array_map;

final class MailingList implements MailingListInterface
{
    private ListId $listId;
    private string $name;
    private bool $doubleOptIn;
    /** @var array<array-key, FieldDefinition> */
    private array $fieldDefinitions;
    private ListStats $stats;
    private DateTimeImmutable $createdAt;

    /** @param array<array-key, FieldDefinition> $fieldDefinitions */
    private function __construct(
        ListId $listId,
        string $name,
        bool $doubleOptIn,
        array $fieldDefinitions,
        ListStats $stats,
        DateTimeImmutable $createdAt
    ) {
        $this->listId = $listId;
        $this->name = $name;
        $this->doubleOptIn = $doubleOptIn;
        $this->fieldDefinitions = $fieldDefinitions;
        $this->stats = $stats;
        $this->createdAt = $createdAt;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws AssertionFailed if any of the data provided in invalid.
     */
    public static function fromArray(array $data): self
    {
        $keys = [
            'id',
            'name',
            'double_opt_in',
            'fields',
            'counts',
            'created_at',
        ];
        foreach ($keys as $key) {
            Assert::keyExists($data, $key);
        }

        Assert::string($data['id']);
        Assert::string($data['name']);
        Assert::boolean($data['double_opt_in']);
        Assert::isArray($data['fields']);
        Assert::isArray($data['counts']);
        Assert::string($data['created_at']);

        $createdAt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $data['created_at']);
        Assert::isInstanceOf($createdAt, DateTimeImmutable::class);

        /** @psalm-var array<string, int> $stats */
        $stats = $data['counts'];
        /** @psalm-var array<array-key, array<string, string>> $fields */
        $fields = $data['fields'];

        return new self(
            ID::fromString($data['id']),
            $data['name'],
            $data['double_opt_in'],
            array_map(static function (array $input): FieldDefinition {
                return FieldDefinition::fromArray($input);
            }, $fields),
            ListStats::fromArray($stats),
            $createdAt
        );
    }

    public function listId(): ListId
    {
        return $this->listId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isDoubleOptIn(): bool
    {
        return $this->doubleOptIn;
    }

    public function stats(): ListStats
    {
        return $this->stats;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @throws InvalidArgument if the field name given does not exist.
     */
    public function field(string $tag): FieldDefinition
    {
        foreach ($this->fieldDefinitions as $field) {
            if ($field->tag() !== $tag) {
                continue;
            }

            return $field;
        }

        throw InvalidArgument::mailingListFieldNameDoesNotExist($this, $tag);
    }

    /** @return iterable<FieldDefinition> */
    public function fields(): iterable
    {
        return $this->fieldDefinitions;
    }
}
