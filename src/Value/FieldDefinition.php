<?php

declare(strict_types=1);

namespace ListInterop\Octopus\Value;

use ListInterop\Octopus\Exception\AssertionFailed;
use ListInterop\Octopus\Util\Assert;

/**
 * @psalm-immutable
 */
final class FieldDefinition
{
    private string $tag;
    private FieldType $fieldType;
    private string $label;
    private ?string $fallback;

    /**
     * @param int|string|null $defaultValue
     */
    private function __construct(
        string $tag,
        FieldType $fieldType,
        string $label,
        ?string $fallback
    ) {
        $this->tag = $tag;
        $this->fieldType = $fieldType;
        $this->label = $label;
        $this->fallback = $fallback;
    }

    /**
     * @param array<string, string|null> $input
     *
     * @throws AssertionFailed if any of the data provided in invalid.
     */
    public static function fromArray(array $input): self
    {
        $keys = ['tag', 'type', 'label', 'fallback'];
        foreach ($keys as $key) {
            Assert::keyExists($input, $key);
        }

        Assert::string($input['tag']);
        Assert::string($input['type']);
        Assert::string($input['label']);
        Assert::nullOrString($input['fallback']);

        return new self(
            $input['tag'],
            new FieldType($input['type']),
            $input['label'],
            $input['fallback']
        );
    }

    public function tag(): string
    {
        return $this->tag;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function type(): FieldType
    {
        return $this->fieldType;
    }

    public function fallback(): ?string
    {
        return $this->fallback;
    }
}
