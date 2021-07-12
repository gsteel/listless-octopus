<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Exception;

use GSteel\Listless\Octopus\Util\Json;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function is_string;

/**
 * @internal
 */
final class ErrorFactory
{
    private const INVALID_KEY = 'API_KEY_INVALID';
    private const MEMBER_NOT_FOUND = 'MEMBER_NOT_FOUND';
    private const MEMBER_ALREADY_SUBSCRIBED = 'MEMBER_EXISTS_WITH_EMAIL_ADDRESS';
    private const GENERIC_NOT_FOUND = 'NOT_FOUND';
    private const INVALID_PARAMETERS = 'INVALID_PARAMETERS';

    public static function withHttpExchange(RequestInterface $request, ResponseInterface $response): ResponseError
    {
        try {
            $payload = Json::decodeToArray((string) $response->getBody());
        } catch (JsonError $error) {
            $payload = [];
        }

        /** @var mixed $code */
        $code = $payload['error']['code'] ?? null;
        /** @var mixed $message */
        $message = $payload['error']['message'] ?? null;
        $message = is_string($message) ? $message : null;

        if ($code === self::INVALID_KEY) {
            return InvalidApiKey::new($request, $response);
        }

        if ($code === self::MEMBER_NOT_FOUND) {
            return MemberNotFound::new($request, $response);
        }

        if ($code === self::MEMBER_ALREADY_SUBSCRIBED) {
            return MemberAlreadySubscribed::new($request, $response);
        }

        if ($code === self::INVALID_PARAMETERS) {
            return InvalidRequestParameters::new($request, $response);
        }

        /**
         * Known codes not yet covered:
         * - UNAUTHORISED
         * - UNKNOWN
         */

        /**
         * 404 errors are often HTML responses 👍 - Event with an "Accept" header set to JSON.
         *
         * This condition should come near the end so that more specific 'Not Found' situations like 'list not found',
         * 'campaign not found', etc. are not clobbered by this generic error.
         */
        if ($code === self::GENERIC_NOT_FOUND || $response->getStatusCode() === 404) {
            return ApiResourceNotFound::new($request, $response);
        }

        if (is_string($code) && is_string($message)) {
            return ApiError::generic($code, $message, $request, $response);
        }

        return ApiError::unknown($request, $response);
    }
}
