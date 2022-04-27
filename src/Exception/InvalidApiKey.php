<?php

declare(strict_types=1);

namespace ListInterop\Octopus\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class InvalidApiKey extends ResponseError
{
    public static function new(RequestInterface $request, ResponseInterface $response): self
    {
        return self::withHttpExchange(
            'An invalid API key was configured for Email Octopus',
            $request,
            $response
        );
    }
}
