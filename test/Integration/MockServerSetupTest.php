<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Integration;

use function sprintf;

class MockServerSetupTest extends RemoteIntegrationTestCase
{
    public function testThatTheServerIsRunningAndRespondsWithTheExpectedOutput(): void
    {
        $request = $this->requestFactory()->createRequest('GET', sprintf('%s/ping', self::apiServerUri()));
        $response = $this->httpClient()->sendRequest($request);
        self::assertEquals(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        self::assertStringContainsString('pong', $body);
    }
}
