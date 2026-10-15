<?php

declare(strict_types=1);

namespace Estratos\SieBanxico\Tests\DependencyInjection;

use Estratos\SieBanxico\DependencyInjection\Configuration;
use Estratos\SieBanxico\DependencyInjection\SieBanxicoExtension;
use Estratos\SieBanxico\SieBanxicoClient;
use Estratos\SieBanxico\Tests\Compat\StubContainerHttpClient;
use Estratos\SieBanxico\Tests\Compat\StubResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class SieBanxicoExtensionTest extends TestCase
{
    public function testAliasIsTheHistoricalConfigurationRootName(): void
    {
        self::assertSame(Configuration::ROOT_NAME, (new SieBanxicoExtension())->getAlias());
    }

    public function testNoContainerServiceIsRegisteredWhenNoTokenIsConfigured(): void
    {
        $container = new ContainerBuilder();

        (new SieBanxicoExtension())->load([[]], $container);

        self::assertFalse($container->hasDefinition('sie_banxico.client'));
        self::assertFalse($container->hasAlias(SieBanxicoClient::class));
    }

    public function testClientServiceIsRegisteredWhenATokenIsConfigured(): void
    {
        $container = new ContainerBuilder();

        (new SieBanxicoExtension())->load([[Configuration::NODE_TOKEN => 'a-token']], $container);

        self::assertTrue($container->hasDefinition('sie_banxico.client'));

        $definition = $container->getDefinition('sie_banxico.client');
        self::assertSame(SieBanxicoClient::class, $definition->getClass());
        self::assertSame(['a-token'], $definition->getArguments());
        self::assertTrue($definition->isPublic());
    }

    public function testHttpClientOptionIsInjectedAsAServiceReference(): void
    {
        $container = new ContainerBuilder();

        (new SieBanxicoExtension())->load(
            [[Configuration::NODE_TOKEN => 'a-token', Configuration::NODE_HTTP_CLIENT => 'app.http_client']],
            $container,
        );

        $arguments = $container->getDefinition('sie_banxico.client')->getArguments();
        self::assertArrayHasKey(1, $arguments);
        self::assertInstanceOf(Reference::class, $arguments[1]);
        self::assertSame('app.http_client', (string) $arguments[1]);
    }

    public function testClientClassIsAliasedForAutowiring(): void
    {
        $container = new ContainerBuilder();

        (new SieBanxicoExtension())->load([[Configuration::NODE_TOKEN => 'a-token']], $container);

        self::assertTrue($container->hasAlias(SieBanxicoClient::class));
        self::assertSame('sie_banxico.client', (string) $container->getAlias(SieBanxicoClient::class));
    }

    /**
     * End to end check of what an application actually writes: the YAML block is
     * parsed by the DI loader and processed by the extension.
     */
    public function testConfigurationCanBeLoadedFromYaml(): void
    {
        $configFile = $this->writeConfigFile(<<<'YAML'
            Estratos_how_to:
                test_node: my_value
                token: a-token
            YAML);

        try {
            $container = new ContainerBuilder();
            $extension = new SieBanxicoExtension();
            $container->registerExtension($extension);

            (new YamlFileLoader($container, new FileLocator(\dirname($configFile))))->load($configFile);
            $extension->load($container->getExtensionConfig($extension->getAlias()), $container);

            $definition = $container->getDefinition('sie_banxico.client');
            self::assertSame(['a-token'], $definition->getArguments());
        } finally {
            @unlink($configFile);
        }
    }

    public function testLegacyConfigurationWithoutATokenIsStillAccepted(): void
    {
        $configFile = $this->writeConfigFile(<<<'YAML'
            Estratos_how_to:
                test_node: legacy_value
            YAML);

        try {
            $container = new ContainerBuilder();
            $extension = new SieBanxicoExtension();
            $container->registerExtension($extension);

            (new YamlFileLoader($container, new FileLocator(\dirname($configFile))))->load($configFile);
            $extension->load($container->getExtensionConfig($extension->getAlias()), $container);

            self::assertFalse($container->hasDefinition('sie_banxico.client'));
        } finally {
            @unlink($configFile);
        }
    }

    public function testUnknownOptionsAreRejected(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        (new SieBanxicoExtension())->load([['not_an_option' => true]], new ContainerBuilder());
    }

    /**
     * Compiles a container and resolves the service, which proves that the
     * definition, the argument order and the injected PSR-18 client are all
     * usable by a real application.
     */
    public function testCompiledContainerBuildsTheClientWithTheConfiguredHttpClient(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.http_client', StubContainerHttpClient::class)->setPublic(true);

        (new SieBanxicoExtension())->load(
            [[Configuration::NODE_TOKEN => 'a-token', Configuration::NODE_HTTP_CLIENT => 'app.http_client']],
            $container,
        );
        $container->compile();

        $client = $container->get('sie_banxico.client');

        self::assertInstanceOf(SieBanxicoClient::class, $client);

        /** @var StubContainerHttpClient $httpClient */
        $httpClient = $container->get('app.http_client');
        $httpClient->setResponse(new StubResponse(200, json_encode([
            'bmx' => ['series' => [['idSerie' => 'SF46410', 'datos' => [['fecha' => '01/01/2024', 'dato' => '18.5']]]]],
        ], \JSON_THROW_ON_ERROR)));

        self::assertSame('18.5', $client->exchangeRateEuroDetermination());
    }

    public function testCompiledContainerResolvesTheClientAlias(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.http_client', StubContainerHttpClient::class)->setPublic(true);

        (new SieBanxicoExtension())->load(
            [[Configuration::NODE_TOKEN => 'a-token', Configuration::NODE_HTTP_CLIENT => 'app.http_client']],
            $container,
        );
        $container->compile();

        self::assertInstanceOf(SieBanxicoClient::class, $container->get(SieBanxicoClient::class));
    }

    private function writeConfigFile(string $yaml): string
    {
        $file = tempnam(sys_get_temp_dir(), 'sie-banxico-') . '.yml';
        file_put_contents($file, $yaml);

        return $file;
    }
}
