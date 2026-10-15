<?php

declare(strict_types=1);

namespace Estratos\SieBanxico\DependencyInjection;

use Estratos\SieBanxico\SieBanxicoClient;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers the bundle services.
 *
 * The extension alias is explicitly set to {@see Configuration::ROOT_NAME} so
 * it keeps matching the configured root node of previous releases. Symfony
 * derives the default alias from the class name (`sie_banxico`), which no
 * released version of this bundle ever used.
 */
class SieBanxicoExtension extends Extension
{
    private Configuration $configuration;

    public function __construct(?Configuration $configuration = null)
    {
        $this->configuration = $configuration ?? new Configuration();
    }

    public function getAlias(): string
    {
        return Configuration::ROOT_NAME;
    }

    /**
     * @param array<int, array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = (new Processor())->processConfiguration($this->configuration, $configs);

        $token = $config[Configuration::NODE_TOKEN] ?? null;
        if (!\is_string($token) || '' === $token) {
            // Nothing to wire up: the application did not configure a token, so
            // it is using the bundle as a plain library (new SieBanxico($token)).
            return;
        }

        $client = $container->register('sie_banxico.client', SieBanxicoClient::class)
            ->setArguments([$token]);

        $httpClient = $config[Configuration::NODE_HTTP_CLIENT] ?? null;
        if (\is_string($httpClient) && '' !== $httpClient) {
            $client->setArgument(1, new Reference($httpClient));
        }

        $client->setPublic(true);

        // Allow type-hinting the client without knowing the service id.
        $container->setAlias(SieBanxicoClient::class, 'sie_banxico.client')->setPublic(true);
    }
}
