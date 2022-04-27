<?php

declare(strict_types=1);

namespace ListInterop\Octopus\Test\Unit;

use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\StreamFactory;
use ListInterop\Octopus\BaseClient;
use ListInterop\Octopus\Test\Unit\Stub\CaseSensitiveEmail;
use ListInterop\Octopus\Test\Unit\Stub\UriFactory;
use ListInterop\Value\EmailAddress;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

use function md5;

class BaseClientTest extends TestCase
{
    private BaseClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new BaseClient(
            'AnyKey',
            $this->createMock(ClientInterface::class),
            new RequestFactory(),
            new UriFactory(),
            new StreamFactory(),
            'http://0.0.0.0'
        );
    }

    public function testThatEmailHashIsTheExpectedValue(): void
    {
        $expect = md5('me@example.com');
        self::assertEquals($expect, $this->client->emailAddressHash(EmailAddress::fromString('me@example.com')));
    }

    public function testThatTheEmailHashWillBeConsistentRegardlessOfEmailAddressCase(): void
    {
        $email = new CaseSensitiveEmail('ME@EXAMPLE.COM');
        $expect = md5('me@example.com');
        self::assertNotEquals($expect, md5($email->toString()));
        self::assertEquals($expect, $this->client->emailAddressHash($email));
    }

    public function testThatTheBaseUriWillBeTrimmedDuringConstructionSoThatAppendingPathsWorks(): void
    {
        $factory = new UriFactory();
        new BaseClient(
            'AnyKey',
            $this->createMock(ClientInterface::class),
            new RequestFactory(),
            $factory,
            new StreamFactory(),
            'http://0.0.0.0/some/path//'
        );

        self::assertEquals('http://0.0.0.0/some/path', (string) $factory->lastUri());
    }
}
