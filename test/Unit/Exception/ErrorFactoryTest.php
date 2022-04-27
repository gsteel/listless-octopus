<?php

declare(strict_types=1);

namespace ListInterop\Octopus\Test\Unit\Exception;

use ListInterop\Octopus\Exception\ApiError;
use ListInterop\Octopus\Exception\ApiResourceNotFound;
use ListInterop\Octopus\Exception\ErrorFactory;
use ListInterop\Octopus\Exception\InvalidApiKey;
use ListInterop\Octopus\Exception\InvalidRequestParameters;
use ListInterop\Octopus\Exception\MemberNotFound;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function random_int;

class ErrorFactoryTest extends TestCase
{
    private RequestInterface $request;
    private ResponseInterface $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = (new RequestFactory())->createRequest('GET', '/foo');
        $this->response = (new ResponseFactory())->createResponse(100, '');
    }

    private function responseWithBody(string $body): ResponseInterface
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->response
            ->withStatus(random_int(405, 599))
            ->withBody((new StreamFactory())->createStream($body));
    }

    /** @return array<string, array{0: string, 1: class-string, 2: string}> */
    public function responseBodyProvider(): array
    {
        return [
            'Generic Not Found' => [
                '{"error":{"code":"MEMBER_NOT_FOUND","message":"The contact could not be found."}}',
                MemberNotFound::class,
                'The email address provided is not a known member',
            ],
            'Invalid API Key' => [
                '{"error":{"code":"API_KEY_INVALID","message":"Your API key is invalid."}}',
                InvalidApiKey::class,
                'An invalid API key was configured',
            ],
            'Invalid Params' => [
                '{"error":{"code":"INVALID_PARAMETERS","message":"Parameters are missing or invalid."}}',
                InvalidRequestParameters::class,
                'The request contained invalid parameters',
            ],
            'An Unknown Error in the typical shape' => [
                '{"error":{"code":"SOMETHING_ELSE","message":"Something bad happened"}}',
                ApiError::class,
                'The remote API returned the error "Something bad happened" with the error code "SOMETHING_ELSE"',
            ],
            'Possible 404 Error Shape' => [
                '{"error":{"code":"NOT_FOUND","message":"Have not yet seen a 404 error that isn’t an HTML response"}}',
                ApiResourceNotFound::class,
                'The API resource requested could not be found',
            ],
            'Missing Code should result in a generic error' => [
                '{"error":{"message":"Something bad happened"}}',
                ApiError::class,
                'An unknown error occurred during communication with the api',
            ],
            'Missing Message should result in a generic error' => [
                '{"error":{"code":"SOMETHING_ELSE"}}',
                ApiError::class,
                'An unknown error occurred during communication with the api',
            ],
        ];
    }

    /**
     * @param class-string $expectedException
     *
     * @dataProvider responseBodyProvider
     */
    public function testErrorFactoryWithFixedResponseBody(
        string $responseBody,
        string $expectedException,
        string $expectedMessage
    ): void {
        $response = $this->responseWithBody($responseBody);

        $error = ErrorFactory::withHttpExchange($this->request, $response);
        self::assertInstanceOf($expectedException, $error);
        self::assertEquals($response->getStatusCode(), $error->getCode());
        self::assertSame($response, $error->response());
        self::assertSame($this->request, $error->request());
        self::assertStringContainsString($expectedMessage, $error->getMessage());
    }

    public function testThatA404HtmlResponseWillBeClassifiedAsANotFoundError(): void
    {
        $response = new HtmlResponse('<html lang=""></html>', 404);
        $error = ErrorFactory::withHttpExchange($this->request, $response);
        self::assertInstanceOf(ApiResourceNotFound::class, $error);
    }

    public function testThatAnUnknownErrorWillBeGeneratedForOtherSituations(): void
    {
        $response = new TextResponse('Who Knows?', 200);
        $error = ErrorFactory::withHttpExchange($this->request, $response);
        self::assertInstanceOf(ApiError::class, $error);
        self::assertEquals('An unknown error occurred during communication with the api', $error->getMessage());
    }
}
