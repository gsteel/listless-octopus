<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Value;

use GSteel\Listless\Exception\InvalidArgument;
use GSteel\Listless\Octopus\Util\Assert;
use GSteel\Listless\SubscriberInformation;

use function array_keys;
use function gettype;
use function is_int;
use function is_string;
use function sprintf;

/**
 * @psalm-immutable
 */
class ContactFields implements SubscriberInformation
{
    public const FIELD_NAME_FIRST_NAME = 'FirstName';
    public const FIELD_NAME_LAST_NAME = 'LastName';

    /** @var array<string, int|string|null> */
    private array $data;

    /** @param array<string, int|string|null> $data */
    final protected function __construct(array $data)
    {
        $this->data = $data;
    }

    /** @param array<string, int|string|null> $data */
    public static function fromArray(array $data): self
    {
        $input = [];
        Assert::allString(array_keys($data));
        foreach ($data as $key => $value) {
            $input[$key] = self::validateValue($value);
        }

        return new static($input);
    }

    /**
     * @param mixed|null $value
     *
     * @return int|string|null
     *
     * @psalm-mutation-free
     */
    final protected static function validateValue($value)
    {
        if ($value === null || is_string($value) || is_int($value)) {
            return $value;
        }

        throw new InvalidArgument(sprintf(
            'Only integers, strings or null values are acceptable to Email Octopus contact fields. Received: %s',
            gettype($value)
        ));
    }

    /**
     * @return string|int|null
     *
     * @psalm-mutation-free
     */
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Only integers and strings are acceptable to Email Octopus
     *
     * @param int|string|null $value
     * @psalm-param mixed|null $value
     */
    public function set(string $key, $value): SubscriberInformation
    {
        $data = $this->data;
        $data[$key] = self::validateValue($value);

        return new self($data);
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /** @return array<string, string|int|null> */
    public function getArrayCopy(): array
    {
        return $this->data;
    }

    public function firstName(): ?string
    {
        $name = $this->get(self::FIELD_NAME_FIRST_NAME);

        return is_string($name) ? $name : null;
    }

    public function lastName(): ?string
    {
        $name = $this->get(self::FIELD_NAME_LAST_NAME);

        return is_string($name) ? $name : null;
    }
}
