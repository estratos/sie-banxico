<?php

declare(strict_types=1);

namespace Estratos\SieBanxico\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Bundle configuration.
 *
 * The root node is `estratos_sie_banxico`, the conventional alias for
 * `Estratos\SieBanxico\SieBanxico` (the bundle class short name does not follow
 * the convention, so the extension alias is set explicitly).
 *
 * Earlier releases shipped a `Estratos_how_to` root with an unused `test_node`
 * from the bundle scaffolding template. That tree was never reachable: the
 * extension had an empty `load()`, so it never read the configuration. Both were
 * removed instead of being carried forward.
 */
class Configuration implements ConfigurationInterface
{
    public const ROOT_NAME = 'estratos_sie_banxico';

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
