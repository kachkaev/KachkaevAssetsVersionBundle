<?php

namespace Kachkaev\AssetsVersionBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class KachkaevAssetsVersionExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('config.yml');
        $loader->load('services.yml');

        if (null !== $config['file_path']) {
            $container->setParameter('kachkaev_assets_version.file_path', $config['file_path']);
        }
        if (null !== $config['parameter_name']) {
            $container->setParameter('kachkaev_assets_version.parameter_name', $config['parameter_name']);
        }
        if (null !== $config['manager']) {
            $container->setParameter('kachkaev_assets_version.manager.class', $config['manager']);
        }
    }
}
