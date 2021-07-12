<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Unit\Exception;

use GSteel\Listless\Octopus\Exception\BadMethodCall;
use GSteel\Listless\Octopus\Exception\JsonError;
use GSteel\Listless\Octopus\Util\Json;
use JsonException;
use PHPUnit\Framework\TestCase;

class JsonErrorTest extends TestCase
{
    public function testThatTheStringPayloadIsAvailableWhenDecodingFails(): void
    {
        $input = '{"foo":"bar",}';
        try {
            Json::decodeToArray($input);
            $this->fail('No exception was thrown');
        } catch (JsonError $error) {
            self::assertEquals($input, $error->jsonString());
        }
    }

    public function testThatAttemptingToRetrieveThePayloadIsExceptionalWhenItIsNull(): void
    {
        $error = new JsonError('Foo');
        $this->expectException(BadMethodCall::class);
        $error->jsonString();
    }
}
