<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus;

use GSteel\Listless\EmailAddress;
use GSteel\Listless\ListId;
use GSteel\Listless\Octopus\Exception\ErrorFactory;
use GSteel\Listless\Octopus\Exception\Exception;
use GSteel\Listless\Octopus\Exception\MailingListNotFound;
use GSteel\Listless\Octopus\Exception\MemberAlreadySubscribed;
use GSteel\Listless\Octopus\Exception\MemberNotFound;
use GSteel\Listless\Octopus\Exception\RequestFailure;
use GSteel\Listless\Octopus\Util\Assert;
use GSteel\Listless\Octopus\Util\Json;
use GSteel\Listless\Octopus\Value\Contact;
use GSteel\Listless\Octopus\Value\ListId as ID;
use GSteel\Listless\Octopus\Value\MailingList;
use GSteel\Listless\Octopus\Value\SubscriptionStatus;
use GSteel\Listless\SubscriberInformation;
use GSteel\Listless\SubscriptionResult as SubscriptionResultContract;
use GSteel\Listless\Value\SubscriptionResult;
use Psr\Http\Client\ClientExceptionInterface as PsrHttpError;
use Psr\Http\Client\ClientInterface as HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

use function array_filter;
use function http_build_query;
use function md5;
use function rtrim;
use function sprintf;
use function strtolower;

final class BaseClient implements Client
{
    protected const BASE_URI = 'https://emailoctopus.com/api/1.5';

    private UriInterface $baseUri;
    private HttpClient $httpClient;
    private RequestFactoryInterface $requestFactory;
    private string $apiKey;
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        string $apiKey,
        HttpClient $httpClient,
        RequestFactoryInterface $requestFactory,
        UriFactoryInterface $uriFactory,
        StreamFactoryInterface $streamFactory,
        string $baseUri = self::BASE_URI
    ) {
        $this->baseUri = $uriFactory->createUri(rtrim($baseUri, '/'));
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->apiKey = $apiKey;
    }

    public function emailAddressHash(EmailAddress $address): string
    {
        return md5(strtolower($address->toString()));
    }

    public function subscribe(
        EmailAddress $address,
        ListId $listId,
        ?SubscriberInformation $subscriberInformation = null
    ): SubscriptionResultContract {
        try {
            $contact = $this->addContactToList($address, $listId, $subscriberInformation);
        } catch (MemberAlreadySubscribed $error) {
            return SubscriptionResult::duplicate();
        }

        return $contact->status()->equals(SubscriptionStatus::pending())
            ? SubscriptionResult::pending()
            : SubscriptionResult::subscribed();
    }

    /**
     * @link https://emailoctopus.com/api-documentation/lists/create-contact
     *
     * @throws Exception if anything else goes wrong.
     *
     * @inheritDoc
     */
    public function addContactToList(
        EmailAddress $address,
        ListId $listId,
        ?SubscriberInformation $fields = null,
        ?SubscriptionStatus $status = null
    ): Contact {
        $response = $this->post(sprintf(
            '/lists/%s/contacts',
            $listId->toString()
        ), array_filter([
            'email_address' => $address->toString(),
            'fields' => $fields ? $fields->getArrayCopy() : [],
            'status' => $status ? $status->getValue() : null,
        ]));

        return $this->contactFromResponse($response);
    }

    /**
     * @throws Exception if it is not possible to determine either way if a contact is subscribed or not.
     */
    public function isSubscribed(
        EmailAddress $address,
        ListId $listId
    ): bool {
        try {
            $contact = $this->findListContactByEmailAddress($address, $listId);
        } catch (MemberNotFound $notFound) {
            return false;
        }

        return $contact->status()->equals(SubscriptionStatus::subscribed())
            || $contact->status()->equals(SubscriptionStatus::pending());
    }

    /**
     * @throws Exception if anything else goes wrong.
     *
     * @inheritDoc
     */
    public function findListContactByEmailAddress(EmailAddress $address, ListId $listId): Contact
    {
        $response = $this->get(sprintf(
            '/lists/%s/contacts/%s',
            $listId->toString(),
            $this->emailAddressHash($address)
        ));

        return $this->contactFromResponse($response);
    }

    private function contactFromResponse(ResponseInterface $response): Contact
    {
        $payload = Json::decodeToArray((string) $response->getBody());

        return Contact::fromArray($payload);
    }

    private function appendPath(string $path): UriInterface
    {
        return $this->baseUri->withPath(
            $this->baseUri->getPath()
            . $path
        );
    }

    /**
     * @throws Exception
     */
    private function get(string $path): ResponseInterface
    {
        $uri = $this->appendPath($path)
            ->withQuery(http_build_query([
                'api_key' => $this->apiKey,
            ]));

        $request = $this->requestFactory->createRequest('GET', $uri);

        return $this->send($request);
    }

    /**
     * @param array<array-key, mixed> $parameters
     *
     * @throws Exception
     */
    private function post(string $path, array $parameters): ResponseInterface
    {
        $uri = $this->appendPath($path);
        $parameters['api_key'] = $this->apiKey;
        $request = $this->requestFactory->createRequest('POST', $uri)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream(Json::encodeArray($parameters)));

        return $this->send($request);
    }

    /**
     * @param array<array-key, mixed> $parameters
     *
     * @throws Exception
     */
    private function put(string $path, array $parameters): ResponseInterface
    {
        $uri = $this->appendPath($path);
        $parameters['api_key'] = $this->apiKey;
        $request = $this->requestFactory->createRequest('PUT', $uri)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream(Json::encodeArray($parameters)));

        return $this->send($request);
    }

    /**
     * @param array<array-key, mixed> $parameters
     *
     * @throws Exception
     */
    private function delete(string $path, array $parameters): ResponseInterface
    {
        $uri = $this->appendPath($path);
        $parameters['api_key'] = $this->apiKey;
        $request = $this->requestFactory->createRequest('DELETE', $uri)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream(Json::encodeArray($parameters)));

        return $this->send($request);
    }

    /**
     * @throws Exception
     */
    private function send(RequestInterface $request): ResponseInterface
    {
        $request = $request->withHeader('Accept', 'application/json');
        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (PsrHttpError $error) {
            throw RequestFailure::withPsrError($request, $error);
        }

        if ($response->getStatusCode() !== 200) {
            throw ErrorFactory::withHttpExchange($request, $response);
        }

        return $response;
    }

    /**
     * @throws Exception
     */
    public function unsubscribe(EmailAddress $address, ListId $fromList): void
    {
        try {
            $this->changeSubscriptionStatus($address, $fromList, SubscriptionStatus::unsubscribed());
        } catch (MemberNotFound $notFound) {
            return;
        }
    }

    /**
     * @throws Exception
     */
    public function changeSubscriptionStatus(
        EmailAddress $forAddress,
        ListId $onList,
        SubscriptionStatus $toStatus
    ): Contact {
        $response = $this->put(sprintf(
            '/lists/%s/contacts/%s',
            $onList->toString(),
            $this->emailAddressHash($forAddress)
        ), ['status' => $toStatus->getValue()]);

        return $this->contactFromResponse($response);
    }

    /**
     * @throws Exception
     * @throws MailingListNotFound if the list does not exist.
     */
    public function findMailingListById(ListId $id): MailingList
    {
        $response = $this->get(sprintf(
            '/lists/%s',
            $id->toString()
        ));

        return $this->mailingListFromResponse($response);
    }

    private function mailingListFromResponse(ResponseInterface $response): MailingList
    {
        $payload = Json::decodeToArray((string) $response->getBody());

        return MailingList::fromArray($payload);
    }

    /**
     * @throws Exception
     */
    public function createMailingList(string $name): ID
    {
        Assert::notEmpty($name, 'List name cannot be empty');
        $response = $this->post('/lists', ['name' => $name]);

        $payload = Json::decodeToArray((string) $response->getBody());
        Assert::keyExists($payload, 'id', 'The response did not have a list id present');
        Assert::string($payload['id'], 'Expected a string list identifier. Received %s');

        return ID::fromString($payload['id']);
    }

    public function deleteMailingList(ListId $listId): void
    {
        $this->delete(sprintf('/lists/%s', $listId->toString()), []);
    }

    public function deleteListContact(EmailAddress $address, ListId $fromList): void
    {
        $this->delete(sprintf(
            '/lists/%s/contacts/%s',
            $fromList->toString(),
            $this->emailAddressHash($address)
        ), []);
    }
}
