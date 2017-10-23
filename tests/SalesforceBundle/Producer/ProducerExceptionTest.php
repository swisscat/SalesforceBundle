<?php

namespace Swisscat\SalesforceBundle\Test\Producer;

use Swisscat\SalesforceBundle\Mapping\MappingException;
use Swisscat\SalesforceBundle\Producer\ProducerException;
use Swisscat\SalesforceBundle\Test\TestCase;

class ProducerExceptionTest extends TestCase
{
    public function testExceptionsProperlyGenerated()
    {
        $this->assertInstanceOf(ProducerException::class, ProducerException::fromAmqpPublishException(new \Exception()));
        $this->assertInstanceOf(ProducerException::class, ProducerException::fromMappingException(MappingException::invalidMappingDefinition('class', 'reason')));
    }
}