<?php

declare(strict_types=1);

namespace ListInterop\Octopus\Test\Unit\Exception;

use ListInterop\Octopus\Exception\AssertionFailed;
use ListInterop\Octopus\Exception\ResponseError;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function assert;
use function call_user_func;

class ResponseErrorTest extends TestCase
{
    private ResponseError $error;

    protected function setUp(): void
    {
        parent::setUp();

        /** @psalm-suppress InternalClass */
        $this->error = new class ('STATIC MESSAGE', 0) extends ResponseError
        {
            public static function new(RequestInterface $request, ResponseInterface $response): self
            {
                return self::withHttpExchange('MESSAGE', $request, $response);
            }
        };
    }

    public function testAnExceptionIsThrownAccessingTheFailedRequestWhenItDoesNotExist(): void
    {
        $this->expectException(AssertionFailed::class);
        $this->error->request();
    }

    public function testAnExceptionIsThrownAccessingTheFailedResponseWhenItDoesNotExist(): void
    {
        $this->expectException(AssertionFailed::class);
        $this->error->response();
    }

    public function testThatTheErrorCodeIsEqualToTheResponseHttpStatusCode(): void
    {
        $request = (new RequestFactory())->createRequest('GET', '/uri');
        $response = (new ResponseFactory())->createResponse(123, 'Bad Stuff');

        $error = call_user_func([$this->error, 'new'], $request, $response);
        assert($error instanceof ResponseError);

        self::assertEquals(123, $error->getCode());
    }
}
