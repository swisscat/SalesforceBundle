<?php

namespace Swisscat\SalesforceBundle\DependencyInjection;

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
        $treeBuilder->root('salesforce')
            ->children()
                ->arrayNode('soap_client')->isRequired()
                    ->children()
                        ->scalarNode('wsdl')->isRequired()->end()
                        ->scalarNode('username')->isRequired()->end()
                        ->scalarNode('password')->isRequired()->end()
                        ->scalarNode('token')->isRequired()->end()
                        ->scalarNode('logging')->defaultValue('%kernel.debug%')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
