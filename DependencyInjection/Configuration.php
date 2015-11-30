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
                ->scalarNode('file_path')
                    ->defaultValue('%kernel.root_dir%/config/parameters.yml')
                    ->info('path to the file that contains the assets version parameter')
                    ->end()
                ->scalarNode('parameter_name')
                    ->defaultValue('assets_version')
                    ->info('name of the parameter to work with')
                    ->end()
                ->scalarNode('manager')
                    ->info('name of the class that manages the assets version')
                    ->defaultValue('Kachkaev\AssetsVersionBundle\AssetsVersionManager')
                    ->end()
            ->end();

        return $treeBuilder;
    }
}
