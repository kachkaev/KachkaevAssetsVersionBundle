<?php

namespace Kachkaev\AssetsVersionBundle\DependencyInjection;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('assets_version');

        $treeBuilder
            ->getRootNode()
            ->children()
                ->scalarNode('file_path')
                    ->defaultValue('%kernel.project_dir%/../config/parameters.yml')
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
