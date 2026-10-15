<?php

declare(strict_types=1);

namespace Estratos\SieBanxico\Tests;

use Estratos\SieBanxico\DependencyInjection\Configuration;
use Estratos\SieBanxico\DependencyInjection\SieBanxicoExtension;
use Estratos\SieBanxico\SieBanxico;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class EstratosSieBanxicoTest extends TestCase
{
    public function testGetContainerExtension(): void
    {
        $bundle = new SieBanxico();

        self::assertInstanceOf(SieBanxicoExtension::class, $bundle->getContainerExtension());
    }

    /**
     * The bundle class is named `SieBanxico` for backwards compatibility, so
     * Symfony would expect the alias `sie_banxico`; the extension has always
     * used `Estratos_how_to`. The override in the bundle keeps them aligned,
     * which is what makes `$bundle->getContainerExtension()` usable at all.
     */
    public function testExtensionAliasDeviatesFromTheNamingConventionOnPurpose(): void
    {
        $bundle = new SieBanxico();
        $conventionalAlias = ContainerBuilder::underscore('SieBanxico');

        self::assertSame('sie_banxico', $conventionalAlias);
        self::assertNotSame($conventionalAlias, $bundle->getContainerExtension()?->getAlias());
        self::assertSame('Estratos_how_to', $bundle->getContainerExtension()?->getAlias());
    }

    public function testGetContainerExtensionIsCached(): void
    {
        $bundle = new SieBanxico();

        self::assertSame($bundle->getContainerExtension(), $bundle->getContainerExtension());
    }

    /**
     * The configured root node must match the extension alias, otherwise
     * Symfony never maps the application configuration onto the tree.
     */
    public function testExtensionAliasMatchesTheConfigurationRootNode(): void
    {
        $bundle = new SieBanxico();
        $extension = $bundle->getContainerExtension();

        self::assertNotNull($extension);
        self::assertSame(Configuration::ROOT_NAME, $extension->getAlias());
        self::assertSame(Configuration::ROOT_NAME, (new Configuration())->getConfigTreeBuilder()->buildTree()->getName());
    }

    public function testBundleNameAndNamespace(): void
    {
        $bundle = new SieBanxico();

        self::assertSame('SieBanxico', $bundle->getName());
        self::assertSame('Estratos\\SieBanxico', $bundle->getNamespace());
        self::assertSame(
            str_replace('\\', '/', \dirname(__DIR__) . '/src'),
            str_replace('\\', '/', $bundle->getPath()),
        );
    }

    public function testBundleCanBeConstructedWithoutAToken(): void
    {
        self::assertNull((new SieBanxico())->getToken());
        self::assertSame('a-token', (new SieBanxico('a-token'))->getToken());
    }
}
