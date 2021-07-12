<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Unit\Exception;

use GSteel\Listless\Octopus\Exception\RequestFailure;
use GSteel\Listless\Octopus\Exception\UnexpectedValue;
use Laminas\Diactoros\RequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

class RequestFailureTest extends TestCase
{
    public function testThatExceptionPropertiesHaveTheExpectedValues(): void
    {
        $psr = new class ('PSR MESSAGE') extends RuntimeException implements ClientExceptionInterface
        {
        };
        $request = (new RequestFactory())->createRequest('GET', '/uri');

        $error = RequestFailure::withPsrError($request, $psr);

        self::assertStringContainsString('PSR MESSAGE', $error->getMessage());
        self::assertSame(0, $error->getCode());
        self::assertStringContainsString('/uri', $error->getMessage());
        self::assertSame($request, $error->failedRequest());
    }

    public function testThatAccessingTheFailedRequestIsExceptionalWhenItDoesNotExist(): void
    {
        $error = new RequestFailure('Foo');
        $this->expectException(UnexpectedValue::class);
        $error->failedRequest();
    }
}
