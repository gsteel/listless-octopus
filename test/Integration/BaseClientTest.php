<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Integration;

use GSteel\Listless\Octopus\BaseClient;
use GSteel\Listless\Octopus\Exception\InvalidApiKey;
use GSteel\Listless\Octopus\Exception\RequestFailure;
use GSteel\Listless\Value\EmailAddress;
use GSteel\Listless\Value\ListId;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Throwable;

use function get_class;
use function sprintf;

class BaseClientTest extends RemoteIntegrationTestCase
{
    /** @var BaseClient */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new BaseClient(
            MockServer::VALID_API_KEY,
            $this->httpClient(),
            $this->requestFactory(),
            new UriFactory(),
            new StreamFactory(),
            self::apiServerUri()
        );
    }

    public function testThatIsSubscribedWillReturnFalseWhenAUserIsNotSubscribed(): void
    {
        self::assertFalse($this->client->isSubscribed(
            EmailAddress::fromString(MockServer::EMAIL_NOT_SUBSCRIBED),
            ListId::fromString(MockServer::VALID_LIST)
        ));
    }

    public function testThatIsSubscribedWillReturnTrueWhenAUserIsSubscribed(): void
    {
        self::assertTrue($this->client->isSubscribed(
            EmailAddress::fromString(MockServer::EMAIL_IS_SUBSCRIBED),
            ListId::fromString(MockServer::VALID_LIST)
        ));
    }

    public function testThatIsSubscribedWillReturnTrueWhenAUserIsPending(): void
    {
        self::assertTrue($this->client->isSubscribed(
            EmailAddress::fromString(MockServer::EMAIL_IS_PENDING),
            ListId::fromString(MockServer::VALID_LIST)
        ));
    }

    public function testThatIsSubscribedWillReturnFalseWhenAUserIsMarkedAsUnsubscribed(): void
    {
        self::assertFalse($this->client->isSubscribed(
            EmailAddress::fromString(MockServer::EMAIL_IS_UNSUBSCRIBED),
            ListId::fromString(MockServer::VALID_LIST)
        ));
    }

    public function testThatASuccessfulSubscriptionWillReturnAResultDeemedSuccessful(): void
    {
        $result = $this->client->subscribe(
            EmailAddress::fromString(MockServer::WILL_BE_SUCCESSFULLY_SUBSCRIBED),
            ListId::fromString(MockServer::VALID_LIST)
        );

        self::assertTrue($result->isSuccess());
    }

    public function testThatADuplicateSubscriptionWillReturnAnUnsuccessfulResult(): void
    {
        $result = $this->client->subscribe(
            EmailAddress::fromString(MockServer::IS_EXISTING_CONTACT),
            ListId::fromString(MockServer::VALID_LIST)
        );

        self::assertFalse($result->isSuccess());
    }

    public function testThatPendingSubscriptionsAreDeemedSuccessful(): void
    {
        $result = $this->client->subscribe(
            EmailAddress::fromString(MockServer::WILL_BE_SUBSCRIBED_PENDING),
            ListId::fromString(MockServer::VALID_LIST)
        );

        self::assertTrue($result->isSuccess());
    }

    public function testThatAnHttpErrorWillBeWrappedInARequestFailure(): void
    {
        $client = new BaseClient(
            MockServer::VALID_API_KEY,
            $this->httpClient(),
            $this->requestFactory(),
            new UriFactory(),
            new StreamFactory(),
            'http://0.0.0.0:0'
        );

        try {
            $client->isSubscribed(EmailAddress::fromString('throw@example.com'), ListId::fromString('foo'));
        } catch (RequestFailure $failure) {
            self::assertInstanceOf(ClientExceptionInterface::class, $failure->getPrevious());

            return;
        } catch (Throwable $other) {
            $this->fail(sprintf('Expected a %s exception. Received %s', RequestFailure::class, get_class($other)));
        }

        $this->fail('An exception was not thrown');
    }

    public function testThatAnInvalidApiKeyResponseWillBeClassifiedWithTheCorrectException(): void
    {
        $this->expectException(InvalidApiKey::class);
        $this->client->isSubscribed(
            EmailAddress::fromString(MockServer::IS_SUBSCRIBED_WILL_CAUSE_INVALID_API_KEY),
            ListId::fromString(MockServer::VALID_LIST)
        );
    }
}
