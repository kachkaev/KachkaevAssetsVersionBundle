<?php

namespace Kachkaev\AssetsVersionBundle\DependencyInjection;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('assets_version');

        $rootNode
            ->children()
                ->scalarNode('filename')->defaultNull()->end()
                ->scalarNode('parametername')->defaultNull()->end()
                ->scalarNode('manager')->defaultNull()->end()
                ->scalarNode('parameterhashname')->defaultNull()->end()
                ->arrayNode('scannedfiles')->isRequired()->prototype('scalar')->end()->end()
            ->end();

        return $treeBuilder;
    }
}
