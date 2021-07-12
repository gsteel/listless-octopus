<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Unit;

use GSteel\Listless\Octopus\BaseClient;
use GSteel\Listless\Octopus\Test\Unit\Stub\CaseSensitiveEmail;
use GSteel\Listless\Value\EmailAddress;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

use function md5;

class BaseClientTest extends TestCase
{
    /** @var BaseClient */
    private $client;

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
}
