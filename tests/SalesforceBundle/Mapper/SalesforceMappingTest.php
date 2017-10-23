<?php

namespace Swisscat\SalesforceBundle\Test\Mapper;

use Swisscat\SalesforceBundle\Entity\SalesforceMapping;
use Swisscat\SalesforceBundle\Test\TestCase;

class SalesforceMappingTest extends TestCase
{
    public function testSalesforceMappingBasicProperties()
    {
        $mapping = new SalesforceMapping();

        $mapping->setId(10);
        $mapping->setEntityType('toto');
        $mapping->setEntityId(11);
        $this->assertEquals(10, $mapping->getId());
        $this->assertEquals('toto', $mapping->getEntityType());
        $this->assertEquals(11, $mapping->getEntityId());
    }
}