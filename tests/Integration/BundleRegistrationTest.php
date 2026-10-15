<?php

declare(strict_types=1);

namespace Estratos\SieBanxico\Tests\Integration;

use Estratos\SieBanxico\DependencyInjection\Configuration;
use Estratos\SieBanxico\DependencyInjection\SieBanxicoExtension;
use Estratos\SieBanxico\SieBanxico;
use Estratos\SieBanxico\SieBanxicoClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Registers the bundle the way an application does — through the bundle's own
 * extension — and feeds the configuration through Symfony's extension-merging
 * compiler pass.
 *
 * This is the code path that was broken in 0.1.x: the alias derived from the
 * bundle class name never matched the configuration root, so mapping the
 * application configuration onto the extension could not work.
 */
final class BundleRegistrationTest extends TestCase
{
    private string $configFile = '';

    protected function tearDown(): void
    {
        if ('' !== $this->configFile && is_file($this->configFile)) {
            @unlink($this->configFile);
        }

        $this->configFile = '';
    }

    /**
     * @return array{0: ContainerBuilder, 1: SieBanxicoExtension}
     */
    private function createContainerWithBundle(): array
    {
        $bundle = new SieBanxico();
        $extension = $bundle->getContainerExtension();

        self::assertInstanceOf(SieBanxicoExtension::class, $extension);
        self::assertSame(Configuration::ROOT_NAME, $extension->getAlias());

        $container = new ContainerBuilder();
        $container->registerExtension($extension);

        return [$container, $extension];
    }

    public function testTheBundleConfigurationIsMappedOntoTheExtension(): void
    {
        [$container, $extension] = $this->createContainerWithBundle();

        $container->loadFromExtension($extension->getAlias(), ['token' => 'a-token']);

        (new MergeExtensionConfigurationPass())->process($container);

        // Reaching this point means Symfony mapped the "estratos_sie_banxico"
        // root onto the extension: any mismatch would have thrown here.
        self::assertTrue($container->hasDefinition('sie_banxico.client'));
        self::assertSame(['a-token'], $container->getDefinition('sie_banxico.client')->getArguments());

        $container->compile();

        self::assertInstanceOf(SieBanxicoClient::class, $container->get('sie_banxico.client'));
    }

    public function testTheBundleConfigurationCanBeLoadedFromAYamlFile(): void
    {
        [$container] = $this->createContainerWithBundle();

        $this->configFile = sys_get_temp_dir() . '/sie-banxico-integration.yml';
        file_put_contents($this->configFile, <<<'YAML'
            estratos_sie_banxico:
                token: a-token
            YAML);

        (new YamlFileLoader($container, new FileLocator(\dirname($this->configFile))))->load($this->configFile);

        (new MergeExtensionConfigurationPass())->process($container);

        self::assertTrue($container->hasDefinition('sie_banxico.client'));
        self::assertSame(['a-token'], $container->getDefinition('sie_banxico.client')->getArguments());
    }

    public function testTheAliasWorksInAContainerWithABuildId(): void
    {
        // Applications set a `container.build_id`; the alias containing
        // underscores must still resolve through the regular machinery.
        [$container] = $this->createContainerWithBundle();
        $container->setParameter('container.build_id', 'estratos_sie_banxico');
        $container->loadFromExtension(Configuration::ROOT_NAME, ['token' => 'a-token']);

        (new MergeExtensionConfigurationPass())->process($container);
        $container->compile();

        self::assertTrue($container->has('sie_banxico.client'));
    }

    public function testTheRemovedScaffoldingRootIsRejected(): void
    {
        [$container] = $this->createContainerWithBundle();

        // `Estratos_how_to` is no longer a registered extension alias, so the
        // container refuses it right away.
        $this->expectException(\Symfony\Component\DependencyInjection\Exception\LogicException::class);
        $this->expectExceptionMessage('Estratos_how_to');

        $container->loadFromExtension('Estratos_how_to', []);
    }
}
