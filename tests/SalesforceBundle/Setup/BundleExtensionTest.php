<?php

namespace Phpforce\SalesforceBundle\Tests\Setup;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Swisscat\SalesforceBundle\DependencyInjection\SalesforceExtension;

class BundleExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new SalesforceExtension(),
        ];
    }

    public function testContainerBuilderProperlyLoaded()
    {
        $this->container->setParameter('kernel.bundles', array(
            'TestAddedBundle' => 'Swisscat\SalesforceBundle\Test\TestData\AddedBundle',
        ));

        $this->load([
            'soap_client' => [
                'wsdl' => 'url-wsdl',
                'username' => 'auser',
                'password' => 'apass',
                'token' => 'atoken',
                'logging' => '%kernel.debug%',
            ],
            'streams' => [
                [
                    'name' => 'TestTopic',
                    'type' => 'topic',
                    'resource' => 'Swisscat\SalesforceBundle\Test\TestData\Customer',
                ]
            ],
        ]);

        $this->assertContainerBuilderHasParameter('salesforce.streams');
    }
}