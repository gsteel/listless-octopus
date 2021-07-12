<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;

final class ApiError extends ResponseError
{
    public static function generic(
        string $apiCode,
        string $message,
        RequestInterface $request,
        ResponseInterface $response
    ): self {
        return self::withHttpExchange(
            sprintf('The remote API returned the error "%s" with the error code "%s"', $message, $apiCode),
            $request,
            $response
        );
    }

    public static function unknown(RequestInterface $request, ResponseInterface $response): self
    {
        return self::withHttpExchange(
            'An unknown error occurred during communication with the api',
            $request,
            $response
        );
    }
}
