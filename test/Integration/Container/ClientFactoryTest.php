<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Test\Integration\Container;

use GSteel\Listless\Octopus\Container\ClientFactory;
use GSteel\Listless\Octopus\Exception\UnexpectedValue;
use Http\Client\Curl\Client;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class ClientFactoryTest extends TestCase
{
    private ClientFactory $factory;
    /** @var MockObject&ContainerInterface */
    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new ClientFactory();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /** @return array<string, array{0: bool, 1: mixed, 2: string}> */
    public function erroneousConfig(): array
    {
        return [
            'No Config' => [
                false,
                null,
                'No configuration can be retrieved from the given container',
            ],
            'Null Config' => [
                true,
                null,
                'No configuration can be retrieved from the given container',
            ],
            'Empty Config' => [
                true,
                [],
                'Missing configuration `email-octopus`',
            ],
            'String top level, ffs' => [
                true,
                ['email-octopus' => 'foo'],
                'Missing configuration `email-octopus`',
            ],
            'Missing key' => [
                true,
                ['email-octopus' => []],
                'No API key has been configured',
            ],
            'Key not string' => [
                true,
                ['email-octopus' => ['api-key' => 1]],
                'No API key has been configured',
            ],
        ];
    }

    /**
     * @param mixed $get
     *
     * @dataProvider erroneousConfig
     */
    public function testThatTheContainerMustHaveConfiguration(bool $has, $get, string $expectedErrorMessage): void
    {
        $this->container->expects(self::once())
            ->method('has')
            ->with('config')
            ->willReturn($has);

        if ($has) {
            $this->container->expects(self::once())
                ->method('get')
                ->with('config')
                ->willReturn($get);
        } else {
            $this->container->expects(self::never())
                ->method('get');
        }

        $this->expectException(UnexpectedValue::class);
        $this->expectExceptionMessage($expectedErrorMessage);
        ($this->factory)($this->container);
    }

    public function testClientCreationWillProceedWhenTheContainerHasAllRequiredDependencies(): void
    {
        $this->container->expects(self::exactly(5))
            ->method('has')
            ->willReturn(true);

        $this->container->expects(self::exactly(5))
            ->method('get')
            ->willReturnMap([
                ['config', ['email-octopus' => ['api-key' => 'foo']]],
                [ClientInterface::class, new Client()],
                [RequestFactoryInterface::class, new RequestFactory()],
                [UriFactoryInterface::class, new UriFactory()],
                [StreamFactoryInterface::class, new StreamFactory()],
            ]);

        ($this->factory)($this->container);
    }

    public function testClientCreationWillProceedWhenOnlyConfigIsAvailable(): void
    {
        $this->container->expects(self::exactly(5))
            ->method('has')
            ->willReturnMap([
                ['config', true],
                [ClientInterface::class, false],
                [RequestFactoryInterface::class, false],
                [UriFactoryInterface::class, false],
                [StreamFactoryInterface::class, false],
            ]);
        $this->container->expects(self::once())
            ->method('get')
            ->willReturn(['email-octopus' => ['api-key' => 'foo']]);

        ($this->factory)($this->container);
    }
}
