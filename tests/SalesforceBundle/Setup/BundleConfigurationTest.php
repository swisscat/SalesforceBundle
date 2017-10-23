<?php

namespace Phpforce\SalesforceBundle\Tests\Setup;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;
use Swisscat\SalesforceBundle\DependencyInjection\Configuration;
use Swisscat\SalesforceBundle\DependencyInjection\SalesforceExtension;

class BundleConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    protected function getContainerExtension()
    {
        return new SalesforceExtension();
    }

    protected function getConfiguration()
    {
        return new Configuration();
    }

    public function testConfigTreeBuilderWorksAsExpected()
    {
        $expectedConfiguration = [
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
        ];

        $sources = [
            dirname(__DIR__).'/TestData/config.yml',
        ];

        $this->assertProcessedConfigurationEquals($expectedConfiguration, $sources);
    }
}