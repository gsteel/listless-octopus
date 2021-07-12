<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Unit\Exception;

use GSteel\Listless\Octopus\Exception\ResponseError;
use GSteel\Listless\Octopus\Exception\UnexpectedValue;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function assert;
use function call_user_func;

class ResponseErrorTest extends TestCase
{
    /** @var ResponseError */
    private $error;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->expectException(UnexpectedValue::class);
        $this->error->request();
    }

    public function testAnExceptionIsThrownAccessingTheFailedResponseWhenItDoesNotExist(): void
    {
        $this->expectException(UnexpectedValue::class);
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
