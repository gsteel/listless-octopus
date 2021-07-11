<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus;

use GSteel\Listless\Action\Subscribe;
use GSteel\Listless\EmailAddress;
use GSteel\Listless\ListId;
use GSteel\Listless\Octopus\Exception\ErrorFactory;
use GSteel\Listless\Octopus\Exception\Exception;
use GSteel\Listless\Octopus\Exception\MemberAlreadySubscribed;
use GSteel\Listless\Octopus\Exception\MemberNotFound;
use GSteel\Listless\Octopus\Exception\RequestFailure;
use GSteel\Listless\Octopus\Util\Json;
use GSteel\Listless\Octopus\Value\Contact;
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

final class BaseClient implements Client, Subscribe
{
    protected const BASE_URI = 'https://emailoctopus.com/api/1.5';

    /** @var UriInterface */
    private $baseUri;
    /** @var HttpClient */
    private $httpClient;
    /** @var RequestFactoryInterface */
    private $requestFactory;
    /** @var string */
    private $apiKey;
    /** @var StreamFactoryInterface */
    private $streamFactory;

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

    public function memberIdFromEmailAddress(EmailAddress $address): string
    {
        return md5(strtolower($address->toString()));
    }

    /**
     * @link https://emailoctopus.com/api-documentation/lists/create-contact
     *
     * @inheritDoc
     */
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
     * @throws MemberAlreadySubscribed if the email address is for someone already subscribed to the list.
     * @throws Exception if anything else goes wrong.
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
     * @throws MemberNotFound if the contact does not exist on the list.
     * @throws Exception if anything else goes wrong.
     */
    public function findListContactByEmailAddress(EmailAddress $address, ListId $listId): Contact
    {
        $response = $this->get(sprintf(
            '/lists/%s/contacts/%s',
            $listId->toString(),
            $this->memberIdFromEmailAddress($address)
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
            ->withBody($this->streamFactory->createStream(Json::encodeArray($parameters)));

        return $this->send($request);
    }

    /**
     * @throws Exception
     */
    private function send(RequestInterface $request): ResponseInterface
    {
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
}
