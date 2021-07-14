<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Integration;

use GSteel\Listless\Octopus\BaseClient;
use GSteel\Listless\Octopus\Exception\InvalidApiKey;
use GSteel\Listless\Octopus\Exception\MailingListNotFound;
use GSteel\Listless\Octopus\Exception\MemberAlreadySubscribed;
use GSteel\Listless\Octopus\Exception\MemberNotFound;
use GSteel\Listless\Octopus\Exception\RequestFailure;
use GSteel\Listless\Octopus\Exception\UnauthorisedRequest;
use GSteel\Listless\Octopus\Util\Json;
use GSteel\Listless\Octopus\Value\SubscriptionStatus;
use GSteel\Listless\Value\EmailAddress;
use GSteel\Listless\Value\ListId;
use GSteel\Listless\Value\SubscriptionResult;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use function assert;
use function get_class;
use function md5;
use function parse_str;
use function sprintf;

class BaseClientTest extends RemoteIntegrationTestCase
{
    private BaseClient $client;

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

    public function testThatFindingAContactIsExceptionalWhenTheyAreNotSubscribed(): void
    {
        $this->expectException(MemberNotFound::class);
        $this->client->findListContactByEmailAddress(
            EmailAddress::fromString(MockServer::EMAIL_NOT_SUBSCRIBED),
            ListId::fromString(MockServer::VALID_LIST)
        );
    }

    public function testThatGettingAContactWillSendTheRequestToTheExpectedUri(): void
    {
        $this->client->findListContactByEmailAddress(
            EmailAddress::fromString(MockServer::EMAIL_IS_SUBSCRIBED),
            ListId::fromString(MockServer::VALID_LIST)
        );

        $request = $this->httpClient()->lastRequest();
        assert($request instanceof RequestInterface);

        $expect = sprintf(
            '%s/lists/%s/contacts/%s?api_key=%s',
            self::apiServerUri(),
            MockServer::VALID_LIST,
            md5(MockServer::EMAIL_IS_SUBSCRIBED),
            MockServer::VALID_API_KEY
        );

        self::assertEquals($expect, (string) $request->getUri());
    }

    public function testThatGettingAContactWillAddTheApiKeyToTheRequestQuery(): void
    {
        $this->client->findListContactByEmailAddress(
            EmailAddress::fromString(MockServer::EMAIL_IS_SUBSCRIBED),
            ListId::fromString(MockServer::VALID_LIST)
        );

        $request = $this->httpClient()->lastRequest();
        assert($request instanceof RequestInterface);

        parse_str($request->getUri()->getQuery(), $query);
        self::assertArrayHasKey('api_key', $query);
        self::assertEquals(MockServer::VALID_API_KEY, $query['api_key']);
    }

    public function testThatIsSubscribedWillReturnTrueWhenAUserIsSubscribed(): void
    {
        self::assertTrue($this->client->isSubscribed(
            EmailAddress::fromString(MockServer::EMAIL_IS_SUBSCRIBED),
            ListId::fromString(MockServer::VALID_LIST)
        ));
    }

    public function testThatFindingAContactIsPossibleWhenTheyAreSubscribed(): void
    {
        $email = EmailAddress::fromString(MockServer::EMAIL_IS_SUBSCRIBED);
        $contact = $this->client->findListContactByEmailAddress(
            $email,
            ListId::fromString(MockServer::VALID_LIST)
        );

        self::assertTrue($contact->emailAddress()->isEqualTo($email));
    }

    public function testThatIsSubscribedWillReturnTrueWhenAUserIsPending(): void
    {
        self::assertTrue($this->client->isSubscribed(
            EmailAddress::fromString(MockServer::EMAIL_IS_PENDING),
            ListId::fromString(MockServer::VALID_LIST)
        ));
    }

    public function testThatAPendingSubscriberCanBeFound(): void
    {
        $email = EmailAddress::fromString(MockServer::EMAIL_IS_PENDING);
        $contact = $this->client->findListContactByEmailAddress(
            $email,
            ListId::fromString(MockServer::VALID_LIST)
        );

        self::assertTrue($contact->emailAddress()->isEqualTo($email));
    }

    public function testThatIsSubscribedWillReturnFalseWhenAUserIsMarkedAsUnsubscribed(): void
    {
        self::assertFalse($this->client->isSubscribed(
            EmailAddress::fromString(MockServer::EMAIL_IS_UNSUBSCRIBED),
            ListId::fromString(MockServer::VALID_LIST)
        ));
    }

    public function testThatAnUnsubscribedContactCanBeFound(): void
    {
        $email = EmailAddress::fromString(MockServer::EMAIL_IS_UNSUBSCRIBED);
        $contact = $this->client->findListContactByEmailAddress(
            $email,
            ListId::fromString(MockServer::VALID_LIST)
        );

        self::assertTrue($contact->emailAddress()->isEqualTo($email));
    }

    public function testThatASuccessfulSubscriptionWillReturnAResultDeemedSuccessful(): void
    {
        $result = $this->client->subscribe(
            EmailAddress::fromString(MockServer::WILL_BE_SUCCESSFULLY_SUBSCRIBED),
            ListId::fromString(MockServer::VALID_LIST)
        );

        self::assertTrue($result->isSuccess());
    }

    public function testThatASuccessfulSubscriptionWillReturnAResultRepresentingActualSubscription(): void
    {
        $result = $this->client->subscribe(
            EmailAddress::fromString(MockServer::WILL_BE_SUCCESSFULLY_SUBSCRIBED),
            ListId::fromString(MockServer::VALID_LIST)
        );

        self::assertTrue($result->equals(SubscriptionResult::subscribed()));
    }

    public function testThatANewContactCanBeAddedToAList(): void
    {
        $email = EmailAddress::fromString(MockServer::WILL_BE_SUCCESSFULLY_SUBSCRIBED);
        $contact = $this->client->addContactToList(
            $email,
            ListId::fromString(MockServer::VALID_LIST)
        );
        self::assertTrue($contact->emailAddress()->isEqualTo($email));
    }

    public function testThatADuplicateSubscriptionWillReturnAnUnsuccessfulResult(): void
    {
        $result = $this->client->subscribe(
            EmailAddress::fromString(MockServer::IS_EXISTING_CONTACT),
            ListId::fromString(MockServer::VALID_LIST)
        );

        self::assertFalse($result->isSuccess());
    }

    public function testThatAddingAnExistingContactToAListIsExceptional(): void
    {
        $this->expectException(MemberAlreadySubscribed::class);
        $this->client->addContactToList(
            EmailAddress::fromString(MockServer::IS_EXISTING_CONTACT),
            ListId::fromString(MockServer::VALID_LIST)
        );
    }

    public function testThatSubscriptionStatusWillNotBePresentInTheRequestBodyWhenNotProvided(): void
    {
        $this->client->addContactToList(
            EmailAddress::fromString(MockServer::WILL_BE_SUCCESSFULLY_SUBSCRIBED),
            ListId::fromString(MockServer::VALID_LIST)
        );
        $request = $this->httpClient()->lastRequest();
        assert($request instanceof RequestInterface);
        $body = (string) $request->getBody();
        self::assertJson($body);
        $payload = Json::decodeToArray($body);
        self::assertArrayNotHasKey('status', $payload);
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

    public function testThatChangingStatusToUnsubscribedIsSuccessful(): void
    {
        $email = EmailAddress::fromString(MockServer::EMAIL_IS_SUBSCRIBED);
        $contact = $this->client->changeSubscriptionStatus(
            $email,
            ListId::fromString(MockServer::VALID_LIST),
            SubscriptionStatus::unsubscribed()
        );

        self::assertTrue($email->isEqualTo($contact->emailAddress()));
        self::assertTrue($contact->status()->equals(SubscriptionStatus::unsubscribed()));
    }

    public function testMemberNotFoundDuringChangeStatus(): void
    {
        $email = EmailAddress::fromString(MockServer::EMAIL_NOT_SUBSCRIBED);
        $this->expectException(MemberNotFound::class);
        $this->client->changeSubscriptionStatus(
            $email,
            ListId::fromString(MockServer::VALID_LIST),
            SubscriptionStatus::unsubscribed()
        );
    }

    public function testThatUnsubscribeWillSendTheExpectedRequestPayload(): void
    {
        $email = EmailAddress::fromString(MockServer::EMAIL_IS_SUBSCRIBED);
        $this->client->unsubscribe($email, ListId::fromString(MockServer::VALID_LIST));
        $request = $this->httpClient()->lastRequest();
        assert($request instanceof RequestInterface);
        self::assertMessageBodyHasSubscriptionStatusOf(
            $request,
            SubscriptionStatus::unsubscribed()
        );
    }

    public function testThatUnsubscribingANonExistentUserDoesNotCauseAnException(): void
    {
        $email = EmailAddress::fromString(MockServer::EMAIL_NOT_SUBSCRIBED);
        $this->client->unsubscribe($email, ListId::fromString(MockServer::VALID_LIST));
        $request = $this->httpClient()->lastRequest();
        $response = $this->httpClient()->lastResponse();
        assert($request instanceof RequestInterface);
        self::assertMessageBodyHasSubscriptionStatusOf($request, SubscriptionStatus::unsubscribed());
        assert($response instanceof ResponseInterface);
        $body = Json::decodeToArray((string) $response->getBody());
        self::assertIsArray($body['error']);
        self::assertEquals(
            'MEMBER_NOT_FOUND',
            $body['error']['code']
        );
    }

    private static function assertMessageBodyHasSubscriptionStatusOf(MessageInterface $message, SubscriptionStatus $status): void
    {
        $body = (string) $message->getBody();
        $body = Json::decodeToArray($body);
        self::assertArrayHasKey('status', $body, 'The message did not contain a status parameter');
        self::assertEquals($status->getValue(), $body['status'], 'The status did not match');
    }

    public function testThatListNotFoundMightMeanUnauthorised(): void
    {
        $this->expectException(UnauthorisedRequest::class);
        $this->client->findMailingListById(ListId::fromString(MockServer::UNAUTHORISED_LIST_ID));
    }

    public function testThatListNotFoundWillThrowListNotFoundIfTheResponseWasSane(): void
    {
        $this->expectException(MailingListNotFound::class);
        $this->client->findMailingListById(ListId::fromString(MockServer::LIST_ID_NOT_FOUND));
    }

    public function testThatListsCanBeRetrieved(): void
    {
        $list = $this->client->findMailingListById(ListId::fromString(MockServer::VALID_LIST));
        self::assertEquals($list->listId()->toString(), MockServer::VALID_LIST);
    }
}
