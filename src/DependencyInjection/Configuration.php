<?php

declare(strict_types=1);

namespace Estratos\SieBanxico\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Bundle configuration.
 *
 * `ROOT_NAME` keeps its historical, non-conventional value because it is a
 * public constant and the extension alias is derived from it. Renaming it
 * would break existing `Estratos_how_to:` configuration blocks.
 */
class Configuration implements ConfigurationInterface
{
    public const ROOT_NAME = 'Estratos_how_to';

    /**
     * @deprecated Kept for backwards compatibility. The node does not configure
     *             anything; it will be removed in 4.0.
     */
    public const NODE_TEST = 'test_node';

    public const NODE_TOKEN = 'token';

    public const NODE_HTTP_CLIENT = 'http_client';

    /**
     * Builds the configuration tree.
     *
     * `TreeBuilder` requires the root name in its constructor since Symfony 5.3
     * and `TreeBuilder::root()` was removed in Symfony 7.0, hence the
     * name-first, `getRootNode()` style.
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NAME);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode(self::NODE_TEST)
                    ->defaultValue('test_node_value')
                    ->info('Unused. Retained for backwards compatibility; removed in 4.0.')
                ->end()
                ->variableNode(self::NODE_TOKEN)
                    ->defaultNull()
                    ->info('Banxico SIE API token (https://www.banxico.org.mx/SieAPIRest/service/v1/token).')
                ->end()
                ->variableNode(self::NODE_HTTP_CLIENT)
                    ->defaultNull()
                    ->info('Service id of the PSR-18 HTTP client used to reach the Banxico SIE API.')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
