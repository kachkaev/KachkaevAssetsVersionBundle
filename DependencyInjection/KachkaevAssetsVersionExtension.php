<?php

namespace Kachkaev\AssetsVersionBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class KachkaevAssetsVersionExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('config.yml');

        if (null !== $config['filename']) {
            $container->setParameter('kachkaev_assets_version.filename', $config['filename']);
        }
        if (null !== $config['parametername']) {
            $container->setParameter('kachkaev_assets_version.parametername', $config['parametername']);
        }
        if (null !== $config['manager']) {
            $container->setParameter('kachkaev_assets_version.manager.class', $config['manager']);
        }
    }
}
