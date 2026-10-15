<?php

declare(strict_types=1);

namespace Estratos\SieBanxico\Tests\DependencyInjection;

use Estratos\SieBanxico\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testConfigurationRootNameIsKeptForBackwardsCompatibility(): void
    {
        $tree = (new Configuration())->getConfigTreeBuilder()->buildTree();

        // The literal value matters: applications already use this config root.
        self::assertSame('Estratos_how_to', Configuration::ROOT_NAME);
        self::assertSame(Configuration::ROOT_NAME, $tree->getName());
    }

    public function testConfigTreeBuilderUsesTheNameFirstConstructor(): void
    {
        // Regression guard: `new TreeBuilder()` without arguments and
        // `TreeBuilder::root()` were removed in Symfony 7.0.
        $treeBuilder = (new Configuration())->getConfigTreeBuilder();

        self::assertSame(Configuration::ROOT_NAME, $treeBuilder->buildTree()->getName());
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
                    Configuration::NODE_TEST => 'test_node_value',
                    Configuration::NODE_TOKEN => null,
                    Configuration::NODE_HTTP_CLIENT => null,
                ],
            ],
            'the legacy test node is still accepted' => [
                [Configuration::NODE_TEST => 'test_value'],
                [
                    Configuration::NODE_TEST => 'test_value',
                    Configuration::NODE_TOKEN => null,
                    Configuration::NODE_HTTP_CLIENT => null,
                ],
            ],
            'token and http client are read' => [
                [Configuration::NODE_TOKEN => 'a-token', Configuration::NODE_HTTP_CLIENT => 'my.client'],
                [
                    Configuration::NODE_TEST => 'test_node_value',
                    Configuration::NODE_TOKEN => 'a-token',
                    Configuration::NODE_HTTP_CLIENT => 'my.client',
                ],
            ],
        ];
    }

    public function testTokenAcceptsAnyScalarValue(): void
    {
        $processed = (new Processor())->processConfiguration(
            new Configuration(),
            [[Configuration::NODE_TOKEN => 'a-token']],
        );

        self::assertSame('a-token', $processed[Configuration::NODE_TOKEN]);
    }
}
