<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Integration;

use GSteel\Listless\Octopus\Util\Assert;
use GSteel\Listless\Octopus\Util\Json;
use GSteel\Listless\Octopus\Value\SubscriptionStatus;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\Message\Response;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;

use function is_callable;
use function md5;
use function sprintf;

final class MockServer
{
    public const VALID_API_KEY = 'valid-key';
    public const VALID_LIST = 'valid-list-id';
    public const VERSION = '1.5';
    public const EMAIL_NOT_SUBSCRIBED = 'not-subscribed@example.com';
    public const EMAIL_IS_SUBSCRIBED = 'is-subscribed@example.com';
    public const EMAIL_IS_UNSUBSCRIBED = 'is-unsubscribed@example.com';
    public const EMAIL_IS_PENDING = 'is-pending@example.com';
    public const WILL_BE_SUCCESSFULLY_SUBSCRIBED = 'new-successful@example.com';
    public const WILL_BE_SUBSCRIBED_PENDING = 'new-pending@example.com';
    public const IS_EXISTING_CONTACT = 'already-subscribed@example.com';
    public const IS_SUBSCRIBED_WILL_CAUSE_INVALID_API_KEY = 'invalid-api-key@example.com';
    public const UNAUTHORISED_LIST_ID = 'list-id-unauthorised';
    public const LIST_ID_NOT_FOUND = 'list-not-found';
    public const LIST_NAME_TO_TRIGGER_MISSING_ID = 'Gimme a payload with a missing list id';
    public const LIST_NAME_TO_TRIGGER_INVALID_ID = 'Gimme a payload with an integer list id';
    public const LIST_NAME_FOR_SUCCESSFUL_CREATION = 'New List Name';

    private LoopInterface $loop;
    private HttpServer $server;
    private SocketServer $socket;
    /**
     * Seconds before the server shuts down automatically
     */
    private int $timeout = 10;

    /** @var array<string, array{uri:string, method: string, body: string, type: string, code: int, bodyMatcher: callable|null}> */
    private array $responses;
    private string $basePath;

    public function __construct(int $port, string $basePath)
    {
        $this->basePath = $basePath;
        $this->seedResponses();
        $this->loop = Loop::get();
        $this->server = new HttpServer($this->loop, function (RequestInterface $request): ResponseInterface {
            return $this->handleRequest($request);
        });
        $this->socket = new SocketServer($port, $this->loop);
        $this->server->listen($this->socket);
    }

    public function start(): void
    {
        $this->loop->addTimer($this->timeout, function (): void {
            $this->stop();
        });
        $this->loop->run();
    }

    public function stop(): void
    {
        $this->loop->stop();
        $this->server->removeAllListeners();
        $this->socket->close();
    }

    private function handleRequest(RequestInterface $request): ResponseInterface
    {
        $data = $this->matchUri($request);

        return new Response($data['code'], ['Content-Type' => $data['type']], $data['body']);
    }

    /**
     * @return array{uri:string, method: string, body: string, type: string, code: int, bodyMatcher: callable|null}
     */
    private function matchUri(RequestInterface $request): array
    {
        $uri = $request->getUri()->getPath();

        foreach ($this->responses as $data) {
            $match = $data['uri'] ?? null;
            $match = $match ? $this->basePath . $match : null;
            if ($match !== $uri) {
                continue;
            }

            if ($request->getMethod() !== $data['method']) {
                continue;
            }

            $body = (string) $request->getBody();
            if (is_callable($data['bodyMatcher']) && $data['bodyMatcher']($body) === false) {
                continue;
            }

            return $data;
        }

        return [
            'uri' => $uri,
            'method' => 'GET',
            'body' => 'NOT FOUND: ' . $uri,
            'type' => 'text/plain',
            'code' => 404,
            'bodyMatcher' => null,
        ];
    }

    private function seedResponses(): void
    {
        $this->responses = [
            'Ping' => [
                'uri' => '/ping',
                'method' => 'GET',
                'body' => 'pong',
                'type' => 'text/plain',
                'code' => 200,
                'bodyMatcher' => null,
            ],
            'User is not subscribed' => [
                'uri' => sprintf(
                    '/lists/%s/contacts/%s',
                    self::VALID_LIST,
                    md5(self::EMAIL_NOT_SUBSCRIBED),
                ),
                'method' => 'GET',
                'body' => '{"error":{"code":"MEMBER_NOT_FOUND","message":"The contact could not be found."}}',
                'type' => 'application/json',
                'code' => 404,
                'bodyMatcher' => null,
            ],
            'User is subscribed' => [
                'uri' => sprintf(
                    '/lists/%s/contacts/%s',
                    self::VALID_LIST,
                    md5(self::EMAIL_IS_SUBSCRIBED),
                ),
                'method' => 'GET',
                'body' => '{"id":"672c2a54-e25d-11eb-96e5-06b4694bee2a","email_address":"is-subscribed@example.com","fields":{"FirstName":"Some","LastName":"Body"},"status":"SUBSCRIBED","created_at":"2021-07-11T15:33:55+00:00"}',
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => null,
            ],
            'User is unsubscribed' => [
                'uri' => sprintf(
                    '/lists/%s/contacts/%s',
                    self::VALID_LIST,
                    md5(self::EMAIL_IS_UNSUBSCRIBED),
                ),
                'method' => 'GET',
                'body' => '{"id":"672c2a54-e25d-11eb-96e5-06b4694bee2a","email_address":"is-unsubscribed@example.com","fields":{"FirstName":"Some","LastName":"Body"},"status":"UNSUBSCRIBED","created_at":"2021-07-11T15:33:55+00:00"}',
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => null,
            ],
            'User is pending' => [
                'uri' => sprintf(
                    '/lists/%s/contacts/%s',
                    self::VALID_LIST,
                    md5(self::EMAIL_IS_PENDING),
                ),
                'method' => 'GET',
                'body' => '{"id":"672c2a54-e25d-11eb-96e5-06b4694bee2a","email_address":"is-pending@example.com","fields":{"FirstName":"Some","LastName":"Body"},"status":"PENDING","created_at":"2021-07-11T15:33:55+00:00"}',
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => null,
            ],
            'Successfully Subscribe new User' => [
                'uri' => sprintf(
                    '/lists/%s/contacts',
                    self::VALID_LIST
                ),
                'method' => 'POST',
                'body' => '{"id":"af8c2ddc-e26f-11eb-96e5-06b4694bee2a","email_address":"new-successful@example.com","fields":{"FirstName":null,"LastName":null},"status":"SUBSCRIBED","created_at":"2021-07-11T17:44:47+00:00"}',
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => static function (string $body): bool {
                    $payload = Json::decodeToArray($body);

                    /** @psalm-var string|null $email */
                    $email = $payload['email_address'] ?? null;

                    return $email === self::WILL_BE_SUCCESSFULLY_SUBSCRIBED;
                },
            ],
            'Subscribe Pending User' => [
                'uri' => sprintf(
                    '/lists/%s/contacts',
                    self::VALID_LIST
                ),
                'method' => 'POST',
                'body' => '{"id":"af8c2ddc-e26f-11eb-96e5-06b4694bee2a","email_address":"new-pending@example.com","fields":{"FirstName":null,"LastName":null},"status":"PENDING","created_at":"2021-07-11T17:44:47+00:00"}',
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => static function (string $body): bool {
                    $payload = Json::decodeToArray($body);

                    /** @psalm-var string|null $email */
                    $email = $payload['email_address'] ?? null;

                    return $email === self::WILL_BE_SUBSCRIBED_PENDING;
                },
            ],
            'Subscribe Duplicate User' => [
                'uri' => sprintf(
                    '/lists/%s/contacts',
                    self::VALID_LIST
                ),
                'method' => 'POST',
                'body' => '{"error":{"code":"MEMBER_EXISTS_WITH_EMAIL_ADDRESS","message":"A member already exists with the supplied email address."}}',
                'type' => 'application/json',
                'code' => 409,
                'bodyMatcher' => static function (string $body): bool {
                    $payload = Json::decodeToArray($body);

                    /** @psalm-var string|null $email */
                    $email = $payload['email_address'] ?? null;

                    return $email === self::IS_EXISTING_CONTACT;
                },
            ],
            'Invalid API Key Error Will Be Caused' => [
                'uri' => sprintf(
                    '/lists/%s/contacts/%s',
                    self::VALID_LIST,
                    md5(self::IS_SUBSCRIBED_WILL_CAUSE_INVALID_API_KEY),
                ),
                'method' => 'GET',
                'body' => '{"error":{"code":"API_KEY_INVALID","message":"Your API key is invalid."}}',
                'type' => 'application/json',
                'code' => 403,
                'bodyMatcher' => null,
            ],
            'Unsubscribe User with PUT' => [
                'uri' => sprintf(
                    '/lists/%s/contacts/%s',
                    self::VALID_LIST,
                    md5(self::EMAIL_IS_SUBSCRIBED),
                ),
                'method' => 'PUT',
                'body' => '{"id":"af8c2ddc-e26f-11eb-96e5-06b4694bee2a","email_address":"is-subscribed@example.com","fields":{"FirstName":null,"LastName":null},"status":"UNSUBSCRIBED","created_at":"2021-07-11T17:44:47+00:00"}',
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => static function (string $body): bool {
                    $payload = Json::decodeToArray($body);
                    Assert::keyExists($payload, 'status');
                    Assert::string($payload['status']);

                    return $payload['status'] === SubscriptionStatus::unsubscribed()->getValue();
                },
            ],
            'Unsubscribe User that does not exist' => [
                'uri' => sprintf(
                    '/lists/%s/contacts/%s',
                    self::VALID_LIST,
                    md5(self::EMAIL_NOT_SUBSCRIBED),
                ),
                'method' => 'PUT',
                'body' => '{"error":{"code":"MEMBER_NOT_FOUND","message":"The contact could not be found."}}',
                'type' => 'application/json',
                'code' => 404,
                'bodyMatcher' => static function (string $body): bool {
                    $payload = Json::decodeToArray($body);
                    Assert::keyExists($payload, 'status');
                    Assert::string($payload['status']);

                    return $payload['status'] === SubscriptionStatus::unsubscribed()->getValue();
                },
            ],
            'Retrieve a list by its id' => [
                'uri' => sprintf(
                    '/lists/%s',
                    self::VALID_LIST
                ),
                'method' => 'GET',
                'body' => '{"id":"' . self::VALID_LIST . '","name":"Example List","double_opt_in":false,"fields":[{"tag":"EmailAddress","type":"TEXT","label":"Email address","fallback":null},{"tag":"FirstName","type":"TEXT","label":"First name","fallback":null},{"tag":"LastName","type":"TEXT","label":"Last name","fallback":null},{"tag":"ArbitraryText","type":"TEXT","label":"Arbitrary Text","fallback":"Foo"},{"tag":"NumericValue","type":"NUMBER","label":"Numeric Value","fallback":"42"}],"counts":{"pending":0,"subscribed":1,"unsubscribed":1},"created_at":"2021-01-01T01:00:00+00:00"}',
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => null,
            ],
            'Retrieve a list and get unauthorised response' => [
                'uri' => sprintf('/lists/%s', self::UNAUTHORISED_LIST_ID),
                'method' => 'GET',
                'body' => '{"error":{"code":"UNAUTHORISED","message":"You\u0027re not authorised to perform that action."}}',
                'type' => 'application/json',
                'code' => 403,
                'bodyMatcher' => null,
            ],
            'Retrieve a non-existent list and get the documented error' => [
                'uri' => sprintf('/lists/%s', self::LIST_ID_NOT_FOUND),
                'method' => 'GET',
                'body' => '{"error":{"code":"LIST_NOT_FOUND","message":"This is a fabricated response. The API does not return a 404"}}',
                'type' => 'application/json',
                'code' => 404,
                'bodyMatcher' => null,
            ],
            'Post a new list' => [
                'uri' => '/lists',
                'method' => 'POST',
                'body' => '{"id":"new-list-id","name":"New List Name","created_at":"2021-07-14T19:19:07+00:00"}',
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => static function (string $body): bool {
                    $payload = Json::decodeToArray($body);
                    Assert::keyExists($payload, 'name');
                    Assert::string($payload['name']);

                    return $payload['name'] === self::LIST_NAME_FOR_SUCCESSFUL_CREATION;
                },
            ],
            'Post a new list and get a missing id' => [
                'uri' => '/lists',
                'method' => 'POST',
                'body' => '{"name":"New List Name","created_at":"2021-07-14T19:19:07+00:00"}',
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => static function (string $body): bool {
                    $payload = Json::decodeToArray($body);
                    Assert::keyExists($payload, 'name');
                    Assert::string($payload['name']);

                    return $payload['name'] === self::LIST_NAME_TO_TRIGGER_MISSING_ID;
                },
            ],
            'Post a new list and get an empty id' => [
                'uri' => '/lists',
                'method' => 'POST',
                'body' => '{"id": 10,"name":"New List Name","created_at":"2021-07-14T19:19:07+00:00"}',
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => static function (string $body): bool {
                    $payload = Json::decodeToArray($body);
                    Assert::keyExists($payload, 'name');
                    Assert::string($payload['name']);

                    return $payload['name'] === self::LIST_NAME_TO_TRIGGER_INVALID_ID;
                },
            ],
            'Delete a mailing list' => [
                'uri' => sprintf('/lists/%s', self::VALID_LIST),
                'method' => 'DELETE',
                'body' => '{}',
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => null,
            ],
            'Delete a list contact' => [
                'uri' => sprintf('/lists/%s/contacts/%s', self::VALID_LIST, md5(self::EMAIL_IS_SUBSCRIBED)),
                'method' => 'DELETE',
                'body' => '{}',
                'type' => 'application/json',
                'code' => 200,
                'bodyMatcher' => null,
            ],
        ];
    }
}
