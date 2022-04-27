<?php

declare(strict_types=1);

namespace ListInterop\Octopus\Test\Smoke;

use ListInterop\ListId;
use ListInterop\Octopus\BaseClient;
use ListInterop\Octopus\Exception\Exception;
use ListInterop\Octopus\Exception\MemberNotFound;
use ListInterop\Octopus\Value\MailingList;
use ListInterop\Value\EmailAddress;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use PHPUnit\Framework\TestCase;

use function getenv;
use function is_string;
use function sprintf;
use function uniqid;

/** @group Smoke */
final class CreateAndTearDownMailingListTest extends TestCase
{
    private BaseClient $client;
    private EmailAddress $email;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = getenv('OCTOPUS_API_KEY');
        if (! is_string($apiKey) || empty($apiKey)) {
            $this->markTestSkipped('No API Key is available in the environment variable `OCTOPUS_API_KEY`');

            return;
        }

        $this->client = new BaseClient(
            $apiKey,
            Psr18ClientDiscovery::find(),
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findUriFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );

        $this->email = EmailAddress::fromString('me@example.com');
    }

    public function testThatAMailingListCanBeCreated(): ListId
    {
        $listName = sprintf('Temporary Test List %s', uniqid('', false));
        $this->expectNotToPerformAssertions();

        return $this->client->createMailingList($listName);
    }

    /** @depends testThatAMailingListCanBeCreated */
    public function testThatAMailingListCanBeRetrieved(ListId $listId): MailingList
    {
        $list = $this->client->findMailingListById($listId);
        self::assertTrue($listId->isEqualTo($list->listId()));
        self::assertStringStartsWith('Temporary Test List', $list->name());

        return $list;
    }

    /** @depends testThatAMailingListCanBeRetrieved */
    public function testThatAnEmailAddressCanBeAddedToAList(MailingList $list): ListId
    {
        $contact = $this->client->addContactToList($this->email, $list->listId());
        self::assertTrue($this->email->isEqualTo($contact->emailAddress()));

        return $list->listId();
    }

    /** @depends testThatAnEmailAddressCanBeAddedToAList */
    public function testThatAContactCanBeRetrievedByEmailAddress(ListId $listId): ListId
    {
        $contact = $this->client->findListContactByEmailAddress($this->email, $listId);
        self::assertTrue($this->email->isEqualTo($contact->emailAddress()));

        return $listId;
    }

    /** @depends testThatAContactCanBeRetrievedByEmailAddress */
    public function testThatAContactCanBeDeletedByEmailAddress(ListId $listId): ListId
    {
        $this->expectNotToPerformAssertions();
        $this->client->deleteListContact($this->email, $listId);

        return $listId;
    }

    /** @depends testThatAContactCanBeDeletedByEmailAddress */
    public function testThatADeletedContactCannotBeFound(ListId $listId): ListId
    {
        try {
            $this->client->findListContactByEmailAddress($this->email, $listId);
            $this->fail('No exception was thrown after fetching a deleted contact');
        } catch (MemberNotFound $error) {
            $this->expectNotToPerformAssertions();

            return $listId;
        }
    }

    /** @depends testThatADeletedContactCannotBeFound */
    public function testThatAListCanBeDeleted(ListId $listId): ListId
    {
        $this->expectNotToPerformAssertions();
        $this->client->deleteMailingList($listId);

        return $listId;
    }

    /** @depends testThatAListCanBeDeleted */
    public function testThatADeletedListCannotBeFound(ListId $listId): void
    {
        $this->expectException(Exception::class);
        $this->client->findMailingListById($listId);
    }
}
