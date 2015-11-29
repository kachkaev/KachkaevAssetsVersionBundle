<?php

namespace Kachkaev\AssetsVersionBundle\DependencyInjection;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

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
                ->scalarNode('filename')
                    ->defaultValue('%kernel.root_dir%/config/parameters.yml')
                    ->info('the name of the file that contains the assets version parameter')
                    ->end()
                ->scalarNode('parametername')
                    ->defaultValue('assets_version')
                    ->info('the name of the parameter to work with')
                    ->end()
                ->scalarNode('manager')
                    ->info('the name of the class that manages the assets version')
                    ->defaultValue('Kachkaev\AssetsVersionBundle\AssetsVersionManager')
                    ->end()
            ->end();

        return $treeBuilder;
    }
}
