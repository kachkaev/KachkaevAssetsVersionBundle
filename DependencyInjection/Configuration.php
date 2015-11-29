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
