<?php

namespace Swisscat\SalesforceBundle\DependencyInjection;

use Swisscat\SalesforceBundle\Mapping\Driver\XmlDriver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

class SalesforceExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->loadConfiguration($configs, $container);
        $this->loadMappingInformation($container);
    }

    private function loadConfiguration(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('soap_client.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($config['soap_client'] as $key => $value) {
            $container->setParameter('salesforce.soap_client.' . $key, $value);
        }

        $container->setParameter('salesforce.streams', $config['streams'] ?? []);
    }

    private function loadMappingInformation(ContainerBuilder $container)
    {
        $mappingDirectories = [];

        foreach ($container->getParameter('kernel.bundles') as $name => $class) {
            $bundle = new \ReflectionClass($class);

            $bundleDir = dirname($bundle->getFileName());

            if (file_exists($dirName = $bundleDir.'/Resources/config/salesforce')) {
                $mappingDirectories[] = $dirName;
            }
        }

        $mappingService = 'salesforce.mapping.driver';

        $mappingDriverDef = $container->hasDefinition($mappingService)
            ? $container->getDefinition($mappingService)
            : new Definition(XmlDriver::class);

        $mappingDriverDef->setArguments([$mappingDirectories]);

        $container->setDefinition('salesforce.mapping.driver', $mappingDriverDef);
    }
}