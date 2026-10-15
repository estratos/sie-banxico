<?php

declare(strict_types=1);

namespace Estratos\SieBanxico\Tests\DependencyInjection;

use Estratos\SieBanxico\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testConfigurationRootName(): void
    {
        $tree = (new Configuration())->getConfigTreeBuilder()->buildTree();

        // The root is the conventional alias for Estratos\SieBanxico\SieBanxico.
        self::assertSame('estratos_sie_banxico', Configuration::ROOT_NAME);
        self::assertSame(Configuration::ROOT_NAME, $tree->getName());
    }

    public function testConfigTreeBuilderUsesTheNameFirstConstructor(): void
    {
        // Regression guard: `new TreeBuilder()` without arguments and
        // `TreeBuilder::root()` were removed in Symfony 7.0.
        $treeBuilder = (new Configuration())->getConfigTreeBuilder();

        self::assertSame(Configuration::ROOT_NAME, $treeBuilder->buildTree()->getName());
    }

    public function testOnlyTheDocumentedOptionsAreDefined(): void
    {
        $processed = (new Processor())->processConfiguration(new Configuration(), [[]]);

        // Guards against re-adding the scaffolding `test_node` placeholder.
        self::assertSame(['token', 'http_client'], array_keys($processed));
        self::assertArrayNotHasKey('test_node', $processed);
    }

    /**
     * @param array<string, mixed> $inputConfig
     * @param array<string, mixed> $expectedConfig
     */
    #[DataProvider('dataTestConfiguration')]
    public function testConfiguration(array $inputConfig, array $expectedConfig): void
    {
        $configuration = new Configuration();
        $processed = (new Processor())->processConfiguration($configuration, [$inputConfig]);

        // The order of the keys is an implementation detail of the tree.
        self::assertEqualsCanonicalizing(array_keys($expectedConfig), array_keys($processed));

        foreach ($expectedConfig as $key => $value) {
            self::assertArrayHasKey($key, $processed);
            self::assertSame($value, $processed[$key], sprintf('Unexpected value for the "%s" option.', $key));
        }
    }

    /**
     * @return array<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function dataTestConfiguration(): array
    {
        return [
            'empty configuration uses defaults' => [
                [],
                [
                    Configuration::NODE_TOKEN => null,
                    Configuration::NODE_HTTP_CLIENT => null,
                ],
            ],
            'token and http client are read' => [
                [Configuration::NODE_TOKEN => 'a-token', Configuration::NODE_HTTP_CLIENT => 'my.client'],
                [
                    Configuration::NODE_TOKEN => 'a-token',
                    Configuration::NODE_HTTP_CLIENT => 'my.client',
                ],
            ],
        ];
    }

    public function testTokenAcceptsScalarValues(): void
    {
        $processed = (new Processor())->processConfiguration(
            new Configuration(),
            [[Configuration::NODE_TOKEN => 'a-token']],
        );

        self::assertSame('a-token', $processed[Configuration::NODE_TOKEN]);
    }

    public function testTheRemovedScaffoldingOptionIsRejected(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        (new Processor())->processConfiguration(new Configuration(), [['test_node' => 'test_value']]);
    }
}
