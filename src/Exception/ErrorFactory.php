<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Exception;

use GSteel\Listless\Octopus\Exception\Exception as InternalException;
use GSteel\Listless\Octopus\Util\Json;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
final class ErrorFactory
{
    private const INVALID_KEY = 'API_KEY_INVALID';
    private const MEMBER_NOT_FOUND = 'MEMBER_NOT_FOUND';
    private const MEMBER_ALREADY_SUBSCRIBED = 'MEMBER_EXISTS_WITH_EMAIL_ADDRESS';

    public static function withHttpExchange(RequestInterface $request, ResponseInterface $response): InternalException
    {
        $payload = Json::decodeToArray((string) $response->getBody());
        /** @var mixed $code */
        $code = $payload['error']['code'] ?? null;

        if ($code === self::INVALID_KEY) {
            return InvalidApiKey::new($request, $response);
        }

        if ($code === self::MEMBER_NOT_FOUND) {
            return MemberNotFound::new($request, $response);
        }

        if ($code === self::MEMBER_ALREADY_SUBSCRIBED) {
            return MemberAlreadySubscribed::new($request, $response);
        }

        return new BadMethodCall('Unhandled exception');
    }
}
