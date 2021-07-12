<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Unit\Util;

use GSteel\Listless\Octopus\Exception\JsonError;
use GSteel\Listless\Octopus\Exception\UnexpectedValue;
use GSteel\Listless\Octopus\Util\Json;
use PHPUnit\Framework\TestCase;
use Throwable;

use function json_encode;

use const JSON_ERROR_DEPTH;
use const JSON_THROW_ON_ERROR;
use const STDOUT;

class JsonTest extends TestCase
{
    public function testAnArrayCanBeEncoded(): void
    {
        $input = ['foo' => 'bar'];
        $expect = json_encode($input, JSON_THROW_ON_ERROR);
        $result = Json::encodeArray($input);
        self::assertEquals($expect, $result);
    }

    public function testAnUnEncodeAbleArrayWillCauseAnException(): void
    {
        $this->expectException(JsonError::class);
        Json::encodeArray(['cant-touch-this' => STDOUT]);
    }

    /** @return array<string, array{0: string, 1: class-string<Throwable>}> */
    public function decodeArrayInvalidData(): array
    {
        return [
            'Trailing Comma' => ['{"foo":"bar",}', JsonError::class],
            'Unquoted Word' => ['foo', JsonError::class],
            'Quoted Word' => ['"foo"', UnexpectedValue::class],
            'Boolean' => ['true', UnexpectedValue::class],
            'Null' => ['null', UnexpectedValue::class],
        ];
    }

    /**
     * @param class-string<Throwable> $expectedException
     *
     * @dataProvider decodeArrayInvalidData
     */
    public function testArrayDecodingFailures(string $json, string $expectedException): void
    {
        $this->expectException($expectedException);
        Json::decodeToArray($json);
    }

    public function testDecodeToArrayCanDecode(): void
    {
        $expect = ['foo' => 'bar'];
        self::assertEquals($expect, Json::decodeToArray('{"foo":"bar"}'));
    }

    public function testMaxDepthExceeded(): void
    {
        $inputArray = ['foo' => ['foo' => ['foo' => ['foo' => ['foo' => 'foo']]]]];
        $json = json_encode($inputArray, JSON_THROW_ON_ERROR);

        $this->expectException(JsonError::class);
        $this->expectExceptionCode(JSON_ERROR_DEPTH);

        Json::decodeToArray($json);
    }
}
