<?php

declare(strict_types=1);

namespace ListInterop\Octopus\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ApiResourceNotFound extends ResponseError
{
    public static function new(RequestInterface $request, ResponseInterface $response): self
    {
        return self::withHttpExchange(
            'The API resource requested could not be found',
            $request,
            $response
        );
    }
}
