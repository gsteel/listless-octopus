<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Integration;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpClient implements ClientInterface
{
    /** @var ClientInterface */
    private $client;
    /** @var RequestInterface|null */
    private $lastRequest;
    /** @var ResponseInterface|null */
    private $lastResponse;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;
        $this->lastResponse = $this->client->sendRequest($request);

        return $this->lastResponse;
    }

    public function lastRequest(): ?RequestInterface
    {
        return $this->lastRequest;
    }

    public function lastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
    }
}
