<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus\Container;

use GSteel\Listless\Octopus\BaseClient;
use GSteel\Listless\Octopus\Client;
use GSteel\Listless\Octopus\Util\Assert;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * PSR-11 Container Factory
 *
 * If you use one of the many available PSR-11 containers, this factory might be useful to you.
 *
 * It assumes that array configuration can be retrieved with the id `config` and that this config array contains an api
 * key for email octopus under `$config['email-octopus']['api-key']`.
 *
 * The other dependencies required by the API client, are a PSR-18 HTTP Client and various PSR-17 factories.
 * The factory will query the container for all of these factories and the HTTP client and fall back to "Discover"
 * those dependencies as provided by popular libraries using the `php-http/discovery` library/tool.
 */
final class ClientFactory
{
    public function __invoke(ContainerInterface $container): Client
    {
        $config = $container->has('config') ? $container->get('config') : null;
        Assert::isArray($config, 'No configuration can be retrieved from the given container');
        /** @psalm-var array|null $config */
        $config = $config['email-octopus'] ?? null;
        Assert::isArray($config, 'Missing configuration `email-octopus`');
        $apiKey = $config['api-key'] ?? null;
        Assert::string($apiKey, 'No API key has been configured');

        /** @psalm-suppress MixedArgument */
        return new BaseClient(
            $apiKey,
            $container->has(ClientInterface::class)
                ? $container->get(ClientInterface::class)
                : Psr18ClientDiscovery::find(),
            $container->has(RequestFactoryInterface::class)
                ? $container->get(RequestFactoryInterface::class)
                : Psr17FactoryDiscovery::findRequestFactory(),
            $container->has(UriFactoryInterface::class)
                ? $container->get(UriFactoryInterface::class)
                : Psr17FactoryDiscovery::findUriFactory(),
            $container->has(StreamFactoryInterface::class)
                ? $container->get(StreamFactoryInterface::class)
                : Psr17FactoryDiscovery::findStreamFactory()
        );
    }
}
