<?php

namespace Swisscat\SalesforceBundle\Test\Mapper;

use Swisscat\SalesforceBundle\Mapping\Driver\DriverInterface;
use Swisscat\SalesforceBundle\Mapping\Driver\XmlDriver;
use Swisscat\SalesforceBundle\Test\TestCase;

class MappingGenerationTest extends TestCase
{
    public function testXmlDriverWorks()
    {
        $this->assertTrue(new XmlDriver() instanceof DriverInterface);
    }
}