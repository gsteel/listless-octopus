<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MemberAlreadySubscribed extends ResponseError
{
    public static function new(RequestInterface $request, ResponseInterface $response): self
    {
        return self::withHttpExchange(
            'The email address provided is already subscribed to the given list',
            $request,
            $response
        );
    }
}
