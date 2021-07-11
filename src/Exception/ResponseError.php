<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Exception;

use GSteel\Listless\Octopus\Util\Assert;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/** @internal */
abstract class ResponseError extends RuntimeException implements Exception
{
    /** @var RequestInterface|null */
    protected $request;
    /** @var ResponseInterface|null */
    protected $response;

    final public function __construct(string $message, int $code = 0, ?Throwable $previous = null) // phpcs:ignore
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return static
     */
    final public static function withHttpExchange(
        string $message,
        RequestInterface $request,
        ResponseInterface $response
    ): self {
        $error = new static($message);
        $error->request = $request;
        $error->response = $response;

        return $error;
    }

    public function request(): RequestInterface
    {
        Assert::isInstanceOf(
            $this->request,
            RequestInterface::class,
            'This error was not provided a request instance'
        );

        return $this->request;
    }

    public function response(): ResponseInterface
    {
        Assert::isInstanceOf(
            $this->response,
            ResponseInterface::class,
            'This error was not provided a response instance'
        );

        return $this->response;
    }
}
